$ErrorActionPreference = 'Stop'

$ftpServer   = 'ftp://jcloud02.jedlik.eu'
$ftpUser     = $env:FTP_USER
$ftpPass     = $env:FTP_PASS
$maxParallel = 40   # concurrent uploads - tune to taste

if (-not $ftpUser -or -not $ftpPass) {
    Write-Host "HIBA: FTP_USER vagy FTP_PASS nincs beallitva!" -ForegroundColor Red
    exit 1
}

# -- parallel upload engine -------------------------------------------------
function Upload-Recursive($localDir, $remoteBase) {
    $files = Get-ChildItem $localDir -Recurse -File
    $total = $files.Count
    if ($total -eq 0) { return 0 }

    # Build work items
    $tasks = foreach ($file in $files) {
        $rel       = $file.FullName.Substring($localDir.Length).TrimStart('\').Replace('\', '/')
        $remoteUrl = "$ftpServer/$remoteBase/$rel"
        [PSCustomObject]@{ Local = $file.FullName; Remote = $remoteUrl; Rel = $rel }
    }

    # Thread-safe counters
    $done   = [System.Threading.Interlocked]
    $counter = @{ Value = 0 }
    $errList = [System.Collections.Concurrent.ConcurrentBag[string]]::new()

    # Runspace pool
    $pool = [RunspaceFactory]::CreateRunspacePool(1, $maxParallel)
    $pool.Open()

    $scriptBlock = {
        param($local, $remote, $user, $pass)
        $out = & curl.exe -s -S --ssl-reqd --ftp-create-dirs --ftp-pasv -k `
                   -u "${user}:${pass}" -T $local $remote 2>&1
        $LASTEXITCODE
    }

    # Launch all jobs
    $handles = foreach ($t in $tasks) {
        $ps = [PowerShell]::Create().AddScript($scriptBlock)
        $ps.AddArgument($t.Local).AddArgument($t.Remote).AddArgument($ftpUser).AddArgument($ftpPass) | Out-Null
        $ps.RunspacePool = $pool
        [PSCustomObject]@{ Shell = $ps; Handle = $ps.BeginInvoke(); Rel = $t.Rel }
    }

    # Collect results
    $failed = @()
    $i      = 0
    foreach ($h in $handles) {
        $exitCode = $h.Shell.EndInvoke($h.Handle)
        $i++
        $pct = [math]::Round(($i / $total) * 100)

        if ($exitCode -ne 0) {
            Write-Host "  [$i/$total] (${pct}%) $($h.Rel) - HIBA" -ForegroundColor Red
            $failed += $h.Rel
        } else {
            Write-Host "  [$i/$total] (${pct}%) $($h.Rel)"
        }
        $h.Shell.Dispose()
    }

    $pool.Close()
    $pool.Dispose()
    return ,$failed
}

# -- Upload ----------------------------------------------------------------
Write-Host "Core mappa feltoltese -> public_html/core/ ..." -ForegroundColor Cyan
$coreDir = (Resolve-Path 'deploy\core').Path
$e1 = Upload-Recursive $coreDir 'public_html/core'
Write-Host ""

Write-Host "Public_html tartalom feltoltese -> public_html/ ..." -ForegroundColor Cyan
$pubDir = (Resolve-Path 'deploy\public_html').Path
$e2 = Upload-Recursive $pubDir 'public_html'
Write-Host ""

# -- Summary ---------------------------------------------------------------
$allFailed = @($e1) + @($e2)
if ($allFailed.Count -gt 0) {
    Write-Host "FIGYELEM: $($allFailed.Count) fajl feltoltese sikertelen:" -ForegroundColor Red
    foreach ($f in $allFailed) {
        Write-Host "  - $f" -ForegroundColor Red
    }
    exit 1
} else {
    Write-Host "Minden fajl sikeresen feltoltve!" -ForegroundColor Green
}

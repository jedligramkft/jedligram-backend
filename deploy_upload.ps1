$ErrorActionPreference = 'Stop'

$ftpServer = 'ftp://jcloud02.jedlik.eu'
$ftpUser   = $env:FTP_USER
$ftpPass   = $env:FTP_PASS

if (-not $ftpUser -or -not $ftpPass) {
    Write-Host "HIBA: FTP_USER vagy FTP_PASS nincs beallitva!" -ForegroundColor Red
    exit 1
}

function Upload-Recursive($localDir, $remoteBase) {
    $files  = Get-ChildItem $localDir -Recurse -File
    $total  = $files.Count
    $i      = 0
    $errors = 0

    foreach ($file in $files) {
        $i++
        $rel       = $file.FullName.Substring($localDir.Length).TrimStart('\').Replace('\', '/')
        $remoteUrl = "$ftpServer/$remoteBase/$rel"
        $pct       = [math]::Round(($i / $total) * 100)

        Write-Host "  [$i/$total] ($pct%) $rel"

        $result = & curl.exe -s -S --ssl-reqd --ftp-create-dirs --ftp-pasv -k -u "${ftpUser}:${ftpPass}" -T $file.FullName $remoteUrl 2>&1

        if ($LASTEXITCODE -ne 0) {
            Write-Host "    HIBA: $rel feltoltese sikertelen!" -ForegroundColor Red
            $errors++
        }
    }

    return $errors
}

# Upload core directory
Write-Host "Core mappa feltoltese -> public_html/core/ ..." -ForegroundColor Cyan
$coreDir = (Resolve-Path 'deploy\core').Path
$e1 = Upload-Recursive $coreDir 'public_html/core'
Write-Host ""

# Upload public_html contents
Write-Host "Public_html tartalom feltoltese -> public_html/ ..." -ForegroundColor Cyan
$pubDir = (Resolve-Path 'deploy\public_html').Path
$e2 = Upload-Recursive $pubDir 'public_html'
Write-Host ""

# Summary
$totalErrors = $e1 + $e2
if ($totalErrors -gt 0) {
    Write-Host "FIGYELEM: $totalErrors fajl feltoltese sikertelen!" -ForegroundColor Red
    exit 1
} else {
    Write-Host "Minden fajl sikeresen feltoltve!" -ForegroundColor Green
}

@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion

echo ============================================
echo   JedlikCloud Deploy - Geher Marcell
echo   Cel: public_html gyokerbe
echo   URL: https://jcloud02.jedlik.eu/geher.marcell
echo   FTPS: ftp://jcloud02.jedlik.eu (explicit TLS)
echo ============================================
echo.

REM Navigate to project root (where this BAT file lives)
cd /d "%~dp0"

REM ============================================
REM Pre-check: PHP, Composer, curl availability
REM ============================================
where php >nul 2>&1
if errorlevel 1 (
    echo HIBA: PHP nem talalhato! Telepitsd vagy add hozza a PATH-hoz.
    pause
    exit /b 1
)
where composer >nul 2>&1
if errorlevel 1 (
    echo HIBA: Composer nem talalhato! Telepitsd vagy add hozza a PATH-hoz.
    pause
    exit /b 1
)
where curl.exe >nul 2>&1
if errorlevel 1 (
    echo HIBA: curl.exe nem talalhato! Windows 10+ eseten alapbol elerheto.
    pause
    exit /b 1
)

REM ============================================
REM Pre-check: FTP credentials from .env
REM ============================================
set "FTP_USER="
set "FTP_PASS="
if not exist ".env" (
    echo HIBA: .env fajl nem talalhato!
    pause
    exit /b 1
)
for /f "usebackq tokens=1,* delims==" %%a in (".env") do (
    if "%%a"=="FTP_USER" set "FTP_USER=%%b"
    if "%%a"=="FTP_PASS" set "FTP_PASS=%%b"
)
if "%FTP_USER%"=="" (
    echo HIBA: FTP_USER nem talalhato a .env fajlban!
    echo Add hozza: FTP_USER=felhasznalonev
    pause
    exit /b 1
)
if "%FTP_PASS%"=="" (
    echo HIBA: FTP_PASS nem talalhato a .env fajlban!
    echo Add hozza: FTP_PASS=jelszo
    pause
    exit /b 1
)
echo   FTP felhasznalo: %FTP_USER%

REM ============================================
REM 1. Vendor ellenorzes
REM ============================================
if not exist "vendor\" (
    echo [1/8] vendor mappa nem talalhato, composer install futtatasa...
    call composer install --no-dev --optimize-autoloader
    if errorlevel 1 (
        echo HIBA: composer install sikertelen!
        pause
        exit /b 1
    )
) else (
    echo [1/8] vendor mappa mar letezik, composer install kihagyva.
)
echo.

REM ============================================
REM 2. App key generalas
REM ============================================
echo [2/8] App key generalas...
call php artisan key:generate --force
echo.

REM ============================================
REM 3. Config es route cache torles
REM ============================================
echo [3/8] Config es route cache torlese...
call php artisan config:clear
call php artisan route:clear
echo.

REM ============================================
REM 4. .env biztonsagi mentes
REM ============================================
echo [4/8] .env fajl biztonsagi mentese...
if exist ".env" (
    copy /Y ".env" ".env.dev-backup" >nul
    echo   .env masolva: .env.dev-backup
) else (
    echo   HIBA: .env fajl nem talalhato!
    pause
    exit /b 1
)
echo.

REM ============================================
REM 5. Deploy mappa eloallitasa
REM ============================================
echo [5/8] Deploy mappa eloallitasa...

if exist "deploy\" (
    echo   Elozo deploy mappa torlese...
    rmdir /S /Q "deploy" >nul 2>&1
)

mkdir "deploy\core" 2>nul
mkdir "deploy\public_html" 2>nul

REM Minden masolasa a core-ba (kiveve: public, node_modules, .git, deploy)
echo   Projekt masolasa -^> deploy\core\ ...
robocopy . "deploy\core" /E /XD "public" "node_modules" ".git" "deploy" /NFL /NDL /NJH /NJS /NC /NS /NP >nul

REM public/ mappa tartalma -^> public_html/
echo   public\ masolasa  -^> deploy\public_html\ ...
robocopy "public" "deploy\public_html" /E /NFL /NDL /NJH /NJS /NC /NS /NP >nul

echo.

REM ============================================
REM 6. Fajlok modositasa produkciohoz
REM ============================================
echo [6/8] Fajlok modositasa a produkciohoz...

REM 6a. index.php - eleresi utak atirasa a core mappara
echo   index.php modositasa...
powershell -NoProfile -Command ^
  "$f='deploy\public_html\index.php';" ^
  "$c=[IO.File]::ReadAllText($f);" ^
  "$c=$c.Replace('/../storage/framework/maintenance.php','/core/storage/framework/maintenance.php');" ^
  "$c=$c.Replace('/../vendor/autoload.php','/core/vendor/autoload.php');" ^
  "$c=$c.Replace('/../bootstrap/app.php','/core/bootstrap/app.php');" ^
  "[IO.File]::WriteAllText($f,$c)"

REM 6b. .htaccess - RewriteRule modositas
echo   .htaccess modositasa...
powershell -NoProfile -Command ^
  "$f='deploy\public_html\.htaccess';" ^
  "$c=[IO.File]::ReadAllText($f);" ^
  "$c=$c.Replace('RewriteRule ^ index.php [L]','RewriteRule ^ geher.marcell/index.php [L]');" ^
  "[IO.File]::WriteAllText($f,$c)"

REM 6c. Production .env letrehozasa
echo   Production .env letrehozasa...
powershell -NoProfile -Command ^
  "Copy-Item '.env' 'deploy\core\.env' -Force;" ^
  "$lines=Get-Content 'deploy\core\.env';" ^
  "$lines=$lines -replace '^APP_ENV=.*$','APP_ENV=production';" ^
  "$lines=$lines -replace '^APP_DEBUG=.*$','APP_DEBUG=false';" ^
  "$lines=$lines -replace '^APP_URL=.*$','APP_URL=https://jcloud02.jedlik.eu/geher.marcell';" ^
  "Set-Content 'deploy\core\.env' $lines"

echo.

REM ============================================
REM 7. Biztonsag es takaritas
REM ============================================
echo [7/8] Biztonsag es takaritas...

REM 7a. Biztonsagi .htaccess a core mappaban (.env es mappak vedelme)
echo   Biztonsagi .htaccess letrehozasa (core)...
(
    echo options -Indexes
    echo ^<Files .env^>
    echo order allow,deny
    echo Deny from all
    echo ^</Files^>
) > "deploy\core\.htaccess"

REM 7b. Felesleges fajlok torlese a core mappa gyokerebol
REM     Megmarad: .env, composer.json, .htaccess
REM     Minden mappa megmarad (app, bootstrap, config, database, routes, storage, vendor, stb.)
echo   Core mappa takaritasa (felesleges fajlok torlese)...
for %%f in ("deploy\core\*") do (
    if /I not "%%~nxf"==".env" (
        if /I not "%%~nxf"=="composer.json" (
            if /I not "%%~nxf"==".htaccess" (
                del "%%f" >nul 2>&1
            )
        )
    )
)

echo.

REM ============================================
REM 8. FTPS feltoltes
REM ============================================
echo [8/8] FTPS feltoltes: ftp://jcloud02.jedlik.eu (explicit TLS) ...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy_upload.ps1"

if errorlevel 1 (
    echo.
    echo HIBA: FTPS feltoltes kozben hiba tortent!
    pause
    exit /b 1
)

echo.
echo ============================================
echo   DEPLOY ES FELTOLTES SIKERESEN BEFEJEZVE!
echo ============================================
echo.
echo   Biztonsagi mentes: .env.dev-backup
echo.
echo   Elerheto: https://jcloud02.jedlik.eu/geher.marcell
echo.
echo ============================================
echo.
pause

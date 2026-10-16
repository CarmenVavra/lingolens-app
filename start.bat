@echo off
REM ================================================================
REM  LingoLens AI - startet Webserver (Port 8250) und Queue-Worker
REM  "start.bat handy" (oder start-handy.bat): auch im WLAN erreichbar,
REM  damit du die App am Handy testen kannst. Nur im eigenen WLAN nutzen!
REM ================================================================
setlocal EnableDelayedExpansion
cd /d "%~dp0"
set PORT=8250
set HOST=127.0.0.1
if /i "%~1"=="handy" set HOST=0.0.0.0

if not exist vendor (
    echo [LingoLens] Installiere PHP-Abhaengigkeiten ...
    call composer install --no-interaction
)

if not exist .env (
    echo [LingoLens] Erstelle .env ...
    copy .env.example .env >nul
    php artisan key:generate --force
)

set SEED=
if not exist database\database.sqlite (
    type nul > database\database.sqlite
    set SEED=1
)

php artisan migrate --force
if defined SEED (
    echo [LingoLens] Lege Demo-Daten an ...
    php artisan db:seed --force
)

if not exist public\build\manifest.json (
    echo [LingoLens] Baue Frontend-Assets ...
    call npm install
    call npm run build
)

echo [LingoLens] Starte Queue-Worker ...
start "LingoLens Queue" /min php artisan queue:work --tries=1 --timeout=300

echo [LingoLens] Starte Server auf http://localhost:%PORT% ...
start "LingoLens Server" /min php artisan serve --host=%HOST% --port=%PORT%

timeout /t 3 /nobreak >nul
start "" http://localhost:%PORT%
echo [LingoLens] Laeuft. Beenden mit stop.bat

if "%HOST%"=="0.0.0.0" (
    for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notmatch '^(127|169\.254)\.' -and $_.PrefixOrigin -ne 'WellKnown' } | Select-Object -First 1).IPAddress"`) do set LANIP=%%i
    echo.
    echo [LingoLens] Am Handy im selben WLAN oeffnen:  http://!LANIP!:%PORT%
    echo [LingoLens] Fragt Windows nach der Firewall, "Private Netzwerke" erlauben.
)
endlocal

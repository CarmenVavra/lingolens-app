@echo off
REM ================================================================
REM  LingoLens AI - startet Webserver (Port 8250) und Queue-Worker
REM ================================================================
setlocal
cd /d "%~dp0"
set PORT=8250

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
start "LingoLens Server" /min php artisan serve --host=127.0.0.1 --port=%PORT%

timeout /t 3 /nobreak >nul
start "" http://localhost:%PORT%
echo [LingoLens] Laeuft. Beenden mit stop.bat
endlocal

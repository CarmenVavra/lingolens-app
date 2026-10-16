@echo off
REM ================================================================
REM  LingoLens AI - stoppt Webserver (Port 8250) und Queue-Worker
REM ================================================================
setlocal
set PORT=8250

echo [LingoLens] Stoppe Server auf Port %PORT% und Queue-Worker ...
REM Sucht die PHP-Prozesse ueber ihre Befehlszeile (unabhaengig von der Windows-Sprache).
powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" | Where-Object { $_.CommandLine -match 'artisan\s+serve\s.*--port=%PORT%|\s-S\s+\S+:%PORT%\s|queue:work.*--name=lingolens' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }"

REM Fallback fuer Fenster aus aelteren Versionen von start.bat
taskkill /F /T /FI "WINDOWTITLE eq LingoLens Server*" >nul 2>&1
taskkill /F /T /FI "WINDOWTITLE eq LingoLens Queue*" >nul 2>&1

echo [LingoLens] Gestoppt.
endlocal

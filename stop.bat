@echo off
REM ================================================================
REM  LingoLens AI - stoppt Webserver (Port 8250) und Queue-Worker
REM ================================================================
setlocal
rem Vite-Dev-Server (falls "npm run dev" laeuft): fester Port, siehe vite.config.js
set "VITE_PORT=6350"
for /f "tokens=5" %%v in ('netstat -ano ^| findstr /R /C:":%VITE_PORT%  *0\.0\.0\.0:0 " /C:":%VITE_PORT%  *\[::\]:0 "') do taskkill /PID %%v /T /F >nul 2>&1
if exist "%~dp0public\hot" del /q "%~dp0public\hot" >nul 2>&1
set PORT=8250

echo [LingoLens] Stoppe Server auf Port %PORT% und Queue-Worker ...
REM Sucht die PHP-Prozesse ueber ihre Befehlszeile (unabhaengig von der Windows-Sprache).
powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" | Where-Object { $_.CommandLine -match 'artisan\s+serve\s.*--port=%PORT%|\s-S\s+\S+:%PORT%\s|queue:work.*--name=lingolens' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }"

REM Fallback fuer Fenster aus aelteren Versionen von start.bat
taskkill /F /T /FI "WINDOWTITLE eq LingoLens Server*" >nul 2>&1
taskkill /F /T /FI "WINDOWTITLE eq LingoLens Queue*" >nul 2>&1

echo [LingoLens] Gestoppt.
endlocal

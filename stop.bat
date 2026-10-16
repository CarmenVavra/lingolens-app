@echo off
REM ================================================================
REM  LingoLens AI - stoppt Webserver (Port 8250) und Queue-Worker
REM ================================================================
setlocal
set PORT=8250

echo [LingoLens] Stoppe Server auf Port %PORT% ...
for /f "tokens=5" %%p in ('netstat -ano ^| findstr /r /c:":%PORT% .*LISTENING"') do (
    taskkill /F /T /PID %%p >nul 2>&1
)

taskkill /F /T /FI "WINDOWTITLE eq LingoLens Server*" >nul 2>&1

echo [LingoLens] Stoppe Queue-Worker ...
taskkill /F /T /FI "WINDOWTITLE eq LingoLens Queue*" >nul 2>&1

echo [LingoLens] Gestoppt.
endlocal

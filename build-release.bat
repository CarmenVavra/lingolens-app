@echo off
REM ================================================================
REM  LingoLens AI - Upload-Paket fuer netcup bauen (siehe DEPLOYMENT.md)
REM ================================================================
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy\build-release.ps1"
pause

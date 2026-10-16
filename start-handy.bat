@echo off
REM ================================================================
REM  LingoLens AI - startet die App so, dass sie auch am Handy im
REM  selben WLAN erreichbar ist (zum Testen der Kamera).
REM  Nur im eigenen WLAN verwenden. Beenden mit stop.bat
REM ================================================================
call "%~dp0start.bat" handy
pause

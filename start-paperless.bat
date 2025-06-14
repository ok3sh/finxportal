@echo off

echo Starting Paperless (docker compose)...
start cmd /k "cd /d D:\scavi\paperless && docker compose up -d"
echo.
echo Press any key to stop Paperless and close its terminal.
pause > nul
call stop-paperless.bat
endlocal 
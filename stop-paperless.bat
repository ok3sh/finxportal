@echo off
echo Stopping Paperless (docker compose)...
start cmd /k "cd /d D:\scavi\paperless && docker compose down"
echo Paperless should now be stopping in Docker.
pause 
@echo off
echo Debugging Access Control System...
echo.

REM Run the debug SQL file
psql -U postgres -d fin_backend -f debug_access_control.sql

echo.
echo Debug completed. Check the output above.
echo Press any key to close...
pause 
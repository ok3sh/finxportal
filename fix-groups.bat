@echo off
echo Fixing Access Control Groups...
echo.

REM Run the fix SQL file
psql -U postgres -d fin_backend -f fix_groups.sql

echo.
echo Groups fixed. The system should now recognize your IT groups.
echo Press any key to close...
pause 
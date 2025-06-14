@echo off
echo Running Access Control Setup for PostgreSQL...
echo.

REM Run the SQL file using PostgreSQL
psql -U postgres -d fin_backend -f simple_setup.sql

echo.
echo Script execution completed.
echo Press any key to close this window...
pause 
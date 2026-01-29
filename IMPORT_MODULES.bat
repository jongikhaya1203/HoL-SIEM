@echo off
echo ========================================
echo  Importing SolarWinds Modules Database
echo ========================================
echo.

cd /d "%~dp0"

echo Current directory: %CD%
echo.

if not exist "database\modules_tables.sql" (
    echo ERROR: Cannot find database\modules_tables.sql
    echo Please make sure you're in the networkscan folder
    pause
    exit /b 1
)

echo Found: database\modules_tables.sql
echo.

set MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe

if not exist "%MYSQL_PATH%" (
    echo ERROR: MySQL not found at %MYSQL_PATH%
    echo Please check your XAMPP installation
    pause
    exit /b 1
)

echo MySQL found at: %MYSQL_PATH%
echo.
echo Importing modules_tables.sql...
echo You may be prompted for MySQL root password (press Enter if no password)
echo.

"%MYSQL_PATH%" -u root -p network_security_scanner < database\modules_tables.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  SUCCESS! Modules database imported
    echo ========================================
    echo.
    echo You can now access the dashboard at:
    echo http://localhost/networkscan/index.php
    echo.
) else (
    echo.
    echo ========================================
    echo  ERROR: Import failed
    echo ========================================
    echo.
    echo Common solutions:
    echo 1. Make sure MySQL is running in XAMPP
    echo 2. Check if database 'network_security_scanner' exists
    echo 3. Verify MySQL root password
    echo.
)

pause

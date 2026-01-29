@echo off
setlocal EnableDelayedExpansion

:: IOC Database Setup Script
:: Creates database and imports schema

set "APP_PATH=%~1"
if "%APP_PATH%"=="" set "APP_PATH=%~dp0.."

echo ========================================
echo  IOC Database Setup
echo ========================================
echo.

:: Read XAMPP path
set "XAMPP_PATH="
if exist "%APP_PATH%\scripts\xampp_path.txt" (
    set /p XAMPP_PATH=<"%APP_PATH%\scripts\xampp_path.txt"
)

if "%XAMPP_PATH%"=="" set "XAMPP_PATH=C:\xampp"

set "MYSQL_BIN=%XAMPP_PATH%\mysql\bin\mysql.exe"
set "PHP_BIN=%XAMPP_PATH%\php\php.exe"

:: Check if MySQL binary exists
if not exist "%MYSQL_BIN%" (
    echo Error: MySQL not found at %MYSQL_BIN%
    echo Please ensure XAMPP is installed correctly.
    exit /b 1
)

:: Wait for MySQL to be ready
echo Waiting for MySQL to start...
set RETRY=0
:WAIT_MYSQL
"%MYSQL_BIN%" -u root -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
    set /a RETRY+=1
    if !RETRY! lss 30 (
        timeout /t 2 /nobreak >nul
        goto WAIT_MYSQL
    ) else (
        echo Error: MySQL did not start in time.
        echo Please start MySQL manually and run this script again.
        exit /b 1
    )
)

echo MySQL is running.
echo.

:: Create database
echo Creating database 'networkscan'...
"%MYSQL_BIN%" -u root -e "CREATE DATABASE IF NOT EXISTS networkscan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 (
    echo Warning: Could not create database. It may already exist.
)

:: Import main schema
echo Importing main schema...
if exist "%APP_PATH%\database\schema.sql" (
    "%MYSQL_BIN%" -u root networkscan < "%APP_PATH%\database\schema.sql"
    if errorlevel 1 (
        echo Warning: Some schema imports may have failed (tables may already exist).
    ) else (
        echo Main schema imported successfully.
    )
) else (
    echo Warning: schema.sql not found
)

:: Import CMS tables
echo Importing CMS tables...
if exist "%APP_PATH%\database\cms_tables.sql" (
    "%MYSQL_BIN%" -u root networkscan < "%APP_PATH%\database\cms_tables.sql"
    if errorlevel 1 (
        echo Warning: Some CMS tables may have failed (tables may already exist).
    ) else (
        echo CMS tables imported successfully.
    )
) else (
    echo Warning: cms_tables.sql not found
)

:: Import module tables
echo Importing module tables...
if exist "%APP_PATH%\database\modules_tables.sql" (
    "%MYSQL_BIN%" -u root networkscan < "%APP_PATH%\database\modules_tables.sql"
    if errorlevel 1 (
        echo Warning: Some module tables may have failed (tables may already exist).
    ) else (
        echo Module tables imported successfully.
    )
) else (
    echo Warning: modules_tables.sql not found
)

:: Run PHP setup script if exists
if exist "%PHP_BIN%" (
    if exist "%APP_PATH%\fix_database.php" (
        echo Running database fix script...
        "%PHP_BIN%" "%APP_PATH%\fix_database.php"
    )
)

echo.
echo ========================================
echo  Database setup complete!
echo ========================================
echo.
echo Default admin credentials:
echo   Username: admin
echo   Password: admin123
echo.
echo IMPORTANT: Change these credentials after first login!
echo.

exit /b 0

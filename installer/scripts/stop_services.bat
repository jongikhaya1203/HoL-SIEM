@echo off
setlocal EnableDelayedExpansion

:: IOC Stop Services Script
:: Stops Apache and MySQL

set "APP_PATH=%~1"
if "%APP_PATH%"=="" set "APP_PATH=%~dp0.."

:: Read XAMPP path
set "XAMPP_PATH="
if exist "%APP_PATH%\scripts\xampp_path.txt" (
    set /p XAMPP_PATH=<"%APP_PATH%\scripts\xampp_path.txt"
)

if "%XAMPP_PATH%"=="" set "XAMPP_PATH=C:\xampp"

echo Stopping IOC Services...
echo.

:: Stop Apache
echo Stopping Apache...
taskkill /F /IM httpd.exe >nul 2>&1
if errorlevel 1 (
    echo Apache was not running.
) else (
    echo Apache stopped.
)

:: Stop MySQL
echo Stopping MySQL...
taskkill /F /IM mysqld.exe >nul 2>&1
if errorlevel 1 (
    echo MySQL was not running.
) else (
    echo MySQL stopped.
)

echo.
echo IOC Services stopped.
echo.

exit /b 0

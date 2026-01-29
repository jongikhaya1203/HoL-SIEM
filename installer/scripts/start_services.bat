@echo off
setlocal EnableDelayedExpansion

:: IOC Start Services Script
:: Starts Apache and MySQL

set "APP_PATH=%~1"
if "%APP_PATH%"=="" set "APP_PATH=%~dp0.."

:: Read XAMPP path
set "XAMPP_PATH="
if exist "%APP_PATH%\scripts\xampp_path.txt" (
    set /p XAMPP_PATH=<"%APP_PATH%\scripts\xampp_path.txt"
)

if "%XAMPP_PATH%"=="" set "XAMPP_PATH=C:\xampp"

echo Starting IOC Services...
echo XAMPP Path: %XAMPP_PATH%
echo.

:: Start Apache
echo Starting Apache...
if exist "%XAMPP_PATH%\apache\bin\httpd.exe" (
    start "" "%XAMPP_PATH%\apache\bin\httpd.exe"
    echo Apache started.
) else if exist "%XAMPP_PATH%\apache_start.bat" (
    start "" "%XAMPP_PATH%\apache_start.bat"
    echo Apache started.
) else (
    echo Warning: Apache not found
)

:: Start MySQL
echo Starting MySQL...
if exist "%XAMPP_PATH%\mysql\bin\mysqld.exe" (
    start "" "%XAMPP_PATH%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP_PATH%\mysql\bin\my.ini"
    echo MySQL started.
) else if exist "%XAMPP_PATH%\mysql_start.bat" (
    start "" "%XAMPP_PATH%\mysql_start.bat"
    echo MySQL started.
) else (
    echo Warning: MySQL not found
)

echo.
echo Services started. Waiting for them to initialize...
timeout /t 5 /nobreak >nul

echo.
echo IOC Services are now running.
echo.

exit /b 0

@echo off
setlocal EnableDelayedExpansion

:: IOC Intelligent Operating Centre - Launcher
:: Starts services and opens the web interface

set "APP_PATH=%~dp0"
cd /d "%APP_PATH%"

echo ========================================
echo  IOC Intelligent Operating Centre
echo  Starting Application...
echo ========================================
echo.

:: Read XAMPP path
set "XAMPP_PATH="
if exist "%APP_PATH%scripts\xampp_path.txt" (
    set /p XAMPP_PATH=<"%APP_PATH%scripts\xampp_path.txt"
)

:: Check common XAMPP locations
if "%XAMPP_PATH%"=="" (
    if exist "%APP_PATH%xampp" set "XAMPP_PATH=%APP_PATH%xampp"
)
if "%XAMPP_PATH%"=="" (
    if exist "C:\xampp" set "XAMPP_PATH=C:\xampp"
)
if "%XAMPP_PATH%"=="" (
    if exist "D:\xampp" set "XAMPP_PATH=D:\xampp"
)

if "%XAMPP_PATH%"=="" (
    echo Error: XAMPP not found!
    echo Please install XAMPP or configure the path.
    pause
    exit /b 1
)

echo Using XAMPP at: %XAMPP_PATH%
echo.

:: Check if services are already running
set "APACHE_RUNNING=0"
set "MYSQL_RUNNING=0"

tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if not errorlevel 1 set "APACHE_RUNNING=1"

tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if not errorlevel 1 set "MYSQL_RUNNING=1"

:: Start Apache if not running
if "%APACHE_RUNNING%"=="0" (
    echo Starting Apache...
    if exist "%XAMPP_PATH%\xampp_start.exe" (
        start "" "%XAMPP_PATH%\xampp_start.exe"
    ) else if exist "%XAMPP_PATH%\apache\bin\httpd.exe" (
        start "" "%XAMPP_PATH%\apache\bin\httpd.exe"
    ) else (
        echo Warning: Could not start Apache automatically.
        echo Please start it manually from XAMPP Control Panel.
    )
) else (
    echo Apache is already running.
)

:: Start MySQL if not running
if "%MYSQL_RUNNING%"=="0" (
    echo Starting MySQL...
    if exist "%XAMPP_PATH%\mysql\bin\mysqld.exe" (
        start "" "%XAMPP_PATH%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP_PATH%\mysql\bin\my.ini"
    ) else (
        echo Warning: Could not start MySQL automatically.
        echo Please start it manually from XAMPP Control Panel.
    )
) else (
    echo MySQL is already running.
)

:: Wait for services
echo.
echo Waiting for services to initialize...
timeout /t 3 /nobreak >nul

:: Determine the URL
:: Check if we're in htdocs or installed separately
set "WEB_URL=http://localhost/networkscanscada"

:: If installed in Program Files, we need to set up a symlink or virtual host
:: For now, assume standard htdocs installation or that user configured Apache

echo.
echo ========================================
echo  IOC is starting...
echo ========================================
echo.
echo Opening web browser...
echo URL: %WEB_URL%
echo.

:: Open the default browser
start "" "%WEB_URL%"

echo.
echo IOC Intelligent Operating Centre is now running!
echo.
echo Press any key to exit this window...
echo (The application will continue running in your browser)
pause >nul

exit /b 0

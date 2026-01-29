@echo off
setlocal EnableDelayedExpansion

:: IOC Configuration Script
:: Called by the installer to configure the application

set "APP_PATH=%~1"
if "%APP_PATH%"=="" set "APP_PATH=%~dp0.."

echo ========================================
echo  IOC Intelligent Operating Centre
echo  Configuration Script
echo ========================================
echo.

:: Read XAMPP path
set "XAMPP_PATH="
if exist "%APP_PATH%\scripts\xampp_path.txt" (
    set /p XAMPP_PATH=<"%APP_PATH%\scripts\xampp_path.txt"
)

if "%XAMPP_PATH%"=="" (
    echo Warning: XAMPP path not found
    set "XAMPP_PATH=C:\xampp"
)

echo Using XAMPP at: %XAMPP_PATH%
echo Application at: %APP_PATH%
echo.

:: Create required directories
echo Creating directories...
if not exist "%APP_PATH%\assets\uploads" mkdir "%APP_PATH%\assets\uploads"
if not exist "%APP_PATH%\reports" mkdir "%APP_PATH%\reports"
if not exist "%APP_PATH%\logs" mkdir "%APP_PATH%\logs"
echo Done.

:: Check if config exists, if not copy template
if not exist "%APP_PATH%\config\database.php" (
    echo Creating default configuration...
    if exist "%APP_PATH%\config\database.php.template" (
        copy "%APP_PATH%\config\database.php.template" "%APP_PATH%\config\database.php"
    )
)

:: Configure Apache virtual host (optional)
echo.
echo Configuration complete!
echo.

exit /b 0

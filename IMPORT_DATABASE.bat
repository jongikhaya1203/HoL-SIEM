@echo off
echo ========================================
echo Database Import Helper
echo ========================================
echo.

REM Change to script directory
cd /d "%~dp0"

echo Checking MySQL...
where mysql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo MySQL not in PATH. Using XAMPP path...
    set MYSQL=C:\xampp\mysql\bin\mysql.exe
) else (
    set MYSQL=mysql
)

if not exist "%MYSQL%" (
    echo ERROR: MySQL not found!
    echo Please check that XAMPP is installed or MySQL is in your PATH.
    pause
    exit /b 1
)

echo.
echo Found MySQL at: %MYSQL%
echo.

REM Check if schema exists
if not exist "database\schema.sql" (
    echo ERROR: database\schema.sql not found!
    echo Make sure you're running this from the networkscan folder.
    pause
    exit /b 1
)

echo ========================================
echo Step 1: Import Main Schema
echo ========================================
echo.
echo This will create all main tables.
echo.

set /p "CONTINUE=Continue? (Y/N): "
if /i not "%CONTINUE%"=="Y" goto SKIP_MAIN

echo.
echo Importing schema.sql...
"%MYSQL%" -u root -p network_security_scanner < "database\schema.sql"

if %ERRORLEVEL% EQU 0 (
    echo SUCCESS: Main schema imported!
) else (
    echo ERROR: Failed to import schema.
    echo.
    echo Common issues:
    echo 1. MySQL not running - Start it in XAMPP Control Panel
    echo 2. Wrong password - The default is usually blank press Enter
    echo 3. Database doesn't exist - Create it first
    pause
    exit /b 1
)

:SKIP_MAIN

echo.
echo ========================================
echo Step 2: Import CMS Tables (Optional)
echo ========================================
echo.
echo This adds the Admin Portal tables.
echo.

set /p "CONTINUE_CMS=Import CMS tables? (Y/N): "
if /i not "%CONTINUE_CMS%"=="Y" goto DONE

if not exist "database\cms_tables.sql" (
    echo WARNING: database\cms_tables.sql not found!
    echo Skipping CMS import.
    goto DONE
)

echo.
echo Importing cms_tables.sql...
"%MYSQL%" -u root -p network_security_scanner < "database\cms_tables.sql"

if %ERRORLEVEL% EQU 0 (
    echo SUCCESS: CMS tables imported!
) else (
    echo ERROR: Failed to import CMS tables.
    pause
    exit /b 1
)

:DONE

echo.
echo ========================================
echo Import Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Run: php test_db.php  (to verify)
echo 2. Access: http://localhost/networkscan/
echo 3. Admin: http://localhost/networkscan/admin/
echo.
echo Admin credentials:
echo   Username: admin
echo   Password: admin123
echo.
pause

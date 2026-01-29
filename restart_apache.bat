@echo off
echo Stopping Apache...
taskkill /F /IM httpd.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo Starting Apache...
cd C:\xampp\apache\bin
start /B httpd.exe

timeout /t 3 /nobreak >nul
echo Apache restarted!

echo.
echo Now clearing PHP cache...
"C:\xampp\php\php.exe" "C:\XAMPP\HTDOCS\networkscan\clear_cache.php"

echo.
echo Done! You can now test the scanner.
pause

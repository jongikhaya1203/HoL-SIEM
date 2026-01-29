@echo off
title NetworkScanScada POC Installer
echo.
echo ============================================================
echo     NetworkScanScada - PROOF OF CONCEPT INSTALLER
echo                    30-Day Evaluation
echo ============================================================
echo.

:: Check for admin privileges
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This installer requires Administrator privileges.
    echo.
    echo Please right-click and select "Run as Administrator"
    echo.
    pause
    exit /b 1
)

:: Run PowerShell installer
powershell.exe -ExecutionPolicy Bypass -NoProfile -File "%~dp0NetworkScanScada-POC-Setup.ps1"

exit /b %errorLevel%

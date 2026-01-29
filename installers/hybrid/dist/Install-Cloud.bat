@echo off
title NetworkScanScada Cloud Tenant Setup
echo.
echo ============================================================
echo     NetworkScanScada - CLOUD TENANT SETUP
echo                 Hybrid Deployment
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
powershell.exe -ExecutionPolicy Bypass -NoProfile -File "%~dp0NetworkScanScada-Cloud-Setup.ps1"

exit /b %errorLevel%

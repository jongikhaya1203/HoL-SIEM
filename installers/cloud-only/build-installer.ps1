#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Build NetworkScanScada Pure Cloud/SaaS Installer

.DESCRIPTION
    Creates executable installer for pure cloud deployment
#>

param(
    [string]$OutputDir = ".\dist",
    [switch]$Sign
)

$ErrorActionPreference = "Stop"
$AppVersion = "1.0.0"

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "NetworkScanScada SaaS Installer Builder" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Create output directory
if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

# Install PS2EXE if needed
if (-not (Get-Module -ListAvailable -Name ps2exe)) {
    Write-Host "Installing PS2EXE module..." -ForegroundColor Yellow
    Install-Module -Name ps2exe -Scope CurrentUser -Force
}

Import-Module ps2exe

Write-Host "Building SaaS Setup executable..." -ForegroundColor Yellow

$exePath = Join-Path $OutputDir "NetworkScanScada-SaaS-Setup.exe"

$params = @{
    InputFile = ".\NetworkScanScada-SaaS-Setup.ps1"
    OutputFile = $exePath
    Title = "NetworkScanScada Pure Cloud SaaS Setup"
    Description = "100% cloud-based deployment of NetworkScanScada"
    Company = "HoL SIEM Security"
    Product = "NetworkScanScada SaaS"
    Version = $AppVersion
    Copyright = "Copyright $(Get-Date -Format yyyy) HoL SIEM Security"
    RequireAdmin = $true
    NoConsole = $false
}

try {
    Invoke-ps2exe @params
    Write-Host "Created: $exePath" -ForegroundColor Green
}
catch {
    Write-Host "Build failed: $_" -ForegroundColor Red
    exit 1
}

# Create README
$readme = @"
# NetworkScanScada Pure Cloud / SaaS Deployment

## Overview

This installer sets up NetworkScanScada as a fully cloud-hosted solution:
- No on-premise components required
- Database hosted in the cloud (Aurora/RDS)
- Application runs on cloud infrastructure
- Automatic scaling and high availability

## Requirements

- Valid subscription key (or use 14-day free trial)
- Cloud provider account (AWS, Azure, or GCP)
- Cloud CLI tools installed (optional, for automated deployment)

## Installation

``````
NetworkScanScada-SaaS-Setup.exe [-SubscriptionKey <key>] [-OrganizationName <name>] [-AdminEmail <email>] [-Region <region>] [-CloudProvider <AWS|Azure|GCP>] [-Plan <starter|professional|enterprise>]
``````

## Plans

| Plan | Assets | Users | Price |
|------|--------|-------|-------|
| Starter | 50 | 3 | \$99/mo |
| Professional | 500 | 10 | \$299/mo |
| Enterprise | Unlimited | Unlimited | \$999/mo |

## Post-Installation

1. Access the application at the provided URL
2. Login with the admin credentials
3. **Change password immediately**
4. Configure your network scan targets
5. Set up users and permissions

## Support

- Email: support@networkscanscada.com
- Docs: https://docs.networkscanscada.com
"@

Set-Content -Path "$OutputDir\README.md" -Value $readme

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "Build Complete!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host "Output: $OutputDir" -ForegroundColor White
Get-ChildItem $OutputDir | ForEach-Object { Write-Host "  - $($_.Name)" -ForegroundColor Gray }

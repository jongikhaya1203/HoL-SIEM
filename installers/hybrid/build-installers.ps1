#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Build NetworkScanScada Hybrid Installers

.DESCRIPTION
    Creates executable installers for:
    - Cloud Tenant Setup
    - On-Premise Collector

.NOTES
    Requires: PS2EXE module
#>

param(
    [string]$OutputDir = ".\dist",
    [switch]$CloudOnly,
    [switch]$CollectorOnly,
    [switch]$Sign
)

$ErrorActionPreference = "Stop"
$AppVersion = "1.0.0"

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "NetworkScanScada Hybrid Installer Builder" -ForegroundColor Cyan
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

# Build Cloud Setup
if (-not $CollectorOnly) {
    Write-Host "[1/2] Building Cloud Tenant Setup..." -ForegroundColor Yellow

    $cloudExe = Join-Path $OutputDir "NetworkScanScada-Cloud-Setup.exe"

    $params = @{
        InputFile = ".\cloud\NetworkScanScada-Cloud-Setup.ps1"
        OutputFile = $cloudExe
        Title = "NetworkScanScada Cloud Tenant Setup"
        Description = "Sets up cloud tenant for hybrid deployment"
        Company = "HoL SIEM Security"
        Product = "NetworkScanScada Cloud"
        Version = $AppVersion
        Copyright = "Copyright $(Get-Date -Format yyyy) HoL SIEM Security"
        RequireAdmin = $true
        NoConsole = $false
    }

    try {
        Invoke-ps2exe @params
        Write-Host "  Created: $cloudExe" -ForegroundColor Green
    }
    catch {
        Write-Host "  Build failed: $_" -ForegroundColor Red
    }
}

# Build Collector Setup
if (-not $CloudOnly) {
    Write-Host "[2/2] Building Collector Setup..." -ForegroundColor Yellow

    $collectorExe = Join-Path $OutputDir "NetworkScanScada-Collector-Setup.exe"

    $params = @{
        InputFile = ".\collector\NetworkScanScada-Collector-Setup.ps1"
        OutputFile = $collectorExe
        Title = "NetworkScanScada On-Premise Collector Setup"
        Description = "Installs on-premise data collector for hybrid deployment"
        Company = "HoL SIEM Security"
        Product = "NetworkScanScada Collector"
        Version = $AppVersion
        Copyright = "Copyright $(Get-Date -Format yyyy) HoL SIEM Security"
        RequireAdmin = $true
        NoConsole = $false
    }

    try {
        Invoke-ps2exe @params
        Write-Host "  Created: $collectorExe" -ForegroundColor Green
    }
    catch {
        Write-Host "  Build failed: $_" -ForegroundColor Red
    }
}

# Create README
$readme = @"
# NetworkScanScada Hybrid Deployment Installers

## Files

- **NetworkScanScada-Cloud-Setup.exe** - Cloud tenant setup
- **NetworkScanScada-Collector-Setup.exe** - On-premise collector

## Installation Order

1. Run Cloud Setup first to create your tenant
2. Note the Tenant ID and Collector Token
3. Run Collector Setup on each on-premise server

## Cloud Setup Options

``````
NetworkScanScada-Cloud-Setup.exe [-TenantId <id>] [-SubscriptionKey <key>] [-Region <region>] [-CloudProvider <AWS|Azure|GCP>]
``````

## Collector Setup Options

``````
NetworkScanScada-Collector-Setup.exe [-TenantId <id>] [-CollectorToken <token>] [-CollectorName <name>] [-ConfigFile <path>]
``````

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

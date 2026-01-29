# Build All NetworkScanScada Installers
# This script builds all three installer packages

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "     NetworkScanScada - BUILD ALL INSTALLERS" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Check for PS2EXE
Write-Host "Checking PS2EXE module..." -ForegroundColor Yellow
if (-not (Get-Module -ListAvailable -Name ps2exe)) {
    Write-Host "Installing PS2EXE module..." -ForegroundColor Yellow
    Install-Module -Name ps2exe -Scope CurrentUser -Force -AllowClobber
}
Import-Module ps2exe -Force
Write-Host "PS2EXE module loaded" -ForegroundColor Green
Write-Host ""

$successCount = 0
$failCount = 0

# ============================================================
# Build POC Installer
# ============================================================
Write-Host "[1/4] Building POC Installer..." -ForegroundColor Cyan

$pocPath = Join-Path $PSScriptRoot "poc"
$pocDist = Join-Path $pocPath "dist"
if (-not (Test-Path $pocDist)) { New-Item -ItemType Directory -Path $pocDist -Force | Out-Null }

try {
    $pocParams = @{
        InputFile = Join-Path $pocPath "NetworkScanScada-POC-Setup.ps1"
        OutputFile = Join-Path $pocDist "NetworkScanScada-POC-Setup.exe"
        Title = "NetworkScanScada POC Installer"
        Description = "30-Day Proof of Concept Trial"
        Company = "HoL SIEM Security"
        Product = "NetworkScanScada POC"
        Version = "1.0.0"
        Copyright = "Copyright 2024 HoL SIEM Security"
        RequireAdmin = $true
        NoConsole = $false
        NoOutput = $false
    }
    Invoke-ps2exe @pocParams
    Copy-Item (Join-Path $pocPath "Install-POC.bat") $pocDist -Force
    $file = Get-Item $pocParams.OutputFile
    Write-Host "  SUCCESS: $($file.Name) ($([math]::Round($file.Length / 1KB, 2)) KB)" -ForegroundColor Green
    $successCount++
}
catch {
    Write-Host "  FAILED: $_" -ForegroundColor Red
    $failCount++
}

# ============================================================
# Build Hybrid Cloud Installer
# ============================================================
Write-Host ""
Write-Host "[2/4] Building Hybrid Cloud Tenant Installer..." -ForegroundColor Cyan

$hybridPath = Join-Path $PSScriptRoot "hybrid"
$hybridDist = Join-Path $hybridPath "dist"
if (-not (Test-Path $hybridDist)) { New-Item -ItemType Directory -Path $hybridDist -Force | Out-Null }

try {
    $cloudParams = @{
        InputFile = Join-Path $hybridPath "cloud\NetworkScanScada-Cloud-Setup.ps1"
        OutputFile = Join-Path $hybridDist "NetworkScanScada-Cloud-Setup.exe"
        Title = "NetworkScanScada Cloud Tenant Setup"
        Description = "Hybrid Deployment - Cloud Component"
        Company = "HoL SIEM Security"
        Product = "NetworkScanScada Cloud"
        Version = "1.0.0"
        Copyright = "Copyright 2024 HoL SIEM Security"
        RequireAdmin = $true
        NoConsole = $false
        NoOutput = $false
    }
    Invoke-ps2exe @cloudParams
    Copy-Item (Join-Path $hybridPath "cloud\Install-Cloud.bat") $hybridDist -Force
    $file = Get-Item $cloudParams.OutputFile
    Write-Host "  SUCCESS: $($file.Name) ($([math]::Round($file.Length / 1KB, 2)) KB)" -ForegroundColor Green
    $successCount++
}
catch {
    Write-Host "  FAILED: $_" -ForegroundColor Red
    $failCount++
}

# ============================================================
# Build Hybrid Collector Installer
# ============================================================
Write-Host ""
Write-Host "[3/4] Building Hybrid Collector Installer..." -ForegroundColor Cyan

try {
    $collectorParams = @{
        InputFile = Join-Path $hybridPath "collector\NetworkScanScada-Collector-Setup.ps1"
        OutputFile = Join-Path $hybridDist "NetworkScanScada-Collector-Setup.exe"
        Title = "NetworkScanScada On-Premise Collector Setup"
        Description = "Hybrid Deployment - On-Premise Collector"
        Company = "HoL SIEM Security"
        Product = "NetworkScanScada Collector"
        Version = "1.0.0"
        Copyright = "Copyright 2024 HoL SIEM Security"
        RequireAdmin = $true
        NoConsole = $false
        NoOutput = $false
    }
    Invoke-ps2exe @collectorParams
    Copy-Item (Join-Path $hybridPath "collector\Install-Collector.bat") $hybridDist -Force
    $file = Get-Item $collectorParams.OutputFile
    Write-Host "  SUCCESS: $($file.Name) ($([math]::Round($file.Length / 1KB, 2)) KB)" -ForegroundColor Green
    $successCount++
}
catch {
    Write-Host "  FAILED: $_" -ForegroundColor Red
    $failCount++
}

# ============================================================
# Build Cloud-Only SaaS Installer
# ============================================================
Write-Host ""
Write-Host "[4/4] Building Cloud-Only SaaS Installer..." -ForegroundColor Cyan

$saasPath = Join-Path $PSScriptRoot "cloud-only"
$saasDist = Join-Path $saasPath "dist"
if (-not (Test-Path $saasDist)) { New-Item -ItemType Directory -Path $saasDist -Force | Out-Null }

try {
    $saasParams = @{
        InputFile = Join-Path $saasPath "NetworkScanScada-SaaS-Setup.ps1"
        OutputFile = Join-Path $saasDist "NetworkScanScada-SaaS-Setup.exe"
        Title = "NetworkScanScada Pure Cloud SaaS Setup"
        Description = "100% Cloud-Based Deployment"
        Company = "HoL SIEM Security"
        Product = "NetworkScanScada SaaS"
        Version = "1.0.0"
        Copyright = "Copyright 2024 HoL SIEM Security"
        RequireAdmin = $true
        NoConsole = $false
        NoOutput = $false
    }
    Invoke-ps2exe @saasParams
    Copy-Item (Join-Path $saasPath "Install-SaaS.bat") $saasDist -Force
    $file = Get-Item $saasParams.OutputFile
    Write-Host "  SUCCESS: $($file.Name) ($([math]::Round($file.Length / 1KB, 2)) KB)" -ForegroundColor Green
    $successCount++
}
catch {
    Write-Host "  FAILED: $_" -ForegroundColor Red
    $failCount++
}

# ============================================================
# Create README files in dist folders
# ============================================================

# POC README
$pocReadme = @"
# NetworkScanScada POC Installer
30-Day Proof of Concept Trial

## Installation
1. Right-click NetworkScanScada-POC-Setup.exe
2. Select "Run as Administrator"
3. Follow the installation prompts

## Alternative
Run Install-POC.bat as Administrator

## Features
- 30-day automatic trial expiration
- Auto-uninstall after trial expires
- Full functionality during trial period

## Support
Email: support@networkscanscada.com
"@
Set-Content -Path (Join-Path $pocDist "README.md") -Value $pocReadme

# Hybrid README
$hybridReadme = @"
# NetworkScanScada Hybrid Installers

## Files
- NetworkScanScada-Cloud-Setup.exe - Cloud tenant setup (run first)
- NetworkScanScada-Collector-Setup.exe - On-premise collector

## Installation Order
1. Run Cloud Setup first to create your tenant
2. Note the Tenant ID and Collector Token
3. Run Collector Setup on each on-premise server

## Alternative
Run the .bat files as Administrator

## Support
Email: support@networkscanscada.com
"@
Set-Content -Path (Join-Path $hybridDist "README.md") -Value $hybridReadme

# SaaS README
$saasReadme = @"
# NetworkScanScada Pure Cloud SaaS Installer
100% Cloud-Based Deployment

## Installation
1. Right-click NetworkScanScada-SaaS-Setup.exe
2. Select "Run as Administrator"
3. Follow the installation prompts

## Alternative
Run Install-SaaS.bat as Administrator

## Plans
- Starter: `$99/mo (50 assets, 3 users)
- Professional: `$299/mo (500 assets, 10 users)
- Enterprise: `$999/mo (Unlimited)

## Support
Email: support@networkscanscada.com
"@
Set-Content -Path (Join-Path $saasDist "README.md") -Value $saasReadme

# ============================================================
# Summary
# ============================================================
Write-Host ""
Write-Host "============================================================" -ForegroundColor $(if ($failCount -eq 0) { "Green" } else { "Yellow" })
Write-Host "                     BUILD COMPLETE" -ForegroundColor $(if ($failCount -eq 0) { "Green" } else { "Yellow" })
Write-Host "============================================================" -ForegroundColor $(if ($failCount -eq 0) { "Green" } else { "Yellow" })
Write-Host ""
Write-Host "  Successful: $successCount" -ForegroundColor Green
Write-Host "  Failed: $failCount" -ForegroundColor $(if ($failCount -eq 0) { "Gray" } else { "Red" })
Write-Host ""
Write-Host "  Output Locations:" -ForegroundColor Cyan
Write-Host "    POC:    $pocDist" -ForegroundColor White
Write-Host "    Hybrid: $hybridDist" -ForegroundColor White
Write-Host "    SaaS:   $saasDist" -ForegroundColor White
Write-Host ""

if ($failCount -gt 0) {
    Write-Host "  Some builds failed. Check the errors above." -ForegroundColor Yellow
}

# Build NetworkScanScada Hybrid Installers
$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "NetworkScanScada Hybrid Installer Builder" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Create output directory
$distPath = Join-Path $PSScriptRoot "dist"
if (-not (Test-Path $distPath)) {
    New-Item -ItemType Directory -Path $distPath -Force | Out-Null
    Write-Host "Created dist directory" -ForegroundColor Gray
}

# Check for PS2EXE
Write-Host "Checking PS2EXE module..." -ForegroundColor Yellow
if (-not (Get-Module -ListAvailable -Name ps2exe)) {
    Write-Host "Installing PS2EXE module..." -ForegroundColor Yellow
    Install-Module -Name ps2exe -Scope CurrentUser -Force -AllowClobber
}

Import-Module ps2exe -Force
Write-Host "PS2EXE module loaded" -ForegroundColor Green
Write-Host ""

# Build Cloud Setup
Write-Host "[1/2] Building Cloud Tenant Setup..." -ForegroundColor Yellow

$cloudInput = Join-Path $PSScriptRoot "cloud\NetworkScanScada-Cloud-Setup.ps1"
$cloudOutput = Join-Path $distPath "NetworkScanScada-Cloud-Setup.exe"

$cloudParams = @{
    InputFile = $cloudInput
    OutputFile = $cloudOutput
    Title = "NetworkScanScada Cloud Tenant Setup"
    Description = "Sets up cloud tenant for hybrid deployment"
    Company = "HoL SIEM Security"
    Product = "NetworkScanScada Cloud"
    Version = "1.0.0"
    Copyright = "Copyright 2024 HoL SIEM Security"
    RequireAdmin = $true
    NoConsole = $false
    NoOutput = $false
}

try {
    Invoke-ps2exe @cloudParams
    $cloudFile = Get-Item $cloudOutput
    Write-Host "  Created: $($cloudFile.Name) ($([math]::Round($cloudFile.Length / 1KB, 2)) KB)" -ForegroundColor Green
}
catch {
    Write-Host "  Cloud Setup build failed: $_" -ForegroundColor Red
}

# Build Collector Setup
Write-Host ""
Write-Host "[2/2] Building Collector Setup..." -ForegroundColor Yellow

$collectorInput = Join-Path $PSScriptRoot "collector\NetworkScanScada-Collector-Setup.ps1"
$collectorOutput = Join-Path $distPath "NetworkScanScada-Collector-Setup.exe"

$collectorParams = @{
    InputFile = $collectorInput
    OutputFile = $collectorOutput
    Title = "NetworkScanScada On-Premise Collector Setup"
    Description = "Installs on-premise data collector for hybrid deployment"
    Company = "HoL SIEM Security"
    Product = "NetworkScanScada Collector"
    Version = "1.0.0"
    Copyright = "Copyright 2024 HoL SIEM Security"
    RequireAdmin = $true
    NoConsole = $false
    NoOutput = $false
}

try {
    Invoke-ps2exe @collectorParams
    $collectorFile = Get-Item $collectorOutput
    Write-Host "  Created: $($collectorFile.Name) ($([math]::Round($collectorFile.Length / 1KB, 2)) KB)" -ForegroundColor Green
}
catch {
    Write-Host "  Collector Setup build failed: $_" -ForegroundColor Red
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

NetworkScanScada-Cloud-Setup.exe [-TenantId <id>] [-SubscriptionKey <key>] [-Region <region>] [-CloudProvider <AWS|Azure|GCP>]

## Collector Setup Options

NetworkScanScada-Collector-Setup.exe [-TenantId <id>] [-CollectorToken <token>] [-CollectorName <name>] [-ConfigFile <path>]

## Support

- Email: support@networkscanscada.com
- Docs: https://docs.networkscanscada.com
"@

Set-Content -Path (Join-Path $distPath "README.md") -Value $readme

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Build Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Contents of dist folder:" -ForegroundColor Cyan
Get-ChildItem $distPath | ForEach-Object {
    Write-Host "  - $($_.Name) ($([math]::Round($_.Length / 1KB, 2)) KB)" -ForegroundColor Gray
}

# Build NetworkScanScada Cloud-Only/SaaS Installer
$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "NetworkScanScada SaaS Installer Builder" -ForegroundColor Cyan
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

# Build SaaS Setup
Write-Host "Building SaaS Setup executable..." -ForegroundColor Yellow

$saasInput = Join-Path $PSScriptRoot "NetworkScanScada-SaaS-Setup.ps1"
$saasOutput = Join-Path $distPath "NetworkScanScada-SaaS-Setup.exe"

$saasParams = @{
    InputFile = $saasInput
    OutputFile = $saasOutput
    Title = "NetworkScanScada Pure Cloud SaaS Setup"
    Description = "100% cloud-based deployment of NetworkScanScada"
    Company = "HoL SIEM Security"
    Product = "NetworkScanScada SaaS"
    Version = "1.0.0"
    Copyright = "Copyright 2024 HoL SIEM Security"
    RequireAdmin = $true
    NoConsole = $false
    NoOutput = $false
}

try {
    Invoke-ps2exe @saasParams
    $saasFile = Get-Item $saasOutput
    Write-Host "  Created: $($saasFile.Name) ($([math]::Round($saasFile.Length / 1KB, 2)) KB)" -ForegroundColor Green
}
catch {
    Write-Host "  SaaS Setup build failed: $_" -ForegroundColor Red
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

NetworkScanScada-SaaS-Setup.exe [-SubscriptionKey <key>] [-OrganizationName <name>] [-AdminEmail <email>] [-Region <region>] [-CloudProvider <AWS|Azure|GCP>] [-Plan <starter|professional|enterprise>]

## Plans

| Plan | Assets | Users | Price |
|------|--------|-------|-------|
| Starter | 50 | 3 | `$99/mo |
| Professional | 500 | 10 | `$299/mo |
| Enterprise | Unlimited | Unlimited | `$999/mo |

## Post-Installation

1. Access the application at the provided URL
2. Login with the admin credentials
3. **Change password immediately**
4. Configure your network scan targets
5. Set up users and permissions

## Terraform Deployment

For infrastructure-as-code deployment, use the terraform/ directory:

    cd terraform
    terraform init
    terraform plan -var-file="my-tenant.tfvars"
    terraform apply -var-file="my-tenant.tfvars"

## Support

- Email: support@networkscanscada.com
- Docs: https://docs.networkscanscada.com
"@

Set-Content -Path (Join-Path $distPath "README.md") -Value $readme

# Copy Terraform files to dist
$terraformSrc = Join-Path $PSScriptRoot "terraform"
$terraformDst = Join-Path $distPath "terraform"
if (Test-Path $terraformSrc) {
    if (-not (Test-Path $terraformDst)) {
        New-Item -ItemType Directory -Path $terraformDst -Force | Out-Null
    }
    Copy-Item -Path "$terraformSrc\*" -Destination $terraformDst -Recurse -Force
    Write-Host "  Copied Terraform files to dist" -ForegroundColor Gray
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Build Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Contents of dist folder:" -ForegroundColor Cyan
Get-ChildItem $distPath | ForEach-Object {
    if ($_.PSIsContainer) {
        Write-Host "  - $($_.Name)/ (directory)" -ForegroundColor Gray
    } else {
        Write-Host "  - $($_.Name) ($([math]::Round($_.Length / 1KB, 2)) KB)" -ForegroundColor Gray
    }
}

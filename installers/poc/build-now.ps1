# Build NetworkScanScada POC Installer
$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "NetworkScanScada POC Installer Builder" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Create output directory
$distPath = Join-Path $PSScriptRoot "dist"
if (-not (Test-Path $distPath)) {
    New-Item -ItemType Directory -Path $distPath -Force | Out-Null
    Write-Host "Created dist directory" -ForegroundColor Gray
}

# Create LICENSE.txt
$license = @"
NetworkScanScada Proof of Concept License Agreement

EVALUATION LICENSE - 30 DAY TRIAL

This software is provided for evaluation purposes only. By installing this
software, you agree to the following terms:

1. TRIAL PERIOD: This evaluation license is valid for 30 days from the date
   of installation. After the trial period expires, the software will be
   automatically uninstalled.

2. RESTRICTIONS: This evaluation version:
   - Is limited to 100 assets
   - Is limited to 5 users
   - May not be used in production environments
   - May not be redistributed

3. NO WARRANTY: This software is provided "as is" without warranty of any kind.

Copyright 2024 HoL SIEM Security. All rights reserved.
"@

Set-Content -Path (Join-Path $PSScriptRoot "LICENSE.txt") -Value $license
Write-Host "Created LICENSE.txt" -ForegroundColor Gray

# Create README-POC.txt
$readme = @"
NetworkScanScada Proof of Concept
=================================

Thank you for evaluating NetworkScanScada!

This POC version includes full network scanning and SCADA protocol support.

Limitations:
- 30-day evaluation period
- Maximum 100 assets
- Maximum 5 users
- Automatic uninstall after trial expiry

Getting Started:
1. Complete the installation
2. Access the web interface at https://localhost:8443
3. Login with: admin / admin123

Support: support@networkscanscada.com
"@

Set-Content -Path (Join-Path $PSScriptRoot "README-POC.txt") -Value $readme
Write-Host "Created README-POC.txt" -ForegroundColor Gray

# Check for PS2EXE
Write-Host ""
Write-Host "Checking PS2EXE module..." -ForegroundColor Yellow
if (-not (Get-Module -ListAvailable -Name ps2exe)) {
    Write-Host "Installing PS2EXE module..." -ForegroundColor Yellow
    Install-Module -Name ps2exe -Scope CurrentUser -Force -AllowClobber
}

Import-Module ps2exe -Force
Write-Host "PS2EXE module loaded" -ForegroundColor Green

# Build executable
Write-Host ""
Write-Host "Building executable..." -ForegroundColor Yellow

$inputFile = Join-Path $PSScriptRoot "NetworkScanScada-POC-Setup.ps1"
$outputFile = Join-Path $distPath "NetworkScanScada-POC-Setup.exe"

$buildParams = @{
    InputFile = $inputFile
    OutputFile = $outputFile
    Title = "NetworkScanScada POC Setup"
    Description = "NetworkScanScada Proof of Concept Installer (30-Day Trial)"
    Company = "HoL SIEM Security"
    Product = "NetworkScanScada POC"
    Version = "1.0.0"
    Copyright = "Copyright 2024 HoL SIEM Security"
    RequireAdmin = $true
    NoConsole = $false
    NoOutput = $false
}

try {
    Invoke-ps2exe @buildParams

    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Build Successful!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""

    $exeFile = Get-Item $outputFile
    Write-Host "Output file: $outputFile" -ForegroundColor White
    Write-Host "File size: $([math]::Round($exeFile.Length / 1KB, 2)) KB" -ForegroundColor White
    Write-Host ""

    # List dist contents
    Write-Host "Contents of dist folder:" -ForegroundColor Cyan
    Get-ChildItem $distPath | ForEach-Object {
        Write-Host "  - $($_.Name) ($([math]::Round($_.Length / 1KB, 2)) KB)" -ForegroundColor Gray
    }
}
catch {
    Write-Host ""
    Write-Host "Build failed: $_" -ForegroundColor Red
    exit 1
}

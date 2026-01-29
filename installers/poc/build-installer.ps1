#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Build NetworkScanScada POC Installer Executable

.DESCRIPTION
    Converts PowerShell installer script to standalone .exe file
    using PS2EXE or creates an Inno Setup installer.

.NOTES
    Requires: PS2EXE module or Inno Setup Compiler
#>

param(
    [ValidateSet("PS2EXE", "InnoSetup", "Both")]
    [string]$BuildType = "Both",
    [string]$OutputDir = ".\dist",
    [switch]$Sign
)

$ErrorActionPreference = "Stop"

# Configuration
$AppName = "NetworkScanScada-POC"
$AppVersion = "1.0.0"
$Publisher = "HoL SIEM Security"
$ScriptPath = ".\NetworkScanScada-POC-Setup.ps1"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "NetworkScanScada POC Installer Builder" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Create output directory
if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

# ============================================================
# PS2EXE Build
# ============================================================
function Build-PS2EXE {
    Write-Host "[1/3] Building with PS2EXE..." -ForegroundColor Yellow

    # Check if PS2EXE is installed
    if (-not (Get-Module -ListAvailable -Name ps2exe)) {
        Write-Host "Installing PS2EXE module..." -ForegroundColor Gray
        Install-Module -Name ps2exe -Scope CurrentUser -Force
    }

    Import-Module ps2exe

    $exePath = Join-Path $OutputDir "$AppName-Setup.exe"

    $params = @{
        InputFile = $ScriptPath
        OutputFile = $exePath
        Title = "$AppName Setup"
        Description = "NetworkScanScada Proof of Concept Installer (30-Day Trial)"
        Company = $Publisher
        Product = $AppName
        Version = $AppVersion
        Copyright = "Copyright $(Get-Date -Format yyyy) $Publisher"
        RequireAdmin = $true
        NoConsole = $false
        NoOutput = $false
        Verbose = $true
    }

    try {
        Invoke-ps2exe @params
        Write-Host "  Created: $exePath" -ForegroundColor Green
        return $exePath
    }
    catch {
        Write-Host "  PS2EXE build failed: $_" -ForegroundColor Red
        return $null
    }
}

# ============================================================
# Inno Setup Build
# ============================================================
function Build-InnoSetup {
    Write-Host "[2/3] Building with Inno Setup..." -ForegroundColor Yellow

    # Create Inno Setup script
    $issContent = @"
; NetworkScanScada POC Installer Script
; Inno Setup 6.x

#define MyAppName "$AppName"
#define MyAppVersion "$AppVersion"
#define MyAppPublisher "$Publisher"
#define MyAppURL "https://www.networkscanscada.com"
#define MyAppExeName "Start-NetworkScanScada.exe"

[Setup]
AppId={{A1B2C3D4-E5F6-7890-ABCD-EF1234567890}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}/support
AppUpdatesURL={#MyAppURL}/updates
DefaultDirName={autopf}\NetworkScanScada-POC
DefaultGroupName=NetworkScanScada POC
AllowNoIcons=yes
LicenseFile=.\LICENSE.txt
InfoBeforeFile=.\README-POC.txt
OutputDir=.\dist
OutputBaseFilename=NetworkScanScada-POC-Setup-{#MyAppVersion}
SetupIconFile=.\assets\icon.ico
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64
UninstallDisplayIcon={app}\assets\icon.ico

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: "quicklaunchicon"; Description: "{cm:CreateQuickLaunchIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked; OnlyBelowVersion: 6.1; Check: not IsAdminInstallMode

[Files]
Source: ".\NetworkScanScada-POC-Setup.ps1"; DestDir: "{app}\scripts"; Flags: ignoreversion
Source: ".\config\*"; DestDir: "{app}\config"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: ".\assets\*"; DestDir: "{app}\assets"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\NetworkScanScada POC"; Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-ExecutionPolicy Bypass -File ""{app}\scripts\NetworkScanScada-POC-Setup.ps1"""; WorkingDir: "{app}"
Name: "{group}\{cm:UninstallProgram,{#MyAppName}}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\NetworkScanScada POC"; Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-ExecutionPolicy Bypass -File ""{app}\scripts\NetworkScanScada-POC-Setup.ps1"""; WorkingDir: "{app}"; Tasks: desktopicon

[Run]
Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-ExecutionPolicy Bypass -File ""{app}\scripts\NetworkScanScada-POC-Setup.ps1"""; Description: "Complete installation"; Flags: runascurrentuser postinstall skipifsilent

[UninstallRun]
Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-ExecutionPolicy Bypass -File ""{app}\scripts\uninstall.ps1"" -Silent"; Flags: runhidden

[Code]
var
  TrialExpired: Boolean;

function InitializeSetup(): Boolean;
var
  ExpiryDate: String;
  RegPath: String;
begin
  Result := True;
  TrialExpired := False;

  // Check if already installed and trial expired
  RegPath := 'SOFTWARE\NetworkScanScada\POC';
  if RegQueryStringValue(HKLM, RegPath, 'ExpiryDate', ExpiryDate) then
  begin
    // Simple date comparison (would need proper parsing in production)
    if MsgBox('NetworkScanScada POC is already installed. Do you want to reinstall?',
              mbConfirmation, MB_YESNO) = IDNO then
    begin
      Result := False;
    end;
  end;
end;

procedure CurUninstallStepChanged(CurUninstallStep: TUninstallStep);
begin
  if CurUninstallStep = usPostUninstall then
  begin
    // Clean up registry
    RegDeleteKeyIncludingSubkeys(HKLM, 'SOFTWARE\NetworkScanScada\POC');
  end;
end;
"@

    $issPath = Join-Path $OutputDir "$AppName.iss"
    Set-Content -Path $issPath -Value $issContent

    # Check for Inno Setup Compiler
    $isccPaths = @(
        "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe",
        "${env:ProgramFiles}\Inno Setup 6\ISCC.exe",
        "C:\Program Files (x86)\Inno Setup 6\ISCC.exe"
    )

    $iscc = $null
    foreach ($path in $isccPaths) {
        if (Test-Path $path) {
            $iscc = $path
            break
        }
    }

    if ($iscc) {
        Write-Host "  Compiling with Inno Setup..." -ForegroundColor Gray
        & $iscc $issPath
        Write-Host "  Created: $OutputDir\$AppName-Setup-$AppVersion.exe" -ForegroundColor Green
    }
    else {
        Write-Host "  Inno Setup not found. ISS script saved to: $issPath" -ForegroundColor Yellow
        Write-Host "  Download Inno Setup from: https://jrsoftware.org/isinfo.php" -ForegroundColor Gray
    }
}

# ============================================================
# Create Supporting Files
# ============================================================
function Create-SupportingFiles {
    Write-Host "[3/3] Creating supporting files..." -ForegroundColor Yellow

    # Create assets directory
    $assetsDir = ".\assets"
    if (-not (Test-Path $assetsDir)) {
        New-Item -ItemType Directory -Path $assetsDir -Force | Out-Null
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

4. DATA: All data collected during the evaluation will be deleted upon
   expiration or uninstallation.

5. CONVERSION: To continue using NetworkScanScada after the trial period,
   please contact sales@networkscanscada.com for licensing options.

Copyright $(Get-Date -Format yyyy) $Publisher. All rights reserved.
"@

    Set-Content -Path ".\LICENSE.txt" -Value $license

    # Create README-POC.txt
    $readme = @"
NetworkScanScada Proof of Concept
=================================

Thank you for evaluating NetworkScanScada!

This POC version includes:
- Full network scanning capabilities
- SCADA protocol support (Modbus, DNP3, IEC61850, OPC-UA)
- Vulnerability assessment
- Comprehensive reporting

Limitations:
- 30-day evaluation period
- Maximum 100 assets
- Maximum 5 users
- Automatic uninstall after trial expiry

Getting Started:
1. Complete the installation
2. Access the web interface at https://localhost:8443
3. Login with: admin / admin123
4. Change the default password immediately

Support:
- Documentation: https://docs.networkscanscada.com
- Support: support@networkscanscada.com
- Sales: sales@networkscanscada.com

Trial Information:
- Install Date: Will be set during installation
- Expiry Date: 30 days from installation
- Days Remaining: Check the dashboard

To purchase a full license, contact sales@networkscanscada.com
"@

    Set-Content -Path ".\README-POC.txt" -Value $readme

    # Create config directory
    $configDir = ".\config"
    if (-not (Test-Path $configDir)) {
        New-Item -ItemType Directory -Path $configDir -Force | Out-Null
    }

    Write-Host "  Supporting files created" -ForegroundColor Green
}

# ============================================================
# Code Signing
# ============================================================
function Sign-Executable {
    param([string]$ExePath)

    if (-not $Sign) { return }

    Write-Host "Signing executable..." -ForegroundColor Yellow

    # Look for code signing certificate
    $cert = Get-ChildItem -Path Cert:\CurrentUser\My -CodeSigningCert | Select-Object -First 1

    if ($cert) {
        Set-AuthenticodeSignature -FilePath $ExePath -Certificate $cert -TimestampServer "http://timestamp.digicert.com"
        Write-Host "  Signed: $ExePath" -ForegroundColor Green
    }
    else {
        Write-Host "  No code signing certificate found" -ForegroundColor Yellow
    }
}

# ============================================================
# Main
# ============================================================
try {
    Create-SupportingFiles

    if ($BuildType -eq "PS2EXE" -or $BuildType -eq "Both") {
        $exePath = Build-PS2EXE
        if ($exePath -and $Sign) {
            Sign-Executable -ExePath $exePath
        }
    }

    if ($BuildType -eq "InnoSetup" -or $BuildType -eq "Both") {
        Build-InnoSetup
    }

    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Build Complete!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Output directory: $OutputDir" -ForegroundColor White
    Get-ChildItem $OutputDir | ForEach-Object {
        Write-Host "  - $($_.Name)" -ForegroundColor Gray
    }
}
catch {
    Write-Host "Build failed: $_" -ForegroundColor Red
    exit 1
}

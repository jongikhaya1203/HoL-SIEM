#Requires -Version 5.1
<#
.SYNOPSIS
    NetworkScanScada Proof of Concept Installer
    30-Day Trial with Automatic Expiration
#>

param(
    [switch]$Silent,
    [switch]$Uninstall,
    [string]$InstallPath = "C:\Program Files\NetworkScanScada-POC",
    [int]$TrialDays = 30
)

$ErrorActionPreference = "Stop"
$AppName = "NetworkScanScada POC"
$AppVersion = "1.0.0"
$RegistryPath = "HKLM:\SOFTWARE\NetworkScanScada\POC"
$TaskName = "NetworkScanScada-POC-ExpiryCheck"
$InstallDate = Get-Date
$ExpiryDate = $InstallDate.AddDays($TrialDays)

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    switch ($Level) {
        "ERROR" { Write-Host "[$timestamp] [ERROR] $Message" -ForegroundColor Red }
        "WARN"  { Write-Host "[$timestamp] [WARN] $Message" -ForegroundColor Yellow }
        "SUCCESS" { Write-Host "[$timestamp] [SUCCESS] $Message" -ForegroundColor Green }
        default { Write-Host "[$timestamp] [INFO] $Message" }
    }
}

function Show-Banner {
    Clear-Host
    Write-Host ""
    Write-Host "  ============================================================" -ForegroundColor Cyan
    Write-Host "       NetworkScanScada - PROOF OF CONCEPT INSTALLER" -ForegroundColor Cyan
    Write-Host "                    30-Day Evaluation" -ForegroundColor Yellow
    Write-Host "  ============================================================" -ForegroundColor Cyan
    Write-Host ""
}

function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Get-MachineFingerprint {
    $computerName = $env:COMPUTERNAME
    $cpuId = (Get-WmiObject -Class Win32_Processor -ErrorAction SilentlyContinue | Select-Object -First 1).ProcessorId
    if (-not $cpuId) { $cpuId = "UNKNOWN" }
    $fingerprint = "$computerName-$cpuId"
    $hash = [System.Security.Cryptography.SHA256]::Create()
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($fingerprint)
    $hashBytes = $hash.ComputeHash($bytes)
    return [Convert]::ToBase64String($hashBytes).Substring(0, 32)
}

function Install-Application {
    Write-Log "Starting installation..."
    Write-Log "Install path: $InstallPath"
    Write-Log "Trial expires: $($ExpiryDate.ToString('yyyy-MM-dd'))"

    # Create directories
    Write-Log "Creating directories..."
    $dirs = @("", "\bin", "\config", "\data", "\logs", "\scripts", "\web")
    foreach ($dir in $dirs) {
        $path = "$InstallPath$dir"
        if (-not (Test-Path $path)) {
            New-Item -ItemType Directory -Path $path -Force | Out-Null
        }
    }

    # Create configuration
    Write-Log "Creating configuration..."
    $config = @{
        Application = @{
            Name = $AppName
            Version = $AppVersion
            Environment = "POC"
        }
        Trial = @{
            InstallDate = $InstallDate.ToString("o")
            ExpiryDate = $ExpiryDate.ToString("o")
            DaysTotal = $TrialDays
            MachineId = Get-MachineFingerprint
        }
        Database = @{
            Type = "SQLite"
            Path = "$InstallPath\data\networkscan.db"
        }
        Network = @{
            Port = 8443
            SSLEnabled = $true
        }
    }
    $config | ConvertTo-Json -Depth 5 | Set-Content "$InstallPath\config\appsettings.json" -Encoding UTF8

    # Create expiry check script
    Write-Log "Creating expiry check script..."
    $expiryScript = @'
$RegistryPath = "HKLM:\SOFTWARE\NetworkScanScada\POC"
if (-not (Test-Path $RegistryPath)) { exit 0 }
$expiryString = Get-ItemPropertyValue -Path $RegistryPath -Name "ExpiryDate" -ErrorAction SilentlyContinue
if (-not $expiryString) { exit 0 }
$expiryDate = [datetime]::Parse($expiryString)
$now = Get-Date
$daysRemaining = ($expiryDate - $now).Days
$InstallPath = Get-ItemPropertyValue -Path $RegistryPath -Name "InstallPath" -ErrorAction SilentlyContinue
if ($now -gt $expiryDate) {
    Stop-Service -Name "NetworkScanScadaPOC" -Force -ErrorAction SilentlyContinue
    $uninstaller = "$InstallPath\scripts\uninstall.ps1"
    if (Test-Path $uninstaller) {
        Start-Process -FilePath "powershell.exe" -ArgumentList "-ExecutionPolicy Bypass -File `"$uninstaller`" -AutoExpiry" -Wait -WindowStyle Hidden
    }
}
elseif ($daysRemaining -le 7 -and $daysRemaining -gt 0) {
    Add-Type -AssemblyName System.Windows.Forms
    [System.Windows.Forms.MessageBox]::Show("Your NetworkScanScada POC trial expires in $daysRemaining days.", "Trial Expiring", "OK", "Warning")
}
'@
    Set-Content -Path "$InstallPath\scripts\check-expiry.ps1" -Value $expiryScript -Encoding UTF8

    # Create uninstall script
    Write-Log "Creating uninstaller..."
    $uninstallScript = @'
param([switch]$AutoExpiry)
$RegistryPath = "HKLM:\SOFTWARE\NetworkScanScada\POC"
$InstallPath = Get-ItemPropertyValue -Path $RegistryPath -Name "InstallPath" -ErrorAction SilentlyContinue
if (-not $InstallPath) { exit 0 }
Unregister-ScheduledTask -TaskName "NetworkScanScada-POC-ExpiryCheck" -Confirm:$false -ErrorAction SilentlyContinue
Stop-Service -Name "NetworkScanScadaPOC" -Force -ErrorAction SilentlyContinue
Remove-Item -Path $InstallPath -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path $RegistryPath -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\NetworkScanScada-POC" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$env:PUBLIC\Desktop\NetworkScanScada POC.lnk" -Force -ErrorAction SilentlyContinue
if ($AutoExpiry) {
    Add-Type -AssemblyName System.Windows.Forms
    [System.Windows.Forms.MessageBox]::Show("Your NetworkScanScada POC trial has expired and was removed.", "Trial Expired", "OK", "Information")
}
'@
    Set-Content -Path "$InstallPath\scripts\uninstall.ps1" -Value $uninstallScript -Encoding UTF8

    # Create launcher
    Write-Log "Creating launcher..."
    $launcher = @"
Write-Host "NetworkScanScada POC" -ForegroundColor Cyan
Write-Host "===================" -ForegroundColor Cyan
`$expiryDate = [datetime]::Parse("$($ExpiryDate.ToString("o"))")
`$daysRemaining = (`$expiryDate - (Get-Date)).Days
if (`$daysRemaining -lt 0) {
    Write-Host "Trial expired!" -ForegroundColor Red
    exit 1
}
Write-Host "Trial days remaining: `$daysRemaining" -ForegroundColor Yellow
Write-Host ""
Write-Host "Starting web server on https://localhost:8443" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop" -ForegroundColor Gray
Write-Host ""
`$webRoot = "$InstallPath\web"
if (Test-Path "C:\xampp\php\php.exe") {
    & "C:\xampp\php\php.exe" -S localhost:8443 -t "`$webRoot"
} else {
    Write-Host "PHP not found. Please access the web files directly at: `$webRoot" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
}
"@
    Set-Content -Path "$InstallPath\bin\Start-NetworkScanScada.ps1" -Value $launcher -Encoding UTF8

    # Create scheduled task
    Write-Log "Creating scheduled task..."
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue
    $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$InstallPath\scripts\check-expiry.ps1`""
    $trigger = New-ScheduledTaskTrigger -Daily -At "9:00AM"
    $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description "NetworkScanScada POC Trial Check" | Out-Null

    # Create registry entries
    Write-Log "Creating registry entries..."
    if (-not (Test-Path $RegistryPath)) {
        New-Item -Path $RegistryPath -Force | Out-Null
    }
    Set-ItemProperty -Path $RegistryPath -Name "InstallPath" -Value $InstallPath
    Set-ItemProperty -Path $RegistryPath -Name "Version" -Value $AppVersion
    Set-ItemProperty -Path $RegistryPath -Name "InstallDate" -Value $InstallDate.ToString("o")
    Set-ItemProperty -Path $RegistryPath -Name "ExpiryDate" -Value $ExpiryDate.ToString("o")
    Set-ItemProperty -Path $RegistryPath -Name "MachineId" -Value (Get-MachineFingerprint)

    # Add to Programs and Features
    $uninstallPath = "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\NetworkScanScada-POC"
    if (-not (Test-Path $uninstallPath)) {
        New-Item -Path $uninstallPath -Force | Out-Null
    }
    Set-ItemProperty -Path $uninstallPath -Name "DisplayName" -Value "$AppName (30-Day Trial)"
    Set-ItemProperty -Path $uninstallPath -Name "DisplayVersion" -Value $AppVersion
    Set-ItemProperty -Path $uninstallPath -Name "Publisher" -Value "HoL SIEM Security"
    Set-ItemProperty -Path $uninstallPath -Name "InstallLocation" -Value $InstallPath
    Set-ItemProperty -Path $uninstallPath -Name "UninstallString" -Value "powershell.exe -ExecutionPolicy Bypass -File `"$InstallPath\scripts\uninstall.ps1`""

    # Create desktop shortcut
    Write-Log "Creating shortcuts..."
    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut("$env:PUBLIC\Desktop\NetworkScanScada POC.lnk")
    $shortcut.TargetPath = "powershell.exe"
    $shortcut.Arguments = "-ExecutionPolicy Bypass -NoExit -File `"$InstallPath\bin\Start-NetworkScanScada.ps1`""
    $shortcut.WorkingDirectory = $InstallPath
    $shortcut.Description = "NetworkScanScada POC"
    $shortcut.Save()

    # Copy web files if available
    $sourceWeb = Join-Path (Split-Path -Parent $PSScriptRoot) ""
    if (Test-Path "$sourceWeb\index.php") {
        Write-Log "Copying web files..."
        Copy-Item -Path "$sourceWeb\*" -Destination "$InstallPath\web" -Recurse -Force -ErrorAction SilentlyContinue
    }

    Write-Log "Installation complete!" "SUCCESS"
}

# Main
if (-not (Test-Administrator)) {
    Write-Host "ERROR: This installer requires Administrator privileges." -ForegroundColor Red
    Write-Host "Please right-click and select 'Run as Administrator'" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Show-Banner

if ($Uninstall) {
    $uninstaller = "$InstallPath\scripts\uninstall.ps1"
    if (Test-Path $uninstaller) {
        & powershell.exe -ExecutionPolicy Bypass -File $uninstaller
    } else {
        Write-Log "Uninstaller not found" "ERROR"
    }
    exit 0
}

try {
    Install-Application

    Write-Host ""
    Write-Host "  ============================================================" -ForegroundColor Green
    Write-Host "              INSTALLATION COMPLETE!" -ForegroundColor Green
    Write-Host "  ============================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Trial Period: 30 days (expires $($ExpiryDate.ToString('yyyy-MM-dd')))" -ForegroundColor White
    Write-Host "  Install Path: $InstallPath" -ForegroundColor White
    Write-Host ""
    Write-Host "  Access: https://localhost:8443" -ForegroundColor Cyan
    Write-Host "  Login:  admin / admin123" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  A desktop shortcut has been created." -ForegroundColor Gray
    Write-Host ""
    Write-Host "  NOTE: This software will auto-uninstall after trial expiry." -ForegroundColor Yellow
    Write-Host ""

    if (-not $Silent) {
        Read-Host "Press Enter to exit"
    }
}
catch {
    Write-Log "Installation failed: $_" "ERROR"
    if (-not $Silent) {
        Read-Host "Press Enter to exit"
    }
    exit 1
}

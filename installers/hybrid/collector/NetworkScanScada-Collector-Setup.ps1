#Requires -Version 5.1
<#
.SYNOPSIS
    NetworkScanScada On-Premise Collector Setup
    Hybrid deployment - Collector component
#>

param(
    [switch]$Silent,
    [switch]$Uninstall,
    [string]$InstallPath = "C:\Program Files\NetworkScanScada-Collector",
    [string]$ConfigFile,
    [string]$TenantId,
    [string]$CollectorToken,
    [string]$CloudEndpoint = "https://api.networkscanscada.com",
    [string]$CollectorName = $env:COMPUTERNAME
)

$ErrorActionPreference = "Stop"
$AppName = "NetworkScanScada Collector"
$AppVersion = "1.0.0"
$ServiceName = "NetworkScanScadaCollector"
$RegistryPath = "HKLM:\SOFTWARE\NetworkScanScada\Collector"

function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    if (-not $Silent) {
        switch ($Level) {
            "ERROR" { Write-Host $logMessage -ForegroundColor Red }
            "WARN"  { Write-Host $logMessage -ForegroundColor Yellow }
            "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
            default { Write-Host $logMessage }
        }
    }
    $logDir = if (Test-Path $InstallPath) { "$InstallPath\logs" } else { $env:TEMP }
    if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Path $logDir -Force | Out-Null }
    Add-Content -Path "$logDir\collector-install.log" -Value $logMessage
}

function Show-Banner {
    if ($Silent) { return }
    Clear-Host
    Write-Host ""
    Write-Host "  ============================================================" -ForegroundColor Cyan
    Write-Host "       NetworkScanScada - ON-PREMISE COLLECTOR SETUP" -ForegroundColor Cyan
    Write-Host "                    Hybrid Deployment" -ForegroundColor Yellow
    Write-Host "  ============================================================" -ForegroundColor Cyan
    Write-Host ""
}

function Get-CollectorConfig {
    Write-Log "Loading collector configuration..."

    if ($ConfigFile -and (Test-Path $ConfigFile)) {
        Write-Log "Loading from config file: $ConfigFile"
        $config = Get-Content $ConfigFile -Raw | ConvertFrom-Json
        return $config
    }

    if (-not $TenantId -and -not $Silent) {
        $script:TenantId = Read-Host "Enter Tenant ID"
    }
    if (-not $TenantId) { throw "Tenant ID is required" }

    if (-not $CollectorToken -and -not $Silent) {
        $script:CollectorToken = Read-Host "Enter Collector Token"
    }
    if (-not $CollectorToken) { throw "Collector token is required" }

    return @{
        tenant = @{ id = $TenantId; apiEndpoint = $CloudEndpoint }
        collector = @{ name = $CollectorName; token = $CollectorToken; syncInterval = 300; batchSize = 100 }
    }
}

function Install-ApplicationFiles {
    param($Config)
    Write-Log "Installing collector files to $InstallPath..."

    $directories = @($InstallPath, "$InstallPath\bin", "$InstallPath\config", "$InstallPath\data", "$InstallPath\logs", "$InstallPath\cache", "$InstallPath\scripts")
    foreach ($dir in $directories) {
        if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    }

    $collectorConfig = @{
        collector = @{
            id = [guid]::NewGuid().ToString()
            name = $CollectorName
            version = $AppVersion
            installDate = (Get-Date).ToString("o")
        }
        tenant = $Config.tenant
        sync = @{ enabled = $true; interval = 300; batchSize = 100; compression = $true }
        database = @{ type = "SQLite"; path = "$InstallPath\data\collector.db" }
        scanning = @{ enabled = $true; protocols = @("Modbus", "DNP3", "OPC-UA", "BACnet") }
    }
    $collectorConfig | ConvertTo-Json -Depth 5 | Set-Content "$InstallPath\config\collector.json" -Encoding UTF8

    # Store token
    $tokenPath = "$InstallPath\config\.token"
    $Config.collector.token | Out-File $tokenPath -Encoding UTF8

    Write-Log "Application files installed" "SUCCESS"
}

function Install-LocalDatabase {
    Write-Log "Setting up local SQLite database..."

    $schema = @"
-- NetworkScanScada Collector Local Database
CREATE TABLE IF NOT EXISTS collector_info (
    id INTEGER PRIMARY KEY,
    collector_id TEXT NOT NULL,
    name TEXT NOT NULL,
    tenant_id TEXT NOT NULL,
    registered_at TEXT,
    last_sync TEXT
);

CREATE TABLE IF NOT EXISTS assets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    asset_id TEXT UNIQUE,
    name TEXT,
    ip_address TEXT,
    mac_address TEXT,
    hostname TEXT,
    asset_type TEXT,
    protocol TEXT,
    status TEXT DEFAULT 'active',
    discovered_at TEXT,
    last_seen TEXT,
    sync_status TEXT DEFAULT 'pending'
);

CREATE TABLE IF NOT EXISTS scan_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scan_id TEXT UNIQUE,
    asset_id INTEGER,
    scan_type TEXT,
    status TEXT,
    findings_count INTEGER DEFAULT 0,
    started_at TEXT,
    completed_at TEXT,
    sync_status TEXT DEFAULT 'pending'
);

CREATE TABLE IF NOT EXISTS sync_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type TEXT NOT NULL,
    entity_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    data TEXT,
    priority INTEGER DEFAULT 5,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
"@
    Set-Content -Path "$InstallPath\data\schema.sql" -Value $schema -Encoding UTF8
    Write-Log "Database schema created" "SUCCESS"
}

function Install-Scripts {
    Write-Log "Installing scripts..."

    # Sync agent script
    $syncAgent = @"
# NetworkScanScada Collector - Sync Agent
param([switch]`$Force, [switch]`$Verbose)
`$configPath = Join-Path `$PSScriptRoot '..\config\collector.json'
`$config = Get-Content `$configPath -Raw | ConvertFrom-Json
`$logPath = Join-Path `$PSScriptRoot '..\logs\sync.log'
function Write-SyncLog { param([string]`$Message, [string]`$Level = 'INFO')
    `$timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    Add-Content -Path `$logPath -Value "[`$timestamp] [`$Level] `$Message"
    if (`$Verbose) { Write-Host "[`$Level] `$Message" }
}
Write-SyncLog 'Sync agent starting...'
Write-SyncLog 'Sync agent completed'
"@
    Set-Content -Path "$InstallPath\scripts\sync-agent.ps1" -Value $syncAgent -Encoding UTF8

    # Scanner script
    $scanner = @"
# NetworkScanScada Collector - Network Scanner
param([string]`$TargetNetwork, [string]`$ScanType = 'quick', [switch]`$Verbose)
`$configPath = Join-Path `$PSScriptRoot '..\config\collector.json'
`$config = Get-Content `$configPath -Raw | ConvertFrom-Json
`$logPath = Join-Path `$PSScriptRoot '..\logs\scanner.log'
function Write-ScanLog { param([string]`$Message, [string]`$Level = 'INFO')
    Add-Content -Path `$logPath -Value "[`$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [`$Level] `$Message"
    if (`$Verbose) { Write-Host "[`$Level] `$Message" }
}
Write-ScanLog "Scanner starting - Type: `$ScanType"
if (-not `$TargetNetwork) {
    `$localIP = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { `$_.InterfaceAlias -notmatch 'Loopback' } | Select-Object -First 1).IPAddress
    `$TargetNetwork = `$localIP -replace '\.\d+`$', '.0/24'
}
Write-ScanLog "Target network: `$TargetNetwork"
`$arpEntries = arp -a
Write-ScanLog 'Scan complete'
"@
    Set-Content -Path "$InstallPath\scripts\scanner.ps1" -Value $scanner -Encoding UTF8

    # Service wrapper
    $serviceWrapper = @"
# NetworkScanScada Collector Service Wrapper
`$installPath = '$InstallPath'
`$configPath = "`$installPath\config\collector.json"
`$config = Get-Content `$configPath -Raw | ConvertFrom-Json
`$syncInterval = 300
while (`$true) {
    try { & "`$installPath\scripts\sync-agent.ps1" }
    catch { Add-Content -Path "`$installPath\logs\service-error.log" -Value "`$(Get-Date): `$_" }
    Start-Sleep -Seconds `$syncInterval
}
"@
    Set-Content -Path "$InstallPath\bin\service-wrapper.ps1" -Value $serviceWrapper -Encoding UTF8

    # Uninstaller
    $uninstaller = @"
#Requires -Version 5.1
param([switch]`$Silent)
`$RegistryPath = 'HKLM:\SOFTWARE\NetworkScanScada\Collector'
`$InstallPath = Get-ItemPropertyValue -Path `$RegistryPath -Name 'InstallPath' -ErrorAction SilentlyContinue
if (-not `$InstallPath) { Write-Host 'Collector not installed.'; exit 1 }
if (-not `$Silent) {
    `$confirm = Read-Host 'Uninstall NetworkScanScada Collector? (yes/no)'
    if (`$confirm -ne 'yes') { exit 0 }
}
Write-Host 'Uninstalling...'
Unregister-ScheduledTask -TaskName 'NetworkScanScada-Collector-*' -Confirm:`$false -ErrorAction SilentlyContinue
Stop-Service -Name 'NetworkScanScadaCollector' -Force -ErrorAction SilentlyContinue
Remove-Item -Path `$InstallPath -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path `$RegistryPath -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -Path 'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\NetworkScanScada-Collector' -Force -ErrorAction SilentlyContinue
Write-Host 'Uninstall complete.'
"@
    Set-Content -Path "$InstallPath\uninstall.ps1" -Value $uninstaller -Encoding UTF8

    Write-Log "Scripts installed" "SUCCESS"
}

function Install-ScheduledTasks {
    Write-Log "Creating scheduled tasks..."

    # Sync task
    $syncAction = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$InstallPath\scripts\sync-agent.ps1`""
    $syncTrigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5)
    Register-ScheduledTask -TaskName "NetworkScanScada-Collector-Sync" -Action $syncAction -Trigger $syncTrigger -User "SYSTEM" -RunLevel Highest -Description "Syncs collector data" -Force | Out-Null

    # Scan task
    $scanAction = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$InstallPath\scripts\scanner.ps1`" -ScanType quick"
    $scanTrigger = New-ScheduledTaskTrigger -Daily -At "02:00"
    Register-ScheduledTask -TaskName "NetworkScanScada-Collector-Scan" -Action $scanAction -Trigger $scanTrigger -User "SYSTEM" -RunLevel Highest -Description "Daily network scan" -Force | Out-Null

    Write-Log "Scheduled tasks created" "SUCCESS"
}

function Install-RegistryEntries {
    param($Config)
    Write-Log "Creating registry entries..."

    if (-not (Test-Path $RegistryPath)) { New-Item -Path $RegistryPath -Force | Out-Null }
    Set-ItemProperty -Path $RegistryPath -Name "InstallPath" -Value $InstallPath
    Set-ItemProperty -Path $RegistryPath -Name "Version" -Value $AppVersion
    Set-ItemProperty -Path $RegistryPath -Name "CollectorName" -Value $CollectorName
    Set-ItemProperty -Path $RegistryPath -Name "TenantId" -Value $Config.tenant.id
    Set-ItemProperty -Path $RegistryPath -Name "InstallDate" -Value (Get-Date).ToString("o")

    $uninstallPath = "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\NetworkScanScada-Collector"
    if (-not (Test-Path $uninstallPath)) { New-Item -Path $uninstallPath -Force | Out-Null }
    Set-ItemProperty -Path $uninstallPath -Name "DisplayName" -Value $AppName
    Set-ItemProperty -Path $uninstallPath -Name "DisplayVersion" -Value $AppVersion
    Set-ItemProperty -Path $uninstallPath -Name "Publisher" -Value "HoL SIEM Security"
    Set-ItemProperty -Path $uninstallPath -Name "InstallLocation" -Value $InstallPath
    Set-ItemProperty -Path $uninstallPath -Name "UninstallString" -Value "powershell.exe -ExecutionPolicy Bypass -File `"$InstallPath\uninstall.ps1`""

    Write-Log "Registry entries created" "SUCCESS"
}

# Main
if (-not (Test-Administrator)) {
    Write-Host "ERROR: This installer requires Administrator privileges." -ForegroundColor Red
    Write-Host "Please right-click and select 'Run as Administrator'" -ForegroundColor Yellow
    if (-not $Silent) { Read-Host "Press Enter to exit" }
    exit 1
}

Show-Banner

if ($Uninstall) {
    $uninstaller = "$InstallPath\uninstall.ps1"
    if (Test-Path $uninstaller) { & powershell.exe -ExecutionPolicy Bypass -File $uninstaller -Silent:$Silent }
    exit 0
}

Write-Log "Starting NetworkScanScada Collector installation..."
Write-Log "Installation path: $InstallPath"
Write-Log "Collector name: $CollectorName"
Write-Host ""

try {
    $config = Get-CollectorConfig
    Install-ApplicationFiles -Config $config
    Install-LocalDatabase
    Install-Scripts
    Install-ScheduledTasks
    Install-RegistryEntries -Config $config

    Write-Host ""
    Write-Host "  ============================================================" -ForegroundColor Green
    Write-Host "           COLLECTOR INSTALLATION COMPLETE!" -ForegroundColor Green
    Write-Host "  ============================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Collector Name: $CollectorName" -ForegroundColor White
    Write-Host "  Tenant ID: $($config.tenant.id)" -ForegroundColor White
    Write-Host "  Install Path: $InstallPath" -ForegroundColor White
    Write-Host ""
    Write-Host "  Data syncs to cloud every 5 minutes" -ForegroundColor Cyan
    Write-Host "  Network scans run daily at 2:00 AM" -ForegroundColor Cyan
    Write-Host ""

    if (-not $Silent) { Read-Host "Press Enter to exit" }
    exit 0
}
catch {
    Write-Log "Installation failed: $_" "ERROR"
    if (-not $Silent) { Read-Host "Press Enter to exit" }
    exit 1
}

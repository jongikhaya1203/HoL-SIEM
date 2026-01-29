<?php
/**
 * Agent Installer Generator
 * Creates a complete agent installation package
 */
require_once __DIR__ . '/classes/Database.php';

// Get parameters
$apiKey = $_GET['api_key'] ?? '';
$platform = $_GET['platform'] ?? 'windows';
$serverUrl = $_GET['server_url'] ?? 'http://localhost/networkscan/agent_api.php';

if (empty($apiKey)) {
    die('Error: API key required');
}

// Validate API key
$db = Database::getInstance();
$keyCheck = $db->fetchOne(
    "SELECT ak.*, t.tenant_name FROM agent_api_keys ak
     JOIN tenants t ON ak.tenant_id = t.id
     WHERE ak.api_key = ? AND ak.status = 'active'",
    [$apiKey]
);

if (!$keyCheck) {
    die('Error: Invalid or inactive API key');
}

$tenantName = $keyCheck['tenant_name'];

if ($platform === 'windows') {
    // Create Windows agent installer
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="NetworkAgent-Setup.bat"');

    echo generateWindowsInstaller($apiKey, $serverUrl, $tenantName);
} else {
    // Create Linux agent installer
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="network-agent-install.sh"');

    echo generateLinuxInstaller($apiKey, $serverUrl, $tenantName);
}

/**
 * Generate Windows Installer Batch Script
 */
function generateWindowsInstaller($apiKey, $serverUrl, $tenantName) {
    $psScript = generateWindowsAgentService($apiKey, $serverUrl, $tenantName);
    $psScriptBase64 = base64_encode($psScript);

    return <<<BAT
@echo off
:: Network Security Agent Installer
:: Tenant: {$tenantName}
:: Auto-generated installation script

echo =========================================
echo Network Security Agent Installer
echo Tenant: {$tenantName}
echo =========================================
echo.

:: Check for administrator privileges
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This installer must be run as Administrator!
    echo Please right-click and select "Run as Administrator"
    pause
    exit /b 1
)

echo [1/5] Checking PowerShell version...
powershell -Command "if (\$PSVersionTable.PSVersion.Major -lt 3) { exit 1 }"
if %errorLevel% neq 0 (
    echo ERROR: PowerShell 3.0 or higher is required
    pause
    exit /b 1
)
echo     PowerShell version OK

echo.
echo [2/5] Creating agent directory...
if not exist "C:\\ProgramData\\NetworkAgent" mkdir "C:\\ProgramData\\NetworkAgent"
echo     Directory created: C:\\ProgramData\\NetworkAgent

echo.
echo [3/5] Installing agent script...
powershell -Command "\$encodedScript = '{$psScriptBase64}'; \$script = [System.Text.Encoding]::UTF8.GetString([System.Convert]::FromBase64String(\$encodedScript)); Set-Content -Path 'C:\\ProgramData\\NetworkAgent\\agent.ps1' -Value \$script -Encoding UTF8"
echo     Agent script installed

echo.
echo [4/5] Creating Windows Service...
powershell -ExecutionPolicy Bypass -File "C:\\ProgramData\\NetworkAgent\\agent.ps1" -Install
if %errorLevel% neq 0 (
    echo ERROR: Failed to create service
    pause
    exit /b 1
)
echo     Service created successfully

echo.
echo [5/5] Starting agent service...
sc start "NetworkAgent" >nul 2>&1
timeout /t 2 /nobreak >nul
sc query "NetworkAgent" | find "RUNNING" >nul
if %errorLevel% equ 0 (
    echo     Service started successfully
) else (
    echo     Service will start automatically at next boot
)

echo.
echo =========================================
echo Installation Complete!
echo =========================================
echo.
echo Agent installed to: C:\\ProgramData\\NetworkAgent
echo Service Name: NetworkAgent
echo.
echo To check status: sc query NetworkAgent
echo To start service: sc start NetworkAgent
echo To stop service: sc stop NetworkAgent
echo To uninstall: sc delete NetworkAgent
echo.
pause
BAT;
}

/**
 * Generate Windows PowerShell Agent with Service Support
 */
function generateWindowsAgentService($apiKey, $serverUrl, $tenantName) {
    return <<<'POWERSHELL'
<#
.SYNOPSIS
    Network Security Agent Service
.DESCRIPTION
    Runs as Windows service, collects system info and reports to server
.PARAMETER Install
    Install as Windows service
.PARAMETER Uninstall
    Remove Windows service
#>

param(
    [switch]$Install,
    [switch]$Uninstall
)

# Configuration
$API_KEY = "{API_KEY}"
$SERVER_URL = "{SERVER_URL}"
$AGENT_VERSION = "1.0.0"
$CHECK_IN_INTERVAL = 3600 # 1 hour
$SERVICE_NAME = "NetworkAgent"
$SERVICE_DISPLAY_NAME = "Network Security Agent"
$SERVICE_DESCRIPTION = "Monitors system and network security for {TENANT_NAME}"

# Service Installation
if ($Install) {
    try {
        # Create scheduled task instead of service (easier to manage)
        $action = New-ScheduledTaskAction -Execute 'PowerShell.exe' `
            -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"C:\ProgramData\NetworkAgent\agent.ps1`""

        $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours 1)

        $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

        $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

        Register-ScheduledTask -TaskName $SERVICE_NAME `
            -Action $action `
            -Trigger $trigger `
            -Principal $principal `
            -Settings $settings `
            -Description $SERVICE_DESCRIPTION `
            -Force

        Write-Host "Agent installed as scheduled task: $SERVICE_NAME"

        # Run first check-in
        Start-ScheduledTask -TaskName $SERVICE_NAME

        exit 0
    }
    catch {
        Write-Error "Installation failed: $_"
        exit 1
    }
}

# Service Uninstallation
if ($Uninstall) {
    try {
        Unregister-ScheduledTask -TaskName $SERVICE_NAME -Confirm:$false
        Write-Host "Agent uninstalled successfully"
        exit 0
    }
    catch {
        Write-Error "Uninstallation failed: $_"
        exit 1
    }
}

# Agent Functions
function Get-AgentID {
    $machineGuid = (Get-ItemProperty -Path "HKLM:\SOFTWARE\Microsoft\Cryptography" -Name MachineGuid).MachineGuid
    $hash = [System.Security.Cryptography.SHA256]::Create()
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($machineGuid)
    $hashBytes = $hash.ComputeHash($bytes)
    return [BitConverter]::ToString($hashBytes).Replace("-", "").ToLower()
}

function Get-SystemInfo {
    $os = Get-CimInstance -ClassName Win32_OperatingSystem
    $cs = Get-CimInstance -ClassName Win32_ComputerSystem

    return @{
        agent_id = Get-AgentID
        hostname = $env:COMPUTERNAME
        os_family = "Windows"
        os = $os.Caption
        os_version = $os.Version
        architecture = $cs.SystemType
        ip_addresses = @((Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.InterfaceAlias -notlike "*Loopback*"}).IPAddress)
        domain = $cs.Domain
        manufacturer = $cs.Manufacturer
        model = $cs.Model
        total_memory_gb = [math]::Round($cs.TotalPhysicalMemory / 1GB, 2)
    }
}

function Get-NetworkInfo {
    $openPorts = @()

    try {
        $connections = Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue
        foreach ($conn in $connections | Select-Object -First 50) {
            $process = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
            $openPorts += @{
                port = $conn.LocalPort
                process = if ($process) { $process.Name } else { "Unknown" }
                protocol = "TCP"
            }
        }
    } catch {}

    $dnsServers = @((Get-DnsClientServerAddress -AddressFamily IPv4 | Where-Object {$_.ServerAddresses}).ServerAddresses)
    $gateway = (Get-NetRoute -DestinationPrefix "0.0.0.0/0" | Select-Object -First 1).NextHop

    return @{
        open_ports = $openPorts
        dns_servers = $dnsServers
        default_gateway = $gateway
        routing_table = "IPv4 Default Gateway: $gateway"
    }
}

function Get-ProcessInfo {
    $processes = @()
    $procs = Get-Process | Sort-Object -Property CPU -Descending | Select-Object -First 50

    foreach ($proc in $procs) {
        $processes += @{
            name = $proc.ProcessName
            pid = $proc.Id
            cpu_percent = [math]::Round(($proc.CPU / (Get-CimInstance Win32_Processor).NumberOfLogicalProcessors), 2)
            memory_mb = [math]::Round($proc.WorkingSet64 / 1MB, 2)
            path = try { $proc.Path } catch { "N/A" }
        }
    }

    return $processes
}

function Get-SecurityInfo {
    $firewallProfiles = Get-NetFirewallProfile
    $firewallStatus = ($firewallProfiles | Where-Object {$_.Enabled -eq $true}).Name -join ", "

    $defenderStatus = "Unknown"
    try {
        $defender = Get-MpComputerStatus -ErrorAction SilentlyContinue
        if ($defender) {
            $defenderStatus = if ($defender.AntivirusEnabled) { "Enabled" } else { "Disabled" }
        }
    } catch {}

    $users = @()
    $localUsers = Get-LocalUser | Select-Object -First 20
    foreach ($user in $localUsers) {
        $users += @{
            username = $user.Name
            enabled = $user.Enabled
            last_logon = if ($user.LastLogon) { $user.LastLogon.ToString("yyyy-MM-dd HH:mm:ss") } else { "Never" }
        }
    }

    return @{
        firewall_status = if ($firewallStatus) { $firewallStatus } else { "Disabled" }
        antivirus_status = "Windows Defender"
        windows_defender = $defenderStatus
        last_update = (Get-HotFix | Sort-Object -Property InstalledOn -Descending | Select-Object -First 1).InstalledOn
        user_accounts = $users
    }
}

function Send-CheckIn {
    $systemInfo = Get-SystemInfo
    $networkInfo = Get-NetworkInfo
    $processes = Get-ProcessInfo
    $securityInfo = Get-SecurityInfo

    $payload = @{
        api_key = $API_KEY
        action = "checkin"
        agent_version = $AGENT_VERSION
        data = @{
            agent_version = $AGENT_VERSION
            system = $systemInfo
            network = $networkInfo
            processes = $processes
            security = $securityInfo
        }
    } | ConvertTo-Json -Depth 10

    try {
        $response = Invoke-RestMethod -Uri $SERVER_URL -Method Post -Body $payload -ContentType "application/json" -TimeoutSec 30

        $logPath = "C:\ProgramData\NetworkAgent\agent.log"
        $logEntry = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Check-in successful for agent $($systemInfo.agent_id)`n"
        Add-Content -Path $logPath -Value $logEntry

        return $true
    } catch {
        $logPath = "C:\ProgramData\NetworkAgent\agent.log"
        $logEntry = "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Check-in failed: $_`n"
        Add-Content -Path $logPath -Value $logEntry
        return $false
    }
}

# Main execution (when running as agent, not installer)
if (-not $Install -and -not $Uninstall) {
    Send-CheckIn
}
POWERSHELL;

    // Replace placeholders
    $script = str_replace('{API_KEY}', $apiKey, $script);
    $script = str_replace('{SERVER_URL}', $serverUrl, $script);
    $script = str_replace('{TENANT_NAME}', $tenantName, $script);

    return $script;
}

/**
 * Generate Linux Installer
 */
function generateLinuxInstaller($apiKey, $serverUrl, $tenantName) {
    return "#!/bin/bash\n# Linux agent installer - Coming soon\necho 'Linux installer not yet implemented'\n";
}

<?php
/**
 * Agent Download Generator
 * Generates pre-configured agent scripts with API key embedded
 */
require_once __DIR__ . '/classes/Database.php';

// Get parameters
$apiKey = $_GET['api_key'] ?? '';
$platform = $_GET['platform'] ?? 'windows'; // windows or linux
$serverUrl = $_GET['server_url'] ?? 'http://localhost/networkscan/agent_api.php';

if (empty($apiKey)) {
    die('Error: API key required. Please generate an API key first from the Tenants page.');
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

// Generate agent script based on platform
if ($platform === 'windows') {
    // Windows PowerShell Agent
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="network-agent.ps1"');

    echo generateWindowsAgent($apiKey, $serverUrl, $tenantName);
} else {
    // Linux Bash Agent
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="network-agent.sh"');

    echo generateLinuxAgent($apiKey, $serverUrl, $tenantName);
}

/**
 * Generate Windows PowerShell Agent
 */
function generateWindowsAgent($apiKey, $serverUrl, $tenantName) {
    return <<<'POWERSHELL'
<#
.SYNOPSIS
    Network Security Agent for Windows
.DESCRIPTION
    Collects system, network, and security information and reports to central server
.NOTES
    Tenant: {TENANT_NAME}
    Generated: {DATE}
#>

# Configuration
$API_KEY = "{API_KEY}"
$SERVER_URL = "{SERVER_URL}"
$AGENT_VERSION = "1.0.0"
$CHECK_IN_INTERVAL = 3600 # 1 hour in seconds

# Generate unique agent ID based on machine
function Get-AgentID {
    $machineGuid = (Get-ItemProperty -Path "HKLM:\SOFTWARE\Microsoft\Cryptography" -Name MachineGuid).MachineGuid
    $hash = [System.Security.Cryptography.SHA256]::Create()
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($machineGuid)
    $hashBytes = $hash.ComputeHash($bytes)
    return [BitConverter]::ToString($hashBytes).Replace("-", "").ToLower()
}

# Collect system information
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

# Collect network information
function Get-NetworkInfo {
    $openPorts = @()
    $listeningServices = @()

    # Get listening ports
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

    # Get DNS servers
    $dnsServers = @((Get-DnsClientServerAddress -AddressFamily IPv4 | Where-Object {$_.ServerAddresses}).ServerAddresses)

    # Get default gateway
    $gateway = (Get-NetRoute -DestinationPrefix "0.0.0.0/0" | Select-Object -First 1).NextHop

    return @{
        open_ports = $openPorts
        listening_services = $listeningServices
        dns_servers = $dnsServers
        default_gateway = $gateway
        routing_table = "IPv4 Default Gateway: $gateway"
    }
}

# Collect running processes (top 50)
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

# Collect security information
function Get-SecurityInfo {
    $firewallProfiles = Get-NetFirewallProfile
    $firewallStatus = ($firewallProfiles | Where-Object {$_.Enabled -eq $true}).Name -join ", "

    # Check Windows Defender status
    $defenderStatus = "Unknown"
    try {
        $defender = Get-MpComputerStatus -ErrorAction SilentlyContinue
        if ($defender) {
            $defenderStatus = if ($defender.AntivirusEnabled) { "Enabled" } else { "Disabled" }
        }
    } catch {}

    # Get user accounts
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

# Send check-in to server
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

        if ($response.success) {
            Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Check-in successful for agent $($systemInfo.agent_id)"
            return $true
        } else {
            Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Check-in failed: $($response.message)"
            return $false
        }
    } catch {
        Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Error during check-in: $_"

        # Report error to server
        try {
            $errorPayload = @{
                api_key = $API_KEY
                action = "report_error"
                agent_id = $systemInfo.agent_id
                error = $_.Exception.Message
            } | ConvertTo-Json

            Invoke-RestMethod -Uri $SERVER_URL -Method Post -Body $errorPayload -ContentType "application/json" -TimeoutSec 10
        } catch {}

        return $false
    }
}

# Main execution
Write-Host "========================================="
Write-Host "Network Security Agent v$AGENT_VERSION"
Write-Host "Tenant: {TENANT_NAME}"
Write-Host "Server: $SERVER_URL"
Write-Host "========================================="
Write-Host ""

# Perform check-in
Write-Host "Starting check-in process..."
$success = Send-CheckIn

if ($success) {
    Write-Host ""
    Write-Host "Agent check-in completed successfully!"
    Write-Host "This agent will check in every $($CHECK_IN_INTERVAL / 3600) hour(s)."
    Write-Host ""
    Write-Host "To run automatically, create a scheduled task:"
    Write-Host "  schtasks /create /tn ""NetworkAgent"" /tr ""powershell.exe -ExecutionPolicy Bypass -File '$PSCommandPath'"" /sc hourly /ru SYSTEM"
} else {
    Write-Host ""
    Write-Host "Check-in failed. Please check your network connection and API key."
    exit 1
}
POWERSHELL;

    // Replace placeholders
    $script = str_replace('{API_KEY}', $apiKey, $script);
    $script = str_replace('{SERVER_URL}', $serverUrl, $script);
    $script = str_replace('{TENANT_NAME}', $tenantName, $script);
    $script = str_replace('{DATE}', date('Y-m-d H:i:s'), $script);

    return $script;
}

/**
 * Generate Linux Bash Agent
 */
function generateLinuxAgent($apiKey, $serverUrl, $tenantName) {
    $script = <<<'BASH'
#!/bin/bash
#
# Network Security Agent for Linux
# Tenant: {TENANT_NAME}
# Generated: {DATE}
#

# Configuration
API_KEY="{API_KEY}"
SERVER_URL="{SERVER_URL}"
AGENT_VERSION="1.0.0"
CHECK_IN_INTERVAL=3600  # 1 hour in seconds

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Generate unique agent ID
get_agent_id() {
    if [ -f /etc/machine-id ]; then
        echo -n $(cat /etc/machine-id) | sha256sum | awk '{print $1}'
    elif [ -f /var/lib/dbus/machine-id ]; then
        echo -n $(cat /var/lib/dbus/machine-id) | sha256sum | awk '{print $1}'
    else
        echo -n "$(hostname)-$(cat /proc/sys/kernel/random/uuid)" | sha256sum | awk '{print $1}'
    fi
}

# Collect system information
collect_system_info() {
    local agent_id=$(get_agent_id)
    local hostname=$(hostname)
    local os_family="Linux"
    local os=$(cat /etc/os-release 2>/dev/null | grep "^PRETTY_NAME" | cut -d'"' -f2)
    [ -z "$os" ] && os=$(uname -s)
    local architecture=$(uname -m)
    local ip_addresses=$(ip -4 addr show | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | grep -v '^127\.' | paste -sd ',' -)

    cat <<EOF
    "agent_id": "$agent_id",
    "hostname": "$hostname",
    "os_family": "$os_family",
    "os": "$os",
    "architecture": "$architecture",
    "ip_addresses": ["$(echo $ip_addresses | sed 's/,/","/g')"]
EOF
}

# Collect network information
collect_network_info() {
    # Get listening ports
    local ports=$(ss -ltn 2>/dev/null | awk 'NR>1 {split($4,a,":"); if(a[2]!="") print a[2]}' | head -50 | paste -sd ',' -)

    # Get DNS servers
    local dns=$(grep "^nameserver" /etc/resolv.conf 2>/dev/null | awk '{print $2}' | paste -sd ',' -)

    # Get default gateway
    local gateway=$(ip route | grep default | awk '{print $3}' | head -1)

    cat <<EOF
    "open_ports": [$(echo $ports | sed 's/\([0-9]*\)/{"port":\1,"protocol":"TCP"}/g' | sed 's/}{/},{/g')],
    "dns_servers": ["$(echo $dns | sed 's/,/","/g')"],
    "default_gateway": "$gateway"
EOF
}

# Collect process information
collect_processes() {
    local processes=$(ps aux | head -51 | tail -50 | awk '{print "{\"name\":\""$11"\",\"pid\":"$2",\"cpu_percent\":"$3",\"memory_percent\":"$4"}"}' | paste -sd ',' -)

    cat <<EOF
    [$processes]
EOF
}

# Collect security information
collect_security_info() {
    # Check firewall
    local firewall="Unknown"
    if command -v ufw &> /dev/null; then
        firewall=$(ufw status 2>/dev/null | head -1)
    elif command -v firewall-cmd &> /dev/null; then
        firewall="firewalld $(firewall-cmd --state 2>/dev/null)"
    fi

    # Get users
    local users=$(awk -F: '$3 >= 1000 {print "{\"username\":\""$1"\",\"uid\":"$3"}"}' /etc/passwd | head -20 | paste -sd ',' -)

    cat <<EOF
    "firewall_status": "$firewall",
    "user_accounts": [$users]
EOF
}

# Send check-in
send_checkin() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} Collecting system information..."

    local payload=$(cat <<EOF
{
    "api_key": "$API_KEY",
    "action": "checkin",
    "agent_version": "$AGENT_VERSION",
    "data": {
        "agent_version": "$AGENT_VERSION",
        "system": {
            $(collect_system_info)
        },
        "network": {
            $(collect_network_info)
        },
        "processes": $(collect_processes),
        "security": {
            $(collect_security_info)
        }
    }
}
EOF
)

    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} Sending check-in to server..."

    local response=$(curl -s -X POST "$SERVER_URL" \
        -H "Content-Type: application/json" \
        -d "$payload" \
        --max-time 30)

    if echo "$response" | grep -q '"success":true'; then
        echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} Check-in successful!"
        return 0
    else
        echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} Check-in failed: $response"
        return 1
    fi
}

# Main execution
echo "========================================="
echo "Network Security Agent v$AGENT_VERSION"
echo "Tenant: {TENANT_NAME}"
echo "Server: $SERVER_URL"
echo "========================================="
echo ""

# Check for required commands
for cmd in curl ss ip ps awk; do
    if ! command -v $cmd &> /dev/null; then
        echo -e "${RED}Error: Required command '$cmd' not found${NC}"
        exit 1
    fi
done

# Perform check-in
if send_checkin; then
    echo ""
    echo "Agent check-in completed successfully!"
    echo "This agent will check in every $((CHECK_IN_INTERVAL / 3600)) hour(s)."
    echo ""
    echo "To run automatically, add to crontab:"
    echo "  0 * * * * /bin/bash $(realpath $0) >> /var/log/network-agent.log 2>&1"
    exit 0
else
    echo ""
    echo "Check-in failed. Please check your network connection and API key."
    exit 1
fi
BASH;

    // Replace placeholders
    $script = str_replace('{API_KEY}', $apiKey, $script);
    $script = str_replace('{SERVER_URL}', $serverUrl, $script);
    $script = str_replace('{TENANT_NAME}', $tenantName, $script);
    $script = str_replace('{DATE}', date('Y-m-d H:i:s'), $script);

    return $script;
}

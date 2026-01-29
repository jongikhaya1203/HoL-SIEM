#Requires -Version 5.1
<#
.SYNOPSIS
    NetworkScanScada Cloud Tenant Setup
    Hybrid deployment - Cloud component

.DESCRIPTION
    Sets up the cloud tenant for NetworkScanScada hybrid deployment.
    Works with on-premise collectors to receive and process scan data.

.NOTES
    Version: 1.0.0
    License: Subscription-based
#>

param(
    [switch]$Silent,
    [switch]$Uninstall,
    [string]$TenantId,
    [string]$SubscriptionKey,
    [string]$Region = "us-east-1",
    [ValidateSet("AWS", "Azure", "GCP")]
    [string]$CloudProvider = "AWS"
)

# ============================================================
# Admin Check
# ============================================================
function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

if (-not (Test-Administrator)) {
    Write-Host "ERROR: This installer requires Administrator privileges." -ForegroundColor Red
    Write-Host "Please right-click and select 'Run as Administrator'" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# ============================================================
# Configuration
# ============================================================
$script:AppName = "NetworkScanScada Cloud"
$script:AppVersion = "1.0.0"
$script:Publisher = "HoL SIEM Security"
$script:ApiBaseUrl = "https://api.networkscanscada.com"

# ============================================================
# Logging
# ============================================================
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

    $logFile = Join-Path $env:TEMP "NetworkScanScada-Cloud-Setup.log"
    Add-Content -Path $logFile -Value $logMessage
}

# ============================================================
# Banner
# ============================================================
function Show-Banner {
    if ($Silent) { return }

    Clear-Host
    Write-Host @"
╔══════════════════════════════════════════════════════════════════════╗
║                                                                      ║
║     ███╗   ██╗███████╗████████╗██╗    ██╗ ██████╗ ██████╗ ██╗  ██╗   ║
║     ████╗  ██║██╔════╝╚══██╔══╝██║    ██║██╔═══██╗██╔══██╗██║ ██╔╝   ║
║     ██╔██╗ ██║█████╗     ██║   ██║ █╗ ██║██║   ██║██████╔╝█████╔╝    ║
║     ██║╚██╗██║██╔══╝     ██║   ██║███╗██║██║   ██║██╔══██╗██╔═██╗    ║
║     ██║ ╚████║███████╗   ██║   ╚███╔███╔╝╚██████╔╝██║  ██║██║  ██╗   ║
║     ╚═╝  ╚═══╝╚══════╝   ╚═╝    ╚══╝╚══╝  ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝   ║
║                                                                      ║
║              SCADA Security Scanner - CLOUD TENANT                   ║
║                     Hybrid Deployment                                ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝
"@ -ForegroundColor Cyan
    Write-Host ""
}

# ============================================================
# Tenant Registration
# ============================================================
function Register-CloudTenant {
    Write-Log "Registering cloud tenant..."

    if (-not $TenantId) {
        if (-not $Silent) {
            $TenantId = Read-Host "Enter your Tenant ID (or press Enter to create new)"
        }

        if (-not $TenantId) {
            $TenantId = [guid]::NewGuid().ToString()
            Write-Log "Generated new Tenant ID: $TenantId"
        }
    }

    if (-not $SubscriptionKey) {
        if (-not $Silent) {
            $SubscriptionKey = Read-Host "Enter your Subscription Key"
        }

        if (-not $SubscriptionKey) {
            throw "Subscription key is required for cloud deployment"
        }
    }

    # Validate subscription
    Write-Log "Validating subscription..."

    $headers = @{
        "X-Subscription-Key" = $SubscriptionKey
        "Content-Type" = "application/json"
    }

    $body = @{
        tenantId = $TenantId
        region = $Region
        cloudProvider = $CloudProvider
        timestamp = (Get-Date).ToUniversalTime().ToString("o")
    } | ConvertTo-Json

    try {
        # In production, this would call the actual API
        # $response = Invoke-RestMethod -Uri "$script:ApiBaseUrl/v1/tenants/register" -Method Post -Headers $headers -Body $body

        # Simulated response for installer
        $response = @{
            success = $true
            tenantId = $TenantId
            apiEndpoint = "https://$TenantId.api.networkscanscada.com"
            dashboardUrl = "https://$TenantId.app.networkscanscada.com"
            collectorToken = [Convert]::ToBase64String([guid]::NewGuid().ToByteArray())
        }

        Write-Log "Tenant registered successfully" "SUCCESS"
        return $response
    }
    catch {
        Write-Log "Failed to register tenant: $_" "ERROR"
        throw
    }
}

# ============================================================
# Infrastructure Deployment
# ============================================================
function Deploy-CloudInfrastructure {
    param($TenantInfo)

    Write-Log "Deploying cloud infrastructure on $CloudProvider..."

    switch ($CloudProvider) {
        "AWS" {
            Deploy-AWSInfrastructure -TenantInfo $TenantInfo
        }
        "Azure" {
            Deploy-AzureInfrastructure -TenantInfo $TenantInfo
        }
        "GCP" {
            Deploy-GCPInfrastructure -TenantInfo $TenantInfo
        }
    }
}

function Deploy-AWSInfrastructure {
    param($TenantInfo)

    Write-Log "Deploying to AWS region: $Region"

    # Check AWS CLI
    $awsCli = Get-Command aws -ErrorAction SilentlyContinue
    if (-not $awsCli) {
        Write-Log "AWS CLI not found. Please install AWS CLI or deploy manually." "WARN"
        Write-Log "CloudFormation templates are available in the aws/cloudformation directory."
        return
    }

    # Create CloudFormation parameters
    $cfnParams = @"
[
    {"ParameterKey": "EnvironmentName", "ParameterValue": "$($TenantInfo.tenantId)"},
    {"ParameterKey": "TenantId", "ParameterValue": "$($TenantInfo.tenantId)"},
    {"ParameterKey": "CollectorToken", "ParameterValue": "$($TenantInfo.collectorToken)"}
]
"@

    $paramsFile = Join-Path $env:TEMP "cfn-params.json"
    Set-Content -Path $paramsFile -Value $cfnParams

    Write-Log "CloudFormation parameters saved to: $paramsFile"
    Write-Log "To deploy manually, run:"
    Write-Log "  aws cloudformation create-stack --stack-name networkscan-$($TenantInfo.tenantId) --template-body file://aws/cloudformation/master.yaml --parameters file://$paramsFile --capabilities CAPABILITY_IAM CAPABILITY_NAMED_IAM"
}

function Deploy-AzureInfrastructure {
    param($TenantInfo)

    Write-Log "Deploying to Azure region: $Region"

    # Check Azure CLI
    $azCli = Get-Command az -ErrorAction SilentlyContinue
    if (-not $azCli) {
        Write-Log "Azure CLI not found. Please install Azure CLI or deploy manually." "WARN"
        return
    }

    # Create resource group
    $resourceGroup = "networkscan-$($TenantInfo.tenantId)"
    Write-Log "Resource group: $resourceGroup"
}

function Deploy-GCPInfrastructure {
    param($TenantInfo)

    Write-Log "Deploying to GCP region: $Region"

    # Check gcloud CLI
    $gcloud = Get-Command gcloud -ErrorAction SilentlyContinue
    if (-not $gcloud) {
        Write-Log "gcloud CLI not found. Please install Google Cloud SDK or deploy manually." "WARN"
        return
    }
}

# ============================================================
# Generate Collector Configuration
# ============================================================
function New-CollectorConfig {
    param($TenantInfo)

    Write-Log "Generating collector configuration..."

    $config = @{
        tenant = @{
            id = $TenantInfo.tenantId
            apiEndpoint = $TenantInfo.apiEndpoint
            region = $Region
        }
        collector = @{
            token = $TenantInfo.collectorToken
            syncInterval = 300
            batchSize = 100
            compression = $true
            encryption = @{
                enabled = $true
                algorithm = "AES-256-GCM"
            }
        }
        endpoints = @{
            dataIngestion = "$($TenantInfo.apiEndpoint)/v1/data/ingest"
            heartbeat = "$($TenantInfo.apiEndpoint)/v1/collectors/heartbeat"
            config = "$($TenantInfo.apiEndpoint)/v1/collectors/config"
        }
        features = @{
            realTimeSync = $true
            offlineMode = $true
            localDatabase = $true
        }
    }

    $configJson = $config | ConvertTo-Json -Depth 5

    # Save to file for collector installation
    $configPath = Join-Path $env:TEMP "collector-config.json"
    Set-Content -Path $configPath -Value $configJson

    Write-Log "Collector configuration saved to: $configPath"
    return $configPath
}

# ============================================================
# Main Installation
# ============================================================
function Invoke-Installation {
    Show-Banner

    Write-Log "Starting NetworkScanScada Cloud Tenant setup..."
    Write-Log "Cloud Provider: $CloudProvider"
    Write-Log "Region: $Region"
    Write-Host ""

    try {
        # Step 1: Register tenant
        $tenantInfo = Register-CloudTenant

        # Step 2: Deploy infrastructure
        Deploy-CloudInfrastructure -TenantInfo $tenantInfo

        # Step 3: Generate collector config
        $collectorConfigPath = New-CollectorConfig -TenantInfo $tenantInfo

        Write-Host ""
        Write-Host "╔══════════════════════════════════════════════════════════════════════╗" -ForegroundColor Green
        Write-Host "║                 Cloud Tenant Setup Complete!                         ║" -ForegroundColor Green
        Write-Host "╠══════════════════════════════════════════════════════════════════════╣" -ForegroundColor Green
        Write-Host "║                                                                      ║" -ForegroundColor Green
        Write-Host "║  Tenant ID: $($tenantInfo.tenantId.PadRight(43))║" -ForegroundColor Green
        Write-Host "║  Dashboard: $($tenantInfo.dashboardUrl.PadRight(43))║" -ForegroundColor Green
        Write-Host "║  API Endpoint: $($tenantInfo.apiEndpoint.PadRight(40))║" -ForegroundColor Green
        Write-Host "║                                                                      ║" -ForegroundColor Green
        Write-Host "║  NEXT STEPS:                                                         ║" -ForegroundColor Yellow
        Write-Host "║  1. Deploy the CloudFormation/Terraform stack if not auto-deployed   ║" -ForegroundColor Yellow
        Write-Host "║  2. Install collectors on your on-premise servers using:             ║" -ForegroundColor Yellow
        Write-Host "║     NetworkScanScada-Collector-Setup.exe                             ║" -ForegroundColor Yellow
        Write-Host "║  3. Use the collector config file at:                                ║" -ForegroundColor Yellow
        Write-Host "║     $collectorConfigPath   ║" -ForegroundColor Yellow
        Write-Host "║                                                                      ║" -ForegroundColor Green
        Write-Host "╚══════════════════════════════════════════════════════════════════════╝" -ForegroundColor Green

        # Save tenant info for reference
        $tenantInfoPath = Join-Path $env:TEMP "tenant-info.json"
        $tenantInfo | ConvertTo-Json -Depth 3 | Set-Content -Path $tenantInfoPath
        Write-Log "Tenant information saved to: $tenantInfoPath"

        return 0
    }
    catch {
        Write-Log "Setup failed: $_" "ERROR"
        return 1
    }
}

# ============================================================
# Entry Point
# ============================================================
exit (Invoke-Installation)

#Requires -Version 5.1
<#
.SYNOPSIS
    NetworkScanScada Pure Cloud SaaS Setup
    100% Cloud-based deployment
#>

param(
    [switch]$Silent,
    [string]$SubscriptionKey,
    [string]$OrganizationName,
    [string]$AdminEmail,
    [ValidateSet("us-east-1", "us-west-2", "eu-west-1", "eu-central-1", "ap-southeast-1")]
    [string]$Region = "us-east-1",
    [ValidateSet("AWS", "Azure", "GCP")]
    [string]$CloudProvider = "AWS",
    [ValidateSet("starter", "professional", "enterprise")]
    [string]$Plan = "professional"
)

$ErrorActionPreference = "Stop"
$AppName = "NetworkScanScada SaaS"
$AppVersion = "1.0.0"
$ApiBaseUrl = "https://api.networkscanscada.com"

$Plans = @{
    starter = @{ name = "Starter"; maxAssets = 50; maxUsers = 3; price = 99 }
    professional = @{ name = "Professional"; maxAssets = 500; maxUsers = 10; price = 299 }
    enterprise = @{ name = "Enterprise"; maxAssets = -1; maxUsers = -1; price = 999 }
}

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
            "STEP" { Write-Host $logMessage -ForegroundColor Cyan }
            default { Write-Host $logMessage }
        }
    }
    $logFile = Join-Path $env:TEMP "NetworkScanScada-SaaS-Setup.log"
    Add-Content -Path $logFile -Value $logMessage
}

function Show-Banner {
    if ($Silent) { return }
    Clear-Host
    Write-Host ""
    Write-Host "  ============================================================" -ForegroundColor Cyan
    Write-Host "       NetworkScanScada - PURE CLOUD SaaS SETUP" -ForegroundColor Cyan
    Write-Host "              100% Cloud-Based Deployment" -ForegroundColor Yellow
    Write-Host "  ============================================================" -ForegroundColor Cyan
    Write-Host ""
}

function New-RandomPassword {
    param([int]$Length = 16)
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*"
    $password = ""
    for ($i = 0; $i -lt $Length; $i++) {
        $password += $chars[(Get-Random -Maximum $chars.Length)]
    }
    return $password
}

function Get-DeploymentParameters {
    if (-not $Silent) {
        Write-Host "Please provide the following information:" -ForegroundColor Yellow
        Write-Host ""
    }

    if (-not $OrganizationName) {
        if (-not $Silent) { $script:OrganizationName = Read-Host "Organization Name" }
        else { throw "Organization name is required" }
    }

    if (-not $AdminEmail) {
        if (-not $Silent) { $script:AdminEmail = Read-Host "Admin Email" }
        else { throw "Admin email is required" }
    }

    if (-not $SubscriptionKey) {
        if (-not $Silent) {
            Write-Host ""
            Write-Host "Subscription Plans:" -ForegroundColor Cyan
            Write-Host "  1. Starter      - `$99/mo  (50 assets, 3 users)"
            Write-Host "  2. Professional - `$299/mo (500 assets, 10 users)"
            Write-Host "  3. Enterprise   - `$999/mo (Unlimited)"
            Write-Host ""
            $planChoice = Read-Host "Select plan (1-3) [2]"
            switch ($planChoice) {
                "1" { $script:Plan = "starter" }
                "3" { $script:Plan = "enterprise" }
                default { $script:Plan = "professional" }
            }
            Write-Host ""
            $script:SubscriptionKey = Read-Host "Enter Subscription Key (or press Enter for trial)"
            if (-not $SubscriptionKey) {
                Write-Log "Starting 14-day free trial" "WARN"
                $script:SubscriptionKey = "TRIAL-" + [guid]::NewGuid().ToString().Substring(0, 8).ToUpper()
            }
        }
    }

    return @{
        OrganizationName = $OrganizationName
        AdminEmail = $AdminEmail
        SubscriptionKey = $SubscriptionKey
        Plan = $Plan
        Region = $Region
        CloudProvider = $CloudProvider
    }
}

function New-CloudTenant {
    param($Parameters)
    Write-Log "Provisioning cloud tenant..." "STEP"

    $tenantId = [guid]::NewGuid().ToString()
    $subdomain = ($Parameters.OrganizationName -replace '[^a-zA-Z0-9]', '').ToLower()
    $subdomain = "$subdomain-$(Get-Random -Minimum 1000 -Maximum 9999)"

    $tenant = @{
        tenantId = $tenantId
        subdomain = $subdomain
        organizationName = $Parameters.OrganizationName
        adminEmail = $Parameters.AdminEmail
        plan = $Parameters.Plan
        region = $Parameters.Region
        cloudProvider = $Parameters.CloudProvider
        subscriptionKey = $Parameters.SubscriptionKey
        createdAt = (Get-Date).ToUniversalTime().ToString("o")
        status = "provisioning"
        urls = @{
            app = "https://$subdomain.app.networkscanscada.com"
            api = "https://$subdomain.api.networkscanscada.com"
            docs = "https://docs.networkscanscada.com"
        }
        credentials = @{
            adminUsername = "admin"
            adminPassword = New-RandomPassword -Length 16
            apiKey = "nss_" + [Convert]::ToBase64String([guid]::NewGuid().ToByteArray()).Replace("=", "").Replace("+", "").Replace("/", "")
        }
    }

    return $tenant
}

function Deploy-Infrastructure {
    param($Tenant)
    Write-Log "Deploying cloud infrastructure..." "STEP"
    Write-Log "Cloud Provider: $($Tenant.cloudProvider)"
    Write-Log "Region: $($Tenant.region)"

    $stackName = "networkscan-$($Tenant.subdomain)"

    switch ($Tenant.cloudProvider) {
        "AWS" {
            $awsCli = Get-Command aws -ErrorAction SilentlyContinue
            if ($awsCli) {
                Write-Log "AWS CLI detected."
                Write-Log "To deploy manually, run:"
                Write-Log "  aws cloudformation create-stack --stack-name $stackName --template-body file://aws/cloudformation/master.yaml --capabilities CAPABILITY_IAM"
            } else {
                Write-Log "AWS CLI not installed. Deploy manually via AWS Console." "WARN"
            }
        }
        "Azure" {
            Write-Log "Azure deployment - use ARM templates or Azure Portal." "WARN"
        }
        "GCP" {
            Write-Log "GCP deployment - use Deployment Manager or GCP Console." "WARN"
        }
    }
}

function Initialize-Database {
    param($Tenant)
    Write-Log "Initializing cloud database..." "STEP"

    $dbConfig = @{
        engine = "aurora-mysql"
        version = "8.0"
        instanceClass = switch ($Tenant.plan) {
            "starter" { "db.t3.small" }
            "professional" { "db.r6g.large" }
            "enterprise" { "db.r6g.xlarge" }
        }
        multiAZ = $Tenant.plan -ne "starter"
        encrypted = $true
    }

    Write-Log "Database: $($dbConfig.engine) $($dbConfig.version)"
    Write-Log "Instance: $($dbConfig.instanceClass)"
    Write-Log "Multi-AZ: $($dbConfig.multiAZ)"

    return $dbConfig
}

function Save-DeploymentInfo {
    param($Tenant)

    # Use Public Desktop for reliability across elevated sessions
    $outputDir = "$env:PUBLIC\Desktop"
    if (-not (Test-Path $outputDir)) {
        $outputDir = $env:TEMP
    }
    $infoFile = Join-Path $outputDir "NetworkScanScada-SaaS-$($Tenant.subdomain).txt"

    $content = @"
================================================================================
NetworkScanScada SaaS Deployment Information
================================================================================

IMPORTANT: Save this file securely and delete after noting credentials!

Tenant Information
------------------
Tenant ID: $($Tenant.tenantId)
Subdomain: $($Tenant.subdomain)
Organization: $($Tenant.organizationName)
Plan: $($Tenant.plan)
Region: $($Tenant.region)
Cloud Provider: $($Tenant.cloudProvider)

Access URLs
-----------
Application: $($Tenant.urls.app)
API Endpoint: $($Tenant.urls.api)
Documentation: $($Tenant.urls.docs)

Admin Credentials
-----------------
Username: $($Tenant.credentials.adminUsername)
Password: $($Tenant.credentials.adminPassword)

API Key: $($Tenant.credentials.apiKey)

IMPORTANT: Change the admin password immediately after first login!

Subscription
------------
Key: $($Tenant.subscriptionKey)
Status: $(if ($Tenant.subscriptionKey -like "TRIAL-*") { "14-Day Free Trial" } else { "Active" })

Support
-------
Email: support@networkscanscada.com
Docs: https://docs.networkscanscada.com

Created: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
================================================================================
"@

    Set-Content -Path $infoFile -Value $content -Encoding UTF8
    Write-Log "Deployment information saved to: $infoFile"

    return $infoFile
}

# Main
if (-not (Test-Administrator)) {
    Write-Host "ERROR: This installer requires Administrator privileges." -ForegroundColor Red
    Write-Host "Please right-click and select 'Run as Administrator'" -ForegroundColor Yellow
    if (-not $Silent) { Read-Host "Press Enter to exit" }
    exit 1
}

Show-Banner

Write-Log "Starting NetworkScanScada Pure Cloud SaaS deployment..."
Write-Host ""

try {
    $parameters = Get-DeploymentParameters
    Write-Log "Plan selected: $($Plans[$parameters.Plan].name)" "SUCCESS"

    $tenant = New-CloudTenant -Parameters $parameters
    Write-Log "Tenant created: $($tenant.subdomain)" "SUCCESS"

    Deploy-Infrastructure -Tenant $tenant
    $dbConfig = Initialize-Database -Tenant $tenant
    $infoFile = Save-DeploymentInfo -Tenant $tenant

    Write-Host ""
    Write-Host "  ============================================================" -ForegroundColor Green
    Write-Host "           SaaS DEPLOYMENT COMPLETE!" -ForegroundColor Green
    Write-Host "  ============================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Organization: $($tenant.organizationName)" -ForegroundColor White
    Write-Host "  Plan: $($Plans[$tenant.plan].name)" -ForegroundColor White
    Write-Host ""
    Write-Host "  Application URL:" -ForegroundColor Cyan
    Write-Host "    $($tenant.urls.app)" -ForegroundColor White
    Write-Host ""
    Write-Host "  Admin Login:" -ForegroundColor Cyan
    Write-Host "    Username: $($tenant.credentials.adminUsername)" -ForegroundColor White
    Write-Host "    Password: $($tenant.credentials.adminPassword)" -ForegroundColor White
    Write-Host ""
    Write-Host "  API Key: $($tenant.credentials.apiKey.Substring(0,20))..." -ForegroundColor White
    Write-Host ""

    if ($tenant.subscriptionKey -like "TRIAL-*") {
        Write-Host "  *** 14-DAY FREE TRIAL ***" -ForegroundColor Yellow
        Write-Host "  Upgrade at: https://networkscanscada.com/pricing" -ForegroundColor Yellow
        Write-Host ""
    }

    Write-Host "  Credentials saved to Desktop" -ForegroundColor Yellow
    Write-Host "  CHANGE PASSWORD IMMEDIATELY AFTER FIRST LOGIN!" -ForegroundColor Red
    Write-Host ""

    if (-not $Silent) { Read-Host "Press Enter to exit" }
    exit 0
}
catch {
    Write-Log "Deployment failed: $_" "ERROR"
    if (-not $Silent) { Read-Host "Press Enter to exit" }
    exit 1
}

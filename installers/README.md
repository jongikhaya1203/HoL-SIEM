# NetworkScanScada Installation Packages

This directory contains three installation packages for different deployment scenarios.

## Installation Options Overview

| Package | Use Case | Components | License |
|---------|----------|------------|---------|
| **POC** | 30-day evaluation | Self-contained with auto-expiry | Trial |
| **Hybrid** | Enterprise with on-premise collection | Cloud tenant + On-premise collector | Subscription |
| **Cloud Only** | Full SaaS deployment | 100% cloud-based | Subscription |

## Directory Structure

```
installers/
├── poc/                                    # 30-day Proof of Concept
│   ├── NetworkScanScada-POC-Setup.ps1     # Main installer script
│   ├── build-installer.ps1                 # Creates .exe installer
│   └── dist/                               # Built executables
│
├── hybrid/                                 # Hybrid Cloud + On-Premise
│   ├── cloud/                              # Cloud tenant setup
│   │   └── NetworkScanScada-Cloud-Setup.ps1
│   ├── collector/                          # On-premise collector
│   │   └── NetworkScanScada-Collector-Setup.ps1
│   ├── build-installers.ps1                # Build script
│   └── dist/                               # Built executables
│
├── cloud-only/                             # Pure Cloud SaaS
│   ├── NetworkScanScada-SaaS-Setup.ps1    # SaaS setup script
│   ├── build-installer.ps1                 # Build script
│   ├── terraform/                          # Terraform IaC
│   │   ├── main.tf
│   │   └── variables.tf
│   └── dist/                               # Built executables
│
└── README.md                               # This file
```

---

## 1. Proof of Concept (POC) Installation

**Purpose**: 30-day evaluation with full functionality and automatic expiration.

### Features
- Full NetworkScanScada functionality
- Self-contained SQLite database
- Automatic uninstall after 30 days
- Limited to 100 assets and 5 users

### Installation

```powershell
# Run as Administrator
.\NetworkScanScada-POC-Setup.exe

# Or run the PowerShell script directly
powershell -ExecutionPolicy Bypass -File .\NetworkScanScada-POC-Setup.ps1
```

### Options

| Parameter | Description | Default |
|-----------|-------------|---------|
| `-InstallPath` | Installation directory | C:\Program Files\NetworkScanScada-POC |
| `-TrialDays` | Trial period in days | 30 |
| `-Silent` | Silent installation | false |

### What Happens After 30 Days
1. A scheduled task checks the expiry daily
2. Warning notifications appear in the last 7 days
3. After expiration, the software automatically uninstalls
4. Data is backed up to the Desktop before removal

---

## 2. Hybrid Cloud Installation

**Purpose**: Enterprise deployment with on-premise data collection and cloud processing.

### Architecture

```
┌─────────────────────────┐      ┌─────────────────────────┐
│   On-Premise Network    │      │     Cloud Tenant        │
│                         │      │                         │
│  ┌───────────────────┐  │      │  ┌─────────────────┐   │
│  │    Collector      │──┼──────┼─►│   Aurora DB     │   │
│  │  (Local SQLite)   │  │ HTTPS│  │   (Primary)     │   │
│  └───────────────────┘  │      │  └─────────────────┘   │
│           │             │      │           │            │
│           ▼             │      │           ▼            │
│  ┌───────────────────┐  │      │  ┌─────────────────┐   │
│  │  SCADA Devices    │  │      │  │   Web App       │   │
│  │  Network Assets   │  │      │  │   Dashboard     │   │
│  └───────────────────┘  │      │  └─────────────────┘   │
└─────────────────────────┘      └─────────────────────────┘
```

### Installation Steps

#### Step 1: Deploy Cloud Tenant
```powershell
.\NetworkScanScada-Cloud-Setup.exe `
    -SubscriptionKey "YOUR-KEY" `
    -Region "us-east-1" `
    -CloudProvider "AWS"
```

This will:
- Register your cloud tenant
- Generate collector configuration
- Deploy cloud infrastructure (optional)

#### Step 2: Install On-Premise Collectors
```powershell
.\NetworkScanScada-Collector-Setup.exe `
    -TenantId "your-tenant-id" `
    -CollectorToken "your-token" `
    -CollectorName "DC1-Collector"
```

Or use the config file from Step 1:
```powershell
.\NetworkScanScada-Collector-Setup.exe `
    -ConfigFile "C:\temp\collector-config.json"
```

### Collector Features
- **Local Database**: SQLite for offline operation
- **Auto-Sync**: Data syncs to cloud every 5 minutes
- **Offline Mode**: Continues scanning when cloud is unreachable
- **Scheduled Scans**: Quick scans (4 hours) and full scans (daily)
- **SCADA Protocols**: Modbus, DNP3, IEC61850, OPC-UA, BACnet

---

## 3. Cloud-Only (SaaS) Installation

**Purpose**: 100% cloud-hosted solution with no on-premise components.

### Features
- Fully managed infrastructure
- Automatic scaling and high availability
- No maintenance required
- Pay-as-you-go pricing

### Subscription Plans

| Plan | Assets | Users | Scans/Day | Price |
|------|--------|-------|-----------|-------|
| Starter | 50 | 3 | 10 | $99/mo |
| Professional | 500 | 10 | 100 | $299/mo |
| Enterprise | Unlimited | Unlimited | Unlimited | $999/mo |

### Installation

#### Option A: Interactive Setup
```powershell
.\NetworkScanScada-SaaS-Setup.exe
```

#### Option B: Automated Setup
```powershell
.\NetworkScanScada-SaaS-Setup.exe `
    -SubscriptionKey "YOUR-KEY" `
    -OrganizationName "Acme Corp" `
    -AdminEmail "admin@acme.com" `
    -Region "us-east-1" `
    -Plan "professional" `
    -CloudProvider "AWS"
```

#### Option C: Terraform Deployment
```bash
cd terraform
terraform init
terraform plan -var-file="my-tenant.tfvars"
terraform apply -var-file="my-tenant.tfvars"
```

### Post-Installation
1. Access your dashboard at: `https://[subdomain].app.networkscanscada.com`
2. Login with provided admin credentials
3. **Change password immediately**
4. Configure network targets and scans

---

## Building Executables

### Prerequisites
```powershell
# Install PS2EXE module
Install-Module -Name ps2exe -Scope CurrentUser

# Optional: Inno Setup for advanced installers
# Download from: https://jrsoftware.org/isinfo.php
```

### Build All Installers
```powershell
# POC
cd installers\poc
.\build-installer.ps1

# Hybrid
cd installers\hybrid
.\build-installers.ps1

# Cloud-Only
cd installers\cloud-only
.\build-installer.ps1
```

### Output
Executables are created in the `dist/` subdirectory of each installer folder.

---

## Comparison Table

| Feature | POC | Hybrid | Cloud-Only |
|---------|-----|--------|------------|
| **Database Location** | Local SQLite | Local + Cloud | Cloud Only |
| **Internet Required** | No | Periodic sync | Always |
| **SCADA Protocol Support** | Full | Full | Full |
| **Maximum Assets** | 100 | Plan-based | Plan-based |
| **Maximum Users** | 5 | Plan-based | Plan-based |
| **Auto-Updates** | No | Collector only | Automatic |
| **High Availability** | No | Cloud component | Yes |
| **Offline Operation** | Yes | Collector only | No |
| **Data Residency** | On-premise | Hybrid | Cloud |
| **Trial Period** | 30 days | N/A | 14 days |
| **Support** | Email | Email + Phone | 24/7 Priority |

---

## System Requirements

### POC / Collector
- Windows 10/11 or Windows Server 2016+
- 4 GB RAM minimum
- 10 GB disk space
- .NET Framework 4.7.2+
- PowerShell 5.1+
- Administrator privileges

### Cloud Components
- AWS, Azure, or GCP account
- Cloud CLI tools (optional)
- Valid subscription key

---

## Security Considerations

### POC
- Data stored locally with encryption
- No external network access required
- Trial license tied to machine fingerprint

### Hybrid
- TLS 1.2+ for all cloud communication
- Collector tokens secured in Windows Credential Manager
- Local database encrypted at rest
- Data compressed before transmission

### Cloud-Only
- SOC 2 Type II compliant infrastructure
- Data encrypted at rest (AES-256)
- Data encrypted in transit (TLS 1.3)
- WAF protection included
- Optional: Customer-managed encryption keys (Enterprise)

---

## Support

| Channel | POC | Hybrid | Cloud-Only |
|---------|-----|--------|------------|
| Documentation | Yes | Yes | Yes |
| Email Support | Yes | Yes | Yes |
| Phone Support | No | Business hours | 24/7 |
| SLA | None | 99.5% | 99.9% |

### Contact
- **Documentation**: https://docs.networkscanscada.com
- **Support Portal**: https://support.networkscanscada.com
- **Email**: support@networkscanscada.com
- **Sales**: sales@networkscanscada.com

---

## License

- POC: 30-day evaluation license
- Hybrid/Cloud: Commercial subscription license

Copyright 2024 HoL SIEM Security. All rights reserved.

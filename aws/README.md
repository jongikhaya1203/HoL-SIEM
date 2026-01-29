# NetworkScanScada AWS Deployment

## Overview

This directory contains all AWS Infrastructure as Code (IaC) and deployment configurations for running NetworkScanScada on AWS. The infrastructure is designed for high availability, security, and scalability.

## Architecture Summary

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              AWS Cloud                                       │
├─────────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐                                                            │
│  │ CloudFront  │◄──── Users                                                 │
│  │    (CDN)    │                                                            │
│  └──────┬──────┘                                                            │
│         │                                                                   │
│  ┌──────▼──────┐     ┌─────────────┐                                       │
│  │   AWS WAF   │────►│   Route53   │                                       │
│  └──────┬──────┘     └─────────────┘                                       │
│         │                                                                   │
│  ┌──────▼──────────────────────────────────────────────────────┐           │
│  │                    VPC (10.0.0.0/16)                         │           │
│  │  ┌─────────────────────────────────────────────────────────┐ │           │
│  │  │              Public Subnets (2 AZs)                     │ │           │
│  │  │  ┌──────────────────────────────────────────────────┐  │ │           │
│  │  │  │          Application Load Balancer               │  │ │           │
│  │  │  └────────────────────┬─────────────────────────────┘  │ │           │
│  │  │                       │                                 │ │           │
│  │  │  ┌──────────┐   ┌─────▼─────┐                          │ │           │
│  │  │  │   NAT    │   │   NAT     │                          │ │           │
│  │  │  │ Gateway  │   │  Gateway  │                          │ │           │
│  │  │  └────┬─────┘   └─────┬─────┘                          │ │           │
│  │  └───────┼───────────────┼────────────────────────────────┘ │           │
│  │          │               │                                   │           │
│  │  ┌───────▼───────────────▼─────────────────────────────────┐ │           │
│  │  │            Private Subnets (2 AZs)                      │ │           │
│  │  │  ┌─────────────────────────────────────────────────┐   │ │           │
│  │  │  │              Auto Scaling Group                  │   │ │           │
│  │  │  │  ┌──────────┐   ┌──────────┐   ┌──────────┐    │   │ │           │
│  │  │  │  │   EC2    │   │   EC2    │   │   EC2    │    │   │ │           │
│  │  │  │  │ (PHP+Nx) │   │ (PHP+Nx) │   │ (PHP+Nx) │    │   │ │           │
│  │  │  │  └──────────┘   └──────────┘   └──────────┘    │   │ │           │
│  │  │  └─────────────────────────────────────────────────┘   │ │           │
│  │  └────────────────────────────────────────────────────────┘ │           │
│  │                                                              │           │
│  │  ┌───────────────────────────────────────────────────────┐  │           │
│  │  │               Data Subnets (2 AZs)                    │  │           │
│  │  │  ┌────────────────────┐   ┌────────────────────┐     │  │           │
│  │  │  │   Aurora MySQL     │   │   ElastiCache      │     │  │           │
│  │  │  │   (Writer+Reader)  │   │   (Redis Cluster)  │     │  │           │
│  │  │  └────────────────────┘   └────────────────────┘     │  │           │
│  │  └───────────────────────────────────────────────────────┘  │           │
│  └──────────────────────────────────────────────────────────────┘           │
│                                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐       │
│  │     S3      │  │   Secrets   │  │    SQS      │  │     SNS     │       │
│  │  (Storage)  │  │   Manager   │  │  (Queues)   │  │  (Alerts)   │       │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘       │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Directory Structure

```
aws/
├── cloudformation/              # CloudFormation templates
│   ├── 01-vpc.yaml             # VPC, Subnets, NAT Gateways, VPC Endpoints
│   ├── 02-security.yaml        # Security Groups, NACLs
│   ├── 03-database.yaml        # Aurora MySQL cluster (Writer + Reader)
│   ├── 04-cache.yaml           # ElastiCache Redis replication group
│   ├── 05-secrets.yaml         # Secrets Manager, SSM Parameters
│   ├── 06-compute.yaml         # EC2 Launch Template, ASG, ALB, IAM
│   ├── 07-storage.yaml         # S3 buckets, CloudFront, SQS, SNS
│   ├── 08-waf.yaml             # WAF rules, CloudTrail, GuardDuty
│   ├── 09-monitoring.yaml      # CloudWatch dashboards, alarms, log groups
│   ├── 10-cicd.yaml            # CodePipeline, CodeBuild, CodeDeploy
│   └── master.yaml             # Master stack (nested stack orchestration)
│
├── scripts/                     # Deployment and operational scripts
│   ├── deploy.sh               # Main deployment orchestration script
│   ├── setup-ec2.sh            # EC2 instance bootstrap script
│   └── migrate-data.sh         # Database migration script
│
├── runbooks/                    # Operational documentation
│   ├── deployment.md           # Deployment procedures
│   ├── disaster-recovery.md    # DR procedures and RTO/RPO
│   ├── scaling.md              # Scaling procedures
│   └── troubleshooting.md      # Common issues and solutions
│
└── README.md                    # This file
```

## Quick Start

### Prerequisites

- AWS CLI v2 configured with appropriate credentials
- IAM permissions to create VPC, EC2, RDS, ElastiCache, S3, CloudFront, WAF, etc.
- A GitHub repository with the application code
- AWS CodeStar connection to GitHub (create in AWS Console)
- An EC2 key pair for SSH access (optional, Session Manager preferred)

### Step 1: Configure Parameters

Create a parameters file for your environment:

```bash
# Create parameters file
cat > production-params.json << 'EOF'
[
  {"ParameterKey": "EnvironmentName", "ParameterValue": "production"},
  {"ParameterKey": "VPCCidr", "ParameterValue": "10.0.0.0/16"},
  {"ParameterKey": "DBInstanceClass", "ParameterValue": "db.r6g.large"},
  {"ParameterKey": "CacheNodeType", "ParameterValue": "cache.r6g.large"},
  {"ParameterKey": "InstanceType", "ParameterValue": "t3.large"},
  {"ParameterKey": "MinSize", "ParameterValue": "2"},
  {"ParameterKey": "MaxSize", "ParameterValue": "10"},
  {"ParameterKey": "GitHubOwner", "ParameterValue": "your-org"},
  {"ParameterKey": "GitHubRepo", "ParameterValue": "networkscanscada"},
  {"ParameterKey": "AlertEmail", "ParameterValue": "ops@example.com"}
]
EOF
```

### Step 2: Deploy Infrastructure

#### Option A: Using the Deployment Script

```bash
cd aws/scripts
chmod +x deploy.sh

# Validate templates
./deploy.sh production validate

# Deploy all phases
./deploy.sh production create --profile your-aws-profile --region us-east-1

# Check deployment status
./deploy.sh production status
```

#### Option B: Deploy Individual Stacks

```bash
# Set environment variables
export AWS_REGION=us-east-1
export ENV=production

# Phase 1: Foundation
aws cloudformation deploy \
    --template-file cloudformation/01-vpc.yaml \
    --stack-name ${ENV}-networkscan-vpc \
    --parameter-overrides EnvironmentName=$ENV \
    --capabilities CAPABILITY_IAM

aws cloudformation deploy \
    --template-file cloudformation/02-security.yaml \
    --stack-name ${ENV}-networkscan-security \
    --parameter-overrides EnvironmentName=$ENV

aws cloudformation deploy \
    --template-file cloudformation/03-database.yaml \
    --stack-name ${ENV}-networkscan-database \
    --parameter-overrides EnvironmentName=$ENV \
    --capabilities CAPABILITY_IAM

aws cloudformation deploy \
    --template-file cloudformation/04-cache.yaml \
    --stack-name ${ENV}-networkscan-cache \
    --parameter-overrides EnvironmentName=$ENV

aws cloudformation deploy \
    --template-file cloudformation/05-secrets.yaml \
    --stack-name ${ENV}-networkscan-secrets \
    --parameter-overrides EnvironmentName=$ENV \
    --capabilities CAPABILITY_NAMED_IAM

# Phase 2: Application
aws cloudformation deploy \
    --template-file cloudformation/06-compute.yaml \
    --stack-name ${ENV}-networkscan-compute \
    --parameter-overrides EnvironmentName=$ENV \
    --capabilities CAPABILITY_NAMED_IAM

aws cloudformation deploy \
    --template-file cloudformation/07-storage.yaml \
    --stack-name ${ENV}-networkscan-storage \
    --parameter-overrides EnvironmentName=$ENV

# Phase 3: Security
aws cloudformation deploy \
    --template-file cloudformation/08-waf.yaml \
    --stack-name ${ENV}-networkscan-waf \
    --parameter-overrides EnvironmentName=$ENV \
    --capabilities CAPABILITY_NAMED_IAM

# Phase 4: Operations
aws cloudformation deploy \
    --template-file cloudformation/09-monitoring.yaml \
    --stack-name ${ENV}-networkscan-monitoring \
    --parameter-overrides EnvironmentName=$ENV

aws cloudformation deploy \
    --template-file cloudformation/10-cicd.yaml \
    --stack-name ${ENV}-networkscan-cicd \
    --parameter-overrides EnvironmentName=$ENV \
    --capabilities CAPABILITY_NAMED_IAM
```

### Step 3: Migrate Data

```bash
cd aws/scripts
chmod +x migrate-data.sh

# Migrate from on-premises MySQL to Aurora
./migrate-data.sh production \
    --source-host your-mysql-host \
    --source-user root \
    --source-db network_security_scanner
```

### Step 4: Verify Deployment

```bash
# Get ALB DNS name
aws cloudformation describe-stacks \
    --stack-name production-networkscan-compute \
    --query 'Stacks[0].Outputs[?OutputKey==`ALBDNSName`].OutputValue' \
    --output text

# Test health endpoint
curl https://<alb-dns-name>/health
```

## Stack Dependencies

```
01-vpc.yaml
    │
    └──► 02-security.yaml
              │
              ├──► 03-database.yaml
              │         │
              ├──► 04-cache.yaml
              │         │
              │         ├──► 05-secrets.yaml
              │         │         │
              │         │         └──► 06-compute.yaml ◄──┐
              │         │                    │            │
              │         └────────────────────┤            │
              │                              │            │
              └──► 07-storage.yaml           │            │
                         │                   │            │
                         ├───────────────────┘            │
                         │                                │
                         └──► 08-waf.yaml ────────────────┤
                                   │                      │
                                   └──► 09-monitoring.yaml
                                            │
                                            └──► 10-cicd.yaml
```

## Cost Estimate (Monthly)

| Component | Development | Production | Enterprise |
|-----------|-------------|------------|------------|
| EC2 (ASG) | $100 (2x t3.medium) | $400 (2x t3.large) | $800 (4x t3.xlarge) |
| Aurora MySQL | $150 (db.t3.medium) | $400 (db.r6g.large) | $800 (db.r6g.xlarge) |
| ElastiCache | $50 (cache.t3.micro) | $200 (cache.r6g.large) | $400 (cache.r6g.xlarge) |
| ALB | $20 | $50 | $100 |
| NAT Gateway | $65 | $130 | $130 |
| S3 + CloudFront | $20 | $50 | $200 |
| WAF | $10 | $30 | $100 |
| CloudWatch | $20 | $50 | $150 |
| Secrets Manager | $5 | $10 | $20 |
| Data Transfer | $50 | $150 | $500 |
| **Total** | **~$490** | **~$1,470** | **~$3,200** |

*Estimates vary based on usage patterns. Use AWS Cost Explorer for accurate projections.*

## Security Features

- **Network Security**: VPC with private subnets, NACLs, Security Groups
- **WAF**: OWASP Top 10 protection, rate limiting, bot control
- **Encryption**: TLS everywhere, S3 encryption, RDS encryption, Redis AUTH
- **Secrets**: AWS Secrets Manager for all credentials
- **Audit**: CloudTrail, VPC Flow Logs, GuardDuty
- **Access**: IAM roles with least privilege, no SSH keys required (Session Manager)

## Monitoring & Alerting

- **Dashboards**: Operational and Security dashboards in CloudWatch
- **Alarms**: CPU, memory, disk, connections, errors, latency
- **Logs**: Centralized in CloudWatch Logs with retention policies
- **Notifications**: SNS topics for warning and critical alerts

## High Availability

- **Multi-AZ**: All components deployed across 2 Availability Zones
- **Auto Scaling**: EC2 instances scale based on CPU utilization
- **Database**: Aurora with automatic failover to read replica
- **Cache**: Redis with Multi-AZ and automatic failover
- **Load Balancing**: ALB with health checks and connection draining

## Disaster Recovery

| Metric | Target |
|--------|--------|
| RTO (Recovery Time Objective) | 1 hour |
| RPO (Recovery Point Objective) | 5 minutes |

See [Disaster Recovery Runbook](runbooks/disaster-recovery.md) for detailed procedures.

## CI/CD Pipeline

```
GitHub Push → CodePipeline → CodeBuild (Test) → CodeBuild (Build) → CodeDeploy
                                                                        │
                                                                        ▼
                                                              Auto Scaling Group
```

- Automatic deployments on push to main branch
- Unit tests run before build
- Blue/green deployment with automatic rollback
- Notifications via SNS

## Operational Runbooks

| Runbook | Description |
|---------|-------------|
| [Deployment](runbooks/deployment.md) | Deployment and rollback procedures |
| [Disaster Recovery](runbooks/disaster-recovery.md) | DR procedures for various failure scenarios |
| [Scaling](runbooks/scaling.md) | Manual and automatic scaling procedures |
| [Troubleshooting](runbooks/troubleshooting.md) | Common issues and solutions |

## Cleanup

To delete all resources:

```bash
./deploy.sh production delete

# Or manually in reverse order:
aws cloudformation delete-stack --stack-name production-networkscan-cicd
aws cloudformation delete-stack --stack-name production-networkscan-monitoring
aws cloudformation delete-stack --stack-name production-networkscan-waf
aws cloudformation delete-stack --stack-name production-networkscan-storage
aws cloudformation delete-stack --stack-name production-networkscan-compute
aws cloudformation delete-stack --stack-name production-networkscan-secrets
aws cloudformation delete-stack --stack-name production-networkscan-cache
aws cloudformation delete-stack --stack-name production-networkscan-database
aws cloudformation delete-stack --stack-name production-networkscan-security
aws cloudformation delete-stack --stack-name production-networkscan-vpc
```

**Note**: Some resources have deletion protection enabled (Aurora, S3 buckets with data). You may need to manually disable these before deletion.

## Support

For infrastructure issues:
- Check the [Troubleshooting Guide](runbooks/troubleshooting.md)
- Review CloudWatch Logs and Alarms
- Contact the infrastructure team
- Create a ticket in the ITSM system

## Contributing

1. Test all changes in development/staging first
2. Update CloudFormation templates
3. Update runbooks if procedures change
4. Get approval before production deployment

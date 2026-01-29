# NetworkScanScada Disaster Recovery Runbook

## Overview

This runbook covers disaster recovery procedures for NetworkScanScada infrastructure.

## Recovery Objectives

| Metric | Target |
|--------|--------|
| RTO (Recovery Time Objective) | 1 hour |
| RPO (Recovery Point Objective) | 5 minutes |

## Disaster Scenarios

### 1. Single EC2 Instance Failure

**Impact**: Minimal - Auto Scaling handles automatically

**Detection**:
- ALB unhealthy host alarm
- CloudWatch instance status check failure

**Recovery**:
Auto Scaling automatically terminates unhealthy instances and launches replacements.

**Manual Intervention** (if needed):
```bash
# Check ASG activity
aws autoscaling describe-scaling-activities \
    --auto-scaling-group-name production-networkscan-asg \
    --max-items 10

# Force instance refresh
aws autoscaling start-instance-refresh \
    --auto-scaling-group-name production-networkscan-asg \
    --preferences '{"MinHealthyPercentage": 50}'
```

### 2. Availability Zone Failure

**Impact**: Partial - 50% capacity loss

**Detection**:
- Multiple unhealthy hosts in one AZ
- VPC Flow Log anomalies

**Recovery**:
Auto Scaling launches instances in healthy AZ.

**Manual Steps**:
```bash
# Check AZ distribution
aws ec2 describe-instances \
    --filters "Name=tag:Environment,Values=production" \
              "Name=instance-state-name,Values=running" \
    --query 'Reservations[*].Instances[*].[InstanceId,Placement.AvailabilityZone]'

# Update ASG to use single AZ (temporary)
aws autoscaling update-auto-scaling-group \
    --auto-scaling-group-name production-networkscan-asg \
    --availability-zones us-east-1a

# Increase desired capacity
aws autoscaling set-desired-capacity \
    --auto-scaling-group-name production-networkscan-asg \
    --desired-capacity 4
```

### 3. Database Failure

**Impact**: Critical - Application unavailable

#### Aurora Writer Failure

**Detection**:
- Aurora CPU/connection alarms
- Application database errors

**Recovery**:
Aurora automatically fails over to replica.

```bash
# Check cluster status
aws rds describe-db-clusters \
    --db-cluster-identifier production-networkscan-cluster

# Force failover (if needed)
aws rds failover-db-cluster \
    --db-cluster-identifier production-networkscan-cluster \
    --target-db-instance-identifier production-networkscan-replica
```

#### Complete Database Loss

```bash
# List available snapshots
aws rds describe-db-cluster-snapshots \
    --db-cluster-identifier production-networkscan-cluster

# Restore from snapshot
aws rds restore-db-cluster-from-snapshot \
    --db-cluster-identifier production-networkscan-cluster-restored \
    --snapshot-identifier <snapshot-id> \
    --engine aurora-mysql \
    --vpc-security-group-ids <security-group-id> \
    --db-subnet-group-name production-db-subnet-group

# Create DB instances
aws rds create-db-instance \
    --db-instance-identifier production-networkscan-primary-restored \
    --db-cluster-identifier production-networkscan-cluster-restored \
    --db-instance-class db.r6g.large \
    --engine aurora-mysql
```

### 4. Redis Cache Failure

**Impact**: Session loss, degraded performance

**Detection**:
- Redis CPU/memory alarms
- Session errors in application

**Recovery**:
ElastiCache automatically handles failover.

```bash
# Check replication group status
aws elasticache describe-replication-groups \
    --replication-group-id production-networkscan-redis

# Force failover (if needed)
aws elasticache modify-replication-group \
    --replication-group-id production-networkscan-redis \
    --apply-immediately \
    --primary-cluster-id production-networkscan-redis-002
```

### 5. Complete Region Failure

**Impact**: Complete outage until recovery

**Recovery Steps**:

1. **Activate DR Region**
```bash
# Switch to DR region
export AWS_REGION=us-west-2

# Deploy infrastructure
./deploy.sh production create --region us-west-2
```

2. **Restore Database**
```bash
# Copy snapshot to DR region
aws rds copy-db-cluster-snapshot \
    --source-db-cluster-snapshot-identifier arn:aws:rds:us-east-1:123456789:cluster-snapshot:latest \
    --target-db-cluster-snapshot-identifier production-dr-snapshot \
    --source-region us-east-1 \
    --region us-west-2

# Restore cluster
aws rds restore-db-cluster-from-snapshot \
    --db-cluster-identifier production-networkscan-cluster \
    --snapshot-identifier production-dr-snapshot \
    --region us-west-2
```

3. **Update DNS**
```bash
# Update Route 53 to point to DR region
aws route53 change-resource-record-sets \
    --hosted-zone-id <zone-id> \
    --change-batch file://dns-failover.json
```

4. **Restore S3 Data**
```bash
# S3 cross-region replication should have data
# Verify bucket contents
aws s3 ls s3://production-networkscan-reports-dr/ --region us-west-2
```

## Backup Verification

### Daily Verification Tasks

```bash
# Verify Aurora automated backups
aws rds describe-db-cluster-snapshots \
    --db-cluster-identifier production-networkscan-cluster \
    --snapshot-type automated \
    --query 'DBClusterSnapshots[-1].[SnapshotCreateTime,Status]'

# Verify S3 backup bucket
aws s3 ls s3://production-networkscan-backups/ --summarize

# Verify Redis snapshots
aws elasticache describe-snapshots \
    --replication-group-id production-networkscan-redis \
    --query 'Snapshots[-1].[SnapshotName,SnapshotStatus]'
```

### Monthly DR Test Procedure

1. Create test environment from production backups
2. Restore database from latest snapshot
3. Verify application functionality
4. Document recovery time
5. Clean up test resources

## Communication Plan

### Escalation Matrix

| Severity | Response Time | Notification |
|----------|---------------|--------------|
| P1 - Critical | 15 minutes | Page on-call, notify leadership |
| P2 - High | 30 minutes | Notify on-call team |
| P3 - Medium | 4 hours | Email operations team |
| P4 - Low | Next business day | Create ticket |

### Status Page Updates

During incidents:
1. Acknowledge incident within 15 minutes
2. Update status every 30 minutes
3. Post root cause analysis within 48 hours

## Recovery Verification Checklist

- [ ] All health checks passing
- [ ] Database connections working
- [ ] Redis sessions functional
- [ ] Background jobs processing
- [ ] CloudFront serving content
- [ ] Monitoring/alerting operational
- [ ] User authentication working
- [ ] Critical features tested
- [ ] Performance metrics normal

## Post-Incident Actions

1. Document timeline of events
2. Identify root cause
3. Create remediation tickets
4. Update runbooks if needed
5. Schedule post-mortem meeting
6. Communicate to stakeholders

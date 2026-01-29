# NetworkScanScada Scaling Runbook

## Overview

This runbook covers scaling procedures for NetworkScanScada infrastructure components.

## Auto Scaling Configuration

### Current Settings

| Component | Min | Desired | Max | Metric |
|-----------|-----|---------|-----|--------|
| EC2 ASG | 2 | 2 | 10 | CPU 70% |
| Aurora | 1 Writer + 1 Reader | - | Add readers | CPU/Connections |
| ElastiCache | 2 nodes | - | 6 nodes | Memory/CPU |

## EC2 Auto Scaling

### View Current Status

```bash
# Check ASG status
aws autoscaling describe-auto-scaling-groups \
    --auto-scaling-group-names production-networkscan-asg \
    --query 'AutoScalingGroups[0].[MinSize,DesiredCapacity,MaxSize,Instances[*].HealthStatus]'

# Check scaling activities
aws autoscaling describe-scaling-activities \
    --auto-scaling-group-name production-networkscan-asg \
    --max-items 10
```

### Manual Scaling

#### Increase Capacity

```bash
# Increase desired capacity
aws autoscaling set-desired-capacity \
    --auto-scaling-group-name production-networkscan-asg \
    --desired-capacity 4

# Or update all limits
aws autoscaling update-auto-scaling-group \
    --auto-scaling-group-name production-networkscan-asg \
    --min-size 4 \
    --desired-capacity 6 \
    --max-size 12
```

#### Decrease Capacity

```bash
# Decrease gradually (recommended)
aws autoscaling set-desired-capacity \
    --auto-scaling-group-name production-networkscan-asg \
    --desired-capacity 2

# Instance will be terminated respecting cooldown period
```

### Scaling Policies

#### View Current Policies

```bash
aws autoscaling describe-policies \
    --auto-scaling-group-name production-networkscan-asg
```

#### Modify Target Tracking Policy

```bash
# Lower threshold for more aggressive scaling
aws autoscaling put-scaling-policy \
    --auto-scaling-group-name production-networkscan-asg \
    --policy-name production-networkscan-cpu-target \
    --policy-type TargetTrackingScaling \
    --target-tracking-configuration '{
        "PredefinedMetricSpecification": {
            "PredefinedMetricType": "ASGAverageCPUUtilization"
        },
        "TargetValue": 60.0,
        "ScaleOutCooldown": 180,
        "ScaleInCooldown": 300
    }'
```

### Scheduled Scaling

```bash
# Scale up before peak hours (8 AM)
aws autoscaling put-scheduled-update-group-action \
    --auto-scaling-group-name production-networkscan-asg \
    --scheduled-action-name scale-up-morning \
    --recurrence "0 8 * * *" \
    --min-size 4 \
    --desired-capacity 4

# Scale down after peak hours (8 PM)
aws autoscaling put-scheduled-update-group-action \
    --auto-scaling-group-name production-networkscan-asg \
    --scheduled-action-name scale-down-evening \
    --recurrence "0 20 * * *" \
    --min-size 2 \
    --desired-capacity 2
```

## Aurora Database Scaling

### Vertical Scaling (Instance Size)

```bash
# List available instance classes
aws rds describe-orderable-db-instance-options \
    --engine aurora-mysql \
    --query 'OrderableDBInstanceOptions[*].DBInstanceClass' \
    --output text | tr '\t' '\n' | sort -u

# Modify writer instance (causes failover)
aws rds modify-db-instance \
    --db-instance-identifier production-networkscan-primary \
    --db-instance-class db.r6g.xlarge \
    --apply-immediately

# Modify reader instance
aws rds modify-db-instance \
    --db-instance-identifier production-networkscan-replica \
    --db-instance-class db.r6g.xlarge \
    --apply-immediately
```

### Horizontal Scaling (Add Readers)

```bash
# Add a read replica
aws rds create-db-instance \
    --db-instance-identifier production-networkscan-replica-2 \
    --db-cluster-identifier production-networkscan-cluster \
    --db-instance-class db.r6g.large \
    --engine aurora-mysql \
    --availability-zone us-east-1b

# Remove a read replica (when scaling down)
aws rds delete-db-instance \
    --db-instance-identifier production-networkscan-replica-2 \
    --skip-final-snapshot
```

### Aurora Serverless v2 (Future Option)

```bash
# Convert to Serverless v2 for automatic scaling
aws rds modify-db-cluster \
    --db-cluster-identifier production-networkscan-cluster \
    --serverless-v2-scaling-configuration MinCapacity=0.5,MaxCapacity=16
```

## ElastiCache Redis Scaling

### View Current Configuration

```bash
aws elasticache describe-replication-groups \
    --replication-group-id production-networkscan-redis \
    --query 'ReplicationGroups[0].[NodeGroups[0].NodeGroupMembers[*].[CacheClusterId,CurrentRole]]'
```

### Vertical Scaling (Node Type)

```bash
# Modify node type (causes brief interruption)
aws elasticache modify-replication-group \
    --replication-group-id production-networkscan-redis \
    --cache-node-type cache.r6g.xlarge \
    --apply-immediately
```

### Horizontal Scaling (Add Replicas)

```bash
# Increase replica count
aws elasticache increase-replica-count \
    --replication-group-id production-networkscan-redis \
    --new-replica-count 3 \
    --apply-immediately

# Decrease replica count
aws elasticache decrease-replica-count \
    --replication-group-id production-networkscan-redis \
    --new-replica-count 1 \
    --apply-immediately
```

## SQS Queue Scaling

### Lambda Concurrency (for queue processors)

```bash
# View current concurrency
aws lambda get-function-configuration \
    --function-name production-networkscan-scan-processor \
    --query 'ReservedConcurrentExecutions'

# Increase reserved concurrency
aws lambda put-function-concurrency \
    --function-name production-networkscan-scan-processor \
    --reserved-concurrent-executions 100
```

## CloudFront Scaling

CloudFront scales automatically. For high-traffic events:

```bash
# Request origin shield (reduces origin load)
aws cloudfront update-distribution \
    --id <distribution-id> \
    --distribution-config file://cf-config-with-origin-shield.json

# Increase cache TTL temporarily
# Update cache policy or behaviors
```

## Capacity Planning

### Current Usage Metrics

```bash
# EC2 CPU (last 7 days)
aws cloudwatch get-metric-statistics \
    --namespace AWS/EC2 \
    --metric-name CPUUtilization \
    --dimensions Name=AutoScalingGroupName,Value=production-networkscan-asg \
    --start-time $(date -u -d '7 days ago' +%Y-%m-%dT%H:%M:%SZ) \
    --end-time $(date -u +%Y-%m-%dT%H:%M:%SZ) \
    --period 3600 \
    --statistics Maximum

# Aurora connections (last 7 days)
aws cloudwatch get-metric-statistics \
    --namespace AWS/RDS \
    --metric-name DatabaseConnections \
    --dimensions Name=DBClusterIdentifier,Value=production-networkscan-cluster \
    --start-time $(date -u -d '7 days ago' +%Y-%m-%dT%H:%M:%SZ) \
    --end-time $(date -u +%Y-%m-%dT%H:%M:%SZ) \
    --period 3600 \
    --statistics Maximum
```

### Scaling Thresholds

| Metric | Warning | Scale Up | Scale Down |
|--------|---------|----------|------------|
| EC2 CPU | 60% | 70% | 30% |
| Aurora CPU | 70% | 80% | 20% |
| Aurora Connections | 600 | 800 | 200 |
| Redis Memory | 70% | 80% | 40% |
| Redis CPU | 60% | 70% | 20% |

## Pre-Event Scaling

For planned high-traffic events:

### 1 Week Before

- [ ] Review capacity requirements
- [ ] Test scaling in staging
- [ ] Increase ASG max if needed
- [ ] Add Aurora read replicas
- [ ] Warm up CloudFront cache

### 1 Day Before

- [ ] Scale to anticipated capacity
- [ ] Verify all health checks
- [ ] Enable enhanced monitoring
- [ ] Notify on-call team

### During Event

- [ ] Monitor dashboards
- [ ] Be ready for manual intervention
- [ ] Document any issues

### After Event

- [ ] Gradually scale down
- [ ] Review metrics and costs
- [ ] Update capacity planning docs

## Cost Considerations

### Spot Instances for Non-Production

```bash
# Use mixed instances policy with spot
aws autoscaling update-auto-scaling-group \
    --auto-scaling-group-name staging-networkscan-asg \
    --mixed-instances-policy '{
        "LaunchTemplate": {
            "LaunchTemplateSpecification": {
                "LaunchTemplateId": "<template-id>",
                "Version": "$Latest"
            },
            "Overrides": [
                {"InstanceType": "t3.large"},
                {"InstanceType": "t3a.large"},
                {"InstanceType": "m5.large"}
            ]
        },
        "InstancesDistribution": {
            "OnDemandBaseCapacity": 1,
            "OnDemandPercentageAboveBaseCapacity": 0,
            "SpotAllocationStrategy": "capacity-optimized"
        }
    }'
```

### Reserved Instances

For predictable baseline capacity:
- Reserve Aurora instances (1-year or 3-year)
- Reserve ElastiCache nodes
- Consider EC2 Savings Plans for compute

## Emergency Scaling Procedure

For unexpected traffic spikes:

```bash
# 1. Immediately increase capacity
aws autoscaling set-desired-capacity \
    --auto-scaling-group-name production-networkscan-asg \
    --desired-capacity 10

# 2. Increase max if needed
aws autoscaling update-auto-scaling-group \
    --auto-scaling-group-name production-networkscan-asg \
    --max-size 20

# 3. Add database readers
aws rds create-db-instance \
    --db-instance-identifier production-networkscan-replica-emergency \
    --db-cluster-identifier production-networkscan-cluster \
    --db-instance-class db.r6g.xlarge \
    --engine aurora-mysql

# 4. Enable WAF rate limiting if under attack
# Update rate limit rule to lower threshold
```

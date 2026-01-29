# NetworkScanScada Deployment Runbook

## Overview

This runbook covers the deployment procedures for NetworkScanScada on AWS infrastructure.

## Prerequisites

- AWS CLI configured with appropriate credentials
- Access to the AWS account with necessary IAM permissions
- Git access to the repository
- CodeStar connection to GitHub configured

## Deployment Methods

### 1. Automated Deployment (CI/CD Pipeline)

The preferred method for production deployments.

#### Triggering a Deployment

1. Push changes to the configured branch (default: `main`)
2. The CodePipeline automatically:
   - Pulls source code from GitHub
   - Runs unit tests
   - Builds the application
   - Deploys to the Auto Scaling Group

#### Monitoring Pipeline Status

```bash
# Check pipeline status
aws codepipeline get-pipeline-state \
    --name production-networkscan-pipeline \
    --region us-east-1

# View recent executions
aws codepipeline list-pipeline-executions \
    --pipeline-name production-networkscan-pipeline \
    --max-items 5 \
    --region us-east-1
```

### 2. Manual Deployment

For emergency deployments or when CI/CD is unavailable.

#### Step 1: Connect to an EC2 Instance

```bash
# Get instance IDs
aws ec2 describe-instances \
    --filters "Name=tag:Environment,Values=production" \
              "Name=instance-state-name,Values=running" \
    --query 'Reservations[*].Instances[*].InstanceId' \
    --output text

# Connect via Session Manager
aws ssm start-session --target <instance-id>
```

#### Step 2: Deploy Code

```bash
cd /var/www/networkscan

# Pull latest code
sudo -u networkscan git fetch origin
sudo -u networkscan git checkout main
sudo -u networkscan git pull

# Install dependencies
sudo -u networkscan composer install --no-dev --optimize-autoloader
sudo -u networkscan npm ci --production

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Restart PHP-FPM
sudo systemctl restart php-fpm
```

## Rollback Procedures

### Automated Rollback

CodeDeploy automatically rolls back on:
- Deployment failure
- CloudWatch alarm triggers (5xx errors, unhealthy hosts)

### Manual Rollback

#### Option 1: CodeDeploy Rollback

```bash
# List recent deployments
aws deploy list-deployments \
    --application-name production-networkscan \
    --deployment-group-name production-networkscan-deploy \
    --max-items 5

# Rollback to previous deployment
aws deploy create-deployment \
    --application-name production-networkscan \
    --deployment-group-name production-networkscan-deploy \
    --revision revisionType=S3,s3Location='{bucket=<artifact-bucket>,key=<previous-artifact>,bundleType=zip}'
```

#### Option 2: Git Rollback

```bash
# On each instance
cd /var/www/networkscan

# Identify previous good commit
git log --oneline -10

# Checkout previous commit
sudo -u networkscan git checkout <commit-hash>

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php-fpm
```

## Database Migrations

### Running Migrations

```bash
# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate --force

# Rollback last batch (if needed)
php artisan migrate:rollback --step=1
```

### Migration Best Practices

1. Always backup database before migrations
2. Test migrations in staging first
3. Avoid destructive migrations in production
4. Use separate migration for data transformations

## Health Checks

### Application Health

```bash
# Check ALB target health
aws elbv2 describe-target-health \
    --target-group-arn <target-group-arn>

# Check individual instance
curl -f http://localhost/health
```

### Service Status

```bash
# On EC2 instance
systemctl status nginx
systemctl status php-fpm
systemctl status codedeploy-agent
systemctl status amazon-cloudwatch-agent
```

## Deployment Checklist

### Pre-Deployment

- [ ] All tests passing in staging
- [ ] Database migrations tested
- [ ] Feature flags configured
- [ ] Rollback plan prepared
- [ ] On-call team notified

### During Deployment

- [ ] Monitor CloudWatch dashboards
- [ ] Watch for ALB unhealthy hosts
- [ ] Check application error rates
- [ ] Verify key functionality

### Post-Deployment

- [ ] Verify all health checks passing
- [ ] Run smoke tests
- [ ] Check error logs
- [ ] Update deployment log
- [ ] Notify stakeholders

## Troubleshooting

### Deployment Stuck

```bash
# Check CodeDeploy agent logs
sudo tail -f /var/log/aws/codedeploy-agent/codedeploy-agent.log

# Check deployment logs
sudo cat /opt/codedeploy-agent/deployment-root/<deployment-id>/logs/scripts.log
```

### Instance Not Receiving Traffic

1. Check target group health
2. Verify security group rules
3. Check nginx and php-fpm status
4. Review application logs

### Database Connection Issues

```bash
# Test database connectivity
mysql -h <aurora-endpoint> -u admin -p

# Check secrets
aws secretsmanager get-secret-value \
    --secret-id networkscan/production/database-config
```

## Emergency Contacts

| Role | Contact |
|------|---------|
| On-Call Engineer | oncall@example.com |
| Database Admin | dba@example.com |
| Security Team | security@example.com |
| AWS Support | via AWS Console |

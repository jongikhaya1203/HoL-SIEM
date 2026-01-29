# NetworkScanScada Troubleshooting Guide

## Common Issues and Solutions

### 1. Application Not Responding

#### Symptoms
- HTTP 502/503/504 errors
- Page timeouts
- ALB health check failures

#### Diagnostic Steps

```bash
# 1. Check ALB target health
aws elbv2 describe-target-health \
    --target-group-arn <target-group-arn>

# 2. Check EC2 instance status
aws ec2 describe-instance-status \
    --instance-ids <instance-id>

# 3. Connect to instance
aws ssm start-session --target <instance-id>

# 4. Check services
systemctl status nginx
systemctl status php-fpm

# 5. Check application logs
tail -100 /var/www/networkscan/storage/logs/laravel.log
tail -100 /var/log/nginx/error.log
tail -100 /var/log/php-fpm/www-error.log
```

#### Common Fixes

**PHP-FPM not running:**
```bash
sudo systemctl restart php-fpm
```

**Nginx configuration error:**
```bash
sudo nginx -t
sudo systemctl restart nginx
```

**Disk space full:**
```bash
df -h
# Clear old logs
sudo find /var/log -name "*.log" -mtime +7 -delete
# Clear application cache
php artisan cache:clear
```

---

### 2. Database Connection Errors

#### Symptoms
- "Connection refused" errors
- "Too many connections" errors
- Slow queries/timeouts

#### Diagnostic Steps

```bash
# 1. Check Aurora cluster status
aws rds describe-db-clusters \
    --db-cluster-identifier production-networkscan-cluster \
    --query 'DBClusters[0].[Status,Endpoint]'

# 2. Test connectivity from EC2
mysql -h <aurora-endpoint> -u admin -p -e "SELECT 1"

# 3. Check connection count
mysql -e "SHOW STATUS LIKE 'Threads_connected';"

# 4. Check slow queries
mysql -e "SHOW PROCESSLIST;" | head -20
```

#### Common Fixes

**Connection pool exhausted:**
```bash
# Check and kill long-running queries
mysql -e "SELECT * FROM information_schema.processlist WHERE time > 300;"
mysql -e "KILL <process-id>;"
```

**Security group blocking:**
```bash
aws ec2 describe-security-groups \
    --group-ids <db-security-group-id> \
    --query 'SecurityGroups[0].IpPermissions'
```

**Reset connection in application:**
```bash
php artisan config:clear
php artisan cache:clear
```

---

### 3. Redis/Session Issues

#### Symptoms
- Users logged out unexpectedly
- Session data lost
- Slow page loads

#### Diagnostic Steps

```bash
# 1. Check Redis cluster status
aws elasticache describe-replication-groups \
    --replication-group-id production-networkscan-redis

# 2. Test Redis connectivity
redis-cli -h <redis-endpoint> -p 6379 --tls PING

# 3. Check memory usage
redis-cli -h <redis-endpoint> INFO memory

# 4. Check evictions
redis-cli -h <redis-endpoint> INFO stats | grep evicted
```

#### Common Fixes

**High memory usage:**
```bash
# Check large keys
redis-cli -h <redis-endpoint> --bigkeys

# Clear specific cache
redis-cli -h <redis-endpoint> KEYS "cache:*" | xargs redis-cli DEL
```

**Connection issues:**
```bash
# Verify auth token
aws secretsmanager get-secret-value \
    --secret-id networkscan/redis-auth \
    --query 'SecretString'
```

---

### 4. High CPU/Memory Usage

#### Symptoms
- Slow response times
- Auto scaling triggered
- CloudWatch CPU alarms

#### Diagnostic Steps

```bash
# Connect to instance
aws ssm start-session --target <instance-id>

# Check processes
top -c
htop

# Check PHP processes
ps aux | grep php-fpm | wc -l

# Check memory usage
free -m
```

#### Common Fixes

**PHP-FPM process buildup:**
```bash
# Check for stuck processes
ps aux | grep php-fpm | grep -v grep

# Restart PHP-FPM
sudo systemctl restart php-fpm

# Adjust pool settings if needed
sudo vim /etc/php-fpm.d/www.conf
# Modify pm.max_children, pm.start_servers, etc.
```

**Memory leak investigation:**
```bash
# Check memory growth over time
while true; do free -m | grep Mem; sleep 60; done

# Profile PHP memory
php artisan tinker --execute="echo memory_get_peak_usage(true);"
```

---

### 5. Slow Page Loads

#### Symptoms
- High response times in CloudWatch
- User complaints
- ALB latency alarms

#### Diagnostic Steps

```bash
# 1. Check ALB latency metrics
aws cloudwatch get-metric-statistics \
    --namespace AWS/ApplicationELB \
    --metric-name TargetResponseTime \
    --dimensions Name=LoadBalancer,Value=<alb-name> \
    --start-time $(date -u -d '1 hour ago' +%Y-%m-%dT%H:%M:%SZ) \
    --end-time $(date -u +%Y-%m-%dT%H:%M:%SZ) \
    --period 60 \
    --statistics Average

# 2. Check slow query log
mysql -e "SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;"

# 3. Check PHP-FPM slow log
tail -50 /var/log/php-fpm/www-slow.log

# 4. Check network latency
ping -c 10 <aurora-endpoint>
```

#### Common Fixes

**Database query optimization:**
```sql
-- Check missing indexes
EXPLAIN SELECT * FROM your_slow_query;

-- Add index
ALTER TABLE table_name ADD INDEX idx_column (column_name);
```

**Enable OPcache:**
```bash
# Verify OPcache is enabled
php -i | grep opcache

# Check OPcache status
php -r "print_r(opcache_get_status());"
```

**Check for N+1 queries:**
```bash
# Enable query logging temporarily
tail -f /var/www/networkscan/storage/logs/query.log
```

---

### 6. Deployment Failures

#### Symptoms
- CodePipeline failure notification
- CodeDeploy timeout
- Instances not registering with ALB

#### Diagnostic Steps

```bash
# 1. Check CodePipeline status
aws codepipeline get-pipeline-state \
    --name production-networkscan-pipeline

# 2. Check CodeDeploy deployment
aws deploy get-deployment \
    --deployment-id <deployment-id>

# 3. Check CodeDeploy agent on instance
sudo cat /var/log/aws/codedeploy-agent/codedeploy-agent.log

# 4. Check deployment scripts
sudo cat /opt/codedeploy-agent/deployment-root/<deployment-id>/logs/scripts.log
```

#### Common Fixes

**Deployment hook timeout:**
```bash
# Check hook script execution time
# Increase timeout in appspec.yml if needed
```

**Permission issues:**
```bash
# Fix file permissions
sudo chown -R networkscan:networkscan /var/www/networkscan
sudo chmod -R 755 /var/www/networkscan
```

---

### 7. WAF Blocking Legitimate Traffic

#### Symptoms
- Users reporting 403 errors
- Rate limit errors
- Geographic blocks

#### Diagnostic Steps

```bash
# Check WAF logs in CloudWatch
aws logs filter-log-events \
    --log-group-name aws-waf-logs-production-networkscan \
    --filter-pattern '{ $.action = "BLOCK" }' \
    --start-time $(date -u -d '1 hour ago' +%s)000

# Check blocked IPs
aws wafv2 get-sampled-requests \
    --web-acl-arn <waf-arn> \
    --rule-metric-name RateLimitRule \
    --scope REGIONAL \
    --time-window StartTime=$(date -u -d '1 hour ago' +%Y-%m-%dT%H:%M:%SZ),EndTime=$(date -u +%Y-%m-%dT%H:%M:%SZ) \
    --max-items 100
```

#### Common Fixes

**Add IP to allowlist:**
```bash
aws wafv2 update-ip-set \
    --name production-allowed-ips \
    --scope REGIONAL \
    --id <ip-set-id> \
    --addresses "1.2.3.4/32" \
    --lock-token <lock-token>
```

**Adjust rate limit:**
```bash
# Update WAF rule with higher limit
# Edit 08-waf.yaml and redeploy
```

---

## Log Locations

| Log | Location |
|-----|----------|
| Application | `/var/www/networkscan/storage/logs/` |
| Nginx Access | `/var/log/nginx/access.log` |
| Nginx Error | `/var/log/nginx/error.log` |
| PHP-FPM | `/var/log/php-fpm/www-error.log` |
| PHP-FPM Slow | `/var/log/php-fpm/www-slow.log` |
| CodeDeploy | `/var/log/aws/codedeploy-agent/` |
| CloudWatch Agent | `/var/log/amazon/amazon-cloudwatch-agent/` |
| System | `/var/log/messages` |

## Useful CloudWatch Insights Queries

### Error Rate by Endpoint
```
fields @timestamp, @message
| filter @message like /ERROR/
| stats count() by bin(5m)
```

### Slow Requests
```
fields @timestamp, request, response_time
| filter response_time > 2
| sort response_time desc
| limit 100
```

### Failed Logins
```
fields @timestamp, @message
| filter @message like /login/ and @message like /failed/
| stats count() by bin(1h)
```

## Support Escalation

If issues persist after troubleshooting:

1. Gather all relevant logs
2. Document steps taken
3. Create support ticket with:
   - Timeline of events
   - Impact assessment
   - Logs and screenshots
   - Attempted solutions

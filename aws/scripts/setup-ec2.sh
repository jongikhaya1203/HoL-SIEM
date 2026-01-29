#!/bin/bash
# NetworkScanScada EC2 Instance Setup Script
# This script is run via UserData on EC2 instance launch

set -e

# Logging
exec > >(tee /var/log/networkscan-setup.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "=========================================="
echo "NetworkScanScada EC2 Setup Script"
echo "Started at: $(date)"
echo "=========================================="

# Configuration
APP_USER="networkscan"
APP_DIR="/var/www/networkscan"
PHP_VERSION="8.2"
NODE_VERSION="18"

# Get environment from instance tags
INSTANCE_ID=$(curl -s http://169.254.169.254/latest/meta-data/instance-id)
REGION=$(curl -s http://169.254.169.254/latest/meta-data/placement/region)
ENVIRONMENT=$(aws ec2 describe-tags --region $REGION --filters "Name=resource-id,Values=$INSTANCE_ID" "Name=key,Values=Environment" --query 'Tags[0].Value' --output text)

echo "Instance ID: $INSTANCE_ID"
echo "Region: $REGION"
echo "Environment: $ENVIRONMENT"

# ==================== System Updates ====================
echo "Updating system packages..."
yum update -y
yum install -y amazon-linux-extras

# ==================== Install PHP ====================
echo "Installing PHP $PHP_VERSION..."
amazon-linux-extras enable php$PHP_VERSION
yum clean metadata
yum install -y \
    php \
    php-fpm \
    php-mysqlnd \
    php-pdo \
    php-xml \
    php-json \
    php-mbstring \
    php-curl \
    php-zip \
    php-gd \
    php-intl \
    php-opcache \
    php-redis \
    php-bcmath \
    php-soap

# ==================== Install Nginx ====================
echo "Installing Nginx..."
amazon-linux-extras install nginx1 -y

# ==================== Install Node.js ====================
echo "Installing Node.js $NODE_VERSION..."
curl -fsSL https://rpm.nodesource.com/setup_${NODE_VERSION}.x | bash -
yum install -y nodejs

# ==================== Install Additional Tools ====================
echo "Installing additional tools..."
yum install -y \
    git \
    jq \
    nmap \
    htop \
    telnet \
    nc

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ==================== Install CloudWatch Agent ====================
echo "Installing CloudWatch Agent..."
yum install -y amazon-cloudwatch-agent

# ==================== Install CodeDeploy Agent ====================
echo "Installing CodeDeploy Agent..."
yum install -y ruby wget
cd /tmp
wget https://aws-codedeploy-$REGION.s3.$REGION.amazonaws.com/latest/install
chmod +x ./install
./install auto
systemctl enable codedeploy-agent
systemctl start codedeploy-agent

# ==================== Create Application User ====================
echo "Creating application user..."
useradd -r -s /sbin/nologin $APP_USER || true
mkdir -p $APP_DIR
chown -R $APP_USER:$APP_USER $APP_DIR

# ==================== Configure PHP-FPM ====================
echo "Configuring PHP-FPM..."
cat > /etc/php-fpm.d/www.conf << 'EOF'
[www]
user = networkscan
group = networkscan
listen = /run/php-fpm/www.sock
listen.owner = nginx
listen.group = nginx
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; Process priority
process.priority = -10

; Logging
php_admin_value[error_log] = /var/log/php-fpm/www-error.log
php_admin_flag[log_errors] = on
slowlog = /var/log/php-fpm/www-slow.log
request_slowlog_timeout = 5s

; Resource limits
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
php_admin_value[upload_max_filesize] = 50M
php_admin_value[post_max_size] = 50M

; Security
php_admin_value[expose_php] = Off
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; OPcache
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 128
php_admin_value[opcache.interned_strings_buffer] = 16
php_admin_value[opcache.max_accelerated_files] = 10000
php_admin_value[opcache.validate_timestamps] = 0
php_admin_value[opcache.revalidate_freq] = 0
EOF

mkdir -p /var/log/php-fpm
chown -R $APP_USER:$APP_USER /var/log/php-fpm

# ==================== Configure Nginx ====================
echo "Configuring Nginx..."
cat > /etc/nginx/nginx.conf << 'EOF'
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /run/nginx.pid;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for" '
                    '$request_time $upstream_response_time';

    access_log /var/log/nginx/access.log main;

    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 4096;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Hide server version
    server_tokens off;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=30r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    include /etc/nginx/conf.d/*.conf;
}
EOF

cat > /etc/nginx/conf.d/networkscan.conf << 'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/networkscan/public;
    index index.php index.html;

    # Health check endpoint for ALB
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # Static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt|woff|woff2|ttf|eot|svg)$ {
        expires 30d;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    # PHP processing
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Laravel/PHP application routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API rate limiting
    location /api/ {
        limit_req zone=api burst=50 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Login rate limiting
    location /login {
        limit_req zone=login burst=3 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\.(ht|git|env|svn) {
        deny all;
    }

    location ~ /storage/ {
        deny all;
    }
}
EOF

# ==================== Configure CloudWatch Agent ====================
echo "Configuring CloudWatch Agent..."
cat > /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.json << EOF
{
    "agent": {
        "metrics_collection_interval": 60,
        "run_as_user": "root"
    },
    "logs": {
        "logs_collected": {
            "files": {
                "collect_list": [
                    {
                        "file_path": "/var/log/nginx/access.log",
                        "log_group_name": "/networkscan/${ENVIRONMENT}/nginx/access",
                        "log_stream_name": "{instance_id}",
                        "timezone": "UTC"
                    },
                    {
                        "file_path": "/var/log/nginx/error.log",
                        "log_group_name": "/networkscan/${ENVIRONMENT}/nginx/error",
                        "log_stream_name": "{instance_id}",
                        "timezone": "UTC"
                    },
                    {
                        "file_path": "/var/log/php-fpm/www-error.log",
                        "log_group_name": "/networkscan/${ENVIRONMENT}/php-fpm",
                        "log_stream_name": "{instance_id}",
                        "timezone": "UTC"
                    },
                    {
                        "file_path": "${APP_DIR}/storage/logs/*.log",
                        "log_group_name": "/networkscan/${ENVIRONMENT}/application",
                        "log_stream_name": "{instance_id}",
                        "timezone": "UTC"
                    }
                ]
            }
        }
    },
    "metrics": {
        "aggregation_dimensions": [["AutoScalingGroupName"], ["InstanceId"]],
        "append_dimensions": {
            "AutoScalingGroupName": "\${aws:AutoScalingGroupName}",
            "InstanceId": "\${aws:InstanceId}"
        },
        "metrics_collected": {
            "cpu": {
                "measurement": ["cpu_usage_idle", "cpu_usage_user", "cpu_usage_system"],
                "metrics_collection_interval": 60,
                "totalcpu": true
            },
            "disk": {
                "measurement": ["used_percent", "inodes_free"],
                "metrics_collection_interval": 60,
                "resources": ["/"]
            },
            "diskio": {
                "measurement": ["io_time", "write_bytes", "read_bytes"],
                "metrics_collection_interval": 60,
                "resources": ["*"]
            },
            "mem": {
                "measurement": ["mem_used_percent", "mem_available_percent"],
                "metrics_collection_interval": 60
            },
            "netstat": {
                "measurement": ["tcp_established", "tcp_time_wait"],
                "metrics_collection_interval": 60
            }
        }
    }
}
EOF

# ==================== Fetch Secrets and Configure Application ====================
echo "Fetching secrets from AWS Secrets Manager..."

# Get database credentials
DB_SECRET=$(aws secretsmanager get-secret-value --secret-id "networkscan/${ENVIRONMENT}/database-config" --region $REGION --query SecretString --output text)
DB_HOST=$(echo $DB_SECRET | jq -r '.host // empty')
DB_NAME=$(echo $DB_SECRET | jq -r '.database // empty')

# Get application secrets
APP_SECRET=$(aws secretsmanager get-secret-value --secret-id "networkscan/${ENVIRONMENT}/application" --region $REGION --query SecretString --output text 2>/dev/null || echo "{}")
APP_KEY=$(echo $APP_SECRET | jq -r '.APP_KEY // empty')

# Get Redis endpoint from SSM
REDIS_ENDPOINT=$(aws ssm get-parameter --name "/${ENVIRONMENT}/redis/endpoint" --region $REGION --query Parameter.Value --output text 2>/dev/null || echo "")

# Create environment file
echo "Creating application environment file..."
cat > $APP_DIR/.env << EOF
APP_NAME="NetworkScan SCADA"
APP_ENV=${ENVIRONMENT}
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://networkscan.example.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=admin

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=sqs

REDIS_HOST=${REDIS_ENDPOINT}
REDIS_PASSWORD=null
REDIS_PORT=6379

AWS_DEFAULT_REGION=${REGION}
AWS_BUCKET=${ENVIRONMENT}-networkscan-reports

SQS_PREFIX=https://sqs.${REGION}.amazonaws.com
SQS_QUEUE=${ENVIRONMENT}-networkscan-scan-jobs
EOF

chown $APP_USER:$APP_USER $APP_DIR/.env
chmod 600 $APP_DIR/.env

# ==================== Start Services ====================
echo "Starting services..."
systemctl enable php-fpm
systemctl enable nginx
systemctl enable amazon-cloudwatch-agent

systemctl start php-fpm
systemctl start nginx
systemctl start amazon-cloudwatch-agent

# ==================== Signal CloudFormation ====================
echo "Signaling CloudFormation..."
/opt/aws/bin/cfn-signal -e $? --stack ${AWS::StackName} --resource AutoScalingGroup --region $REGION || true

echo "=========================================="
echo "NetworkScanScada EC2 Setup Complete"
echo "Finished at: $(date)"
echo "=========================================="

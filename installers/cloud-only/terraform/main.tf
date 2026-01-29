# NetworkScanScada Pure Cloud / SaaS Deployment
# Terraform Configuration for AWS

terraform {
  required_version = ">= 1.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.0"
    }
  }

  backend "s3" {
    # Configure backend in terraform.tfvars
    # bucket = "networkscan-terraform-state"
    # key    = "saas/terraform.tfstate"
    # region = "us-east-1"
  }
}

provider "aws" {
  region = var.region

  default_tags {
    tags = {
      Application = "NetworkScanScada"
      Environment = var.environment_name
      ManagedBy   = "Terraform"
      TenantId    = var.tenant_id
    }
  }
}

# ============================================================
# Local Variables
# ============================================================
locals {
  name_prefix = "nss-${var.environment_name}"

  plan_configs = {
    starter = {
      db_instance_class    = "db.t3.small"
      db_storage           = 20
      db_multi_az          = false
      cache_node_type      = "cache.t3.micro"
      cache_num_nodes      = 1
      ec2_instance_type    = "t3.small"
      ec2_min_size         = 1
      ec2_max_size         = 2
      backup_retention     = 7
    }
    professional = {
      db_instance_class    = "db.r6g.large"
      db_storage           = 100
      db_multi_az          = true
      cache_node_type      = "cache.r6g.large"
      cache_num_nodes      = 2
      ec2_instance_type    = "t3.large"
      ec2_min_size         = 2
      ec2_max_size         = 6
      backup_retention     = 14
    }
    enterprise = {
      db_instance_class    = "db.r6g.xlarge"
      db_storage           = 500
      db_multi_az          = true
      cache_node_type      = "cache.r6g.xlarge"
      cache_num_nodes      = 3
      ec2_instance_type    = "t3.xlarge"
      ec2_min_size         = 3
      ec2_max_size         = 10
      backup_retention     = 35
    }
  }

  config = local.plan_configs[var.plan]
}

# ============================================================
# Data Sources
# ============================================================
data "aws_availability_zones" "available" {
  state = "available"
}

data "aws_caller_identity" "current" {}

# ============================================================
# VPC Module
# ============================================================
module "vpc" {
  source  = "terraform-aws-modules/vpc/aws"
  version = "~> 5.0"

  name = "${local.name_prefix}-vpc"
  cidr = var.vpc_cidr

  azs              = slice(data.aws_availability_zones.available.names, 0, 2)
  public_subnets   = [cidrsubnet(var.vpc_cidr, 4, 0), cidrsubnet(var.vpc_cidr, 4, 1)]
  private_subnets  = [cidrsubnet(var.vpc_cidr, 4, 2), cidrsubnet(var.vpc_cidr, 4, 3)]
  database_subnets = [cidrsubnet(var.vpc_cidr, 4, 4), cidrsubnet(var.vpc_cidr, 4, 5)]

  enable_nat_gateway     = true
  single_nat_gateway     = var.plan == "starter"
  enable_dns_hostnames   = true
  enable_dns_support     = true

  # VPC Flow Logs
  enable_flow_log                      = true
  create_flow_log_cloudwatch_log_group = true
  create_flow_log_cloudwatch_iam_role  = true
  flow_log_max_aggregation_interval    = 60

  tags = {
    Plan = var.plan
  }
}

# ============================================================
# Security Groups
# ============================================================
resource "aws_security_group" "alb" {
  name_prefix = "${local.name_prefix}-alb-"
  description = "Security group for Application Load Balancer"
  vpc_id      = module.vpc.vpc_id

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group" "app" {
  name_prefix = "${local.name_prefix}-app-"
  description = "Security group for application servers"
  vpc_id      = module.vpc.vpc_id

  ingress {
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group" "database" {
  name_prefix = "${local.name_prefix}-db-"
  description = "Security group for Aurora database"
  vpc_id      = module.vpc.vpc_id

  ingress {
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.app.id]
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group" "cache" {
  name_prefix = "${local.name_prefix}-cache-"
  description = "Security group for ElastiCache"
  vpc_id      = module.vpc.vpc_id

  ingress {
    from_port       = 6379
    to_port         = 6379
    protocol        = "tcp"
    security_groups = [aws_security_group.app.id]
  }

  lifecycle {
    create_before_destroy = true
  }
}

# ============================================================
# Aurora MySQL Database
# ============================================================
resource "random_password" "db_password" {
  length  = 32
  special = false
}

resource "aws_db_subnet_group" "main" {
  name       = "${local.name_prefix}-db-subnet"
  subnet_ids = module.vpc.database_subnets

  tags = {
    Name = "${local.name_prefix}-db-subnet-group"
  }
}

resource "aws_rds_cluster" "main" {
  cluster_identifier = "${local.name_prefix}-aurora"

  engine         = "aurora-mysql"
  engine_version = "8.0.mysql_aurora.3.04.0"

  database_name   = "networkscan"
  master_username = "admin"
  master_password = random_password.db_password.result

  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [aws_security_group.database.id]

  storage_encrypted = true

  backup_retention_period = local.config.backup_retention
  preferred_backup_window = "03:00-04:00"

  skip_final_snapshot = var.plan == "starter"

  enabled_cloudwatch_logs_exports = ["audit", "error", "slowquery"]

  tags = {
    Name = "${local.name_prefix}-aurora-cluster"
  }
}

resource "aws_rds_cluster_instance" "main" {
  count = local.config.db_multi_az ? 2 : 1

  identifier         = "${local.name_prefix}-aurora-${count.index}"
  cluster_identifier = aws_rds_cluster.main.id

  instance_class = local.config.db_instance_class
  engine         = aws_rds_cluster.main.engine

  performance_insights_enabled = var.plan != "starter"

  tags = {
    Name = "${local.name_prefix}-aurora-instance-${count.index}"
  }
}

# ============================================================
# ElastiCache Redis
# ============================================================
resource "random_password" "redis_auth" {
  length  = 32
  special = false
}

resource "aws_elasticache_subnet_group" "main" {
  name       = "${local.name_prefix}-cache-subnet"
  subnet_ids = module.vpc.private_subnets
}

resource "aws_elasticache_replication_group" "main" {
  replication_group_id = "${local.name_prefix}-redis"
  description          = "Redis cluster for NetworkScanScada"

  engine               = "redis"
  engine_version       = "7.0"
  node_type            = local.config.cache_node_type
  num_cache_clusters   = local.config.cache_num_nodes

  subnet_group_name  = aws_elasticache_subnet_group.main.name
  security_group_ids = [aws_security_group.cache.id]

  at_rest_encryption_enabled = true
  transit_encryption_enabled = true
  auth_token                 = random_password.redis_auth.result

  automatic_failover_enabled = local.config.cache_num_nodes > 1

  tags = {
    Name = "${local.name_prefix}-redis"
  }
}

# ============================================================
# S3 Buckets
# ============================================================
resource "aws_s3_bucket" "reports" {
  bucket = "${local.name_prefix}-reports-${data.aws_caller_identity.current.account_id}"

  tags = {
    Name = "${local.name_prefix}-reports"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "reports" {
  bucket = aws_s3_bucket.reports.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "reports" {
  bucket = aws_s3_bucket.reports.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# ============================================================
# Secrets Manager
# ============================================================
resource "aws_secretsmanager_secret" "db_credentials" {
  name = "${local.name_prefix}/database"

  tags = {
    Name = "${local.name_prefix}-db-secret"
  }
}

resource "aws_secretsmanager_secret_version" "db_credentials" {
  secret_id = aws_secretsmanager_secret.db_credentials.id
  secret_string = jsonencode({
    username = aws_rds_cluster.main.master_username
    password = random_password.db_password.result
    host     = aws_rds_cluster.main.endpoint
    port     = aws_rds_cluster.main.port
    database = aws_rds_cluster.main.database_name
  })
}

resource "aws_secretsmanager_secret" "redis_credentials" {
  name = "${local.name_prefix}/redis"

  tags = {
    Name = "${local.name_prefix}-redis-secret"
  }
}

resource "aws_secretsmanager_secret_version" "redis_credentials" {
  secret_id = aws_secretsmanager_secret.redis_credentials.id
  secret_string = jsonencode({
    auth_token = random_password.redis_auth.result
    host       = aws_elasticache_replication_group.main.primary_endpoint_address
    port       = 6379
  })
}

# ============================================================
# Application Load Balancer
# ============================================================
resource "aws_lb" "main" {
  name               = "${local.name_prefix}-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = module.vpc.public_subnets

  enable_deletion_protection = var.plan != "starter"

  tags = {
    Name = "${local.name_prefix}-alb"
  }
}

resource "aws_lb_target_group" "main" {
  name     = "${local.name_prefix}-tg"
  port     = 80
  protocol = "HTTP"
  vpc_id   = module.vpc.vpc_id

  health_check {
    enabled             = true
    healthy_threshold   = 2
    interval            = 30
    matcher             = "200"
    path                = "/health"
    port                = "traffic-port"
    protocol            = "HTTP"
    timeout             = 5
    unhealthy_threshold = 3
  }

  tags = {
    Name = "${local.name_prefix}-target-group"
  }
}

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.main.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type = "redirect"
    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}

# Note: HTTPS listener requires ACM certificate
# resource "aws_lb_listener" "https" { ... }

# ============================================================
# Outputs
# ============================================================
output "vpc_id" {
  description = "VPC ID"
  value       = module.vpc.vpc_id
}

output "database_endpoint" {
  description = "Aurora cluster endpoint"
  value       = aws_rds_cluster.main.endpoint
  sensitive   = true
}

output "redis_endpoint" {
  description = "Redis primary endpoint"
  value       = aws_elasticache_replication_group.main.primary_endpoint_address
  sensitive   = true
}

output "alb_dns_name" {
  description = "Application Load Balancer DNS name"
  value       = aws_lb.main.dns_name
}

output "reports_bucket" {
  description = "S3 bucket for reports"
  value       = aws_s3_bucket.reports.bucket
}

output "db_secret_arn" {
  description = "Database credentials secret ARN"
  value       = aws_secretsmanager_secret.db_credentials.arn
}

# NetworkScanScada SaaS - Terraform Variables

variable "environment_name" {
  description = "Environment/tenant name (used as prefix for resources)"
  type        = string

  validation {
    condition     = can(regex("^[a-z0-9-]+$", var.environment_name))
    error_message = "Environment name must contain only lowercase letters, numbers, and hyphens."
  }
}

variable "tenant_id" {
  description = "Unique tenant identifier"
  type        = string
}

variable "admin_email" {
  description = "Administrator email address"
  type        = string

  validation {
    condition     = can(regex("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$", var.admin_email))
    error_message = "Must be a valid email address."
  }
}

variable "plan" {
  description = "Subscription plan (starter, professional, enterprise)"
  type        = string
  default     = "professional"

  validation {
    condition     = contains(["starter", "professional", "enterprise"], var.plan)
    error_message = "Plan must be one of: starter, professional, enterprise."
  }
}

variable "region" {
  description = "AWS region for deployment"
  type        = string
  default     = "us-east-1"

  validation {
    condition = contains([
      "us-east-1", "us-east-2", "us-west-1", "us-west-2",
      "eu-west-1", "eu-west-2", "eu-central-1",
      "ap-southeast-1", "ap-southeast-2", "ap-northeast-1"
    ], var.region)
    error_message = "Region must be a supported AWS region."
  }
}

variable "vpc_cidr" {
  description = "CIDR block for VPC"
  type        = string
  default     = "10.0.0.0/16"

  validation {
    condition     = can(cidrhost(var.vpc_cidr, 0))
    error_message = "Must be a valid CIDR block."
  }
}

variable "domain_name" {
  description = "Custom domain name (optional)"
  type        = string
  default     = ""
}

variable "certificate_arn" {
  description = "ACM certificate ARN for HTTPS (optional)"
  type        = string
  default     = ""
}

variable "enable_waf" {
  description = "Enable AWS WAF protection"
  type        = bool
  default     = true
}

variable "enable_shield" {
  description = "Enable AWS Shield Advanced (enterprise only)"
  type        = bool
  default     = false
}

variable "allowed_ip_ranges" {
  description = "List of allowed IP CIDR ranges for admin access"
  type        = list(string)
  default     = []
}

variable "tags" {
  description = "Additional tags to apply to all resources"
  type        = map(string)
  default     = {}
}

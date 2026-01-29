#!/bin/bash
# NetworkScanScada AWS Deployment Script
# Usage: ./deploy.sh <environment> <action>
# Actions: create, update, delete, validate

set -e

# Configuration
STACK_PREFIX="networkscan"
TEMPLATES_DIR="$(dirname "$0")/../cloudformation"
REGION="${AWS_REGION:-us-east-1}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

usage() {
    echo "Usage: $0 <environment> <action> [options]"
    echo ""
    echo "Environments: production, staging, development"
    echo "Actions: create, update, delete, validate, status"
    echo ""
    echo "Options:"
    echo "  --profile <aws-profile>   AWS CLI profile to use"
    echo "  --region <region>         AWS region (default: us-east-1)"
    echo "  --params <file>           Parameters file (JSON or properties)"
    echo "  --dry-run                 Show what would be done without executing"
    echo ""
    echo "Examples:"
    echo "  $0 production create --profile prod-account"
    echo "  $0 staging update --params staging-params.json"
    echo "  $0 development delete"
    exit 1
}

validate_templates() {
    log_info "Validating CloudFormation templates..."

    for template in "$TEMPLATES_DIR"/*.yaml; do
        template_name=$(basename "$template")
        log_info "Validating $template_name..."
        aws cloudformation validate-template \
            --template-body "file://$template" \
            --region "$REGION" \
            ${PROFILE:+--profile $PROFILE} > /dev/null

        if [ $? -eq 0 ]; then
            echo "  ✓ $template_name is valid"
        else
            log_error "  ✗ $template_name is invalid"
            exit 1
        fi
    done

    log_info "All templates validated successfully!"
}

upload_templates() {
    local bucket_name="${ENVIRONMENT}-networkscan-templates-${AWS_ACCOUNT_ID}"

    log_info "Uploading templates to S3 bucket: $bucket_name"

    # Create bucket if it doesn't exist
    if ! aws s3api head-bucket --bucket "$bucket_name" 2>/dev/null; then
        log_info "Creating S3 bucket for templates..."
        aws s3 mb "s3://$bucket_name" --region "$REGION" ${PROFILE:+--profile $PROFILE}

        # Enable versioning
        aws s3api put-bucket-versioning \
            --bucket "$bucket_name" \
            --versioning-configuration Status=Enabled \
            ${PROFILE:+--profile $PROFILE}
    fi

    # Upload templates
    for template in "$TEMPLATES_DIR"/*.yaml; do
        template_name=$(basename "$template")
        log_info "Uploading $template_name..."
        aws s3 cp "$template" "s3://$bucket_name/$template_name" \
            --region "$REGION" \
            ${PROFILE:+--profile $PROFILE}
    done

    echo "$bucket_name"
}

deploy_stack() {
    local stack_name="$1"
    local template="$2"
    local params="$3"
    local action="$4"

    log_info "Deploying stack: $stack_name"

    local cmd="aws cloudformation ${action}-stack \
        --stack-name $stack_name \
        --template-body file://$template \
        --capabilities CAPABILITY_IAM CAPABILITY_NAMED_IAM CAPABILITY_AUTO_EXPAND \
        --region $REGION \
        ${PROFILE:+--profile $PROFILE}"

    if [ -n "$params" ]; then
        cmd="$cmd --parameters $params"
    fi

    if [ "$DRY_RUN" = true ]; then
        log_info "Dry run - would execute:"
        echo "$cmd"
        return 0
    fi

    eval "$cmd"

    # Wait for stack completion
    log_info "Waiting for stack $stack_name to complete..."
    aws cloudformation wait stack-${action}-complete \
        --stack-name "$stack_name" \
        --region "$REGION" \
        ${PROFILE:+--profile $PROFILE}

    log_info "Stack $stack_name deployment complete!"
}

deploy_phase1() {
    log_info "=== Deploying Phase 1: Foundation ==="

    # VPC
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-vpc" \
        "$TEMPLATES_DIR/01-vpc.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"

    # Security Groups
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-security" \
        "$TEMPLATES_DIR/02-security.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"

    # Database
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-database" \
        "$TEMPLATES_DIR/03-database.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"

    # Cache
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-cache" \
        "$TEMPLATES_DIR/04-cache.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"

    # Secrets
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-secrets" \
        "$TEMPLATES_DIR/05-secrets.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"
}

deploy_phase2() {
    log_info "=== Deploying Phase 2: Application ==="

    # Compute
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-compute" \
        "$TEMPLATES_DIR/06-compute.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"

    # Storage
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-storage" \
        "$TEMPLATES_DIR/07-storage.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"
}

deploy_phase3() {
    log_info "=== Deploying Phase 3: Security ==="

    # WAF
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-waf" \
        "$TEMPLATES_DIR/08-waf.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"
}

deploy_phase4() {
    log_info "=== Deploying Phase 4: Operations ==="

    # Monitoring
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-monitoring" \
        "$TEMPLATES_DIR/09-monitoring.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"

    # CI/CD
    deploy_stack "${ENVIRONMENT}-${STACK_PREFIX}-cicd" \
        "$TEMPLATES_DIR/10-cicd.yaml" \
        "ParameterKey=EnvironmentName,ParameterValue=$ENVIRONMENT" \
        "$ACTION"
}

get_stack_status() {
    log_info "Getting stack status for environment: $ENVIRONMENT"

    local stacks=(
        "vpc" "security" "database" "cache" "secrets"
        "compute" "storage" "waf" "monitoring" "cicd"
    )

    printf "%-40s %-20s %-30s\n" "Stack Name" "Status" "Last Updated"
    printf "%-40s %-20s %-30s\n" "----------" "------" "------------"

    for stack in "${stacks[@]}"; do
        local stack_name="${ENVIRONMENT}-${STACK_PREFIX}-${stack}"
        local status=$(aws cloudformation describe-stacks \
            --stack-name "$stack_name" \
            --query 'Stacks[0].StackStatus' \
            --output text \
            --region "$REGION" \
            ${PROFILE:+--profile $PROFILE} 2>/dev/null || echo "NOT_FOUND")

        local updated=$(aws cloudformation describe-stacks \
            --stack-name "$stack_name" \
            --query 'Stacks[0].LastUpdatedTime' \
            --output text \
            --region "$REGION" \
            ${PROFILE:+--profile $PROFILE} 2>/dev/null || echo "-")

        printf "%-40s %-20s %-30s\n" "$stack_name" "$status" "$updated"
    done
}

delete_stacks() {
    log_info "Deleting all stacks for environment: $ENVIRONMENT"
    log_warn "This will delete all resources! Are you sure? (yes/no)"
    read -r confirmation

    if [ "$confirmation" != "yes" ]; then
        log_info "Deletion cancelled."
        exit 0
    fi

    # Delete in reverse order
    local stacks=(
        "cicd" "monitoring" "waf" "storage" "compute"
        "secrets" "cache" "database" "security" "vpc"
    )

    for stack in "${stacks[@]}"; do
        local stack_name="${ENVIRONMENT}-${STACK_PREFIX}-${stack}"
        log_info "Deleting stack: $stack_name"

        aws cloudformation delete-stack \
            --stack-name "$stack_name" \
            --region "$REGION" \
            ${PROFILE:+--profile $PROFILE} 2>/dev/null || true

        aws cloudformation wait stack-delete-complete \
            --stack-name "$stack_name" \
            --region "$REGION" \
            ${PROFILE:+--profile $PROFILE} 2>/dev/null || true
    done

    log_info "All stacks deleted!"
}

# Parse arguments
ENVIRONMENT=""
ACTION=""
PROFILE=""
PARAMS_FILE=""
DRY_RUN=false

while [[ $# -gt 0 ]]; do
    case $1 in
        production|staging|development)
            ENVIRONMENT="$1"
            shift
            ;;
        create|update|delete|validate|status)
            ACTION="$1"
            shift
            ;;
        --profile)
            PROFILE="$2"
            shift 2
            ;;
        --region)
            REGION="$2"
            shift 2
            ;;
        --params)
            PARAMS_FILE="$2"
            shift 2
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        -h|--help)
            usage
            ;;
        *)
            log_error "Unknown argument: $1"
            usage
            ;;
    esac
done

# Validate arguments
if [ -z "$ENVIRONMENT" ] || [ -z "$ACTION" ]; then
    log_error "Environment and action are required"
    usage
fi

# Get AWS Account ID
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text ${PROFILE:+--profile $PROFILE})
log_info "AWS Account: $AWS_ACCOUNT_ID"
log_info "Environment: $ENVIRONMENT"
log_info "Region: $REGION"
log_info "Action: $ACTION"

# Execute action
case $ACTION in
    validate)
        validate_templates
        ;;
    create|update)
        validate_templates
        deploy_phase1
        deploy_phase2
        deploy_phase3
        deploy_phase4
        log_info "=== Deployment Complete ==="
        get_stack_status
        ;;
    delete)
        delete_stacks
        ;;
    status)
        get_stack_status
        ;;
    *)
        log_error "Unknown action: $ACTION"
        usage
        ;;
esac

#!/bin/bash
# NetworkScanScada Data Migration Script
# Migrates data from on-premises MySQL to Aurora MySQL

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

usage() {
    echo "Usage: $0 <environment> [options]"
    echo ""
    echo "Options:"
    echo "  --source-host <host>       Source MySQL host"
    echo "  --source-port <port>       Source MySQL port (default: 3306)"
    echo "  --source-user <user>       Source MySQL username"
    echo "  --source-pass <password>   Source MySQL password"
    echo "  --source-db <database>     Source database name"
    echo "  --dry-run                  Show what would be done"
    echo "  --schema-only              Migrate schema only, no data"
    echo "  --data-only                Migrate data only, no schema"
    echo ""
    exit 1
}

# Parse arguments
ENVIRONMENT=""
SOURCE_HOST=""
SOURCE_PORT="3306"
SOURCE_USER=""
SOURCE_PASS=""
SOURCE_DB=""
DRY_RUN=false
SCHEMA_ONLY=false
DATA_ONLY=false

while [[ $# -gt 0 ]]; do
    case $1 in
        production|staging|development)
            ENVIRONMENT="$1"
            shift
            ;;
        --source-host)
            SOURCE_HOST="$2"
            shift 2
            ;;
        --source-port)
            SOURCE_PORT="$2"
            shift 2
            ;;
        --source-user)
            SOURCE_USER="$2"
            shift 2
            ;;
        --source-pass)
            SOURCE_PASS="$2"
            shift 2
            ;;
        --source-db)
            SOURCE_DB="$2"
            shift 2
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --schema-only)
            SCHEMA_ONLY=true
            shift
            ;;
        --data-only)
            DATA_ONLY=true
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

# Validate required arguments
if [ -z "$ENVIRONMENT" ] || [ -z "$SOURCE_HOST" ] || [ -z "$SOURCE_USER" ] || [ -z "$SOURCE_DB" ]; then
    log_error "Missing required arguments"
    usage
fi

# Get AWS region
REGION="${AWS_REGION:-us-east-1}"

# Get Aurora endpoint from SSM
log_info "Fetching Aurora endpoint from SSM..."
AURORA_ENDPOINT=$(aws ssm get-parameter --name "/${ENVIRONMENT}/aurora/endpoint" --region $REGION --query Parameter.Value --output text)
AURORA_DB="network_security_scanner"

# Get Aurora credentials from Secrets Manager
log_info "Fetching Aurora credentials from Secrets Manager..."
AURORA_SECRET_ARN=$(aws cloudformation describe-stacks --stack-name "${ENVIRONMENT}-networkscan-database" --query "Stacks[0].Outputs[?OutputKey=='MasterUserSecretArn'].OutputValue" --output text --region $REGION)
AURORA_CREDS=$(aws secretsmanager get-secret-value --secret-id "$AURORA_SECRET_ARN" --region $REGION --query SecretString --output text)
AURORA_USER=$(echo $AURORA_CREDS | jq -r '.username')
AURORA_PASS=$(echo $AURORA_CREDS | jq -r '.password')

log_info "Migration Configuration:"
log_info "  Source: $SOURCE_USER@$SOURCE_HOST:$SOURCE_PORT/$SOURCE_DB"
log_info "  Target: $AURORA_USER@$AURORA_ENDPOINT:3306/$AURORA_DB"

if [ "$DRY_RUN" = true ]; then
    log_warn "DRY RUN MODE - No changes will be made"
fi

# Create backup directory
BACKUP_DIR="/tmp/migration_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# ==================== Export Schema ====================
if [ "$DATA_ONLY" = false ]; then
    log_info "Exporting schema from source database..."

    if [ "$DRY_RUN" = false ]; then
        mysqldump -h "$SOURCE_HOST" -P "$SOURCE_PORT" -u "$SOURCE_USER" ${SOURCE_PASS:+-p"$SOURCE_PASS"} \
            --no-data \
            --routines \
            --triggers \
            --events \
            --single-transaction \
            "$SOURCE_DB" > "$BACKUP_DIR/schema.sql"

        log_info "Schema exported to $BACKUP_DIR/schema.sql"
    fi
fi

# ==================== Export Data ====================
if [ "$SCHEMA_ONLY" = false ]; then
    log_info "Exporting data from source database..."

    # Get list of tables
    TABLES=$(mysql -h "$SOURCE_HOST" -P "$SOURCE_PORT" -u "$SOURCE_USER" ${SOURCE_PASS:+-p"$SOURCE_PASS"} \
        -N -e "SELECT table_name FROM information_schema.tables WHERE table_schema='$SOURCE_DB' AND table_type='BASE TABLE'")

    for TABLE in $TABLES; do
        log_info "  Exporting table: $TABLE"

        if [ "$DRY_RUN" = false ]; then
            mysqldump -h "$SOURCE_HOST" -P "$SOURCE_PORT" -u "$SOURCE_USER" ${SOURCE_PASS:+-p"$SOURCE_PASS"} \
                --no-create-info \
                --single-transaction \
                --quick \
                --extended-insert \
                --disable-keys \
                "$SOURCE_DB" "$TABLE" > "$BACKUP_DIR/data_${TABLE}.sql"
        fi
    done

    log_info "Data exported to $BACKUP_DIR/"
fi

# ==================== Import to Aurora ====================
if [ "$DRY_RUN" = false ]; then
    log_info "Importing to Aurora..."

    # Import schema
    if [ "$DATA_ONLY" = false ]; then
        log_info "Importing schema..."
        mysql -h "$AURORA_ENDPOINT" -u "$AURORA_USER" -p"$AURORA_PASS" "$AURORA_DB" < "$BACKUP_DIR/schema.sql"
    fi

    # Import data
    if [ "$SCHEMA_ONLY" = false ]; then
        log_info "Importing data..."

        # Disable foreign key checks
        mysql -h "$AURORA_ENDPOINT" -u "$AURORA_USER" -p"$AURORA_PASS" "$AURORA_DB" -e "SET FOREIGN_KEY_CHECKS=0;"

        for DATA_FILE in "$BACKUP_DIR"/data_*.sql; do
            if [ -f "$DATA_FILE" ]; then
                TABLE_NAME=$(basename "$DATA_FILE" | sed 's/data_//' | sed 's/.sql//')
                log_info "  Importing table: $TABLE_NAME"
                mysql -h "$AURORA_ENDPOINT" -u "$AURORA_USER" -p"$AURORA_PASS" "$AURORA_DB" < "$DATA_FILE"
            fi
        done

        # Re-enable foreign key checks
        mysql -h "$AURORA_ENDPOINT" -u "$AURORA_USER" -p"$AURORA_PASS" "$AURORA_DB" -e "SET FOREIGN_KEY_CHECKS=1;"
    fi
fi

# ==================== Verification ====================
log_info "Verifying migration..."

# Compare row counts
log_info "Comparing row counts:"
printf "%-40s %15s %15s %10s\n" "Table" "Source" "Target" "Status"
printf "%-40s %15s %15s %10s\n" "-----" "------" "------" "------"

TABLES=$(mysql -h "$SOURCE_HOST" -P "$SOURCE_PORT" -u "$SOURCE_USER" ${SOURCE_PASS:+-p"$SOURCE_PASS"} \
    -N -e "SELECT table_name FROM information_schema.tables WHERE table_schema='$SOURCE_DB' AND table_type='BASE TABLE'")

ALL_MATCH=true
for TABLE in $TABLES; do
    SOURCE_COUNT=$(mysql -h "$SOURCE_HOST" -P "$SOURCE_PORT" -u "$SOURCE_USER" ${SOURCE_PASS:+-p"$SOURCE_PASS"} \
        -N -e "SELECT COUNT(*) FROM $SOURCE_DB.$TABLE" 2>/dev/null || echo "0")

    if [ "$DRY_RUN" = false ]; then
        TARGET_COUNT=$(mysql -h "$AURORA_ENDPOINT" -u "$AURORA_USER" -p"$AURORA_PASS" \
            -N -e "SELECT COUNT(*) FROM $AURORA_DB.$TABLE" 2>/dev/null || echo "0")
    else
        TARGET_COUNT="N/A"
    fi

    if [ "$SOURCE_COUNT" = "$TARGET_COUNT" ]; then
        STATUS="OK"
    else
        STATUS="MISMATCH"
        ALL_MATCH=false
    fi

    printf "%-40s %15s %15s %10s\n" "$TABLE" "$SOURCE_COUNT" "$TARGET_COUNT" "$STATUS"
done

# ==================== Cleanup ====================
log_info "Cleaning up temporary files..."
if [ "$DRY_RUN" = false ]; then
    rm -rf "$BACKUP_DIR"
fi

# ==================== Summary ====================
echo ""
if [ "$ALL_MATCH" = true ]; then
    log_info "Migration completed successfully!"
else
    log_warn "Migration completed with mismatches. Please verify the data."
fi

log_info "Post-migration steps:"
echo "  1. Run application migrations: php artisan migrate"
echo "  2. Update DNS or load balancer to point to new endpoint"
echo "  3. Monitor application logs for any database errors"
echo "  4. Verify all application functionality"

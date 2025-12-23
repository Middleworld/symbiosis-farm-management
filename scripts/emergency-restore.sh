#!/bin/bash

# ===========================================
# EMERGENCY BACKUP RECOVERY SCRIPT
# For when admin.middleworldfarms.org is completely down
# ===========================================

set -e  # Exit on any error

echo "🚨 EMERGENCY BACKUP RECOVERY 🚨"
echo "================================="
echo "This script restores the admin site from backup when the web interface is unavailable"
echo ""

# Configuration
ADMIN_PATH="/opt/sites/admin.middleworldfarms.org"
BACKUP_DIR="$ADMIN_PATH/storage/app/private/backups"
LATEST_BACKUP=""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() { echo -e "${BLUE}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Function to find the latest backup
find_latest_backup() {
    print_status "Looking for the latest backup..."

    if [ ! -d "$BACKUP_DIR" ]; then
        print_error "Backup directory not found: $BACKUP_DIR"
        exit 1
    fi

    # Find the most recent backup file
    LATEST_BACKUP=$(find "$BACKUP_DIR" -name "*.zip" -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -d' ' -f2-)

    if [ -z "$LATEST_BACKUP" ]; then
        print_error "No backup files found in $BACKUP_DIR"
        exit 1
    fi

    BACKUP_SIZE=$(du -h "$LATEST_BACKUP" | cut -f1)
    BACKUP_DATE=$(date -r "$LATEST_BACKUP" '+%Y-%m-%d %H:%M:%S')

    print_success "Found backup: $(basename "$LATEST_BACKUP")"
    print_status "Size: $BACKUP_SIZE"
    print_status "Created: $BACKUP_DATE"
}

# Function to confirm restoration
confirm_restore() {
    echo ""
    print_warning "⚠️  This will restore the admin site from backup!"
    print_warning "Any changes made since $BACKUP_DATE will be lost."
    echo ""
    read -p "Are you sure you want to continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        print_status "Restore cancelled."
        exit 0
    fi
}

# Function to perform the restore
perform_restore() {
    print_status "Starting emergency restore process..."

    # Change to admin directory
    cd "$ADMIN_PATH"

    # Stop any running processes (if applicable)
    print_status "Stopping web services..."
    sudo systemctl stop apache2 2>/dev/null || true
    sudo systemctl stop nginx 2>/dev/null || true

    # Create backup of current state (just in case)
    CURRENT_BACKUP="/tmp/admin-emergency-backup-$(date +%Y%m%d-%H%M%S).tar.gz"
    print_status "Creating safety backup of current state..."
    sudo tar -czf "$CURRENT_BACKUP" -C /opt/sites admin.middleworldfarms.org 2>/dev/null || true

    # Extract the backup
    print_status "Extracting backup files..."
    BACKUP_NAME=$(basename "$LATEST_BACKUP" .zip)

    # Create temporary extraction directory
    TEMP_DIR="/tmp/admin-restore-$BACKUP_NAME"
    mkdir -p "$TEMP_DIR"

    # Extract backup to temp directory
    unzip -q "$LATEST_BACKUP" -d "$TEMP_DIR"

    # Find the application directory in the backup
    APP_DIR=$(find "$TEMP_DIR" -name "admin.middleworldfarms.org" -type d | head -1)

    if [ -z "$APP_DIR" ]; then
        print_error "Could not find application directory in backup"
        exit 1
    fi

    print_status "Found application directory: $APP_DIR"

    # Backup current database configuration
    DB_CONFIG_BACKUP="/tmp/database-config-backup-$(date +%Y%m%d-%H%M%S)"
    if [ -f "$ADMIN_PATH/.env" ]; then
        cp "$ADMIN_PATH/.env" "$DB_CONFIG_BACKUP"
        print_status "Backed up current .env file"
    fi

    # Restore the application files
    print_status "Restoring application files..."
    sudo rsync -a --delete "$APP_DIR/" "$ADMIN_PATH/"

    # Restore database configuration
    if [ -f "$DB_CONFIG_BACKUP" ]; then
        cp "$DB_CONFIG_BACKUP" "$ADMIN_PATH/.env"
        print_status "Restored database configuration"
    fi

    # Fix permissions
    print_status "Fixing file permissions..."
    sudo chown -R www-data:www-data "$ADMIN_PATH"
    sudo chmod -R 755 "$ADMIN_PATH"
    sudo chmod -R 775 "$ADMIN_PATH/storage"
    sudo chmod -R 775 "$ADMIN_PATH/bootstrap/cache"

    # Clean up temp files
    rm -rf "$TEMP_DIR"

    # Restart web services
    print_status "Restarting web services..."
    sudo systemctl start apache2 2>/dev/null || true
    sudo systemctl start nginx 2>/dev/null || true

    print_success "Emergency restore completed!"
    print_status "Safety backup created: $CURRENT_BACKUP"
    print_status "You may want to test the site and then remove the safety backup."
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Emergency backup recovery script for admin.middleworldfarms.org"
    echo ""
    echo "Options:"
    echo "  --yes, -y    Skip confirmation prompt"
    echo "  --help, -h   Show this help message"
    echo ""
    echo "This script will:"
    echo "1. Find the latest backup in $BACKUP_DIR"
    echo "2. Create a safety backup of current state"
    echo "3. Stop web services"
    echo "4. Extract and restore the backup"
    echo "5. Fix file permissions"
    echo "6. Restart web services"
}

# Main script logic
SKIP_CONFIRM=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --yes|-y)
            SKIP_CONFIRM=true
            shift
            ;;
        --help|-h)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Check if running as root or with sudo
if [[ $EUID -eq 0 ]]; then
    print_warning "Running as root - this is fine for emergency recovery"
else
    print_warning "Not running as root. You may need sudo for some operations."
fi

# Main execution
find_latest_backup

if [ "$SKIP_CONFIRM" = false ]; then
    confirm_restore
fi

perform_restore

print_success "🎉 Emergency recovery completed successfully!"
print_status "Please test the admin site at https://admin.middleworldfarms.org"
print_status "If everything works, you can remove the safety backup: $CURRENT_BACKUP"

#!/bin/bash

# Symbiosis Update Deployment Script
# Uses the UpdateTracking system to deploy specific updates

set -e

# Configuration
PRODUCTION_DIR="/opt/sites/admin.middleworldfarms.org"
STAGING_DIR="/opt/sites/admin.soilsync.shop"
LOG_FILE="/var/log/symbiosis-update-deployment.log"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

# Get update details from tracking system
get_update_info() {
    local version="$1"
    local environment="$2"

    cd "$PRODUCTION_DIR"

    # Use Laravel's artisan tinker to get update info
    php artisan tinker --execute="
    \$update = App\Models\UpdateTracking::where('version', '$version')
        ->where('environment', '$environment')
        ->first();

    if (\$update) {
        echo \$update->id . '|' . \$update->title . '|' . implode(',', \$update->files_changed);
    } else {
        echo 'NOT_FOUND';
    }
    "
}

# Deploy specific update
deploy_update() {
    local version="$1"
    local target_env="$2"

    log "Deploying update $version to $target_env..."

    # Get update information
    local update_info=$(get_update_info "$version" "demo")

    if [[ "$update_info" == "NOT_FOUND" ]]; then
        error "Update $version not found in staging environment"
    fi

    # Parse update info
    IFS='|' read -r update_id title files_changed <<< "$update_info"

    log "Update: $title"
    log "Files: $files_changed"

    # Determine target directory
    local target_dir
    if [[ "$target_env" == "production" ]]; then
        target_dir="$PRODUCTION_DIR"
    elif [[ "$target_env" == "staging" ]]; then
        target_dir="$STAGING_DIR"
    else
        error "Invalid target environment: $target_env"
    fi

    cd "$target_dir"

    # Create backup
    log "Creating backup..."
    php artisan backup:run

    # Pull latest changes
    git pull origin "${target_env}"

    # Run migrations if any files changed include migrations
    if [[ "$files_changed" == *"database/migrations"* ]]; then
        log "Running database migrations..."
        php artisan migrate
    fi

    # Clear caches
    log "Clearing caches..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear

    # Optimize
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Log deployment in update tracking
    php artisan tinker --execute="
    App\Services\UpdateTrackingService::logCodeChange(
        '$version',
        '$title',
        'Deployed to $target_env environment',
        explode(',', '$files_changed'),
        null,
        '$target_env'
    );
    "

    success "Update $version deployed to $target_env successfully"
}

# List available updates for deployment
list_updates() {
    local environment="$1"

    cd "$PRODUCTION_DIR"

    log "Available updates for $environment environment:"

    php artisan tinker --execute="
    \$updates = App\Models\UpdateTracking::where('environment', '$environment')
        ->orderBy('applied_at', 'desc')
        ->take(10)
        ->get();

    foreach (\$updates as \$update) {
        echo \$update->version . ' - ' . \$update->title . ' (' . \$update->applied_at->format('Y-m-d H:i') . ')';
    }
    "
}

# Generate deployment package
generate_package() {
    local version="$1"
    local output_dir="${2:-/tmp}"

    log "Generating deployment package for version $version..."

    cd "$PRODUCTION_DIR"

    # Get update info
    local update_info=$(get_update_info "$version" "demo")

    if [[ "$update_info" == "NOT_FOUND" ]]; then
        error "Update $version not found"
    fi

    IFS='|' read -r update_id title files_changed <<< "$update_info"

    # Create package directory
    local package_dir="$output_dir/symbiosis-update-$version"
    mkdir -p "$package_dir"

    # Copy changed files
    IFS=',' read -ra FILE_ARRAY <<< "$files_changed"
    for file in "${FILE_ARRAY[@]}"; do
        if [[ -f "$file" ]]; then
            mkdir -p "$package_dir/$(dirname "$file")"
            cp "$file" "$package_dir/$file"
            log "Copied: $file"
        fi
    done

    # Create deployment script
    cat > "$package_dir/deploy.sh" << 'EOF'
#!/bin/bash
# Auto-generated deployment script

set -e

echo "Deploying Symbiosis Update '$TITLE' (v$VERSION)"

# Backup
php artisan backup:run

# Run migrations if needed
if [[ -d "database/migrations" ]] && [[ $(find database/migrations -name "*.php" | wc -l) -gt 0 ]]; then
    php artisan migrate
fi

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Copy files
find . -name "*.php" -o -name "*.blade.php" | while read file; do
    if [[ -f "$file" ]]; then
        echo "Updating: $file"
        cp "$file" "/path/to/production/$file"
    fi
done

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deployment completed!"
EOF

    # Replace variables in script
    sed -i "s/\$TITLE/$title/g" "$package_dir/deploy.sh"
    sed -i "s/\$VERSION/$version/g" "$package_dir/deploy.sh"

    chmod +x "$package_dir/deploy.sh"

    # Create tarball
    cd "$output_dir"
    tar -czf "symbiosis-update-$version.tar.gz" "symbiosis-update-$version"

    success "Deployment package created: $output_dir/symbiosis-update-$version.tar.gz"
}

# Usage
usage() {
    echo "Symbiosis Update Deployment Script"
    echo ""
    echo "Usage: $0 <command> [options]"
    echo ""
    echo "Commands:"
    echo "  deploy <version> <environment>    Deploy specific update version"
    echo "  list <environment>                List available updates"
    echo "  package <version> [output_dir]    Generate deployment package"
    echo ""
    echo "Environments: staging, production"
    echo ""
    echo "Examples:"
    echo "  $0 deploy 1.0.4 production"
    echo "  $0 list staging"
    echo "  $0 package 1.0.4 /tmp"
}

# Main
main() {
    local command="$1"
    shift

    case "$command" in
        "deploy")
            if [[ $# -lt 2 ]]; then
                error "Usage: $0 deploy <version> <environment>"
            fi
            deploy_update "$1" "$2"
            ;;
        "list")
            if [[ $# -lt 1 ]]; then
                error "Usage: $0 list <environment>"
            fi
            list_updates "$1"
            ;;
        "package")
            if [[ $# -lt 1 ]]; then
                error "Usage: $0 package <version> [output_dir]"
            fi
            generate_package "$1" "$2"
            ;;
        *)
            usage
            exit 1
            ;;
    esac
}

main "$@"
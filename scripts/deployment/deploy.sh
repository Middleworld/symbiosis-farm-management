#!/bin/bash

# Symbiosis Deployment Script
# Handles promoting changes from staging (demo) to production (master)

set -e  # Exit on any error

# Configuration
STAGING_BRANCH="demo"
PRODUCTION_BRANCH="master"
PRODUCTION_DIR="/opt/sites/admin.middleworldfarms.org"
STAGING_DIR="/opt/sites/admin.soilsync.shop"
LOG_FILE="/var/log/symbiosis-deployment.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
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

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Check if we're in the right directory
check_environment() {
    if [[ ! -d ".git" ]]; then
        error "Not in a git repository. Run this from the project root."
    fi

    if [[ ! -f "artisan" ]]; then
        error "Not in a Laravel project directory."
    fi
}

# Get the latest changes from staging
sync_from_staging() {
    log "Syncing latest changes from staging branch..."

    # Fetch latest changes
    git fetch origin

    # Check if there are new commits in staging
    if git diff --quiet HEAD "origin/$STAGING_BRANCH"; then
        warning "No new changes in staging branch."
        return 0
    fi

    # Merge staging changes
    git merge "origin/$STAGING_BRANCH" --no-edit

    success "Successfully merged staging changes"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."

    # Backup database before migration
    php artisan backup:run --only-db

    # Run migrations
    php artisan migrate

    success "Database migrations completed"
}

# Clear and optimize Laravel caches
optimize_laravel() {
    log "Optimizing Laravel application..."

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize

    success "Laravel optimization completed"
}

# Update composer dependencies
update_dependencies() {
    log "Updating Composer dependencies..."

    composer install --no-dev --optimize-autoloader

    success "Dependencies updated"
}

# Run tests (if they exist)
run_tests() {
    if [[ -d "tests" ]]; then
        log "Running tests..."

        php artisan test

        success "Tests passed"
    else
        warning "No tests directory found, skipping tests"
    fi
}

# Deploy to production
deploy_to_production() {
    log "Starting deployment to production..."

    # Switch to production directory
    cd "$PRODUCTION_DIR"

    # Pull latest changes from master
    git pull origin "$PRODUCTION_BRANCH"

    # Run deployment steps
    update_dependencies
    run_migrations
    optimize_laravel

    success "Production deployment completed"
}

# Rollback function
rollback() {
    error "Deployment failed. Rolling back..."

    # Restore from backup if it exists
    if [[ -f "storage/app/backups/latest.zip" ]]; then
        php artisan backup:restore --filename=latest.zip
        success "Database restored from backup"
    fi

    # Revert git changes
    git reset --hard HEAD~1
    git push origin "$PRODUCTION_BRANCH" --force

    error "Rollback completed. Please investigate the issue."
}

# Main deployment function
deploy() {
    local target="$1"

    log "Starting Symbiosis deployment to: $target"

    check_environment

    case "$target" in
        "staging")
            sync_from_staging
            run_migrations
            optimize_laravel
            ;;
        "production")
            deploy_to_production
            ;;
        "full")
            # Deploy to staging first, then production
            log "Full deployment: staging -> production"

            # Deploy to staging
            deploy "staging"

            # Run tests on staging
            run_tests

            # If tests pass, deploy to production
            if [[ $? -eq 0 ]]; then
                deploy "production"
            else
                error "Tests failed on staging. Aborting production deployment."
            fi
            ;;
        *)
            error "Invalid target. Use: staging, production, or full"
            ;;
    esac

    success "Deployment to $target completed successfully!"
}

# Show usage
usage() {
    echo "Symbiosis Deployment Script"
    echo ""
    echo "Usage: $0 [staging|production|full]"
    echo ""
    echo "Targets:"
    echo "  staging    - Deploy to staging environment (demo branch)"
    echo "  production - Deploy to production environment (master branch)"
    echo "  full       - Deploy to staging first, then production if tests pass"
    echo ""
    echo "Examples:"
    echo "  $0 staging     # Deploy to staging only"
    echo "  $0 production  # Deploy to production only"
    echo "  $0 full        # Full staging -> production deployment"
}

# Main script
main() {
    local target="$1"

    if [[ -z "$target" ]]; then
        usage
        exit 1
    fi

    # Trap errors for rollback
    trap rollback ERR

    deploy "$target"
}

# Run main function with all arguments
main "$@"
# Symbiosis Deployment System

This directory contains automated deployment scripts for managing updates between staging (demo) and production environments.

## Overview

The deployment system consists of two main scripts:

1. **`deploy.sh`** - General deployment script for promoting changes between branches
2. **`update-deploy.sh`** - Update-specific deployment using the UpdateTracking system

## Quick Start

### Basic Deployment

```bash
# Deploy to staging only
./scripts/deployment/deploy.sh staging

# Deploy to production only
./scripts/deployment/deploy.sh production

# Full deployment (staging -> production with tests)
./scripts/deployment/deploy.sh full
```

### Update-Specific Deployment

```bash
# List available updates
./scripts/deployment/update-deploy.sh list staging

# Deploy specific update to production
./scripts/deployment/update-deploy.sh deploy 1.0.4 production

# Generate deployment package
./scripts/deployment/update-deploy.sh package 1.0.4 /tmp
```

## Workflow

### Development Workflow

1. **Develop on staging** (`demo` branch)
   - Make changes in `/opt/sites/admin.soilsync.shop`
   - Test thoroughly
   - Log updates using the admin interface

2. **Deploy to staging**
   ```bash
   cd /opt/sites/admin.middleworldfarms.org
   ./scripts/deployment/deploy.sh staging
   ```

3. **Test on staging**
   - Run automated tests
   - Manual testing
   - User acceptance testing

4. **Deploy to production**
   ```bash
   ./scripts/deployment/deploy.sh production
   ```

### Update Tracking Integration

The system integrates with the UpdateTracking system:

- **Automatic logging**: Deployments are automatically logged
- **Version control**: Each deployment is versioned
- **Rollback capability**: Easy rollback to previous versions
- **Audit trail**: Complete history of all deployments

## Features

### Automated Tasks
- Database migrations
- Cache optimization
- Dependency updates
- Backup creation
- Rollback on failure

### Safety Features
- Automatic backups before deployment
- Rollback on deployment failure
- Test validation (when available)
- Confirmation prompts for production deployments

### Update Tracking
- Version-based deployments
- File change tracking
- Environment-specific logging
- Deployment history

## Configuration

### Environment Variables

Set these in your `.env` file:

```bash
# Deployment configuration
DEPLOY_BACKUP_ENABLED=true
DEPLOY_AUTO_MIGRATE=true
DEPLOY_OPTIMIZE_CACHE=true
DEPLOY_RUN_TESTS=true
```

### Directory Structure

```
/opt/sites/
├── admin.middleworldfarms.org/  # Production
├── admin.soilsync.shop/         # Staging
└── scripts/
    └── deployment/              # Deployment scripts
```

## Advanced Usage

### Custom Deployment Hooks

Add custom deployment steps by modifying the scripts:

```bash
# In deploy.sh, add custom steps
custom_deployment_steps() {
    log "Running custom deployment steps..."

    # Your custom commands here
    php artisan custom:command

    success "Custom steps completed"
}
```

### Automated Testing

The system supports automated testing:

```bash
# Run tests before production deployment
./scripts/deployment/deploy.sh full  # Includes test validation
```

### Monitoring and Alerts

Deployments are logged to `/var/log/symbiosis-deployment.log`. Set up monitoring:

```bash
# Monitor deployment logs
tail -f /var/log/symbiosis-deployment.log

# Check deployment status
grep "SUCCESS\|ERROR" /var/log/symbiosis-deployment.log | tail -10
```

## Troubleshooting

### Common Issues

1. **Migration conflicts**
   ```bash
   # Check migration status
   php artisan migrate:status

   # Rollback if needed
   php artisan migrate:rollback
   ```

2. **Cache issues**
   ```bash
   # Clear all caches
   php artisan optimize:clear
   ```

3. **Permission issues**
   ```bash
   # Fix permissions
   chown -R www-data:www-data /opt/sites/admin.middleworldfarms.org
   chmod -R 755 /opt/sites/admin.middleworldfarms.org/storage
   ```

### Rollback Procedure

If deployment fails:

```bash
# Automatic rollback (built into scripts)
# Or manual rollback:
cd /opt/sites/admin.middleworldfarms.org
php artisan backup:restore --filename=latest.zip
git reset --hard HEAD~1
```

## Security Considerations

- Deployments require appropriate file system permissions
- Database backups are created automatically
- Sensitive configuration is environment-specific
- Logs contain deployment details but not sensitive data

## Future Enhancements

- GitHub Actions integration
- Slack notifications
- Automated rollbacks
- Blue-green deployments
- Multi-environment support
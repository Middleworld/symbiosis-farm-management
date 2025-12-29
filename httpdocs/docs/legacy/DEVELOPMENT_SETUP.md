# Development Environment Setup Guide

## Overview
This guide explains how to properly set up and use the staging environment (`admin.soilsync.shop`) for development work, replacing the previous workflow of developing directly in production.

## Why Use Staging for Development?

### ❌ **Previous Problems:**
- Direct development in production environment
- Manual file copying between environments
- High risk of breaking live site
- No proper testing environment
- Difficult to track changes

### ✅ **New Benefits:**
- **Safe Development**: Test changes in staging before production
- **Automated Deployment**: No more manual file copying
- **Version Control**: All changes tracked via UpdateTracking system
- **Rollback Capability**: Easy to revert problematic changes
- **CI/CD Ready**: Automated testing and deployment pipelines

## Environment Setup

### Current Configuration
- **Production**: `admin.middleworldfarms.org` (master branch)
- **Staging**: `admin.soilsync.shop` (demo branch)

### Switching to Staging Workspace

#### Option 1: VS Code Command Palette
1. Open VS Code
2. Press `Ctrl+Shift+P` (or `Cmd+Shift+P` on Mac)
3. Type "Workspaces: Open Workspace"
4. Navigate to `/opt/sites/admin.soilsync.shop`
5. Select the workspace file or create one

#### Option 2: Terminal Commands
```bash
# Navigate to staging environment
cd /opt/sites/admin.soilsync.shop

# Verify you're in the right place
pwd
# Should show: /opt/sites/admin.soilsync.shop

# Check current branch
git branch --show-current
# Should show: demo
```

## Development Workflow

### 1. Make Changes in Staging
```bash
# Ensure you're in staging
cd /opt/sites/admin.soilsync.shop

# Create/edit your files
# ... make your code changes ...

# Test locally
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Commit Changes
```bash
# Stage your changes
git add .

# Commit with descriptive message
git commit -m "feat: Description of your changes

- What you added/changed
- Why you made these changes
- Any breaking changes"

# Push to remote demo branch
git push origin demo
```

### 3. Test in Staging Environment
```bash
# Run tests
php artisan test

# Check for any linting issues
./vendor/bin/phpcs app/ --standard=PSR12

# Verify functionality manually
# - Visit admin.soilsync.shop
# - Test your new features
# - Check browser console for errors
```

### 4. Deploy to Production
Once testing is complete, deploy using one of these methods:

#### Method A: Automated Deployment Script
```bash
# From staging environment
./scripts/deployment/update-deploy.sh deploy production

# Or for full deployment with migrations
./scripts/deployment/deploy.sh production
```

#### Method B: Laravel Artisan Command
```bash
# From staging environment
php artisan deploy:update production
```

#### Method C: GitHub Actions (Automatic)
- Push changes to `demo` branch
- Create a Pull Request to merge `demo` → `master`
- GitHub Actions will automatically:
  - Run tests
  - Deploy to production if tests pass
  - Log the deployment in UpdateTracking

## File Structure

### Important Directories
```
admin.soilsync.shop/
├── app/                    # Laravel application code
├── resources/views/        # Blade templates
├── routes/                 # Route definitions
├── database/               # Migrations, seeders
├── scripts/deployment/     # Deployment automation
├── .github/workflows/      # CI/CD pipelines
└── config/                 # Configuration files
```

### Key Files for Development
- `app/Http/Controllers/` - Controllers
- `resources/views/admin/` - Admin interface templates
- `routes/web.php` - Route definitions
- `app/Services/` - Business logic services
- `scripts/deployment/` - Deployment tools

## Best Practices

### Code Quality
- Follow PSR-12 coding standards
- Write descriptive commit messages
- Add comments for complex logic
- Use meaningful variable/function names

### Testing
- Test all changes in staging first
- Run `php artisan test` before committing
- Manually test critical user flows
- Check browser console for JavaScript errors

### Version Control
- Commit frequently with small, focused changes
- Use feature branches if working on large features
- Keep commits atomic (one logical change per commit)
- Write clear commit messages

### Deployment Safety
- Always test in staging before production
- Use the UpdateTracking system to log changes
- Keep backups of important data
- Monitor production after deployment

## Troubleshooting

### Common Issues

#### "Permission denied" when running scripts
```bash
# Make scripts executable
chmod +x scripts/deployment/*.sh
```

#### Git conflicts during deployment
```bash
# If conflicts occur, resolve them in staging first
git pull origin demo
# Resolve conflicts, then commit and try deployment again
```

#### Database migration issues
```bash
# Run migrations manually if needed
php artisan migrate

# Rollback if problems
php artisan migrate:rollback
```

#### Cache issues after deployment
```bash
# Clear various caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

## Getting Help

### Documentation
- Check `scripts/deployment/README.md` for deployment details
- Review UpdateTracking logs in admin interface
- Check GitHub Actions logs for CI/CD issues

### Emergency Procedures
If something goes wrong in production:
```bash
# Quick rollback (if available)
./scripts/deployment/deploy.sh rollback production

# Emergency restore from backup
./scripts/emergency-restore.sh --site=admin.middleworldfarms.org --backup-date=YYYY-MM-DD
```

## Migration Checklist

### ✅ Completed Setup
- [x] Staging environment configured (`admin.soilsync.shop`)
- [x] Git branches aligned (demo/staging, master/production)
- [x] Deployment scripts created
- [x] UpdateTracking system implemented
- [x] GitHub Actions CI/CD pipeline
- [x] Laravel deployment commands

### 🔄 Migration Steps
- [ ] Switch primary development to staging workspace
- [ ] Test deployment pipeline end-to-end
- [ ] Update team documentation
- [ ] Train on new workflow
- [ ] Monitor first few deployments

---

**Remember**: Always develop in staging first, test thoroughly, then deploy to production. This ensures stability and provides a safety net for the live site.
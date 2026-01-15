# farmOS Backup - January 15, 2026

This backup was created before restoring farmOS from Plesk backup due to persistent "unexpected error" issues.

## What was backed up:
- `/sites/default/files/` - User uploaded files, images, documents
- `/sites/default/` - Site configuration, settings.php, custom configurations
- `/modules/custom/` - Custom modules developed for this installation
- Disabled modules (*.disabled) - Modules that were disabled due to conflicts

## To restore after Plesk backup:
1. Copy files back: `cp -r files/* /path/to/farmos/web/sites/default/files/`
2. Copy custom modules: `cp -r custom/* /path/to/farmos/web/modules/custom/`
3. Copy site config: `cp -r default/* /path/to/farmos/web/sites/default/`
4. Run: `drush cache-rebuild`
5. Check database connection and run any pending updates: `drush updb`

## Database:
- Database name: soilsync-user_
- Should be restored separately via Plesk/MySQL backup

## Issues that led to restore:
- Persistent "unexpected error" messages
- Cached hook issues from farm_weather_overlays module
- Kernel handle() failures despite successful bootstrap
- Multiple cache clears didn't resolve the issue
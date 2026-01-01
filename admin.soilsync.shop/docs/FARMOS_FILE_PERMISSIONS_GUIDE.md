# File Permission Issues - Prevention Guide

## The Problem

When running Drush scripts as `root`, any files or directories created are owned by `root:root`. This causes upload failures in Drupal because PHP-FPM runs as `wonderful-kilby_axeszvh5cj9:psacln`.

## The Solution

### Option 1: Use the Safe Wrapper (Recommended)
```bash
./drush-safe php:script my_script.php
./drush-safe cr
./drush-safe sql:query "SELECT ..."
```

The `drush-safe` wrapper automatically detects the correct user and runs commands with proper permissions.

### Option 2: Manual User Switch
```bash
sudo -u wonderful-kilby_axeszvh5cj9 vendor/bin/drush php:script my_script.php
```

## Quick Fix When Files Have Wrong Ownership

If you've already run a script as root and files have wrong ownership:

```bash
# Fix the entire private directory
sudo chown -R wonderful-kilby_axeszvh5cj9:psacln web/sites/default/files/private/

# Or fix just the current month's uploads
sudo chown -R wonderful-kilby_axeszvh5cj9:psacln web/sites/default/files/private/farm/term/$(date +%Y-%m)/
```

## Why This Happens

1. **Root creates files** → Files owned by `root:root`
2. **PHP-FPM runs as** → `wonderful-kilby_axeszvh5cj9`
3. **PHP cannot write** → Permission denied
4. **Drupal shows error** → "The file could not be uploaded"

## Checking Current Ownership

```bash
# Check who owns files
ls -la web/sites/default/files/private/farm/term/2025-10/

# Check what user PHP runs as
ps aux | grep php-fpm | grep farmos | head -1

# Should show: wonderful-kilby_axeszvh5cj9
```

## Prevention Checklist

- [ ] Always use `./drush-safe` instead of `vendor/bin/drush` when logged in as root
- [ ] If logged in as root, use `sudo -u wonderful-kilby_axeszvh5cj9` before drush commands
- [ ] After importing files via script, verify ownership matches existing files
- [ ] Check PHP-FPM user: should be `wonderful-kilby_axeszvh5cj9` not `root` or `www-data`

## Related Files

- `drush-safe` - Safe wrapper script that auto-detects correct user
- Import scripts should create files owned by `wonderful-kilby_axeszvh5cj9:psacln`

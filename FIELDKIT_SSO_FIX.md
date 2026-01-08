# FieldKit SSO Integration Fix

**Date**: January 4, 2026  
**Status**: ✅ Fully Working on Production

## Problem

FieldKit SSO button in admin dashboard was returning users to SSO dashboard instead of logging them into FieldKit.

## Root Cause

The SSO dashboard was linking **directly** to `https://fieldkit.middleworldfarms.org` (home page) instead of the login page. This bypassed FieldKit's authentication flow, causing it to redirect back to the SSO dashboard.

## Solution

### 1. Fix SSO Dashboard FieldKit Link

**File**: `resources/views/sso/dashboard.blade.php`

**Change**:
```diff
- <a href="https://fieldkit.middleworldfarms.org" class="site-card">
+ <a href="https://fieldkit.middleworldfarms.org/login" class="site-card">
```

**Line**: ~180

**Reason**: FieldKit's login page (`/login`) contains an SSO button that triggers proper OAuth2 authentication with farmOS. Direct link to home bypasses this flow.

### 2. Fix WordPress URL (if needed)

**File**: `resources/views/sso/dashboard.blade.php`

**Change**:
```diff
- <a href="https://soilsync.shop/wp-admin/admin-ajax.php?action=mwf_sso_callback&token=...
+ <a href="https://middleworldfarms.org/wp-admin/admin-ajax.php?action=mwf_sso_callback&token=...
```

**Line**: ~153

**Reason**: Update to correct production domain.

### 3. Deploy Full QR Scanner Module

The QR Scanner Field Module was only showing a test page. Deploy the full functional scanner:

**Build the module**:
```bash
cd /var/www/vhosts/middleworldfarms.org/subdomains/fieldkit/httpdocs/packages/qr-scanner
npm run build
```

**Deploy to farmOS**:
```bash
cp dist/farm_fieldkit_qr_scanner/js/qr-scanner.0-0-0.js \
   /var/www/vhosts/middleworldfarms.org/subdomains/farmos/web/fieldkit/js/qr-scanner/index.js
```

**Verify**:
```bash
ls -lh /var/www/vhosts/middleworldfarms.org/subdomains/farmos/web/fieldkit/js/qr-scanner/index.js
# Should show ~337KB (was 809 bytes for test file)
```

### 4. Clean Up Leftover Files

**Remove WordPress theme files from Laravel**:
```bash
cd /opt/sites/admin.middleworldfarms.org
rm -rf httpdocs/
echo "/httpdocs/" >> .gitignore
```

## Authentication Flow (How It Works)

1. User clicks **FieldKit** in SSO dashboard
2. Redirects to `https://fieldkit.middleworldfarms.org/login`
3. FieldKit login page shows **"Sign in with Middle World Farms SSO"** button
4. User clicks SSO button
5. FieldKit redirects to `https://admin.middleworldfarms.org:8444/sso/login?redirect=https://fieldkit.middleworldfarms.org/oauth/callback`
6. Admin SSO authenticates user
7. Redirects back to FieldKit OAuth callback
8. FieldKit exchanges code for farmOS OAuth token
9. User is logged into FieldKit and can access farm data

## Testing

1. Log into admin dashboard SSO: `https://admin.middleworldfarms.org:8444/sso/login`
2. Click **FieldKit** card
3. Should see FieldKit login page with SSO button
4. Click **"Sign in with Middle World Farms SSO"**
5. Should be redirected through admin SSO
6. Should land in FieldKit home with menu showing "QR Scanner"
7. Click QR Scanner → Should see "Start Camera" button (not just test page)

## Commits

- `29e12518` - fix: FieldKit SSO link now redirects to login page
- `a2f9d2fd` - fix: Update SSO dashboard WordPress URL + cleanup

## Staging Site Replication

To apply these fixes to staging:

1. Pull latest commits from production git repo
2. Verify FieldKit login page URL in SSO dashboard blade file
3. Update domain names if staging uses different URLs
4. Rebuild and deploy QR Scanner module
5. Test complete authentication flow

## Files Modified

- `resources/views/sso/dashboard.blade.php` - SSO dashboard links
- `/var/www/vhosts/.../farmos/web/fieldkit/js/qr-scanner/index.js` - Full QR scanner
- `.gitignore` - Added `/httpdocs/`

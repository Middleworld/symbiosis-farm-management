# FieldKit SSO Integration Fix - soilsync.shop

**Date**: January 4, 2026  
**Status**: ✅ Ready for Testing

## Problem

FieldKit SSO button in admin dashboard was linking directly to home page instead of the login page, bypassing the authentication flow.

## Solution Applied

### 1. Fixed SSO Dashboard FieldKit Link

**File**: `admin.soilsync.shop/resources/views/sso/dashboard.blade.php`

**Change**:
```diff
- <a href="https://feildkit.soilsync.shop" class="site-card" onclick="alert('Field Kit will check for your SSO authentication automatically...');">
+ <a href="https://feildkit.soilsync.shop/login" class="site-card">
```

**Line**: ~176

**Reason**: FieldKit's login page (`/login`) contains SSO token checking logic that automatically authenticates users if they have an active admin session. Direct link to home bypasses this flow.

### 2. Created farmOS fieldkit Directory

Created directory structure for potential future Field Kit module deployment:
```bash
mkdir -p /var/www/vhosts/soilsync.shop/farmos.soilsync.shop/web/fieldkit/js/qr-scanner
```

## How It Works (Authentication Flow)

1. User clicks **Field Kit** in SSO dashboard
2. Redirects to `https://feildkit.soilsync.shop/login`
3. FieldKit Login.vue component automatically runs `checkSSOTokens()` on mount
4. Makes request to `https://admin.soilsync.shop/sso/farmos-tokens` with credentials
5. If user is authenticated in admin:
   - Returns farmOS OAuth tokens (host, access_token)
   - FieldKit stores tokens in IndexedDB
   - Automatically redirects to `/home`
6. If user is NOT authenticated:
   - Shows login form with link to admin login
   - User can manually enter farmOS credentials

## Existing Infrastructure (Already Working)

### Laravel SSO Controller
- **Route**: `GET /sso/farmos-tokens`
- **Controller**: `SsoController::getFarmOSTokens()`
- **Features**:
  - CORS headers for `https://feildkit.soilsync.shop`
  - Session-based authentication check
  - Automatic token refresh if expiring within 5 minutes
  - Returns farmOS OAuth token in FieldKit-compatible format

### FieldKit Login Component
- **File**: `feildkit.soilsync.shop/packages/field-kit/src/login/Login.vue`
- **Features**:
  - Automatic SSO token check on component mount
  - Loading state while checking authentication
  - Fallback to manual login form if SSO unavailable
  - Link to admin login for seamless authentication

### Configuration Already Present
- Allowed origins in SsoController include `feildkit.soilsync.shop`
- Session handling configured for cross-domain cookies
- farmOS OAuth scope properly configured

## Testing Steps

1. **Test Direct Login (SSO Active)**:
   - Log into admin dashboard: `https://admin.soilsync.shop/sso/login`
   - Click **Field Kit** card
   - Should see "Checking for existing authentication..." message
   - Should automatically redirect to FieldKit home (no login form)

2. **Test Login Without Active Session**:
   - Log out of admin dashboard
   - Visit `https://feildkit.soilsync.shop/login`
   - Should see message: "For seamless login, please first authenticate through the admin system"
   - Click link to admin login
   - After admin login, return to FieldKit
   - Should now auto-authenticate

3. **Test Manual Login**:
   - Without admin session, enter farmOS credentials manually
   - Should authenticate directly with farmOS
   - Should store tokens and redirect to home

## Files Modified

- `admin.soilsync.shop/resources/views/sso/dashboard.blade.php` - SSO dashboard link

## Files Created

- `/var/www/vhosts/soilsync.shop/farmos.soilsync.shop/web/fieldkit/js/qr-scanner/` (directory)

## No Changes Needed

The following were already properly configured:
- SSO controller endpoint (`/sso/farmos-tokens`)
- FieldKit SSO token checking logic
- CORS configuration for cross-domain authentication
- Session handling between admin and FieldKit

## Differences from middleworldfarms.org

1. **Domain naming**: Uses `feildkit.soilsync.shop` (with typo) instead of `fieldkit`
2. **No QR Scanner deployment needed**: Module not yet built for this site
3. **All SSO infrastructure already in place**: No additional setup required

## Next Steps (Optional)

1. **Build QR Scanner Module**: If needed, build from packages and deploy to farmOS
2. **Fix domain typo**: Consider redirecting `fieldkit.soilsync.shop` → `feildkit.soilsync.shop` or vice versa
3. **Test on mobile devices**: Verify FieldKit works as PWA with SSO on phones/tablets

## Commit Information

- Single file modified: SSO dashboard blade template
- Change: Updated FieldKit link from home page to login page
- Impact: Enables proper SSO authentication flow

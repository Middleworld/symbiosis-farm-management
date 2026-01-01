# SSO Implementation - Working Solution

## Overview
A simple, working Single Sign-On (SSO) solution that allows users to log into WordPress using their Laravel Admin credentials. This bypasses the OAuth complexity that was causing issues and provides a reliable authentication flow.

## How It Works

### 1. User Flow
```
WordPress Login Attempt
    ↓
Redirected to Laravel SSO Login (admin.soilsync.shop/sso/login)
    ↓
User enters Laravel credentials (email/password)
    ↓
Laravel authenticates and generates session token
    ↓
Redirects back to WordPress with token
    ↓
WordPress verifies token with Laravel API
    ↓
WordPress logs user in automatically
```

### 2. Components

#### Laravel Side (admin.soilsync.shop)

**Controller:** `app/Http/Controllers/SsoController.php`
- `login()` - Shows SSO login form
- `authenticate()` - Validates credentials
- `redirectBackWithToken()` - Generates token and redirects to WordPress

**Routes:** `routes/web.php`
- `GET /sso/login` - SSO login page
- `POST /sso/authenticate` - Handle login submission

**API:** `routes/api.php`
- `POST /api/sso/verify` - Token verification endpoint

**View:** `resources/views/sso/login.blade.php`
- Professional Bootstrap 5 styled login form

#### WordPress Side (soilsync.shop)

**Plugin:** `wp-content/plugins/mwf-sso/mwf-sso.php`
- Intercepts WordPress login
- Redirects to Laravel SSO
- Handles callback with token
- Creates/logs in WordPress user

## Configuration

### Laravel .env Settings
```env
SESSION_DRIVER=database
SESSION_DOMAIN=.soilsync.shop
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
```

### WordPress Plugin Settings
Go to: **Settings → MWF SSO**
- Enable SSO: ✓ Checked
- Admin URL: `https://admin.soilsync.shop`
- Client ID: (not used in simple SSO)
- Client Secret: (not used in simple SSO)

## Testing Instructions

### 1. Ensure Laravel Server is Running
```bash
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Test the SSO Flow
1. **Logout from WordPress** if currently logged in
2. **Visit WordPress admin:** `https://soilsync.shop/wp-admin/`
3. **You'll be redirected to:** `https://admin.soilsync.shop/sso/login`
4. **Login with Laravel credentials:**
   - Email: `demo@soilsync.shop`
   - Password: [your Laravel admin password]
5. **Automatic redirect and login** to WordPress admin

### 3. Verify Success
- After logging in, you should be at WordPress admin dashboard
- Check that you're logged in as the correct user
- Try accessing WordPress pages - should stay logged in

## Troubleshooting

### Issue: "SSO verification failed"
- Check that Laravel server is running on port 8000
- Verify WordPress can reach `https://admin.soilsync.shop/api/sso/verify`
- Check Laravel logs: `storage/logs/laravel.log`

### Issue: "Invalid SSO token"
- Token expires after 5 minutes
- Clear browser cookies and try again
- Check session configuration in Laravel `.env`

### Issue: Redirect loop
- Ensure `mwf_sso_enabled` is set to `1` in WordPress options
- Check that `loggedout` parameter check is working in plugin
- Clear WordPress and Laravel sessions

### Issue: Can't access SSO login page
- Verify route is registered: `php artisan route:list | grep sso`
- Check Laravel server is running: `ps aux | grep artisan`
- Test directly: `curl http://localhost:8000/sso/login`

## Production Deployment

### When Moving to Production:
1. **Update SESSION_SECURE_COOKIE=true** in `.env` (only if using HTTPS)
2. **Use proper web server** (nginx/Apache) instead of `php artisan serve`
3. **Enable token database storage** instead of sessions for scalability
4. **Add rate limiting** to prevent brute force attacks
5. **Implement proper JWT tokens** for better security

## Key Differences from OAuth/Passport

### Why This Works vs Passport:
1. **No CSRF issues** - Simple form submission
2. **No session domain problems** - Direct token passing
3. **No authorization consent page** - Seamless redirect
4. **Simpler token validation** - Session-based verification
5. **Easier debugging** - Clear error messages

### Trade-offs:
- ✅ Much simpler and more reliable
- ✅ Easier to debug and maintain
- ✅ No complex OAuth flow
- ⚠️ Requires Laravel server to be accessible
- ⚠️ Tokens are session-based (5-minute expiry)

## Files Modified

### New Files
- `app/Http/Controllers/SsoController.php`
- `resources/views/sso/login.blade.php`
- `test-sso.php` (testing script)

### Modified Files
- `routes/web.php` (added SSO routes)
- `routes/api.php` (added verification endpoint)
- `wp-content/plugins/mwf-sso/mwf-sso.php` (updated to use simple SSO)
- `.env` (session configuration)

## Success Criteria

✅ User can logout from WordPress  
✅ Accessing wp-admin redirects to Laravel SSO  
✅ Laravel login form is styled and functional  
✅ Successful login redirects back to WordPress  
✅ WordPress automatically logs user in  
✅ User can access WordPress admin dashboard  

## Next Steps for Production

1. Set up proper nginx/Apache configuration
2. Use database/Redis for token storage
3. Implement JWT for better security
4. Add two-factor authentication support
5. Extend to FarmOS platform (third platform)

---

**Status:** Working SSO implementation complete  
**Date:** December 31, 2025  
**Tested:** Local development environment

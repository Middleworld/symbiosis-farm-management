# SSO Completion Checklist - January 2026

## 🎯 Goal: Complete Single Sign-On Across All Subdomains

**Systems:**
- ✅ `soilsync.shop` (WordPress/WooCommerce)
- ✅ `admin.soilsync.shop` (Laravel Admin - OAuth Provider)
- 🔄 `farmos.soilsync.shop` (FarmOS)
- 🔄 `feildkit.soilsync.shop` (FieldKit Mobile App)

---

## Task 1: Complete FarmOS OAuth Setup (1-2 hours)

### 1.1 Check FarmOS OAuth Consumer Exists
```bash
cd /var/www/vhosts/soilsync.shop/farmos.soilsync.shop
drush config:get simple_oauth.settings
```

### 1.2 Create OAuth Consumer in FarmOS (if not exists)
1. Login to FarmOS admin: `https://farmos.soilsync.shop/admin`
2. Go to: **Configuration → Web Services → OAuth2 Consumers**
3. Click "Add Consumer"
4. Fill in:
   - **Label**: `Laravel Admin SSO`
   - **Client ID**: (auto-generated or set custom)
   - **New Secret**: Generate and SAVE THIS!
   - **Redirect URI**: `https://admin.soilsync.shop/oauth/callback/farmos`
   - **Scopes**: `farm_manager` (or leave empty for all)
   - **Grant Types**: ✅ Authorization Code, ✅ Refresh Token

### 1.3 Update Laravel .env with FarmOS OAuth
```bash
# Add to /var/www/vhosts/soilsync.shop/admin.soilsync.shop/.env
FARMOS_OAUTH_CLIENT_ID=your_client_id_from_step_1.2
FARMOS_OAUTH_CLIENT_SECRET=your_secret_from_step_1.2
FARMOS_URL=https://farmos.soilsync.shop
```

### 1.4 Create FarmOS Callback Route in Laravel
Add to `routes/web.php`:
```php
// FarmOS OAuth callback
Route::get('/oauth/callback/farmos', [App\Http\Controllers\SsoController::class, 'farmosCallback'])->name('oauth.callback.farmos');
```

### 1.5 Test FarmOS → Laravel SSO
```bash
# Test the OAuth flow
curl -I "https://farmos.soilsync.shop/oauth/authorize?client_id=YOUR_CLIENT_ID&redirect_uri=https://admin.soilsync.shop/oauth/callback/farmos&response_type=code&scope=farm_manager"
```

---

## Task 2: Configure FieldKit OAuth Client (1-2 hours)

### 2.1 Check FieldKit Configuration Location
```bash
ls -la /var/www/vhosts/soilsync.shop/feildkit.soilsync.shop/
cat /var/www/vhosts/soilsync.shop/feildkit.soilsync.shop/.env 2>/dev/null || echo "Check for config file"
```

### 2.2 Create OAuth Client in Laravel Passport for FieldKit
```bash
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop
php artisan passport:client --public --name="FieldKit App"
# Note the Client ID (no secret needed for public client)
```

### 2.3 Configure FieldKit OAuth Settings
Update FieldKit config with:
```
OAUTH_PROVIDER_URL=https://admin.soilsync.shop
OAUTH_CLIENT_ID=<client_id_from_step_2.2>
OAUTH_AUTHORIZE_URL=https://admin.soilsync.shop/oauth/authorize
OAUTH_TOKEN_URL=https://admin.soilsync.shop/oauth/token
OAUTH_REDIRECT_URI=https://feildkit.soilsync.shop/callback
```

### 2.4 Add FieldKit Redirect URI to Laravel Passport
Ensure the redirect URI is whitelisted in Laravel Passport client settings.

---

## Task 3: Test Full SSO Circle (30 min)

### 3.1 Test Login Flow
1. **Clear all cookies** for `*.soilsync.shop`
2. Open incognito browser
3. Go to `https://soilsync.shop` → Click Login
4. Should redirect to `https://admin.soilsync.shop/sso/login`
5. Login with credentials
6. Should return to WordPress logged in

### 3.2 Test Cross-System Access
After logging in via WordPress:
1. Visit `https://admin.soilsync.shop` → Should be logged in automatically
2. Visit `https://farmos.soilsync.shop` → Should recognize session or prompt OAuth
3. Visit `https://feildkit.soilsync.shop` → Should work with OAuth flow

### 3.3 Test Logout Flow
1. Logout from any system
2. Verify all systems log out (Single Logout)

---

## Task 4: Document Final Configuration (30 min)

### 4.1 Update SSO_IMPLEMENTATION_GUIDE.md with:
- Final OAuth client IDs (not secrets!)
- Redirect URIs for each system
- Troubleshooting steps

### 4.2 Commit All Changes
```bash
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop
git add .
git commit -m "feat(sso): Complete SSO integration for all subdomains"
git push origin main
```

---

## 🔧 Troubleshooting

### Redirect Loop Issues
- Clear cookies for all `*.soilsync.shop` domains
- Check browser console for redirect chain
- Verify `domain=.soilsync.shop` in session cookies

### OAuth Token Errors
- Check client_id and client_secret match
- Verify redirect_uri exactly matches (including trailing slash)
- Check token expiration times

### FarmOS Not Recognizing Login
- Verify FarmOS Simple OAuth module is enabled
- Check RSA keys are configured: `drush config:get simple_oauth.settings`
- Verify consumer has correct scopes

### FieldKit Connection Issues
- FieldKit uses public client (no secret) - verify client type
- Check CORS headers allow FieldKit domain
- Verify token endpoint returns proper format

---

## 📝 Environment Variables Reference

### Laravel Admin (.env)
```
APP_URL=https://admin.soilsync.shop
SESSION_DOMAIN=.soilsync.shop
SANCTUM_STATEFUL_DOMAINS=soilsync.shop,admin.soilsync.shop,farmos.soilsync.shop,feildkit.soilsync.shop

# FarmOS OAuth (as client)
FARMOS_OAUTH_CLIENT_ID=xxx
FARMOS_OAUTH_CLIENT_SECRET=xxx
FARMOS_URL=https://farmos.soilsync.shop
```

### WordPress (wp-config.php)
```php
define('MWF_SSO_ENABLED', true);
define('MWF_SSO_ADMIN_URL', 'https://admin.soilsync.shop');
```

### FarmOS (settings.php)
```php
// OAuth should be configured via Drupal admin UI
// RSA keys location configured in simple_oauth.settings
```

---

## ✅ Success Criteria

- [ ] User can login once and access all 4 systems
- [ ] Logout from any system logs out everywhere
- [ ] No redirect loops on any flow
- [ ] Mobile FieldKit app can authenticate
- [ ] FarmOS operations work with authenticated user

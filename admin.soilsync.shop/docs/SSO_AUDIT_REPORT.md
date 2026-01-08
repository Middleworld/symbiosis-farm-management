# SSO Audit Report - All 4 Sites
**Date:** January 6, 2026  
**Auditor:** AI Security Review  
**Status:** ⚠️ MIXED - Working but with architectural concerns

---

## Executive Summary

| Site | Auth Method | Status | Risk Level |
|------|-------------|--------|------------|
| **WordPress** (soilsync.shop) | JWT Token | ✅ Working | 🟡 Medium |
| **Admin Dashboard** (admin.soilsync.shop) | Laravel Session | ✅ Working | 🟢 Low |
| **farmOS** (farmos.soilsync.shop) | OAuth2 OpenID Connect | ✅ Working | 🟢 Low |
| **FieldKit** (feildkit.soilsync.shop) | OAuth Token Pre-auth | ✅ Working | 🟡 Medium |

---

## Site 1: WordPress (soilsync.shop)

### Authentication Flow
```
WordPress wp-login.php
    ↓
Redirect to admin.soilsync.shop/sso/login
    ↓
User authenticates with Laravel credentials
    ↓
JWT generated (HMAC-SHA256 signed)
    ↓
Redirect to WordPress with JWT in URL
    ↓
WordPress decodes JWT payload (NO SIGNATURE VERIFICATION)
    ↓
WordPress creates/logs in user
```

### Configuration Found
**Plugin:** `httpdocs/wp-content/plugins/mwf-sso/mwf-sso.php`
- SSO Enabled: Yes (database option)
- Admin URL: `https://admin.soilsync.shop`
- Client ID: `019b707e-63d3-73d9-ad61-2bc52a1c2d89`

**OAuth Client (Laravel DB):**
```json
{
  "id": "019b707e-63d3-73d9-ad61-2bc52a1c2d89",
  "name": "WordPress SSO Client",
  "redirect_uris": ["https://soilsync.shop/wp-admin/admin-ajax.php?action=mwf_oauth_callback"]
}
```

### 🔴 CRITICAL SECURITY ISSUES

#### 1. JWT Signature Not Verified
**File:** `mwf-sso.php` lines 170-200
```php
// WordPress only decodes JWT payload, does NOT verify signature
$parts = explode('.', $token);
$payload_b64 = str_replace(['-', '_'], ['+', '/'], $parts[1]);
$payload = json_decode(base64_decode($payload_b64), true);
// NO HMAC verification against shared secret!
```

**Risk:** Anyone who can intercept a valid JWT can decode it and see the structure, then craft their own token with any user data they want. The HMAC signature is NEVER checked.

**Impact:** Complete authentication bypass - any user can impersonate any other user.

**Fix Required:**
```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret = get_option('mwf_sso_client_secret'); // Must match Laravel's app.key
$payload = JWT::decode($token, new Key($secret, 'HS256'));
```

#### 2. JWT Token in URL (Not Immediately Cleared)
**File:** `mwf-sso.php` - callback handler does NOT redirect after login
```php
// After successful login, user stays on URL with token
// Token appears in: browser history, server logs, referrer headers
```

**Risk:** Token leakage allows replay attacks within expiry window.

**Fix Required:**
```php
// After wp_set_auth_cookie(), immediately redirect:
wp_safe_redirect(remove_query_arg('token'));
exit;
```

#### 3. Token Expiry Too Long
**File:** `SsoController.php` line 148
```php
'exp' => time() + 3600, // 1 HOUR expiry
```

**Risk:** 1-hour window for replay attacks if token is leaked.

**Recommendation:** Reduce to 300 seconds (5 minutes).

### Token Structure
```json
{
  "iss": "https://admin.soilsync.shop",
  "aud": "wordpress",
  "iat": 1736000000,
  "exp": 1736003600,
  "sub": 1,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "User Name"
  }
}
```

### Recommendation Priority
1. 🔴 **IMMEDIATE:** Implement JWT signature verification in WordPress plugin
2. 🔴 **HIGH:** Add redirect-after-login to clear token from URL
3. 🟡 **MEDIUM:** Reduce token expiry to 5 minutes
4. 🟡 **MEDIUM:** Add JTI claim for single-use tokens

---

## Site 2: Admin Dashboard (admin.soilsync.shop)

### Authentication Flow
```
User visits /admin/*
    ↓
admin.auth middleware checks Auth::check()
    ↓
If not authenticated → redirect to /sso/login
    ↓
User enters email/password
    ↓
Laravel validates against users table
    ↓
Session established (database driver)
    ↓
farmOS OAuth token obtained and stored in session
```

### Configuration Found
**Session (.env):**
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
SESSION_DOMAIN=.soilsync.shop
```

### 🟡 MEDIUM CONCERNS

#### 1. SESSION_SAME_SITE=none Unnecessarily Broad
**Why it was set:** To allow FieldKit cross-origin requests to read session.

**Actual need:** FieldKit uses `/sso/farmos-tokens` endpoint which checks `Auth::check()` - this DOES require session cookies.

**Trade-off:** `SameSite=none` increases CSRF risk but is necessary for cross-domain auth.

**Mitigation:** The CSRF token is still required for POST requests, which limits exposure.

#### 2. farmOS Tokens Stored in Session
**File:** `SsoController.php` line 115-121
```php
session([
    'farmos_oauth_token' => $token,
    'farmos_token_expiry' => now()->addMinutes(55)->toDateTimeString(),
    'farmos_host' => config('farmos.url')
]);
```

**Risk:** If session is compromised, attacker gets farmOS API access.

**Mitigation:** Tokens are short-lived (55 minutes), session is database-backed.

### OAuth Clients Registered
| ID | Name | Redirect | Purpose |
|----|------|----------|---------|
| `019b707e-4f5e...` | WordPress SSO Client | middleworldfarms.org | Production WP |
| `019b707e-63d3...` | WordPress SSO Client | soilsync.shop | Dev WP |
| `019b79d1-29cd...` | FarmOS OpenID | farmos.soilsync.shop | farmOS SSO |

### Passport Configuration
**File:** `AppServiceProvider.php`
```php
Passport::setDefaultScope(['openid', 'email', 'profile']);
```
✅ Correct - OpenID scopes enabled for farmOS integration.

---

## Site 3: farmOS (farmos.soilsync.shop)

### Authentication Flow
```
User clicks farmOS card on SSO Dashboard
    ↓
Redirect to admin.soilsync.shop/oauth/authorize
    ↓
Laravel Passport authorization (already authenticated)
    ↓
Redirect to farmos.soilsync.shop/user/openid-connect/generic
    ↓
farmOS exchanges code for token
    ↓
farmOS calls admin.soilsync.shop/api/user
    ↓
farmOS creates/updates user from OpenID claims
```

### Configuration Found
**farmOS OpenID Connect (drush config:get):**
```yaml
generic:
  enabled: true
  settings:
    client_id: 019b79d1-29cd-7078-8662-d4413a597a1f
    client_secret: DemoLaravelSync2025
    authorization_endpoint: 'https://admin.soilsync.shop/oauth/authorize'
    token_endpoint: 'https://admin.soilsync.shop/oauth/token'
    userinfo_endpoint: 'https://admin.soilsync.shop/api/user'
```

**Laravel OAuth Client:**
```json
{
  "id": "019b79d1-29cd-7078-8662-d4413a597a1f",
  "name": "FarmOS OpenID",
  "redirect_uris": [
    "https://farmos.soilsync.shop/user/openid-connect/generic",
    "https://farmos.soilsync.shop/user/login?openid_connect=generic"
  ],
  "grant_types": ["authorization_code", "refresh_token"]
}
```

**RSA Keys:**
```
/farmos.soilsync.shop/keys/
├── private.key (1704 bytes, 640 permissions)
└── public.key (451 bytes, 644 permissions)
```

### ✅ GOOD IMPLEMENTATION

- **OAuth2 Authorization Code flow** - Industry standard
- **RSA keys properly configured** - Asymmetric signing
- **User info endpoint** - Returns OpenID claims correctly
- **Refresh token support** - Long-lived sessions possible
- **CORS disabled** - farmOS doesn't need cross-origin for SSO (it's a redirect flow)

### 🟢 LOW CONCERNS

#### 1. Client Secret in Config
**Issue:** `DemoLaravelSync2025` stored in farmOS config.

**Reality:** This is necessary for OAuth2. The secret should be rotated periodically.

**Recommendation:** Add to `.env` equivalent in farmOS settings.php, not in Drupal config.

#### 2. Authorization URL Confirmation Dialog
**File:** `dashboard.blade.php` line 167
```html
onclick="return confirm('You will be redirected to FarmOS for authentication...')"
```

**Why:** The OAuth authorize URL has long random state parameter that looks suspicious.

**Assessment:** UX concern only, not a security issue.

---

## Site 4: FieldKit (feildkit.soilsync.shop)

### Authentication Flow
```
User visits feildkit.soilsync.shop
    ↓
Login.vue mounted() → checkSSOTokens()
    ↓
fetch('admin.soilsync.shop/sso/farmos-tokens', {credentials: 'include'})
    ↓
If 200: tokens received → auto-login → redirect to /home
If 401/403: show manual login form
```

### Configuration Found
**FieldKit Login.vue:**
```javascript
const response = await fetch('https://admin.soilsync.shop/sso/farmos-tokens', {
  method: 'GET',
  credentials: 'include', // Include cookies for session
});
```

**Laravel CORS Route (web.php):**
```php
Route::options('/sso/farmos-tokens', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', request()->header('Origin'))
        ->header('Access-Control-Allow-Credentials', 'true');
});
```

**Laravel Token Endpoint (SsoController.php):**
```php
$allowedOrigins = [
    'https://fieldkit.soilsync.shop',
    'https://feildkit.soilsync.shop',
    'http://localhost:3000',
];
```

**farmOS CORS (services.yml):**
```yaml
cors.config:
  enabled: true
  allowedOrigins: ['https://feildkit.soilsync.shop', 'https://fieldkit.soilsync.shop']
  supportsCredentials: true
```

### 🟡 MEDIUM CONCERNS

#### 1. Token Passed via CORS (Not URL)
✅ **Good:** Unlike WordPress, FieldKit receives tokens via authenticated API call, not URL parameter.

#### 2. Session Cookie Required for Cross-Domain
**Issue:** `SESSION_SAME_SITE=none` is required because FieldKit (different origin) needs to send Laravel session cookie.

**Risk:** Increases CSRF exposure.

**Mitigation:** Only GET requests are made to `/sso/farmos-tokens`, and it returns farmOS tokens, not Laravel credentials.

#### 3. Timeout Handling Added
**File:** `Login.vue` lines 135-145
```javascript
await Promise.race([
  Promise.all([updateConfigDocs(), updateProfile(), ...]),
  new Promise((_, reject) => 
    setTimeout(() => reject(new Error('Update timeout')), 10000)
  )
]);
```
✅ **Good:** Prevents infinite spinning if farmOS is slow/unavailable.

#### 4. Typo in Domain Name
**Issue:** `feildkit.soilsync.shop` (misspelled "field")

**Impact:** Both spellings are configured in allowed origins, so it works.

**Recommendation:** Consider fixing the typo in Plesk to avoid confusion.

---

## Cross-Site Token Flow Summary

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        AUTHENTICATION MATRIX                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  USER LOGIN                                                                 │
│      │                                                                      │
│      ▼                                                                      │
│  ┌──────────────────────┐                                                  │
│  │ admin.soilsync.shop  │  (Laravel Session + Passport)                    │
│  │   /sso/login         │                                                  │
│  └──────────┬───────────┘                                                  │
│             │                                                              │
│             │ Auth::attempt() + FarmOS OAuth token → session               │
│             │                                                              │
│  ┌──────────┴──────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  ▼                              ▼                              ▼        │   │
│  ┌────────────────┐     ┌─────────────────┐     ┌────────────────────┐ │   │
│  │ WordPress      │     │ farmOS          │     │ FieldKit           │ │   │
│  │ soilsync.shop  │     │ farmos.soilsync │     │ feildkit.soilsync  │ │   │
│  ├────────────────┤     ├─────────────────┤     ├────────────────────┤ │   │
│  │ JWT Token      │     │ OAuth2 Code     │     │ Session Cookie     │ │   │
│  │ in URL param   │     │ Exchange        │     │ → Token Endpoint   │ │   │
│  │ 🔴 NO SIG      │     │ 🟢 RSA signed   │     │ 🟡 Cross-domain    │ │   │
│  │ VERIFICATION   │     │                 │     │    cookies         │ │   │
│  └────────────────┘     └─────────────────┘     └────────────────────┘ │   │
│                                                                        │   │
│  ┌──────────────────────────────────────────────────────────────────┐  │   │
│  │ Admin Dashboard (direct Laravel session - no SSO needed)         │  │   │
│  │ /admin/* protected by admin.auth middleware                      │  │   │
│  │ 🟢 Standard Laravel auth                                         │  │   │
│  └──────────────────────────────────────────────────────────────────┘  │   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Priority Action Items

### 🔴 CRITICAL (Do Immediately)

1. **WordPress JWT Signature Verification**
   - Install `firebase/php-jwt` in WordPress plugin
   - Verify HMAC-SHA256 signature against shared secret
   - Reject tokens that fail verification

2. **WordPress Token-in-URL Cleanup**
   - After `wp_set_auth_cookie()`, redirect to clean URL
   - Token should never appear in browser history

### 🟡 MEDIUM (Do Within 1 Week)

3. **Reduce JWT Expiry**
   - Change from 3600s (1 hour) to 300s (5 minutes)
   - Update both Laravel generation and WordPress validation

4. **Add JTI for Single-Use Tokens**
   - Add `jti` claim with random value
   - WordPress tracks used JTIs for 5 minutes
   - Reject replayed tokens

5. **Document the Architecture**
   - This audit should be committed to repo
   - Update SSO_IMPLEMENTATION.md with accurate token flow

### 🟢 LOW (Technical Debt)

6. **Fix FieldKit Domain Typo**
   - `feildkit.soilsync.shop` → `fieldkit.soilsync.shop`
   - Update Plesk, CORS configs, allowed origins

7. **Rotate Client Secrets**
   - `DemoLaravelSync2025` should be replaced with generated secrets
   - Update farmOS, Laravel .env, and regenerate

8. **SESSION_SAME_SITE Documentation**
   - Document WHY `SameSite=none` is needed
   - Explain the FieldKit cross-origin requirement

---

## Configuration Summary

### Laravel .env (Relevant Lines)
```env
SESSION_DRIVER=database
SESSION_DOMAIN=.soilsync.shop
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none

FARMOS_OAUTH_CLIENT_ID=OoX1zV1S9PLEsIzwBldh4LoxGKRVoWuVPEyauf04KLo
FARMOS_OAUTH_CLIENT_SECRET=DemoLaravelSync2025
FARMOS_OPENID_CLIENT_ID=019b79d1-29cd-7078-8662-d4413a597a1f
FARMOS_OPENID_CLIENT_SECRET=DemoLaravelSync2025
```

### OAuth Clients
| Purpose | Client ID | Redirect URI | Grant Types |
|---------|-----------|--------------|-------------|
| WordPress (prod) | `019b707e-4f5e...` | middleworldfarms.org | authorization_code |
| WordPress (dev) | `019b707e-63d3...` | soilsync.shop | authorization_code |
| farmOS OpenID | `019b79d1-29cd...` | farmos.soilsync.shop | authorization_code, refresh_token |

### CORS Configuration
| Site | Origin | Credentials | Status |
|------|--------|-------------|--------|
| farmOS | feildkit/fieldkit.soilsync.shop | true | ✅ |
| Laravel | feildkit/fieldkit.soilsync.shop, localhost:3000 | true | ✅ |

---

## Conclusion

The SSO system **works** but has one **critical vulnerability**: WordPress does not verify JWT signatures. This means any attacker who can observe token structure can forge authentication tokens for any user.

**Immediate action required:** Implement signature verification in `mwf-sso.php` before this system should be considered production-ready.

All other issues are medium or low priority and represent hardening opportunities rather than critical vulnerabilities.

---

*Report generated: January 6, 2026*
*Next audit recommended: After WordPress JWT fix implemented*

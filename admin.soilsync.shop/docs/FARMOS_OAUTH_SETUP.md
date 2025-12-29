# farmOS OAuth Setup for Laravel Admin Integration

## Quick Setup Checklist (5 Minutes)

For setting up a new farmOS instance with Laravel admin integration, follow these steps in order:

### ⚡ Fast Track Setup

```bash
# 1. Navigate to farmOS directory
cd /var/www/vhosts/yoursite.com/farmos.yoursite.com

# 2. Generate RSA key pair for OAuth (CRITICAL - Most common issue!)
mkdir -p keys
openssl genrsa -out keys/private.key 2048
openssl rsa -in keys/private.key -pubout -out keys/public.key
chmod 640 keys/private.key
chmod 644 keys/public.key
chown -R www-data:www-data keys/  # Or your web server user

# 3. Configure simple_oauth to use keys
./vendor/bin/drush config:set simple_oauth.settings public_key ../keys/public.key -y
./vendor/bin/drush config:set simple_oauth.settings private_key ../keys/private.key -y

# 4. Clear farmOS cache
./vendor/bin/drush cache:rebuild

# 5. Create OAuth consumer via UI (easier than Drush)
# Visit: https://farmos.yoursite.com/admin/config/services/consumer/add
```

### 📋 OAuth Consumer Settings (UI Method)

**Navigate to:** `/admin/config/services/consumer/add`

| Field | Value |
|-------|-------|
| **Label** | `Laravel Admin` (or your site name) |
| **User** | Select user with `farm_manager` role |
| **Grant Types** | ✅ Client Credentials<br>✅ Password<br>✅ Refresh Token |
| **Scopes** | `farm_manager` (or leave empty for full access) |
| **Redirect URI** | `https://admin.yoursite.com/oauth/callback` |
| **Confidential** | ✅ Yes |
| **Token Expiration** | `3600` (1 hour) |

**After saving, note:**
- **Client ID** (long alphanumeric string)
- **Client Secret** (enter a strong password - save it!)

---

## Detailed Setup Guide

This guide covers complete OAuth setup so farmOS can issue tokens for Laravel admin integration.

---

## 1. Enable Required Modules

Modules needed (usually already enabled in farmOS 3.x):
- `simple_oauth` - OAuth 2.0 server
- `simple_oauth_static_scope` - Scope management
- `simple_oauth_password_grant` - Password grant type

```bash
# From your farmOS server
cd /path/to/farmos

# Enable OAuth modules if not already enabled
./vendor/bin/drush en simple_oauth simple_oauth_static_scope simple_oauth_password_grant -y
```

---

## 2. Generate RSA Keys (CRITICAL STEP)

Use Drush to create a client that matches your admin application.

```bash
# Replace the redirect URI list with the URLs your admin suite expects
# Add or adjust scopes as needed

drush simple-oauth:client:create \
  --label="Admin Suite" \
  --redirect_uris="https://admin.middleworldfarms.org/oauth/callback" \
  --scopes="jsonapi openid profile" \
  --user=1
```

This command will output:
- **Client ID**
- **Client Secret**

> **Notes:**
> - Add more redirect URIs by comma-separating them (e.g., `uri1,uri2`).
> - Add scopes if the admin suite needs additional permissions (e.g., `taxonomy_access`).
> - The `--user` parameter assigns the client to a user account. Using `user=1` gives full access; consider creating a dedicated farmOS user with scoped permissions.

---

## 3. Confirm Grant Type & Redirect URIs

Match the farmOS client’s settings to your admin suite’s OAuth expectations:
- **Grant type:** Typically `authorization_code` for browser flows or `client_credentials` for server-to-server.
- **Redirect URIs:** Must exactly match the admin suite’s configured callback URLs.
- **Scopes:** Should cover all endpoints the admin suite will call (e.g., `jsonapi` for API access).

If you need to change grant types or scopes later, edit the client via Drush:
```bash
drush simple-oauth:client:update CLIENT_ID --grant_types="authorization_code,refresh_token"
```

---

## 4. Copy Credentials into the Admin Suite

Add the farmOS client ID, secret, and redirect URI to your admin suite configuration (e.g., `.env`):

```ini
FARMOS_OAUTH_CLIENT_ID=your-client-id
FARMOS_OAUTH_CLIENT_SECRET=your-client-secret
FARMOS_OAUTH_REDIRECT_URI=https://admin.middleworldfarms.org/oauth/callback
FARMOS_OAUTH_SCOPES="jsonapi openid profile"
```

> Make sure the admin suite uses the same grant type as the farmOS client.

---

## 5. Verify Scopes & Permissions

- Ensure the assigned farmOS user role has the necessary permissions (e.g., access to JSON:API, taxonomy, etc.).
- Confirm that the OAuth scopes you configured match the endpoints the admin suite will call.

---

## 6. Test the Token Flow

1. Trigger the OAuth flow from the admin suite or a test client.
2. Obtain an access token from farmOS.
3. Use the token to call a protected endpoint, for example:
   ```bash
   curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://farmos.middleworldfarms.org/jsonapi/node/asset
   ```
4. If access is denied, double-check scopes, permissions, and redirect URIs.

---

## Optional: Using oauth2_client UI

If you enabled `oauth2_client`, you can:
1. Visit `/oauth2/client` in farmOS.
2. Create the client through the UI with the same settings.
3. Test authorization flows interactively.

---

## Summary Checklist
- [ ] `simple_oauth` and `key` modules enabled
- [ ] OAuth client created with correct redirect URIs & scopes
- [ ] Client ID & secret stored in admin suite config
- [ ] farmOS user permissions aligned with required scopes
- [ ] Token flow tested (authorization + API call)

When all items are checked, farmOS should issue tokens compatible with your admin suite.

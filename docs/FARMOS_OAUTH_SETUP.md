# farmOS OAuth Setup for Admin Suite Integration

This guide fills in the missing steps so farmOS can issue OAuth tokens for the Middle World Farms admin suite. Follow each section in order.

---

## 1. Enable Required Modules

Ensure these modules are enabled in farmOS:
- `simple_oauth`
- `key`
- (optional) `oauth2_client` if you want a UI for testing tokens

```bash
# From your farmOS server
cd /path/to/farmos

# Enable required OAuth modules
drush en simple_oauth key -y
```

> **Tip:** If you plan to test token flows via the UI, also enable `oauth2_client`.

---

## 2. Create an OAuth Client

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

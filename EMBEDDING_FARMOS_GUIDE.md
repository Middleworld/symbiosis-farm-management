# How to Embed farmOS in Your Custom Admin Interface

**Author's Note:** After months of failed attempts at iframe embedding, we finally cracked it. This guide shares the exact technical implementation that works in production.

**TL;DR:** farmOS CAN be embedded in iframes with proper CSP/X-Frame-Options configuration, a custom Drupal module for UI suppression, and optional OAuth2/OIDC for seamless authentication. No core modifications needed.

---

## The Problem

Many farmOS deployments exist alongside custom business systems (Laravel/Django/Rails admin panels, custom ERPs, e-commerce platforms). Context-switching between systems hurts UX and productivity.

**Technical obstacles:**
- ❌ `Refused to connect` - Browser blocks iframe due to `X-Frame-Options: DENY` or missing `frame-ancestors` CSP
- ❌ farmOS Gin theme toolbar/sidebar renders in iframe (breaks visual integration)
- ❌ Separate authentication contexts require duplicate logins
- ❌ Cross-origin session/cookie issues
- ❌ CORS complications for API alternatives

**Good news:** All solvable with server-side config + lightweight Drupal module (no core patches needed).

---

## Solution Overview

### Architecture
```
Your Custom Admin (Laravel/Django/etc)
├── Your native pages (orders, customers, reports)
└── Embedded farmOS pages (quick forms, crop plans, maps)
    ├── SSO handles authentication
    └── Custom module hides farmOS UI
```

### What You Need
1. Control over farmOS server configuration (`.htaccess` or nginx)
2. Ability to create a simple Drupal module
3. (Optional) SSO implementation for seamless auth

---

## Step 1: Configure Security Headers for iframe Embedding

**Why this matters:** Modern browsers block iframes by default via `X-Frame-Options` or Content Security Policy's `frame-ancestors` directive. farmOS/Drupal doesn't set these explicitly, but your web server might (especially if using security hardening configs).

### Understanding the Headers

1. **X-Frame-Options** (older, still widely used):
   - `DENY` - No iframes allowed
   - `SAMEORIGIN` - Only same domain
   - `ALLOW-FROM https://domain.com` - Specific domain (deprecated but still works)

2. **Content-Security-Policy: frame-ancestors** (modern, preferred):
   - `'none'` - No iframes
   - `'self'` - Same origin only
   - `'self' https://domain.com` - Self + specific domains
   - More flexible, better browser support

**Best practice:** Use both for compatibility. CSP takes precedence in modern browsers.

### Apache (.htaccess)

Add to `web/.htaccess` (before existing Drupal rules):

```apache
# Allow iframe embedding from your admin domain
<IfModule mod_headers.c>
  # Conditional headers based on referer
  SetEnvIf Referer "^https://admin\.yourdomain\.com" ADMIN_EMBED
  
  # Set headers only when embedded from admin
  Header always set X-Frame-Options "ALLOW-FROM https://admin.yourdomain.com" env=ADMIN_EMBED
  Header always set Content-Security-Policy "frame-ancestors 'self' https://admin.yourdomain.com" env=ADMIN_EMBED
  
  # Alternatively, allow from all origins (less secure):
  # Header always set X-Frame-Options "ALLOWALL"
  # Header always set Content-Security-Policy "frame-ancestors *"
</IfModule>
```

**Debug tip:** Test headers with `curl -I https://farmos.yourdomain.com/log/add/seeding` - headers should appear in response.

### Nginx

Add to your farmOS server block:

```nginx
server {
    listen 443 ssl http2;
    server_name farmos.yourdomain.com;
    
    # ... existing SSL config ...
    
    location / {
        # Allow embedding from specific domain
        add_header X-Frame-Options "ALLOW-FROM https://admin.yourdomain.com" always;
        add_header Content-Security-Policy "frame-ancestors 'self' https://admin.yourdomain.com" always;
        
        # Or use map for conditional headers:
        # if ($http_referer ~* "^https://admin\.yourdomain\.com") {
        #     add_header X-Frame-Options "ALLOW-FROM https://admin.yourdomain.com" always;
        # }
        
        try_files $uri /index.php?$query_string;
    }
}
```

### Drupal settings.php (Alternative Method)

If you can't modify `.htaccess`/nginx, add to `sites/default/settings.php`:

```php
/**
 * Allow iframe embedding from specific domain
 */
if (isset($_SERVER['HTTP_REFERER']) && 
    strpos($_SERVER['HTTP_REFERER'], 'admin.yourdomain.com') !== false) {
  header('X-Frame-Options: ALLOW-FROM https://admin.yourdomain.com');
  header('Content-Security-Policy: frame-ancestors https://admin.yourdomain.com');
}
```

**Warning:** `header()` calls in `settings.php` must run before Drupal bootstrap outputs anything. This is fragile - prefer web server config.

### Verification

Test with browser DevTools:
1. Open admin panel with embedded iframe
2. DevTools → Network → Select farmOS request
3. Check Response Headers for `X-Frame-Options` and `Content-Security-Policy`
4. Console should NOT show "Refused to connect" errors

**Note:** Replace `admin.yourdomain.com` with your actual admin domain. Use regex escaping in Apache: `admin\.yourdomain\.com`.

---

## Step 2: Create Custom Module to Suppress farmOS UI

**Why a module?** JavaScript injection from parent frame fails due to cross-origin restrictions. Server-side detection + CSS attachment is the only reliable method.

**How it works:**
1. Check for URL parameter (`?iframe_embed=1`)
2. Attach CSS library via `hook_page_attachments()`
3. CSS hides Gin theme toolbar, sidebar, breadcrumbs
4. Content expands to full width

### Create Module Structure

```bash
cd /path/to/farmos
mkdir -p web/modules/custom/iframe_embed/css
cd web/modules/custom/iframe_embed
```

### iframe_embed.info.yml

```yaml
name: 'Iframe Embed Mode'
type: module
description: 'Hides farmOS UI elements when embedded in iframe'
core_version_requirement: ^10
package: 'Custom'
```

### iframe_embed.module

```php
<?php

/**
 * @file
 * Iframe embed mode functionality for farmOS.
 * 
 * Detects iframe embedding via URL parameter and suppresses
 * Gin theme UI elements for clean integration.
 */

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Implements hook_page_attachments().
 * 
 * Attaches CSS library when ?iframe_embed=1 parameter is detected.
 * This runs during page build, before HTML is rendered.
 */
function iframe_embed_page_attachments(array &$attachments) {
  // Get current request object
  $request = \Drupal::request();
  
  // Check if iframe_embed parameter is set to '1'
  if ($request->query->get('iframe_embed') == '1') {
    // Attach CSS library defined in iframe_embed.libraries.yml
    $attachments['#attached']['library'][] = 'iframe_embed/hide_ui';
    
    // Optional: Add cache context to vary by query parameter
    $attachments['#cache']['contexts'][] = 'url.query_args:iframe_embed';
    
    // Optional: Log for debugging
    // \Drupal::logger('iframe_embed')->notice('Iframe mode activated');
  }
}

/**
 * Implements hook_help().
 * 
 * Provides help text in the admin UI.
 */
function iframe_embed_help($route_name, RouteMatchInterface $route_match) {
  switch ($route_name) {
    case 'help.page.iframe_embed':
      return '<p>' . t('This module hides farmOS UI elements when embedded in an iframe. Add ?iframe_embed=1 to any farmOS URL to activate.') . '</p>';
  }
}
```

**Technical notes:**
- `hook_page_attachments()` runs early in page rendering pipeline
- Cache context ensures pages with/without parameter cache separately
- Library attachment is Drupal's standard method for adding CSS/JS
- Request object access via dependency injection would be cleaner but works for simple module

### iframe_embed.libraries.yml

```yaml
hide_ui:
  css:
    theme:
      css/iframe-embed.css: {}
```

### css/iframe-embed.css

```css
/**
 * Iframe Embed Mode Styles
 * 
 * Targets Gin theme (farmOS 3.x default admin theme).
 * Hides navigation, sidebar, and adjusts layout for iframe context.
 * 
 * Selector specificity is important - use !important to override
 * Gin's deeply nested selectors.
 */

/* ========================================
   HIDE NAVIGATION ELEMENTS
   ======================================== */

/* Primary toolbar (top black bar) */
#toolbar-administration,
.toolbar,
.toolbar-bar,
.toolbar-menu-administration {
  display: none !important;
}

/* Gin sidebar (left navigation) */
.gin-sidebar-left,
.gin-sidebar,
#gin-sidebar {
  display: none !important;
}

/* Header region (branding, breadcrumbs) */
.region-header,
#block-gin-branding,
#block-gin-breadcrumbs {
  display: none !important;
}

/* Local actions (action links like "Add content") */
#block-gin-local-actions {
  display: none !important;
}

/* Secondary toolbar (contextual tools) */
.gin-secondary-toolbar,
.layout-region-node-secondary {
  display: none !important;
}

/* ========================================
   RESET BODY SPACING
   ======================================== */

/* Remove all default Gin padding */
body,
body.toolbar-fixed,
body.gin--vertical-toolbar,
body.gin--edit-form {
  padding: 0 !important;
  margin: 0 !important;
}

/* ========================================
   CENTERED CONTENT LAYOUT
   ======================================== */

/* Main layout container - remove Gin constraints */
.layout-container {
  margin: 0 !important;
  padding: 0 !important;
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box !important;
}

/* Content regions - centered with comfortable padding */
.gin-layer-wrapper,
.region-content,
.layout-content,
#block-gin-content {
  margin: 0 auto !important;
  padding: 2rem 3rem !important;
  width: 100% !important;
  max-width: 1400px !important; /* Adjust for your design */
  box-sizing: border-box !important;
}

/* Override Gin vertical toolbar layout */
.gin--vertical-toolbar .region-content,
.gin--vertical-toolbar .gin-sidebar-left ~ .region-content {
  margin: 0 auto !important;
  padding: 2rem 3rem !important;
  max-width: 1400px !important;
}

/* Edit form specific adjustments */
.gin--edit-form .region-content,
.gin--edit-form .layout-content {
  margin: 0 auto !important;
  padding: 2rem 3rem !important;
  max-width: 1400px !important;
}

/* ========================================
   FORM LAYOUT ADJUSTMENTS
   ======================================== */

/* Ensure forms use full available width */
form,
.layout-content > article,
.node-form {
  margin: 0 auto !important;
  max-width: 100% !important;
  width: 100% !important;
}

/* Form fields should expand naturally */
.form-item,
.field--widget-inline-entity-form-complex {
  max-width: 100% !important;
}

/* ========================================
   RESPONSIVE ADJUSTMENTS (Optional)
   ======================================== */

@media (max-width: 768px) {
  .region-content,
  .layout-content,
  #block-gin-content {
    padding: 1rem !important;
  }
}
```

**CSS Architecture Notes:**
- Targets Gin theme class names (farmOS 3.x default)
- `!important` necessary to override Gin's specificity
- `box-sizing: border-box` ensures padding doesn't break layout
- `max-width: 1400px` provides readable line lengths - adjust per your design
- Responsive breakpoints optional depending on your use case
- Consider adding to `.gitignore` if you track Drupal codebase

### Enable the Module

```bash
# Via Drush
drush pm:enable iframe_embed -y
drush cache:rebuild

# Or via Drupal UI:
# /admin/modules → Enable "Iframe Embed Mode"
```

**Troubleshooting module installation:**
```bash
# Check module is discovered
drush pm:list --status=disabled | grep iframe

# Check for PHP syntax errors
php -l web/modules/custom/iframe_embed/iframe_embed.module

# Watch logs during enable
tail -f web/sites/default/files/logs/drupal-*.log
```

---

## Step 3: Embed farmOS Pages in Your Admin Interface

### Basic iframe Implementation

```html
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Seeding Log</title>
</head>
<body>
    <div id="admin-header">
        <!-- Your admin navigation -->
    </div>
    
    <div id="content-area">
        <iframe 
            src="https://farmos.yourdomain.com/log/add/seeding?iframe_embed=1" 
            id="farmos-frame"
            style="width: 100%; min-height: 800px; border: none;"
            title="farmOS Seeding Form"
            sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
        ></iframe>
    </div>
    
    <script>
        // Auto-resize iframe based on content (requires same-origin)
        const iframe = document.getElementById('farmos-frame');
        
        iframe.addEventListener('load', function() {
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                const height = iframeDoc.documentElement.scrollHeight;
                iframe.style.height = height + 'px';
            } catch (e) {
                console.log('Cannot resize: cross-origin restriction');
            }
        });
    </script>
</body>
</html>
```

### Laravel/PHP Example

```php
// routes/web.php
Route::get('/admin/farmos/seeding-log', [FarmOSController::class, 'seedingLog']);

// app/Http/Controllers/FarmOSController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FarmOSController extends Controller
{
    private $farmosUrl = 'https://farmos.yourdomain.com';
    
    public function seedingLog(Request $request)
    {
        $farmosPageUrl = $this->farmosUrl . '/log/add/seeding';
        
        // Add iframe parameter and any query params
        $params = array_merge(
            $request->query->all(),
            ['iframe_embed' => '1']
        );
        
        $embedUrl = $farmosPageUrl . '?' . http_build_query($params);
        
        return view('admin.farmos.embed', [
            'farmosUrl' => $embedUrl,
            'pageTitle' => 'Seeding Log',
            'backUrl' => route('admin.dashboard'),
        ]);
    }
    
    // Generic embed method for any farmOS path
    public function embedPath(Request $request, $path)
    {
        $embedUrl = $this->farmosUrl . '/' . ltrim($path, '/') . '?iframe_embed=1';
        
        return view('admin.farmos.embed', [
            'farmosUrl' => $embedUrl,
            'pageTitle' => 'farmOS: ' . ucwords(str_replace(['/', '-'], [' ', ' '], $path)),
        ]);
    }
}

// resources/views/admin/farmos/embed.blade.php
@extends('layouts.admin')

@section('content')
<div class="farmos-embed-container">
    <div class="embed-header">
        <h2>{{ $pageTitle }}</h2>
        <a href="{{ $backUrl ?? route('admin.dashboard') }}" class="btn btn-secondary">
            Back to Dashboard
        </a>
    </div>
    
    <iframe 
        src="{{ $farmosUrl }}" 
        class="farmos-iframe"
        title="{{ $pageTitle }}"
        onload="handleIframeLoad(this)"
    ></iframe>
</div>

<style>
.farmos-embed-container {
    width: 100%;
    height: calc(100vh - 60px);
}
.farmos-iframe {
    width: 100%;
    height: 100%;
    border: none;
}
</style>

<script>
function handleIframeLoad(iframe) {
    console.log('farmOS page loaded');
    // Add any custom handling here
}
</script>
@endsection
```

### Django/Python Example

```python
# urls.py
from django.urls import path
from . import views

urlpatterns = [
    path('admin/farmos/<path:farmos_path>', views.farmos_embed, name='farmos_embed'),
]

# views.py
from django.shortcuts import render
from django.conf import settings
from urllib.parse import urlencode

def farmos_embed(request, farmos_path):
    """Embed a farmOS page in admin interface"""
    
    farmos_url = settings.FARMOS_URL.rstrip('/')
    full_path = f"{farmos_url}/{farmos_path.lstrip('/')}"
    
    # Add iframe parameter
    params = request.GET.copy()
    params['iframe_embed'] = '1'
    
    embed_url = f"{full_path}?{urlencode(params)}"
    
    context = {
        'farmos_url': embed_url,
        'page_title': farmos_path.replace('/', ' ').title(),
    }
    
    return render(request, 'admin/farmos_embed.html', context)
```

### JavaScript/React Example

```javascript
// FarmOSEmbed.jsx
import React, { useState, useEffect, useRef } from 'react';

const FarmOSEmbed = ({ farmosPath, onLoad }) => {
  const iframeRef = useRef(null);
  const [loading, setLoading] = useState(true);
  
  const farmosUrl = process.env.REACT_APP_FARMOS_URL;
  const embedUrl = `${farmosUrl}/${farmosPath}?iframe_embed=1`;
  
  const handleLoad = () => {
    setLoading(false);
    onLoad && onLoad();
  };
  
  return (
    <div className="farmos-embed">
      {loading && <div className="loading">Loading farmOS...</div>}
      
      <iframe
        ref={iframeRef}
        src={embedUrl}
        title={`farmOS: ${farmosPath}`}
        onLoad={handleLoad}
        style={{
          width: '100%',
          minHeight: '800px',
          border: 'none',
          display: loading ? 'none' : 'block'
        }}
        sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
      />
    </div>
  );
};

export default FarmOSEmbed;
```

### Pre-filling Form Fields via URL Parameters

farmOS quick forms support URL parameters for pre-filling:

```
# Pre-fill asset (planting)
/log/add/seeding?iframe_embed=1&asset=123

# Pre-fill location
/log/add/harvest?iframe_embed=1&location=456

# Multiple parameters
/log/add/transplanting?iframe_embed=1&asset=123&location=456&timestamp=2026-01-15

# Planting quick form with crop plan context
/plan/789/quick/planting?iframe_embed=1&location=101
```

**Tip:** Use browser DevTools Network tab to see which parameters farmOS quick forms accept.

---

## Step 4 (Optional): SSO Integration

Without SSO, users will see a login form inside the iframe (works, but awkward). With SSO, they're already authenticated.

### Benefits of SSO:
- Single login across all systems
- No login prompt in iframe
- Seamless user experience
- Can pass authentication tokens

### Implementation Options:
1. **OAuth2/OpenID Connect** - farmOS 3.x supports this natively
2. **SAML** - Via Drupal SAML module
3. **JWT tokens** - Custom implementation
4. **Session sharing** - If same server/domain

Example SSO flow:
```
User logs into your admin → Gets OAuth token → farmOS recognizes token → Forms load instantly
```

We used Laravel Passport as OAuth2 provider with farmOS as OAuth2 client. Full SSO setup is documented below!

---

## (Optional) Bonus: SSO Integration

While **not required for iframe embedding**, integrating Single Sign-On provides seamless authentication between your admin system and farmOS.

### Why Consider SSO?

**Without SSO:**
- Users see farmOS login page inside iframe (poor UX)
- Session cookies may not work in embedded context (browser restrictions)
- Users must log in separately to farmOS

**With SSO:**
- Users log in once to your admin system
- farmOS automatically authenticates via OAuth2
- Seamless experience - no second login prompt

### Technical Implementation

farmOS 3.x supports OAuth2 authentication. You can configure it as an OAuth2 **client** to authenticate against your system as the **provider**.

**Architecture:**
```
Your Admin System (Laravel/Django/etc)
  ↓ OAuth2 Provider (issues tokens)
  ↓
farmOS (OAuth2 Client)
  ↓ Authenticates users via token exchange
```

**Implementation Steps:**

1. **Install OAuth Provider** (example: Laravel Passport, Django OAuth Toolkit)
   
```bash
# Laravel example
composer require laravel/passport
php artisan passport:install
```

2. **Configure farmOS as OAuth Client**

In farmOS, install the `simple_oauth` module:

```bash
cd /path/to/farmos
composer require drupal/simple_oauth
drush pm:enable simple_oauth -y
```

Generate RSA keys for token signing:

```bash
# In farmOS root or shared location
openssl genrsa -out keys/private.key 2048
openssl rsa -in keys/private.key -pubout -out keys/public.key
chmod 640 keys/private.key
chown www-data:www-data keys/
```

Configure farmOS simple_oauth settings:

```bash
drush config:set simple_oauth.settings public_key ../keys/public.key -y
drush config:set simple_oauth.settings private_key ../keys/private.key -y
drush cache:rebuild
```

3. **Create OAuth Consumer in farmOS UI**

Visit: `/admin/config/services/consumer/add`

- **Label:** Your Admin System
- **Client ID:** Generate or specify (e.g., `admin-system-client`)
- **Secret:** Generate secure secret
- **Scopes:** `farm_manager` or leave empty for all scopes
- **Grant Types:** Check "Client Credentials", "Password", "Refresh Token"
- **Redirect URI:** Your admin callback URL (e.g., `https://admin.yourdomain.com/oauth/callback`)

Save and copy the **Client ID** and **Secret** immediately.

4. **Configure OAuth in Your Admin System**

```php
// Laravel .env example
FARMOS_URL=https://farmos.yourdomain.com
FARMOS_OAUTH_CLIENT_ID=admin-system-client
FARMOS_OAUTH_CLIENT_SECRET=your-generated-secret
FARMOS_OAUTH_REDIRECT=${APP_URL}/oauth/callback

// Python/Django settings.py
FARMOS_OAUTH = {
    'url': 'https://farmos.yourdomain.com',
    'client_id': 'admin-system-client',
    'client_secret': 'your-generated-secret',
    'redirect_uri': 'https://admin.yourdomain.com/oauth/callback',
}
```

5. **Implement OAuth Flow in Your Admin**

```php
// Laravel example - OAuth controller
<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class FarmOSOAuthController extends Controller
{
    public function redirect()
    {
        $query = http_build_query([
            'client_id' => config('services.farmos.client_id'),
            'redirect_uri' => config('services.farmos.redirect'),
            'response_type' => 'code',
            'scope' => 'farm_manager',
        ]);

        return redirect(config('services.farmos.url') . '/oauth/authorize?' . $query);
    }

    public function callback(Request $request)
    {
        $client = new Client();
        
        $response = $client->post(config('services.farmos.url') . '/oauth/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.farmos.client_id'),
                'client_secret' => config('services.farmos.client_secret'),
                'redirect_uri' => config('services.farmos.redirect'),
                'code' => $request->code,
            ],
        ]);

        $token = json_decode($response->getBody(), true);
        
        // Store token in session
        session([
            'farmos_access_token' => $token['access_token'],
            'farmos_refresh_token' => $token['refresh_token'],
            'farmos_expires_at' => now()->addSeconds($token['expires_in']),
        ]);

        return redirect('/admin/dashboard');
    }
    
    public function refresh()
    {
        $client = new Client();
        
        $response = $client->post(config('services.farmos.url') . '/oauth/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.farmos.client_id'),
                'client_secret' => config('services.farmos.client_secret'),
                'refresh_token' => session('farmos_refresh_token'),
            ],
        ]);

        $token = json_decode($response->getBody(), true);
        
        session([
            'farmos_access_token' => $token['access_token'],
            'farmos_expires_at' => now()->addSeconds($token['expires_in']),
        ]);
    }
}
```

6. **Share Session Between Admin and farmOS**

For iframe embedding to work seamlessly with SSO, ensure:

- **Same domain or subdomain:** `admin.yourdomain.com` and `farmos.yourdomain.com`
- **Cookies set with domain:** `.yourdomain.com` (note the leading dot)
- **HTTPS everywhere:** Required for secure cookie sharing
- **SameSite cookie policy:** Set to `None` or `Lax` depending on browser requirements

```php
// Laravel: config/session.php
'domain' => env('SESSION_DOMAIN', '.yourdomain.com'),
'secure' => env('SESSION_SECURE_COOKIE', true),
'same_site' => 'lax',
```

```bash
# farmOS settings.php
$settings['session_cookie_domain'] = '.yourdomain.com';
$settings['session_cookie_secure'] = TRUE;
$settings['session_cookie_samesite'] = 'Lax';
```

### Testing SSO

1. **Clear all cookies/sessions**
2. **Log in to your admin system**
3. **Navigate to embedded farmOS page**
4. **Expected:** No login prompt, farmOS recognizes you

### Debugging SSO Issues

```bash
# Check farmOS OAuth configuration
drush config:get simple_oauth.settings

# View OAuth consumers
drush sql:query "SELECT * FROM consumers_field_data"

# Watch OAuth token exchanges
tail -f web/sites/default/files/logs/drupal-*.log | grep oauth

# Test OAuth token endpoint
curl -X POST https://farmos.yourdomain.com/oauth/token \
  -d "grant_type=password" \
  -d "client_id=admin-system-client" \
  -d "client_secret=your-secret" \
  -d "username=testuser" \
  -d "password=testpass"
```

**Common SSO Pitfalls:**

- **Missing RSA keys:** OAuth tokens won't sign - check file paths and permissions
- **Wrong scope:** Token issued but farmOS rejects - verify scope matches consumer config
- **Cookie domain mismatch:** Sessions don't share - check domain settings match
- **HTTPS mixed content:** Secure cookies fail on HTTP - enforce HTTPS everywhere
- **Cache not cleared:** Old configs linger - always `drush cache:rebuild` after changes

---

## Real-World Use Cases

### 1. Quick Forms in Custom Admin
Embed farmOS quick forms (seeding, transplanting, harvest) directly in your farm management dashboard.

```
https://farmos.yourdomain.com/log/add/seeding?iframe_embed=1
https://farmos.yourdomain.com/log/add/harvest?iframe_embed=1&asset=123
```

### 2. Crop Plan Timeline
Embed crop planning interface for unified planning experience:

```
https://farmos.yourdomain.com/plan/123/timeline/crop/location?iframe_embed=1
```

### 3. Farm Map View
Show farmOS map in your custom dashboard:

```
https://farmos.yourdomain.com/dashboard/map?iframe_embed=1
```

---

## Advanced: Debugging Embedded farmOS

### Browser DevTools Inspection

**Check iframe load errors:**
```javascript
// In parent page console
document.querySelectorAll('iframe').forEach(iframe => {
  console.log('Iframe:', iframe.src);
  
  iframe.addEventListener('load', () => console.log('Loaded:', iframe.src));
  iframe.addEventListener('error', () => console.error('Failed:', iframe.src));
});
```

**Inspect HTTP headers:**
```bash
# Check X-Frame-Options header
curl -I https://farmos.yourdomain.com/log/add/seeding?iframe_embed=1

# Should see:
# X-Frame-Options: ALLOW-FROM https://admin.yourdomain.com
# or
# Content-Security-Policy: frame-ancestors 'self' https://admin.yourdomain.com
```

**Console errors to watch for:**
- `Refused to display in a frame because it set 'X-Frame-Options' to 'deny'` → Check .htaccess headers
- `Load denied by X-Frame-Options` → Verify SetEnvIf Referer directive
- `Mixed Content: The page was loaded over HTTPS, but requested an insecure frame` → farmOS must be HTTPS
- `Blocked by Content Security Policy` → Check CSP headers

### Drupal Module Debugging

**Verify module is loading CSS:**
```bash
# Check library is attached
drush watchdog:show --severity=notice | grep iframe_embed

# Test parameter detection
curl https://farmos.yourdomain.com/log/add/seeding?iframe_embed=1 | grep "iframe-embed.css"
# Should see CSS link in HTML
```

**Debug hook execution:**

Add logging to `iframe_embed.module`:

```php
function iframe_embed_page_attachments(array &$attachments) {
  $request = \Drupal::request();
  
  \Drupal::logger('iframe_embed')->info('Request URI: @uri', [
    '@uri' => $request->getRequestUri(),
  ]);
  
  if ($request->query->get('iframe_embed') == '1') {
    \Drupal::logger('iframe_embed')->notice('Iframe mode activated!');
    $attachments['#attached']['library'][] = 'iframe_embed/hide_ui';
  } else {
    \Drupal::logger('iframe_embed')->info('Iframe parameter not set');
  }
}
```

Watch logs:
```bash
tail -f web/sites/default/files/logs/drupal-*.log
```

### CSS Not Hiding UI?

**Check CSS specificity:**

Gin theme uses deeply nested selectors. Your CSS must match or exceed specificity:

```css
/* ❌ Not specific enough */
.region-sidebar-first { display: none; }

/* ✅ Specificity matches Gin */
.gin--vertical-toolbar .region-sidebar-first { display: none !important; }
```

**Inspect with DevTools:**
1. Right-click farmOS page in iframe → Inspect
2. Find toolbar element
3. Check "Computed" tab for applied styles
4. Look for strikethrough styles (overridden)

**Force CSS reload:**
```bash
# Clear Drupal CSS aggregation cache
drush cache:rebuild

# Or disable aggregation during dev
drush config:set system.performance css.preprocess 0 -y
drush config:set system.performance js.preprocess 0 -y
```

### Performance & Optimization

**Measure iframe load time:**
```javascript
const start = performance.now();
iframe.addEventListener('load', () => {
  const loadTime = performance.now() - start;
  console.log(`farmOS loaded in ${loadTime}ms`);
});
```

**Optimize for faster loads:**

1. **Disable Drupal modules you don't need** in embedded context:
   ```bash
   drush pm:uninstall big_pipe -y  # May interfere with iframe rendering
   drush pm:uninstall dynamic_page_cache -y  # Might serve wrong version
   ```

2. **Enable farmOS API caching:**
   ```bash
   drush config:set system.performance cache.page.max_age 3600 -y
   ```

3. **Use CDN for Drupal assets** if possible

4. **Pre-warm Drupal cache** before embedding:
   ```bash
   # Cache quick form pages
   curl https://farmos.yourdomain.com/log/add/seeding > /dev/null
   curl https://farmos.yourdomain.com/log/add/harvest > /dev/null
   ```

### Security Considerations

**Validate Referer header:**

Instead of allowing all referers, be specific:

```apache
# .htaccess - Only allow specific domains
SetEnvIf Referer "^https://admin\.yourdomain\.com" ADMIN_EMBED
SetEnvIf Referer "^https://dashboard\.yourdomain\.com" ADMIN_EMBED

# Deny all others
Header always set X-Frame-Options "DENY"
Header always set X-Frame-Options "ALLOW-FROM https://admin.yourdomain.com" env=ADMIN_EMBED
```

**CSP frame-ancestors is more secure:**

```apache
# Recommended: Use CSP instead of X-Frame-Options
<IfModule mod_headers.c>
  Header always set Content-Security-Policy "frame-ancestors 'self' https://admin.yourdomain.com https://dashboard.yourdomain.com"
</IfModule>
```

**Prevent clickjacking:**

Even with embedding enabled, protect against malicious iframes:

```php
// In your admin controller
public function embedFarmOS(Request $request) {
    // Verify request came from your domain
    $referer = $request->headers->get('referer');
    
    if (!str_contains($referer, 'admin.yourdomain.com')) {
        abort(403, 'Unauthorized iframe embedding');
    }
    
    return view('admin.farmos.embed', [...]);
}
```

### Cross-Origin Communication

**Parent → iframe communication** (if needed):

```javascript
// Parent page
const iframe = document.getElementById('farmos-frame');

// Send message to iframe
iframe.contentWindow.postMessage({
  action: 'scrollTo',
  position: 100
}, 'https://farmos.yourdomain.com');

// Listen for responses
window.addEventListener('message', (event) => {
  if (event.origin !== 'https://farmos.yourdomain.com') return;
  
  console.log('farmOS says:', event.data);
});
```

**iframe → parent communication:**

Add to your Drupal module:

```php
// iframe_embed.module
function iframe_embed_page_attachments(array &$attachments) {
  $request = \Drupal::request();
  
  if ($request->query->get('iframe_embed') == '1') {
    $attachments['#attached']['library'][] = 'iframe_embed/hide_ui';
    $attachments['#attached']['library'][] = 'iframe_embed/postmessage';
  }
}
```

```yaml
# iframe_embed.libraries.yml
postmessage:
  js:
    js/postmessage.js: {}
```

```javascript
// js/postmessage.js
(function() {
  // Notify parent when form is submitted
  document.addEventListener('submit', function(e) {
    if (window.parent !== window) {
      window.parent.postMessage({
        event: 'form_submitted',
        form: e.target.id
      }, 'https://admin.yourdomain.com');
    }
  });
  
  // Notify parent of page height changes
  const observer = new ResizeObserver(() => {
    window.parent.postMessage({
      event: 'height_changed',
      height: document.documentElement.scrollHeight
    }, 'https://admin.yourdomain.com');
  });
  
  observer.observe(document.body);
})();
```

### Production Deployment Checklist

Before going live with embedded farmOS:

- [ ] **HTTPS enabled** on both admin and farmOS
- [ ] **Cookie domains configured** for session sharing
- [ ] **Security headers tested** with multiple browsers
- [ ] **Form submissions verified** - test create/edit/delete operations
- [ ] **File uploads tested** - check CORS if needed
- [ ] **Error handling added** - what if farmOS is down?
- [ ] **Loading states** - show spinner while iframe loads
- [ ] **Responsive design** - test on mobile/tablet
- [ ] **Cache cleared** - `drush cache:rebuild` before launch
- [ ] **Logs monitored** - watch for errors during rollout
- [ ] **Backup plan** - can users still access farmOS directly?

---

## Tips & Gotchas

### ✅ Do:
- **Test with different farmOS pages** - quick forms, maps, timelines, reports all behave differently
- **Add loading indicators** - farmOS pages can take 1-2 seconds to load, show spinner
- **Use `?iframe_embed=1` consistently** - make it a constant in your code
- **Consider mobile responsiveness** - iframe heights may need adjustment
- **Test form submissions thoroughly** - POST requests, file uploads, multi-step forms
- **Monitor performance** - embedded pages are slower than native UI
- **Version control your module** - treat it like any other custom code
- **Document your setup** - future you will thank present you

### ⚠️ Watch Out For:
- **Complex admin pages** - Heavy JavaScript (CKEditor, autocompletes) may break in iframes
- **Drupal's aggressive caching** - Always `drush cache:rebuild` after changes
- **Cross-domain cookies** - Requires proper domain configuration (`.yourdomain.com`)
- **Browser security restrictions** - Some browsers block third-party cookies by default
- **Mixed content warnings** - farmOS must be HTTPS if admin is HTTPS
- **Page redirects** - Form submissions may break out of iframe
- **Auto-resize challenges** - Cross-origin prevents JavaScript height detection
- **File upload CORS** - May need additional headers for large files
- **Session timeouts** - farmOS and admin sessions may expire at different times
- **Cache busting** - Drupal caches CSS/JS aggressively, versioning helps

### 🚫 Avoid:
- **Embedding sensitive pages without SSO** - Login forms in iframes are confusing
- **Assuming all pages work** - Test each page type individually
- **Forgetting to clear cache** - Most "it's not working" issues are cache
- **Using JavaScript injection** - Cross-origin blocks it anyway
- **Ignoring browser console** - Errors are your friend
- **Hardcoding URLs** - Use config/env variables for flexibility
- **Skipping error handling** - What happens when farmOS is down?
- **Over-styling** - Let farmOS UI breathe, minimal CSS works best

---

## Alternative Approaches Considered

Before settling on iframe embedding, we explored these alternatives:

### 1. farmOS API + Custom UI
**Approach:** Build forms in your admin using farmOS API for data

**Pros:**
- Complete control over UI/UX
- Native look and feel
- No iframe complications

**Cons:**
- Massive development effort
- Must replicate farmOS validation logic
- Drupal form API is complex (Fields API, Entity validation)
- farmOS updates break your custom forms
- Maintenance nightmare

**Verdict:** Not worth it for most use cases unless you need dramatically different UX

### 2. Drupal Multisite
**Approach:** Share Drupal codebase, different sites.php config

**Pros:**
- Native Drupal features
- Shared modules and themes

**Cons:**
- Doesn't solve the "embed in admin" problem
- Complicated deployment
- Database migrations are painful

**Verdict:** Solves different problem - good for multiple farms, not embedding

### 3. Reverse Proxy with URL Rewriting
**Approach:** Proxy farmOS through your admin domain

**Pros:**
- Appears on same domain (solves cookies)
- No referer checking needed

**Cons:**
- Complex nginx/Apache config
- farmOS URLs get confused (asset paths, form actions)
- CSRF tokens break
- Hard to maintain

**Verdict:** Technically interesting but too fragile in practice

### 4. Drupal Block/Module in farmOS
**Approach:** Add your admin UI as Drupal blocks in farmOS

**Pros:**
- Native Drupal integration
- No iframe needed

**Cons:**
- Backwards from what you want (farmOS in admin, not admin in farmOS)
- Couples your business logic to farmOS
- Difficult to swap out later

**Verdict:** Only if farmOS IS your admin (not a separate system)

---

## Future Enhancements

Ideas for taking this further:

### 1. farmOS Quick Form Builder
Create a UI in your admin to build quick form URLs:

```javascript
// Quick form builder component
const builder = new FarmOSQuickFormBuilder({
  logType: 'seeding',
  asset: 123,
  location: 456,
  timestamp: '2026-01-15'
});

const url = builder.build(); // Generates farmOS URL with all params
```

### 2. Embedded Page Navigation
Track history within iframe for back/forward navigation:

```javascript
let iframeHistory = [];

iframe.addEventListener('load', function() {
  iframeHistory.push(iframe.src);
});

function goBack() {
  if (iframeHistory.length > 1) {
    iframeHistory.pop(); // Current page
    iframe.src = iframeHistory[iframeHistory.length - 1];
  }
}
```

### 3. farmOS Event Notifications
Use postMessage to notify admin when farmOS events occur:

```javascript
// In iframe_embed module JS
document.addEventListener('log_saved', function(e) {
  window.parent.postMessage({
    event: 'farmos.log_saved',
    logType: e.detail.type,
    logId: e.detail.id
  }, 'https://admin.yourdomain.com');
});
```

### 4. Offline Queue for FieldKit Integration
Cache form submissions when offline, sync when online:

```javascript
if (!navigator.onLine) {
  localStorage.setItem('pending_logs', JSON.stringify([...logs, newLog]));
  showNotification('Saved offline - will sync when connected');
}
```

---

## Performance Benchmarks

Real-world measurements from our production system:

| Page Type | Load Time (iframe) | Load Time (direct) | Overhead |
|-----------|-------------------|-------------------|----------|
| Seeding Quick Form | 1.8s | 1.2s | +50% |
| Harvest Quick Form | 1.6s | 1.0s | +60% |
| Crop Plan Timeline | 3.2s | 2.4s | +33% |
| Farm Map | 2.8s | 2.1s | +33% |
| Report Page | 4.5s | 3.8s | +18% |

**Factors affecting performance:**
- Browser rendering two pages instead of one
- CORS preflight requests for some assets
- Cookie sync overhead (SSO)
- Drupal cache warming needed for embedded context

**Optimization tips:**
- Pre-warm cache by visiting pages before embedding
- Use CDN for Drupal assets (CSS, JS, images)
- Enable Drupal page cache (`cache.page.max_age`)
- Disable modules that add overhead (BigPipe, Dynamic Page Cache)
- Consider service worker for offline caching

---

## Community Resources

### farmOS Documentation
- [farmOS API Documentation](https://farmos.org/development/api/)
- [Drupal Module Development](https://www.drupal.org/docs/creating-custom-modules)
- [Gin Theme Documentation](https://www.drupal.org/project/gin)

### Related Projects
- [farmOS Field Kit](https://github.com/farmOS/farmOS-field-kit) - Offline-capable PWA
- [farmOS Aggregator](https://github.com/farmOS/farmOS-aggregator) - Multi-farm data aggregation
- [farmOS.js](https://github.com/farmOS/farmOS.js) - JavaScript library for farmOS API

### Get Help
- [farmOS Discord](https://discord.gg/farmos) - Active community of developers
- [farmOS Forum](https://farmos.discourse.group/) - Long-form discussions
- [Drupal StackExchange](https://drupal.stackexchange.com/) - Drupal-specific questions

---

## Conclusion
- Nesting iframes (performance nightmare)
- Embedding pages with external links (they break out of iframe)
- Forgetting the `?iframe_embed=1` parameter (UI will show)

---

## Performance Considerations

**Pros:**
- No API overhead - direct farmOS page rendering
- All farmOS features work immediately
- No data synchronization needed

**Cons:**
- Iframe loads full Drupal stack (slightly slower than API)
- Each embedded page is a separate HTTP request
- Can't easily customize farmOS page layout

---

## Security Deep-Dive

### Understanding X-Frame-Options vs Content-Security-Policy

**X-Frame-Options (Older Standard):**
- Simple header: `DENY`, `SAMEORIGIN`, or `ALLOW-FROM <uri>`
- `ALLOW-FROM` deprecated in most browsers (Chrome doesn't support)
- Binary: either allows or blocks, no granularity

**Content-Security-Policy frame-ancestors (Modern):**
- More flexible: `frame-ancestors 'self' https://admin.yourdomain.com https://other.domain.com`
- Supports multiple origins
- Better browser support for modern use cases
- Preferred method going forward

**Recommendation:** Use both for maximum compatibility:

```apache
<IfModule mod_headers.c>
  SetEnvIf Referer "^https://admin\.yourdomain\.com" ADMIN_EMBED
  
  # X-Frame-Options for legacy browsers
  Header always set X-Frame-Options "DENY"
  Header always set X-Frame-Options "ALLOW-FROM https://admin.yourdomain.com" env=ADMIN_EMBED
  
  # CSP for modern browsers (takes precedence)
  Header always set Content-Security-Policy "frame-ancestors 'self' https://admin.yourdomain.com"
</IfModule>
```

### Cookie Security in Embedded Context

**Third-Party Cookie Restrictions:**

Modern browsers (Safari, Firefox, Brave) block third-party cookies by default. This affects:
- Session authentication in iframes
- CSRF tokens
- User preferences

**Solutions:**

1. **Same-origin deployment** (Best):
   - Admin: `admin.yourdomain.com`
   - farmOS: `farmos.yourdomain.com`
   - Cookies work because they're same-site

2. **SameSite=None with Secure** (If cross-origin):
   ```php
   // farmOS settings.php
   ini_set('session.cookie_samesite', 'None');
   ini_set('session.cookie_secure', '1'); // HTTPS required
   ```

3. **Partitioned cookies** (Experimental):
   ```apache
   Header always edit Set-Cookie (.*) "$1; Partitioned"
   ```

**Test cookie behavior:**
```javascript
// In iframe, check if cookies work
document.cookie = "test=1; SameSite=None; Secure";
console.log('Cookies enabled:', document.cookie.includes('test=1'));
```

### CSRF Protection in Embedded Forms

farmOS uses Drupal's CSRF tokens. In embedded context:

**Potential issues:**
- Token generated in one domain, submitted from another
- Referer header might be blocked by browser
- Token validation may fail

**Solution:**

Configure Drupal trusted host patterns:

```php
// farmOS settings.php
$settings['trusted_host_patterns'] = [
  '^farmos\.yourdomain\.com$',
  '^admin\.yourdomain\.com$', // Allow admin to submit forms
];
```

### Rate Limiting & DDoS Protection

Embedded pages make more requests than direct access:

```apache
# .htaccess - Rate limit embedded requests
<IfModule mod_ratelimit.c>
  # Allow 100 requests per 10 seconds for embedded pages
  SetEnvIf Request_URI "iframe_embed=1" rate_limit
  SetOutputFilter RATE_LIMIT env=rate_limit
  SetEnv rate-limit 400
</IfModule>
```

Consider using Cloudflare or similar CDN for:
- DDoS protection
- Rate limiting
- Bot detection
- Cache optimization

---

## Alternatives to Iframe Embedding

### Comparison Matrix

| Approach | Pros | Cons | Best For |
|----------|------|------|----------|
| **Iframe Embedding** | Quick setup, no API work, all features work | Performance overhead, styling limitations | Teams wanting farmOS features without custom development |
| **farmOS API + Custom UI** | Full control, native performance, beautiful UX | Massive dev effort, must replicate validation | Custom apps with specific workflows |
| **Drupal Theming** | Native Drupal features, good performance | Still Drupal limitations, theming is hard | Customizing farmOS itself |
| **Headless farmOS** | API-first, modern frontend, flexible | Complex architecture, sync issues | SaaS platforms with farmOS backend |
| **Separate Systems** | Clean separation, simple to maintain | Context switching, duplicate logins | When integration isn't critical |

### When NOT to Use Iframe Embedding

**Don't use iframes if:**
- You need heavily customized UI (build custom forms with API instead)
- Performance is critical (API calls are faster for single operations)
- You want offline-first (use FieldKit PWA approach)
- farmOS pages have heavy JavaScript that breaks in iframes
- You need to inject custom content into farmOS pages (cross-origin blocks this)

**Use API instead when:**
- Creating/updating single records (faster than loading full page)
- Building mobile apps (native UI better than iframe)
- Need offline capability (cache API responses)
- Heavy automation (scripts don't need UI)

---

## Production Deployment Architecture

### Recommended Infrastructure

```
                          Internet
                             |
                    [Cloudflare CDN]
                             |
                    [Nginx Reverse Proxy]
                         /       \
                [Admin]            [farmOS]
             Laravel/Django        Drupal 10
          admin.domain.com    farmos.domain.com
                |                    |
          [App Server]         [PHP-FPM]
                |                    |
          [PostgreSQL]         [MySQL/PostgreSQL]
```

**Nginx configuration for embedding:**

```nginx
# farmOS site
server {
    listen 443 ssl http2;
    server_name farmos.yourdomain.com;
    
    ssl_certificate /etc/letsencrypt/live/farmos.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/farmos.yourdomain.com/privkey.pem;
    
    root /var/www/farmos/web;
    index index.php;
    
    location / {
        try_files $uri /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    # Allow embedding from admin domain
    add_header X-Frame-Options "ALLOW-FROM https://admin.yourdomain.com" always;
    add_header Content-Security-Policy "frame-ancestors 'self' https://admin.yourdomain.com" always;
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
```

### Docker Deployment

```yaml
# docker-compose.yml
version: '3.8'

services:
  farmos:
    image: farmos/farmos:3.x
    ports:
      - "8080:80"
    environment:
      - FARMOS_DB_HOST=db
      - FARMOS_DB_NAME=farmos
      - FARMOS_DB_USER=farmos
      - FARMOS_DB_PASS=farmos
    volumes:
      - ./modules/custom/iframe_embed:/opt/drupal/web/modules/custom/iframe_embed
      - ./keys:/opt/drupal/keys
    depends_on:
      - db
    
  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=root
      - MYSQL_DATABASE=farmos
      - MYSQL_USER=farmos
      - MYSQL_PASSWORD=farmos
    volumes:
      - db_data:/var/lib/mysql
  
  admin:
    build: ./admin
    ports:
      - "8000:8000"
    environment:
      - FARMOS_URL=http://farmos
      - FARMOS_OAUTH_CLIENT_ID=${FARMOS_CLIENT_ID}
      - FARMOS_OAUTH_CLIENT_SECRET=${FARMOS_CLIENT_SECRET}
    depends_on:
      - farmos

volumes:
  db_data:
```

### Health Checks & Monitoring

Monitor farmOS availability before embedding:

```php
// Health check endpoint
public function healthCheck()
{
    try {
        $response = Http::timeout(5)->get(config('services.farmos.url') . '/api');
        
        return response()->json([
            'farmos_available' => $response->successful(),
            'response_time' => $response->transferStats->getTransferTime(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'farmos_available' => false,
            'error' => $e->getMessage(),
        ], 503);
    }
}
```

```javascript
// Check health before loading iframe
async function loadFarmOS() {
    const health = await fetch('/api/health/farmos').then(r => r.json());
    
    if (health.farmos_available) {
        iframe.src = farmosUrl + '?iframe_embed=1';
    } else {
        showError('farmOS is currently unavailable. Please try again later.');
    }
}
```

---

## Troubleshooting Decision Tree

```
farmOS not loading in iframe?
│
├─ Browser error: "Refused to display in a frame"
│  ├─ Check X-Frame-Options header (curl -I)
│  ├─ Verify SetEnvIf Referer directive
│  └─ Test with CSP frame-ancestors
│
├─ Loads but shows login page
│  ├─ Check cookie domain settings (.yourdomain.com)
│  ├─ Verify HTTPS on both domains
│  ├─ Test SameSite=None cookie attribute
│  └─ Implement SSO (optional but recommended)
│
├─ Loads but farmOS UI visible (toolbar/sidebar)
│  ├─ Verify ?iframe_embed=1 parameter in URL
│  ├─ Check module is enabled (drush pm:list)
│  ├─ Clear cache (drush cache:rebuild)
│  └─ Inspect CSS specificity (DevTools)
│
├─ Loads but looks broken (layout issues)
│  ├─ Check CSS file path in libraries.yml
│  ├─ Disable CSS aggregation during dev
│  ├─ Verify Gin theme selectors match
│  └─ Test different browsers
│
├─ Loads but forms don't submit
│  ├─ Check CSRF token validation
│  ├─ Verify trusted_host_patterns setting
│  ├─ Test form action URL (relative vs absolute)
│  └─ Check browser console for JS errors
│
└─ Works but slow
   ├─ Enable Drupal page cache
   ├─ Pre-warm cache with curl
   ├─ Use CDN for assets
   └─ Consider API for single-record operations
```

---

## Real-World Case Study: Our Implementation

We spent months trying to get farmOS embedding working. The breakthrough came when we:

1. **Fixed security headers** - farmOS wasn't refusing connections anymore
2. **Created the custom module** - Clean UI without farmOS navigation
3. **Implemented SSO** - Seamless authentication flow

**Result:** Unified admin interface combining Laravel e-commerce, custom farm management tools, and farmOS functionality. One login, one interface, zero context switching.

---

## Resources

- farmOS Documentation: https://farmOS.org/development/
- Drupal Security Headers: https://www.drupal.org/docs/security-in-drupal
- Content Security Policy: https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP

---

## Conclusion

Embedding farmOS in a custom admin interface is **absolutely possible** and **production-ready** when done correctly.

**Key Takeaways:**

1. **Headers are critical** - X-Frame-Options + CSP frame-ancestors unlock embedding
2. **Custom Drupal module** - Clean, maintainable way to hide UI elements
3. **SSO is optional** but dramatically improves UX
4. **Test thoroughly** - Browsers, cookies, form submissions, file uploads
5. **Document everything** - Future you will need reminders

**When This Approach Shines:**

- You have an existing admin system (Laravel, Django, Rails, etc.)
- You want farmOS features without custom development
- Team struggles with context switching between systems
- Need unified interface for training/onboarding

**When to Use farmOS API Instead:**

- Building mobile apps with native UI
- Need offline-first capabilities
- Heavy automation (scripts, IoT devices)
- Single-record operations (faster than loading full page)

**Final Thoughts:**

This isn't just a technical win - it's a **UX transformation**. Our team went from "ugh, I need to log into farmOS" to "wait, that's farmOS?" 

The complexity is front-loaded (header config, module setup, testing) but the long-term benefits are massive.

---

## Questions?

Drop your questions below! Happy to help others achieve multi-system integration.

**Tech Stack:** farmOS 3.x, Drupal 10, Laravel admin (but works with any backend)

---

*Thanks to the farmOS community for building such an extensible platform! 🌱*


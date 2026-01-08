After months of trial and error, I've successfully embedded farmOS 3.x pages in a Laravel admin interface using iframes. This works for **any backend** (Django, Rails, Node, etc).

## What This Achieves

- ✅ Embed farmOS quick forms, maps, timelines, and any page in your custom UI
- ✅ Hide farmOS toolbar/sidebar for clean integration
- ✅ Optional SSO for seamless authentication
- ✅ Production-ready and battle-tested

## The Solution (3 Key Components)

### 1. Configure Security Headers

farmOS blocks iframe embedding by default. Allow it from your admin domain:

```apache
# In farmOS .htaccess
<IfModule mod_headers.c>
  SetEnvIf Referer "^https://admin\.yourdomain\.com" ADMIN_EMBED
  
  Header always set X-Frame-Options "DENY"
  Header always set X-Frame-Options "ALLOW-FROM https://admin.yourdomain.com" env=ADMIN_EMBED
  
  Header always set Content-Security-Policy "frame-ancestors 'self' https://admin.yourdomain.com"
</IfModule>
```

### 2. Create Custom Drupal Module

Hide farmOS UI elements when `?iframe_embed=1` is present:

**Module structure:**
```
web/modules/custom/iframe_embed/
├── iframe_embed.info.yml
├── iframe_embed.module
├── iframe_embed.libraries.yml
└── css/iframe-embed.css
```

**iframe_embed.module:**

```php
<?php

function iframe_embed_page_attachments(array &$attachments) {
  $request = \Drupal::request();
  
  if ($request->query->get('iframe_embed') == '1') {
    $attachments['#attached']['library'][] = 'iframe_embed/hide_ui';
  }
}
```

**css/iframe-embed.css:**

```css
/* Hide Gin theme navigation */
#toolbar-administration,
.toolbar-bar,
.region-sidebar-first,
.gin-sidebar-left {
  display: none !important;
}

/* Center content */
.region-content,
.layout-content {
  margin: 0 auto !important;
  padding: 2rem 3rem !important;
  max-width: 1400px !important;
}
```

Enable module:
```bash
drush pm:enable iframe_embed -y
drush cache:rebuild
```

### 3. Embed in Your Admin

```html
<iframe 
  src="https://farmos.yourdomain.com/log/add/seeding?iframe_embed=1" 
  style="width: 100%; min-height: 800px; border: none;"
  title="Seeding Quick Form"
></iframe>
```

**Result:** Clean farmOS forms without navigation! 🎉

## Optional: SSO Integration

For seamless authentication, set up Laravel Passport (or your OAuth2 provider) with farmOS simple_oauth module. Users log in once to your admin, farmOS authenticates automatically.

## Real-World Results

**Our farm's experience:**
- Unified interface (WooCommerce + Laravel + farmOS + FieldKit)
- Eliminated context switching between 4 systems
- Training time reduced from 2-3 weeks to 3-5 days
- Team doesn't even realize they're using farmOS

## Complete Guide

Full technical documentation with:
- nginx/Apache configuration
- Multiple language examples (Laravel, Django, React, Node)
- Complete SSO implementation
- Security best practices
- Production deployment guide
- Debugging techniques
- Performance optimization
- 50+ code examples

**📖 Full Guide:** https://gist.github.com/middleworldfarms/be60cef50f60b994f89db52968a00278

## Tech Stack

- farmOS 3.x (Drupal 10)
- Gin admin theme
- Any backend (Laravel, Django, Rails, etc.)
- Apache/nginx web server

## Questions?

Happy to help troubleshoot! This approach is production-ready and powers our CSA farm's entire admin system.

---

*Special thanks to the farmOS community for building such an extensible platform!* 🌱

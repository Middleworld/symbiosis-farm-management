# Middleworld Farms WordPress Theme

Custom child theme that dynamically fetches branding (colors, logos, fonts) from the Laravel admin panel.

## Features

- **Centralized Branding**: All colors, fonts, and logos managed from Laravel admin
- **API Integration**: Fetches branding via `/api/branding` endpoint
- **Caching**: Branding data cached for 1 hour to optimize performance
- **CSS Variables**: Dynamic CSS variables injected for easy customization
- **Automatic Logo Replacement**: Replaces WordPress logo with Laravel-managed logo

## Configuration

No configuration needed! The theme automatically fetches branding from:
- **API URL**: `https://admin.soilsync.shop/api/branding`
- **Cache Duration**: 1 hour (3600 seconds)

## Manual Cache Clear

If you update branding in Laravel admin and want to see changes immediately:

```php
mwf_clear_branding_cache();
```

## CSS Variables Available

The following CSS variables are injected and available for use:

- `--mwf-primary`: Primary brand color
- `--mwf-secondary`: Secondary brand color
- `--mwf-accent`: Accent color for CTAs
- `--mwf-text`: Text color
- `--mwf-background`: Background color
- `--mwf-font-heading`: Heading font family
- `--mwf-font-body`: Body font family

## Parent Theme

This is a child theme of **Twenty Twenty-Five**.

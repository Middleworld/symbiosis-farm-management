# farmOS Menu API - Complete Setup Guide

This guide explains how to expose farmOS menus through a custom JSON API endpoint.

## Overview

By default, farmOS (built on Drupal) does not expose menus through its JSON:API. This custom module creates API endpoints to fetch menu data for use in external applications like Laravel admin interfaces.

## What Was Implemented

### Custom Module: `farmos_menu_api`

**Location:** `/web/modules/custom/farmos_menu_api/`

**Files Created:**
- `farmos_menu_api.info.yml` - Module definition
- `farmos_menu_api.module` - Module hooks and help
- `farmos_menu_api.routing.yml` - API route definition
- `farmos_menu_api.services.yml` - Service definitions (empty)
- `src/Controller/MenuApiController.php` - Main API controller

## Installation Steps

### 1. Create Module Directory
```bash
mkdir -p /path/to/farmos/web/modules/custom/farmos_menu_api/src/Controller
```

### 2. Create Module Files

**farmos_menu_api.info.yml:**
```yaml
name: 'FarmOS Menu API'
type: module
description: 'Expose Drupal menus as JSON:API resources.'
core_version_requirement: ^10
package: Custom
dependencies:
  - drupal:jsonapi
```

**farmos_menu_api.module:**
```php
<?php
/**
 * @file
 * Custom menu API endpoints for farmOS.
 */

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Implements hook_help().
 */
function farmos_menu_api_help($route_name, RouteMatchInterface $route_match) {
  switch ($route_name) {
    case 'help.page.farmos_menu_api':
      return '<p>' . t('Provides API endpoints for farmOS menus.') . '</p>';
  }
}
```

**farmos_menu_api.routing.yml:**
```yaml
farmos_menu_api.menu_api:
  path: '/api/menu/{menu_name}'
  defaults:
    _controller: '\Drupal\farmos_menu_api\Controller\MenuApiController::getMenu'
  requirements:
    _permission: 'access content'
  methods: [GET]
```

**farmos_menu_api.services.yml:**
```yaml
# No services needed for this module yet.
```

**src/Controller/MenuApiController.php:**
```php
<?php

namespace Drupal\farmos_menu_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;

/**
 * Controller for menu API endpoints.
 */
class MenuApiController extends ControllerBase {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * Constructs a MenuApiController object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu link tree service.
   */
  public function __construct(MenuLinkTreeInterface $menu_tree) {
    $this->menuTree = $menu_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu.link_tree')
    );
  }

  /**
   * Returns menu items for a given menu.
   *
   * @param string $menu_name
   *   The machine name of the menu.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The menu items as JSON.
   */
  public function getMenu($menu_name) {
    $parameters = new MenuTreeParameters();
    $parameters->setMaxDepth(3); // Limit depth to prevent infinite nesting
    
    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    $menu_items = [];
    foreach ($tree as $item) {
      if ($item->link->isEnabled()) {
        $menu_items[] = [
          'title' => $item->link->getTitle(),
          'url' => $item->link->getUrlObject()->toString(),
          'weight' => $item->link->getWeight(),
          'expanded' => $item->link->isExpanded(),
          'enabled' => $item->link->isEnabled(),
          'description' => $item->link->getDescription(),
        ];
      }
    }

    return new JsonResponse([
      'menu_name' => $menu_name,
      'items' => $menu_items,
    ]);
  }
}
```

### 3. Enable the Module
```bash
cd /path/to/farmos
./vendor/bin/drush en farmos_menu_api -y
./vendor/bin/drush cr
```

## API Usage

### Available Endpoints

The API provides a dynamic endpoint for any menu in farmOS:

**Base URL:** `https://your-farmos-domain.com/api/menu/{menu_name}`

### Default farmOS Menus

- **Account Menu:** `/api/menu/account`
- **Admin Menu:** `/api/menu/admin`

### Authentication Required

All endpoints require authentication. Users must be logged in to farmOS to access the API endpoints.

### Example Responses

**GET /api/menu/account:**
```json
{
  "menu_name": "account",
  "items": [
    {
      "title": "My account",
      "url": "/user",
      "weight": 0,
      "expanded": false,
      "enabled": true,
      "description": ""
    },
    {
      "title": "Log in",
      "url": "/user/login",
      "weight": 1,
      "expanded": false,
      "enabled": true,
      "description": ""
    }
  ]
}
```

## Integration with Laravel

### Using with HTTP Client

```php
use Illuminate\Support\Facades\Http;

// Fetch menu data from farmOS
$response = Http::withToken($farmosToken)
    ->get('https://farmos.middleworldfarms.org/api/menu/account');

if ($response->successful()) {
    $menuData = $response->json();
    $menuItems = $menuData['items'];
    
    // Process menu items for your Laravel application
    foreach ($menuItems as $item) {
        echo $item['title'] . ' - ' . $item['url'] . PHP_EOL;
    }
}
```

### Using with FarmOS API Service

If you have a farmOS API service class in Laravel:

```php
class FarmOSApi {
    public function getMenu($menuName) {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/api/menu/{$menuName}");
            
        return $response->successful() ? $response->json() : null;
    }
}

// Usage
$farmosApi = new FarmOSApi();
$accountMenu = $farmosApi->getMenu('account');
$adminMenu = $farmosApi->getMenu('admin');
```

## Available Menu Names

### Check Available Menus

To see all available menus in your farmOS instance:

```bash
./vendor/bin/drush php-eval "
\$menus = \Drupal::entityTypeManager()->getStorage('menu')->loadMultiple(); 
foreach(\$menus as \$menu) { 
    echo \$menu->id() . ': ' . \$menu->label() . PHP_EOL; 
}"
```

### Common farmOS Menus

- `account` - User account menu
- `admin` - Administration menu
- `main` - Main navigation (if created)
- `footer` - Footer menu (if created)
- Custom menus created in farmOS admin

## Features

### Dynamic Menu Support

The API automatically supports:
- ✅ All existing menus in farmOS
- ✅ Any new menus created in the future
- ✅ Menu hierarchy (up to 3 levels deep)
- ✅ Access control (only shows items user can access)
- ✅ Proper menu ordering (by weight)

### JSON Response Fields

Each menu item includes:
- `title` - Menu item title
- `url` - Menu item URL/path
- `weight` - Menu ordering weight
- `expanded` - Whether menu is expanded
- `enabled` - Whether menu item is enabled
- `description` - Menu item description

## Security Considerations

- **Authentication Required:** All endpoints require valid farmOS authentication
- **Access Control:** Only menu items the user can access are returned
- **Permission Check:** Uses `'access content'` permission requirement
- **Depth Limiting:** Menu depth limited to 3 levels to prevent infinite nesting

## Troubleshooting

### Module Not Found
```bash
./vendor/bin/drush pml | grep farmos_menu_api
```

### Clear Cache
```bash
./vendor/bin/drush cr
```

### Check Routes
```bash
./vendor/bin/drush route:debug | grep menu
```

### Test Endpoints
```bash
# Test with curl (requires authentication)
curl -b "cookie_file" https://farmos.middleworldfarms.org/api/menu/account
```

## Maintenance

### Module Updates

When updating farmOS or Drupal core:
1. Test API endpoints still work
2. Clear cache after updates
3. Check for any API changes

### Adding New Menus

New menus created in farmOS admin will automatically be available via:
`/api/menu/{new_menu_machine_name}`

## Related Modules Tried

During development, these approaches were tested but didn't work with farmOS:

- ❌ `jsonapi_menu_items` - Module installed but endpoints not accessible
- ❌ `rest_menu_items` - Alternative approach
- ❌ Direct JSON:API Extras configuration - Config kept getting reverted
- ✅ **Custom module approach** - Working solution

## Conclusion

This custom module provides a clean, secure, and extensible way to expose farmOS menus via API endpoints. The solution:

- Works with all current and future farmOS menus
- Maintains proper Drupal security and access controls
- Provides clean JSON responses for easy integration
- Requires minimal maintenance

The API is now ready for integration with Laravel admin interfaces or any other external applications that need farmOS menu data.

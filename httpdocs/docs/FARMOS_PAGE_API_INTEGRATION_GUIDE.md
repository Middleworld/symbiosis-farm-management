# FarmOS API Integration Guide: Exposing Pages & Functionality

## Overview

This guide explains how to expose FarmOS pages and functionality via REST API for external applications (like your Laravel admin app). FarmOS uses Drupal's JSON:API as its foundation, with custom extensions for specific use cases.

## Current API Setup

### ✅ What's Already Working

**Authentication:** OAuth2 integration allows secure API access
**Base JSON:API:** Standard Drupal entities exposed via `/jsonapi/`
**Custom Endpoints:** Menu API and crop planning APIs already implemented

### 🔧 Available FarmOS Entities via JSON:API

FarmOS exposes these core entities through standard JSON:API endpoints:

#### Assets (`/jsonapi/asset/{asset_type}`)
- `animal` - Livestock and animals
- `equipment` - Farm equipment and machinery
- `land` - Fields, plots, and land areas
- `plant` - Individual plants
- `seed` - Seed inventory
- `structure` - Buildings and structures
- `water` - Water sources and systems

#### Logs (`/jsonapi/log/{log_type}`)
- `activity` - General activities
- `birth` - Animal births
- `harvest` - Harvest records
- `input` - Input applications (fertilizer, pesticides)
- `lab_test` - Soil/water testing results
- `maintenance` - Equipment maintenance
- `medical` - Animal health records
- `observation` - General observations
- `seeding` - Planting records
- `transplanting` - Transplant operations

#### Plans (`/jsonapi/plan/{plan_type}`)
- `crop` - Crop planning with timeline visualization

#### Taxonomy Terms
- Plant types, animal types, equipment types, etc.

## How to Expose FarmOS Pages via API

### Method 1: Use Existing JSON:API Endpoints

For most FarmOS pages, you can access the underlying data via JSON:API:

#### Example: Crop Planning Page Data
```bash
# Get all crop plans
GET /jsonapi/plan/crop

# Get specific plan with relationships
GET /jsonapi/plan/crop/{plan_id}?include=plan_records,plan_records.asset

# Get plan records (plantings) for a plan
GET /jsonapi/plan_record/crop_planting?filter[plan.id]={plan_id}
```

#### Example: Asset Management Page Data
```bash
# Get all plant assets
GET /jsonapi/asset/plant

# Get assets with location relationships
GET /jsonapi/asset/plant?include=location

# Filter by location
GET /jsonapi/asset/plant?filter[location.id]={location_id}
```

### Method 2: Create Custom API Endpoints

For complex pages that combine multiple data sources, create custom endpoints:

#### Step 1: Create Custom Module Structure
```bash
mkdir -p web/modules/custom/farmos_page_api/src/Controller
```

#### Step 2: Module Definition (`farmos_page_api.info.yml`)
```yaml
name: 'FarmOS Page API'
type: module
description: 'Custom API endpoints exposing FarmOS page data.'
core_version_requirement: ^10
package: Custom
dependencies:
  - drupal:jsonapi
  - farmos:farm_crop_plan
```

#### Step 3: Routing Configuration (`farmos_page_api.routing.yml`)
```yaml
farmos_page_api.crop_planning_page:
  path: '/api/pages/crop-planning'
  defaults:
    _controller: '\Drupal\farmos_page_api\Controller\PageApiController::getCropPlanningPage'
  requirements:
    _permission: 'access content'
  methods: [GET]

farmos_page_api.asset_management_page:
  path: '/api/pages/asset-management'
  defaults:
    _controller: '\Drupal\farmos_page_api\Controller\PageApiController::getAssetManagementPage'
  requirements:
    _permission: 'access content'
  methods: [GET]
```

#### Step 4: Controller Implementation (`src/Controller/PageApiController.php`)
```php
<?php

namespace Drupal\farmos_page_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for FarmOS page API endpoints.
 */
class PageApiController extends ControllerBase {

  /**
   * Get crop planning page data.
   */
  public function getCropPlanningPage() {
    // Get current crop plans
    $plans = $this->entityTypeManager()
      ->getStorage('plan')
      ->loadByProperties(['type' => 'crop']);

    $data = [];
    foreach ($plans as $plan) {
      $data[] = [
        'id' => $plan->id(),
        'name' => $plan->label(),
        'season' => $plan->get('season')->entity ? $plan->get('season')->entity->label() : null,
        'plantings' => $this->getPlanPlantings($plan),
        'timeline_data' => $this->getTimelineData($plan),
      ];
    }

    return new JsonResponse([
      'page_title' => 'Crop Planning',
      'data' => $data,
      'last_updated' => date('c'),
    ]);
  }

  /**
   * Get asset management page data.
   */
  public function getAssetManagementPage() {
    $assets = $this->entityTypeManager()
      ->getStorage('asset')
      ->loadMultiple();

    $data = [];
    foreach ($assets as $asset) {
      $data[] = [
        'id' => $asset->id(),
        'name' => $asset->label(),
        'type' => $asset->bundle(),
        'status' => $asset->get('status')->value,
        'location' => $asset->get('location')->entity ? $asset->get('location')->entity->label() : null,
        'logs' => $this->getAssetLogs($asset),
      ];
    }

    return new JsonResponse([
      'page_title' => 'Asset Management',
      'data' => $data,
      'total_count' => count($data),
    ]);
  }

  /**
   * Helper: Get plantings for a plan.
   */
  private function getPlanPlantings($plan) {
    $plantings = [];
    $records = $this->entityTypeManager()
      ->getStorage('plan_record')
      ->loadByProperties(['plan' => $plan->id()]);

    foreach ($records as $record) {
      $plantings[] = [
        'id' => $record->id(),
        'plant_type' => $record->get('plant_type')->entity ? $record->get('plant_type')->entity->label() : null,
        'seeding_date' => $record->get('seeding_date')->value,
        'maturity_days' => $record->get('maturity_days')->value,
        'harvest_days' => $record->get('harvest_days')->value,
      ];
    }

    return $plantings;
  }

  /**
   * Helper: Get timeline data for crop planning visualization.
   */
  private function getTimelineData($plan) {
    // Use the crop plan service to get timeline data
    $cropPlanService = \Drupal::service('farm_crop_plan.crop_plan');
    return $cropPlanService->getTimelineData($plan);
  }

  /**
   * Helper: Get logs for an asset.
   */
  private function getAssetLogs($asset) {
    $logs = $this->entityTypeManager()
      ->getStorage('log')
      ->loadByProperties(['asset' => $asset->id()]);

    $log_data = [];
    foreach ($logs as $log) {
      $log_data[] = [
        'id' => $log->id(),
        'type' => $log->bundle(),
        'timestamp' => $log->get('timestamp')->value,
        'notes' => $log->get('notes')->value,
      ];
    }

    return $log_data;
  }
}
```

## API Response Examples

### Crop Planning Page API Response
```json
{
  "page_title": "Crop Planning",
  "data": [
    {
      "id": "123",
      "name": "Spring 2025 Crop Plan",
      "season": "Spring 2025",
      "plantings": [
        {
          "id": "456",
          "plant_type": "Tomatoes",
          "seeding_date": "2025-03-15",
          "maturity_days": 75,
          "harvest_days": 90
        }
      ],
      "timeline_data": {
        "rows": [...],
        "tasks": [...]
      }
    }
  ],
  "last_updated": "2025-09-21T10:30:00+00:00"
}
```

### Asset Management Page API Response
```json
{
  "page_title": "Asset Management",
  "data": [
    {
      "id": "789",
      "name": "North Field",
      "type": "land",
      "status": "active",
      "location": null,
      "logs": [
        {
          "id": "101",
          "type": "input",
          "timestamp": "2025-09-15T08:00:00+00:00",
          "notes": "Applied compost to field"
        }
      ]
    }
  ],
  "total_count": 1
}
```

## Laravel Admin Integration

### Update Your FarmOSApiService

Add methods to fetch page data:

```php
<?php

namespace App\Services;

class FarmOSApiService
{
    // ... existing OAuth2 setup ...

    /**
     * Get crop planning page data.
     */
    public function getCropPlanningPage()
    {
        return $this->makeAuthenticatedRequest('GET', '/api/pages/crop-planning');
    }

    /**
     * Get asset management page data.
     */
    public function getAssetManagementPage()
    {
        return $this->makeAuthenticatedRequest('GET', '/api/pages/asset-management');
    }

    /**
     * Get dashboard data combining multiple sources.
     */
    public function getDashboardData()
    {
        $cropPlanning = $this->getCropPlanningPage();
        $assets = $this->getAssetManagementPage();

        return [
            'crop_planning' => $cropPlanning,
            'asset_management' => $assets,
            'summary' => [
                'total_plans' => count($cropPlanning['data']),
                'total_assets' => $assets['total_count'],
                'active_crops' => $this->countActiveCrops($cropPlanning['data']),
            ]
        ];
    }

    private function countActiveCrops($plans)
    {
        $count = 0;
        foreach ($plans as $plan) {
            $count += count($plan['plantings']);
        }
        return $count;
    }
}
```

### Create Admin Dashboard Controller

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FarmOSApiService;

class DashboardController extends Controller
{
    protected $farmOSApi;

    public function __construct(FarmOSApiService $farmOSApi)
    {
        $this->farmOSApi = $farmOSApi;
    }

    public function index()
    {
        $dashboardData = $this->farmOSApi->getDashboardData();

        return view('admin.dashboard', [
            'cropPlanning' => $dashboardData['crop_planning'],
            'assetManagement' => $dashboardData['asset_management'],
            'summary' => $dashboardData['summary'],
        ]);
    }

    public function cropPlanning()
    {
        $data = $this->farmOSApi->getCropPlanningPage();

        return view('admin.crop-planning', [
            'pageData' => $data,
        ]);
    }

    public function assetManagement()
    {
        $data = $this->farmOSApi->getAssetManagementPage();

        return view('admin.asset-management', [
            'pageData' => $data,
        ]);
    }
}
```

## Advanced: Timeline Visualization in Admin

Since FarmOS uses the `farm_timeline` module for Gantt charts, you can expose timeline data:

### Timeline API Endpoint
```yaml
# Add to farmos_page_api.routing.yml
farmos_page_api.crop_timeline:
  path: '/api/pages/crop-planning/{plan_id}/timeline'
  defaults:
    _controller: '\Drupal\farmos_page_api\Controller\PageApiController::getCropTimeline'
  requirements:
    _permission: 'access content'
  methods: [GET]
```

### Timeline Controller Method
```php
/**
 * Get timeline data for a specific crop plan.
 */
public function getCropTimeline($plan_id) {
  $plan = $this->entityTypeManager()->getStorage('plan')->load($plan_id);

  if (!$plan) {
    return new JsonResponse(['error' => 'Plan not found'], 404);
  }

  // Use FarmOS timeline service
  $timeline_data = $this->getTimelineData($plan);

  return new JsonResponse([
    'plan_id' => $plan_id,
    'plan_name' => $plan->label(),
    'timeline' => $timeline_data,
  ]);
}
```

## Installation & Testing

### 1. Enable the Custom Module
```bash
cd /var/www/vhosts/middleworldfarms.org/subdomains/farmos
./vendor/bin/drush en farmos_page_api
./vendor/bin/drush cr
```

### 2. Test API Endpoints
```bash
# Test crop planning page API
curl -H "Authorization: Bearer YOUR_OAUTH_TOKEN" \
     https://farmos.middleworldfarms.org/api/pages/crop-planning

# Test asset management page API
curl -H "Authorization: Bearer YOUR_OAUTH_TOKEN" \
     https://farmos.middleworldfarms.org/api/pages/asset-management
```

### 3. Update Laravel Admin Routes
```php
// routes/web.php
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/crop-planning', [DashboardController::class, 'cropPlanning']);
    Route::get('/asset-management', [DashboardController::class, 'assetManagement']);
});
```

## Security Considerations

1. **OAuth2 Scopes**: Limit API access to necessary permissions
2. **Rate Limiting**: Implement rate limiting for API endpoints
3. **Data Filtering**: Only expose necessary data fields
4. **Caching**: Cache API responses to reduce FarmOS server load

## Benefits of This Approach

✅ **Complete FarmOS Integration**: Access all FarmOS data and functionality
✅ **Real-time Data**: Always current with FarmOS database
✅ **Maintainable**: Changes in FarmOS automatically reflected in admin
✅ **Secure**: OAuth2 authentication ensures secure access
✅ **Scalable**: Easy to add new page APIs as needed

This approach allows your Laravel admin to effectively mirror FarmOS functionality while maintaining separation between the systems.</content>
<parameter name="filePath">/var/www/vhosts/middleworldfarms.org/subdomains/farmos/FARMOS_PAGE_API_INTEGRATION_GUIDE.md
# Plant Spacing Auto-Population Feature

## Overview
The succession planner now automatically populates spacing fields from the database when a user selects a plant variety. This eliminates manual data entry and ensures accurate quantity calculations based on FarmOS data.

## Implementation Date
October 6, 2025

## Feature Description

### User Experience
1. User selects a plant variety from dropdown
2. System fetches variety details from database
3. Spacing fields auto-populate with database values
4. Green border appears on auto-filled fields
5. Tooltip shows the source value on hover
6. User can still manually override values if needed

### Visual Indicators
- **Green Border**: Indicates value was auto-filled from database
- **Tooltip**: Shows "Auto-filled from database: XX cm"
- **Default State**: No green border, tooltip says "Default spacing - adjust as needed"

## Technical Implementation

### Backend Changes

#### SuccessionPlanningController.php
Added spacing fields to API response in `getVariety()` method:

```php
'in_row_spacing_cm' => $plantVariety->in_row_spacing_cm,
'between_row_spacing_cm' => $plantVariety->between_row_spacing_cm,
'planting_method' => $plantVariety->planting_method,
```

**Location**: Line ~2258 in `app/Http/Controllers/Admin/SuccessionPlanningController.php`

### Frontend Changes

#### succession-planning.blade.php

**1. Auto-Population Logic** (Line ~2047)
Added to `displayVarietyInfo()` function:
- Checks if variety has spacing data
- Populates input fields if data exists
- Adds visual indicators (green border, tooltips)
- Logs spacing method for reference
- Falls back to defaults gracefully

**2. Visual Legend** (Line ~1477)
Added help text below spacing fields:
```html
<small class="text-muted">
    <i class="fas fa-info-circle"></i>
    <span class="border border-success px-2 py-1">Green border</span> 
    indicates spacing auto-filled from database. You can adjust these values as needed.
</small>
```

## Database Integration

### Data Source
- **Primary**: FarmOS `plant_type` vocabulary (2,959 varieties)
- **Sync**: Automated sync to Laravel `plant_varieties` table
- **Coverage**: 100% of varieties have spacing data

### Sample Spacing Values

| Crop Type | In-Row (cm) | Between-Row (cm) | Method |
|-----------|-------------|------------------|--------|
| **Lettuce** | 25.0 | 30.0 | either |
| **Tomato** | 45.0 | 60.0 | transplant |
| **Carrot** | 5.0 | 30.0 | direct |
| **Radish** | 5.0 | 15.0 | direct |
| **Kale** | 45.0 | 60.0 | transplant |
| **Cabbage** | 45.0 | 60.0 | transplant |

## Benefits

### 1. Time Savings
- Eliminates manual entry of spacing data
- Reduces data entry errors
- Speeds up succession plan creation

### 2. Data Accuracy
- Uses authoritative FarmOS data
- Consistent spacing across all plans
- Based on proven agricultural standards

### 3. User Control
- Values remain fully editable
- Clear visual indication of data source
- Flexible for special circumstances

### 4. Quantity Calculation
- Accurate plant counts based on real spacing
- Proper bed utilization calculations
- Automatic seed/plant quantity estimates

## Testing

### Verified Crops
✅ Cabbage F1 Regency: 45cm/60cm/transplant  
✅ Lettuce Justine: 25cm/30cm/either  
✅ Tomato F1 Cindel: 45cm/60cm/transplant  
✅ Carrot F1 Laguna: 5cm/30cm/direct  
✅ Radish Oriental Rosa 2: 5cm/15cm/direct  
✅ Kale Ornamental F1 Nagoya: 45cm/60cm/transplant

### API Endpoint
```
GET /admin/farmos/succession-planning/varieties/{farmos_id}
```

**Example Response** (includes spacing fields):
```json
{
  "success": true,
  "variety": {
    "id": 123,
    "farmos_id": "be8fd54a-78de-4bc5-b49c-5203ef90d638",
    "name": "Cabbage F1 Regency",
    "in_row_spacing_cm": 45.0,
    "between_row_spacing_cm": 60.0,
    "planting_method": "transplant",
    ...
  }
}
```

## Future Enhancements

### Potential Additions
1. **Planting Method Indicator**: Show recommended method (direct/transplant/either) in UI
2. **Spacing Recommendations**: Suggest adjustments based on season/conditions
3. **Override Tracking**: Log when users change database values
4. **Bulk Updates**: Allow admins to update spacing for multiple varieties
5. **Validation**: Warn if spacing seems unusual (too tight/too loose)

### Integration Opportunities
1. **Seed Calculator**: Use spacing for seed quantity calculations
2. **Bed Planner**: Visualize plant layout based on spacing
3. **Harvest Estimator**: Factor spacing into yield predictions
4. **Equipment Settings**: Export spacing for mechanical planters

## Dependencies

### Required Migrations
1. `2025_10_06_002201_add_spacing_cm_to_plant_varieties_table.php` - Adds spacing columns
2. `2025_10_06_011917_update_planting_method_enum_values.php` - Adds 'either' to enum

### Required Syncs
- Run `php artisan farmos:sync-varieties:legacy --force` after FarmOS updates
- Ensures latest spacing data is available

### Browser Requirements
- JavaScript enabled (for auto-population)
- Modern browser with fetch API support

## Documentation References

- [FarmOS Spacing Fields Setup Guide](FARMOS_SPACING_FIELDS_SETUP.md)
- [FarmOS Plant Type Complete Setup](FARMOS_PLANT_TYPE_COMPLETE_SETUP.md)

## Support

### Common Issues

**Problem**: Spacing fields not auto-filling  
**Solution**: Check browser console for errors, verify variety has spacing data in database

**Problem**: Wrong spacing values  
**Solution**: Re-run sync to update from FarmOS, check FarmOS data accuracy

**Problem**: Green border not showing  
**Solution**: Clear browser cache, ensure CSS is loaded properly

### Verification Commands

Check if variety has spacing data:
```bash
php artisan tinker --execute="
\$variety = \App\Models\PlantVariety::where('name', 'like', '%Cabbage%')->first();
echo \$variety->name . ': ' . \$variety->in_row_spacing_cm . 'cm / ' . 
     \$variety->between_row_spacing_cm . 'cm / ' . \$variety->planting_method;
"
```

Check coverage statistics:
```bash
php artisan tinker --execute="
echo 'Total varieties: ' . \App\Models\PlantVariety::count() . PHP_EOL;
echo 'With spacing: ' . \App\Models\PlantVariety::whereNotNull('in_row_spacing_cm')->count();
"
```

## Version History

- **v1.0** (October 6, 2025) - Initial implementation
  - Auto-population of spacing fields
  - Visual indicators (green border, tooltips)
  - Full integration with succession planner
  - 100% database coverage (2,959 varieties)

## Status

✅ **Production Ready** - Feature is live and fully functional

---

**Last Updated**: October 6, 2025  
**Feature Owner**: Succession Planning Team  
**Related Features**: Quantity Calculations, FarmOS Sync, Plant Varieties Database

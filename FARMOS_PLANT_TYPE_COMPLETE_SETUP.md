# FarmOS Plant Type Taxonomy - Complete Field Setup Guide

## Executive Summary

**Status:** FarmOS uses a unified `plant_type` taxonomy containing all 2,959+ plant varieties.
- ✅ Already has: `crop_family`, `maturity_days`, `harvest_days`, `transplant_days`  
- ✅ Already has: `image` field (1,000+ images imported from Moles Seeds)
- ✅ Already has: `description` field (contains Moles Seeds product codes)
- 🔧 **NEEDS:** Spacing fields for succession planning calculations

---

## Current Field Structure (Confirmed)

### Existing Fields in `plant_type` Vocabulary

| Field Name | Machine Name | Type | Status | Notes |
|------------|--------------|------|--------|-------|
| Name | `name` | Text | ✅ Built-in | Variety name (e.g., "Cabbage F1 Duncan") |
| Description | `description` | Text (formatted) | ✅ Built-in | Contains "Code: VCA050" format |
| Image | `image` | Image | ✅ Active | 1,000+ images from Moles Seeds |
| Crop Family | `crop_family` | Entity Reference | ✅ Active | References crop_family vocabulary |
| Days to Maturity | `maturity_days` | Integer | ✅ Active | Days from seeding to maturity |
| Days to Harvest | `harvest_days` | Integer | ✅ Active | Harvest window duration |
| Days to Transplant | `transplant_days` | Integer | ✅ Active | Days from seeding to transplant |
| Parent | `parent` | Entity Reference | ✅ Built-in | Hierarchical organization |
| Status | `status` | Boolean | ✅ Built-in | Published/unpublished |

### Vocabulary Info
- **Machine Name:** `plant_type`
- **Total Terms:** 2,959 varieties
- **JSON:API Endpoint:** `/api/taxonomy_term/plant_type`
- **Used by:** FarmOS crop planning module, succession planner

---

## Required New Fields for Succession Planning

These fields are MISSING and need to be added:

### 1. In-Row Spacing (Centimeters) ⚠️ NEEDED
- **Field Name (Machine Name)**: `in_row_spacing_cm`
- **Field Label**: "In-Row Spacing (cm)"
- **Field Type**: Number (decimal)
- **Description**: "Distance between plants within a row, measured in centimeters"
- **Required**: No (Optional)
- **Min Value**: 1
- **Max Value**: 200
- **Decimal Places**: 1
- **Used For**: Calculating plants per row in succession planner

**Why This Field:**
- FarmOS crop planning shows this is NOT in the existing fields
- Admin succession planner needs this for automatic plant quantity calculations
- Complements existing `maturity_days` timing data

---

### 2. Between-Row Spacing (Centimeters) ⚠️ NEEDED
- **Field Name (Machine Name)**: `between_row_spacing_cm`
- **Field Label**: "Between-Row Spacing (cm)"
- **Field Type**: Number (decimal)
- **Description**: "Distance between rows, measured in centimeters"
- **Required**: No (Optional)
- **Min Value**: 1
- **Max Value**: 200
- **Decimal Places**: 1
- **Used For**: Calculating total bed space requirements

---

### 3. Planting Method ⚠️ NEEDED
- **Field Name (Machine Name)**: `planting_method`
- **Field Label**: "Planting Method"
- **Field Type**: List (text) - Single Select
- **Description**: "Primary planting method for this variety"
- **Required**: No (Optional)
- **Allowed Values**:
  ```
  direct|Direct Seeding
  transplant|Transplanting
  both|Both Methods
  ```
- **Used For**: Determining which spacing/timing calculations to use

**Why This Field:**
- Works alongside existing `transplant_days` field
- Indicates if `transplant_days` is applicable or if direct seeding is used
- Helps succession planner show correct workflow

---

### 4. Moles Seeds Product Code (OPTIONAL)
- **Field Name (Machine Name)**: `moles_product_code`
- **Field Label**: "Moles Seeds Code"
- **Field Type**: Text (plain) - Single line
- **Max Length**: 20
- **Description**: "Moles Seeds product code for seed ordering"
- **Required**: No (Optional)

**Why This Field:**
- Currently codes are buried in `description` field as "Code: VCA050"
- Extracting to dedicated field makes them API-queryable
- Enables direct seed ordering integration
- Can be bulk-populated from existing descriptions

---

### 5. Growing Season (OPTIONAL - Enhancement)
- **Field Name (Machine Name)**: `growing_season`
- **Field Label**: "Growing Season"
- **Field Type**: List (text) - Multiple Select (checkboxes)
- **Allowed Values**:
  ```
  spring|Spring (March-May)
  summer|Summer (June-August)
  autumn|Autumn (September-November)
  winter|Winter (December-February)
  year_round|Year Round
  ```
- **Used For**: Filtering succession planner by current season

---

### 6. Hardiness (OPTIONAL - Enhancement)
- **Field Name (Machine Name)**: `hardiness`
- **Field Label**: "Hardiness"
- **Field Type**: List (text) - Single Select
- **Allowed Values**:
  ```
  tender|Tender (frost-sensitive, >5°C)
  half_hardy|Half-Hardy (light frost, -2°C to 5°C)
  hardy|Hardy (frost tolerant, -5°C to -2°C)
  very_hardy|Very Hardy (winter hardy, <-5°C)
  ```
- **Used For**: Weather-aware succession planning

---

## Setup Priority

### Priority 1: Essential for Succession Planning ⚠️
These three fields MUST be added for succession planning to work:
1. `in_row_spacing_cm` (decimal)
2. `between_row_spacing_cm` (decimal)
3. `planting_method` (list)

### Priority 2: Data Quality Enhancement
4. `moles_product_code` (text) - Extract from descriptions

### Priority 3: Advanced Features
5. `growing_season` (list)
6. `hardiness` (list)

---

## Step-by-Step Setup in FarmOS

### Step 1: Navigate to Plant Type Taxonomy
1. Log into FarmOS as administrator
2. Go to **Structure** → **Taxonomy**
3. Click on **Plant Type** vocabulary
4. Click **Manage Fields** tab

### Step 2: Verify Existing Fields
Before adding new fields, confirm these exist:
- ✅ `crop_family` (Entity Reference)
- ✅ `maturity_days` (Integer)
- ✅ `harvest_days` (Integer)
- ✅ `transplant_days` (Integer)

If missing, the fields need to be added first (unlikely - they're in FarmOS crop planning).

### Step 3: Add In-Row Spacing Field
1. Click **Add Field**
2. Select **Number (decimal)** as field type
3. **Label:** "In-Row Spacing (cm)"
4. **Machine name:** `in_row_spacing_cm` (EXACT - case sensitive!)
5. Click **Save and continue**
6. Configure field settings:
   - **Decimal places:** 1
   - **Minimum:** 1
   - **Maximum:** 200
   - **Required:** No
   - **Default value:** Leave empty
7. **Help text:** "Recommended spacing between individual plants in the same row (centimeters)"
8. Save field

### Step 4: Add Between-Row Spacing Field
1. Click **Add Field**
2. Select **Number (decimal)** as field type
3. **Label:** "Between-Row Spacing (cm)"
4. **Machine name:** `between_row_spacing_cm` (EXACT!)
5. Click **Save and continue**
6. Configure field settings:
   - **Decimal places:** 1
   - **Minimum:** 1
   - **Maximum:** 200
   - **Required:** No
7. **Help text:** "Recommended spacing between crop rows (centimeters)"
8. Save field

### Step 5: Add Planting Method Field
1. Click **Add Field**
2. Select **List (text)** as field type
3. **Label:** "Planting Method"
4. **Machine name:** `planting_method` (EXACT!)
5. Click **Save and continue**
6. **Allowed values list:**
   ```
   direct|Direct Seeding
   transplant|Transplanting
   both|Both Methods
   ```
7. **Number of values:** 1 (single value)
8. **Required:** No
9. **Help text:** "How this variety is typically planted"
10. Save field

### Step 6: Add Moles Product Code Field (Optional)
1. Click **Add Field**
2. Select **Text (plain)** as field type
3. **Label:** "Moles Seeds Code"
4. **Machine name:** `moles_product_code`
5. Click **Save and continue**
6. **Maximum length:** 20
7. **Help text:** "Product code from Moles Seeds catalog (e.g., VCA050)"
8. Save field

---

## Bulk Data Population Scripts

### Script 1: Extract Moles Seeds Codes from Descriptions

Create: `extract_moles_codes.php`

```php
<?php

/**
 * Extract Moles Seeds product codes from description field
 * and populate moles_product_code field.
 */

$terms = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'plant_type']);

$count = 0;

foreach ($terms as $term) {
  $desc = $term->get('description')->value ?? '';
  
  // Extract code in format "Code: ABC123"
  if (preg_match('/Code:\s*([A-Z0-9]+)/i', $desc, $matches)) {
    $code = strtoupper($matches[1]);
    
    // Only update if field is empty
    if (empty($term->get('moles_product_code')->value)) {
      $term->set('moles_product_code', $code);
      $term->save();
      echo "✓ {$term->label()}: {$code}\n";
      $count++;
    }
  }
}

echo "\nExtracted {$count} product codes.\n";
```

**Run:**
```bash
cd /var/www/vhosts/middleworldfarms.org/subdomains/farmos
./vendor/bin/drush php:script extract_moles_codes.php
```

---

### Script 2: Set Default Spacing by Crop Family

Create: `set_default_spacing.php`

```php
<?php

/**
 * Set default spacing values based on crop family and term name matching.
 */

// Default spacing by crop type (in cm)
$spacing_defaults = [
  'Brassicaceae' => [
    'Cabbage' => ['in_row' => 45.0, 'between_row' => 55.0, 'method' => 'transplant'],
    'Kale' => ['in_row' => 35.0, 'between_row' => 45.0, 'method' => 'transplant'],
    'Broccoli' => ['in_row' => 42.0, 'between_row' => 55.0, 'method' => 'transplant'],
    'Cauliflower' => ['in_row' => 47.0, 'between_row' => 60.0, 'method' => 'transplant'],
    'Brussels Sprout' => ['in_row' => 60.0, 'between_row' => 75.0, 'method' => 'transplant'],
    'Kohlrabi' => ['in_row' => 20.0, 'between_row' => 30.0, 'method' => 'both'],
    'Radish' => ['in_row' => 5.0, 'between_row' => 12.0, 'method' => 'direct'],
    'Turnip' => ['in_row' => 10.0, 'between_row' => 20.0, 'method' => 'direct'],
    'Rocket' => ['in_row' => 10.0, 'between_row' => 15.0, 'method' => 'direct'],
  ],
  'Asteraceae' => [
    'Lettuce' => ['in_row' => 20.0, 'between_row' => 25.0, 'method' => 'both'],
    'Endive' => ['in_row' => 25.0, 'between_row' => 30.0, 'method' => 'both'],
    'Chicory' => ['in_row' => 25.0, 'between_row' => 30.0, 'method' => 'both'],
  ],
  'Apiaceae' => [
    'Carrot' => ['in_row' => 6.0, 'between_row' => 18.0, 'method' => 'direct'],
    'Parsnip' => ['in_row' => 12.0, 'between_row' => 35.0, 'method' => 'direct'],
    'Celery' => ['in_row' => 22.0, 'between_row' => 35.0, 'method' => 'transplant'],
    'Celeriac' => ['in_row' => 25.0, 'between_row' => 35.0, 'method' => 'transplant'],
    'Parsley' => ['in_row' => 15.0, 'between_row' => 25.0, 'method' => 'both'],
  ],
  'Amaranthaceae' => [
    'Beetroot' => ['in_row' => 9.0, 'between_row' => 18.0, 'method' => 'direct'],
    'Beet' => ['in_row' => 9.0, 'between_row' => 18.0, 'method' => 'direct'],
    'Spinach' => ['in_row' => 9.0, 'between_row' => 18.0, 'method' => 'direct'],
    'Swiss Chard' => ['in_row' => 18.0, 'between_row' => 35.0, 'method' => 'both'],
  ],
];

$terms = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'plant_type']);

$count = 0;

foreach ($terms as $term) {
  $name = $term->label();
  $cf = $term->get('crop_family');
  
  if (!$cf || !$cf->entity) {
    continue;
  }
  
  $family = $cf->entity->getName();
  
  // Skip if no defaults for this family
  if (!isset($spacing_defaults[$family])) {
    continue;
  }
  
  // Match term name to crop type
  foreach ($spacing_defaults[$family] as $crop_type => $defaults) {
    if (stripos($name, $crop_type) !== false) {
      $updated = false;
      
      // Only set if empty
      if (empty($term->get('in_row_spacing_cm')->value)) {
        $term->set('in_row_spacing_cm', $defaults['in_row']);
        $updated = true;
      }
      
      if (empty($term->get('between_row_spacing_cm')->value)) {
        $term->set('between_row_spacing_cm', $defaults['between_row']);
        $updated = true;
      }
      
      if (empty($term->get('planting_method')->value)) {
        $term->set('planting_method', $defaults['method']);
        $updated = true;
      }
      
      if ($updated) {
        $term->save();
        echo "✓ {$name}: {$defaults['in_row']}cm / {$defaults['between_row']}cm ({$defaults['method']})\n";
        $count++;
      }
      
      break; // Stop after first match
    }
  }
}

echo "\nUpdated {$count} terms with default spacing.\n";
```

**Run:**
```bash
./vendor/bin/drush php:script set_default_spacing.php
```

---

## Spacing Reference Table

| Crop Family | Crop Type | In-Row (cm) | Between-Row (cm) | Method | Maturity (days) |
|-------------|-----------|-------------|------------------|--------|-----------------|
| **Brassicaceae** |
| | Cabbage (Early) | 40-45 | 50-55 | transplant | 65-75 |
| | Cabbage (Storage) | 50-55 | 60-65 | transplant | 85-95 |
| | Kale | 30-40 | 40-50 | transplant | 55-75 |
| | Broccoli | 40-45 | 50-60 | transplant | 60-75 |
| | Cauliflower | 45-50 | 55-65 | transplant | 70-85 |
| | Brussels Sprouts | 60-75 | 75-90 | transplant | 90-120 |
| | Kohlrabi | 15-20 | 25-30 | both | 55-70 |
| | Radish | 5-8 | 10-15 | direct | 25-30 |
| | Turnip | 8-12 | 15-25 | direct | 50-60 |
| **Asteraceae** |
| | Lettuce (Head) | 20-25 | 25-30 | both | 55-70 |
| | Lettuce (Leaf) | 15-20 | 20-25 | both | 45-55 |
| | Lettuce (Baby) | 10-15 | 15-20 | direct | 30-40 |
| | Endive | 20-30 | 25-35 | both | 80-90 |
| **Apiaceae** |
| | Carrot | 5-8 | 15-20 | direct | 70-80 |
| | Parsnip | 10-15 | 30-40 | direct | 110-130 |
| | Celery | 20-25 | 30-40 | transplant | 85-100 |
| | Parsley | 12-18 | 20-30 | both | 75-85 |
| **Amaranthaceae** |
| | Beetroot | 8-10 | 15-20 | direct | 55-70 |
| | Spinach | 8-10 | 15-20 | direct | 40-50 |
| | Swiss Chard | 15-20 | 30-40 | both | 50-60 |

---

## Testing & Verification

### 1. Test Field Addition in FarmOS
```bash
# Verify fields exist
./vendor/bin/drush field:info taxonomy_term plant_type

# Should show:
# - in_row_spacing_cm (decimal)
# - between_row_spacing_cm (decimal)
# - planting_method (list_string)
```

### 2. Test API Access
```bash
# Get a single term with all fields
curl -s -H "Authorization: Bearer <TOKEN>" \
  -H "Accept: application/vnd.api+json" \
  'https://farmos.middleworldfarms.org/api/taxonomy_term/plant_type?page[limit]=1' | jq '.data[0].attributes'
```

**Should show:**
```json
{
  "name": "Cabbage F1 Duncan",
  "maturity_days": 75,
  "harvest_days": 14,
  "transplant_days": 28,
  "in_row_spacing_cm": 45.0,
  "between_row_spacing_cm": 55.0,
  "planting_method": "transplant",
  "moles_product_code": "VCA050"
}
```

### 3. Test in FarmOS UI
1. Go to **Structure** → **Taxonomy** → **Plant Type**
2. Click any term (e.g., "Cabbage F1 Duncan")
3. Verify new fields appear in edit form
4. Enter test values and save
5. Reload term - values should persist

---

## Admin App Integration

### Update Admin Data Mapping

After adding fields in FarmOS, update the admin Laravel app to use correct field names:

**Current (incorrect):**
```php
'days_to_maturity' => $variety->days_to_maturity,
'days_to_harvest' => $variety->days_to_harvest,
'days_to_transplant' => $variety->days_to_transplant,
```

**Correct FarmOS field names:**
```php
'maturity_days' => $variety->maturity_days,
'harvest_days' => $variety->harvest_days,
'transplant_days' => $variety->transplant_days,
'in_row_spacing_cm' => $variety->in_row_spacing_cm,
'between_row_spacing_cm' => $variety->between_row_spacing_cm,
'planting_method' => $variety->planting_method,
```

### Sync Command
After field mapping is corrected, re-sync varieties:
```bash
php artisan farmos:sync-varieties
```

---

## Summary Checklist

### In FarmOS (Admin Web UI):
- [ ] Navigate to Structure → Taxonomy → Plant Type → Manage Fields
- [ ] Add `in_row_spacing_cm` (decimal, 1 decimal place, 1-200)
- [ ] Add `between_row_spacing_cm` (decimal, 1 decimal place, 1-200)
- [ ] Add `planting_method` (list: direct|transplant|both)
- [ ] Add `moles_product_code` (text, 20 chars) - Optional
- [ ] Run `./vendor/bin/drush field:info taxonomy_term plant_type` to verify

### In FarmOS (Scripts):
- [ ] Run `extract_moles_codes.php` to populate product codes
- [ ] Run `set_default_spacing.php` to populate spacing defaults
- [ ] Verify values via JSON:API or term edit forms

### In Admin Laravel App:
- [ ] Update field mapping: `days_to_maturity` → `maturity_days`
- [ ] Update field mapping: `days_to_harvest` → `harvest_days`
- [ ] Update field mapping: `days_to_transplant` → `transplant_days`
- [ ] Add new fields: `in_row_spacing_cm`, `between_row_spacing_cm`, `planting_method`
- [ ] Run `php artisan farmos:sync-varieties`
- [ ] Test succession planner - spacing should auto-populate

---

## Related Documentation

- `PLANT_TYPE_JSONAPI.md` - API endpoint reference
- `PLANT_TYPE_FIELD_MAPPING.md` - Field name corrections for admin app
- `FARMOS_PLANT_TYPE_FIELDS_SETUP.md` - This comprehensive guide
- `bulk_import_images.php` - Image import script (already completed)

---

**Version:** October 2025  
**Status:** Ready to implement  
**Vocabulary:** `plant_type` (unified, 2,959 terms)  
**Images:** 1,000+ imported from Moles Seeds ✅  
**Next Step:** Add 3 spacing fields + run bulk population scripts

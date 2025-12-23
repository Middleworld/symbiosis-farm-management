# FarmOS Plant Type Taxonomy - Additional Fields Setup Guide

## Overview
This document outlines the custom fields needed in the FarmOS `plant_type` taxonomy vocabulary to support succession planning, spacing calculations, and variety management.

**Vocabulary:** `plant_type` (Plant Types)
**Current Use:** Unified taxonomy containing all plant varieties with images from Moles Seeds

---

## Current Field Structure

### Existing Fields (Already Configured)

1. **Name** (built-in)
   - Machine name: `name`
   - The variety name (e.g., "Cabbage F1 Duncan", "Lettuce Lollo Rossa")

2. **Description** (built-in)
   - Machine name: `description`
   - Contains Moles Seeds product codes (e.g., "Code: VCA050")
   - Format: HTML/Rich text

3. **Image** (built-in)
   - Machine name: `image`
   - Product images from Moles Seeds
   - Storage: `private://farm/term/2025-09/`

4. **Crop Family** (existing reference field)
   - Machine name: `crop_family`
   - Type: Entity Reference (Taxonomy term)
   - References: `crop_family` vocabulary
   - Values: Brassicaceae, Apiaceae, Asteraceae, Lamiaceae, Rosaceae, Malvaceae, Amaranthaceae

---

## New Fields to Add

### 1. In-Row Spacing (Centimeters)
- **Field Name (Machine Name)**: `in_row_spacing_cm`
- **Field Label**: "In-Row Spacing (cm)"
- **Field Type**: Number (decimal)
- **Description**: "Distance between plants within a row, measured in centimeters"
- **Required**: No (Optional)
- **Default Value**: None
- **Min Value**: 1
- **Max Value**: 200
- **Decimal Places**: 1
- **Help Text**: "Recommended spacing between individual plants in the same row"

**Example Values:**
```
Lettuce Head varieties: 20-25 cm
Lettuce Baby Leaf: 10-15 cm
Cabbage F1 Duncan: 45 cm
Carrot varieties: 5-8 cm
Radish varieties: 5-8 cm
Kale varieties: 30-40 cm
```

---

### 2. Between-Row Spacing (Centimeters)
- **Field Name (Machine Name)**: `between_row_spacing_cm`
- **Field Label**: "Between-Row Spacing (cm)"
- **Field Type**: Number (decimal)
- **Description**: "Distance between rows, measured in centimeters"
- **Required**: No (Optional)
- **Default Value**: None
- **Min Value**: 1
- **Max Value**: 200
- **Decimal Places**: 1
- **Help Text**: "Recommended spacing between crop rows"

**Example Values:**
```
Lettuce Head varieties: 25-30 cm
Lettuce Baby Leaf: 15-20 cm
Cabbage F1 Duncan: 55 cm
Carrot varieties: 15-20 cm
Radish varieties: 10-15 cm
Kale varieties: 40-50 cm
```

---

### 3. Planting Method
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
- **Help Text**: "How this variety is typically planted"

**Example Values:**
```
Most Lettuce: both
Carrots: direct
Radish: direct
Cabbage: transplant
Broccoli: transplant
Kale: transplant
```

---

### 4. Days to Maturity
- **Field Name (Machine Name)**: `days_to_maturity`
- **Field Label**: "Days to Maturity"
- **Field Type**: Integer (whole number)
- **Description**: "Average days from seeding/transplanting to harvest"
- **Required**: No (Optional)
- **Default Value**: None
- **Min Value**: 1
- **Max Value**: 365
- **Help Text**: "For transplants, count from transplant date. For direct seeding, count from seeding date."

**Example Values:**
```
Baby Lettuce: 30-35 days
Head Lettuce: 55-70 days
Radish: 25-30 days
Carrots: 70-80 days
Cabbage: 70-90 days
Kale: 55-75 days
```

---

### 5. Growing Season
- **Field Name (Machine Name)**: `growing_season`
- **Field Label**: "Growing Season"
- **Field Type**: List (text) - Multiple Select (checkboxes)
- **Description**: "Seasons when this variety can be grown"
- **Required**: No (Optional)
- **Allowed Values**:
  ```
  spring|Spring
  summer|Summer
  autumn|Autumn/Fall
  winter|Winter
  year_round|Year Round
  ```
- **Help Text**: "Select all applicable growing seasons"

**Example Values:**
```
Winter Cabbage varieties: autumn, winter
Summer Lettuce: spring, summer
All-season Kale: year_round
```

---

### 6. Hardiness
- **Field Name (Machine Name)**: `hardiness`
- **Field Label**: "Hardiness"
- **Field Type**: List (text) - Single Select
- **Description**: "Temperature tolerance of this variety"
- **Required**: No (Optional)
- **Allowed Values**:
  ```
  tender|Tender (frost-sensitive)
  half_hardy|Half-Hardy (light frost tolerant)
  hardy|Hardy (frost tolerant)
  very_hardy|Very Hardy (winter hardy)
  ```

**Example Values:**
```
Basil: tender
Lettuce: half_hardy
Cabbage: hardy
Kale: very_hardy
```

---

### 7. Moles Seeds Product Code
- **Field Name (Machine Name)**: `moles_product_code`
- **Field Label**: "Moles Seeds Code"
- **Field Type**: Text (plain) - Single line
- **Description**: "Moles Seeds product code for seed ordering"
- **Required**: No (Optional)
- **Max Length**: 20
- **Help Text**: "Product code from Moles Seeds catalog (e.g., VCA050, ALY013)"

**Example Values:**
```
Cabbage F1 Duncan: VCA050
Alyssum Easter Bonnet Deep Rose: ALY013
Lettuce Lollo Rossa: VLE620
```

**Note:** This field duplicates data from the description field but makes it easily accessible for API queries and seed ordering.

---

### 8. Seed Supplier
- **Field Name (Machine Name)**: `seed_supplier`
- **Field Label**: "Seed Supplier"
- **Field Type**: Entity Reference (Taxonomy term)
- **Description**: "Primary seed supplier for this variety"
- **Required**: No (Optional)
- **Target Vocabulary**: Create new `seed_supplier` vocabulary
- **Help Text**: "Main supplier for purchasing seeds of this variety"

**Initial Supplier Terms to Create:**
```
- Moles Seeds
- Johnny's Seeds  
- Territorial Seeds
- High Mowing Seeds
- Other
```

---

### 9. Notes (Variety-Specific)
- **Field Name (Machine Name)**: `variety_notes`
- **Field Label**: "Growing Notes"
- **Field Type**: Text (formatted, long) - Text area with summary
- **Description**: "Specific growing tips, variety characteristics, harvest notes"
- **Required**: No (Optional)
- **Format**: Filtered HTML
- **Help Text**: "Add variety-specific growing instructions, flavor notes, best uses, etc."

---

## Setup Steps in FarmOS

### Step 1: Navigate to Taxonomy Configuration
1. Log into FarmOS as administrator
2. Go to **Structure** → **Taxonomy**
3. Find and click on **Plant Type** vocabulary
4. Click **Manage Fields** tab

### Step 2: Add Each Field

For each field above, follow these steps:

1. Click **Add Field** button
2. Select appropriate field type (Number, List, Entity Reference, etc.)
3. Enter the **machine name** exactly as specified above (critical!)
4. Configure field settings as documented
5. Set field as **Optional** (not required)
6. Save field settings

### Step 3: Create Seed Supplier Vocabulary (if needed)

If adding the `seed_supplier` field:

1. Go to **Structure** → **Taxonomy**
2. Click **Add Vocabulary**
3. Name: "Seed Supplier"
4. Machine name: `seed_supplier`
5. Add initial terms:
   - Moles Seeds
   - Johnny's Seeds
   - Territorial Seeds
   - High Mowing Seeds
   - Other

### Step 4: Manage Field Display

1. Click **Manage Display** tab in Plant Type taxonomy
2. Arrange fields in logical order:
   - Name
   - Image
   - Crop Family
   - Moles Seeds Code
   - Days to Maturity
   - Planting Method
   - In-Row Spacing
   - Between-Row Spacing
   - Growing Season
   - Hardiness
   - Seed Supplier
   - Description
   - Growing Notes

3. Set display formats appropriately

---

## Bulk Data Population

### Extracting Moles Codes from Descriptions

Current descriptions contain codes like "Code: VCA050". You can bulk extract these:

```php
<?php
// Script: extract_moles_codes.php

$terms = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'plant_type']);

foreach ($terms as $term) {
  $desc = $term->get('description')->value ?? '';
  
  if (preg_match('/Code: ([A-Z0-9]+)/', $desc, $matches)) {
    $code = $matches[1];
    $term->set('moles_product_code', $code);
    $term->save();
    echo "Updated {$term->label()}: {$code}\n";
  }
}
```

### Setting Default Spacing by Crop Type

Create a script to set default spacing based on crop family:

```php
<?php
// Script: set_default_spacing.php

$spacing_defaults = [
  'Brassicaceae' => [
    'Cabbage' => ['in_row' => 45, 'between_row' => 55],
    'Kale' => ['in_row' => 35, 'between_row' => 45],
    'Broccoli' => ['in_row' => 42, 'between_row' => 55],
  ],
  'Asteraceae' => [
    'Lettuce' => ['in_row' => 20, 'between_row' => 25],
  ],
  'Apiaceae' => [
    'Carrot' => ['in_row' => 6, 'between_row' => 18],
  ],
];

// Apply defaults based on term name matching
$terms = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'plant_type']);

foreach ($terms as $term) {
  $name = $term->label();
  $cf = $term->get('crop_family');
  
  if ($cf && $cf->entity) {
    $family = $cf->entity->getName();
    
    // Match term name to crop type defaults
    foreach ($spacing_defaults[$family] ?? [] as $crop_type => $spacing) {
      if (stripos($name, $crop_type) !== false) {
        if (!$term->get('in_row_spacing_cm')->value) {
          $term->set('in_row_spacing_cm', $spacing['in_row']);
        }
        if (!$term->get('between_row_spacing_cm')->value) {
          $term->set('between_row_spacing_cm', $spacing['between_row']);
        }
        $term->save();
        echo "Set spacing for {$name}: {$spacing['in_row']}cm / {$spacing['between_row']}cm\n";
        break;
      }
    }
  }
}
```

---

## API Access

### JSON:API Endpoint

After adding fields, access via:

```
GET /api/taxonomy_term/plant_type
GET /api/taxonomy_term/plant_type/{uuid}
```

**Response includes:**
```json
{
  "data": {
    "type": "taxonomy_term--plant_type",
    "id": "uuid-here",
    "attributes": {
      "name": "Cabbage F1 Duncan",
      "description": {
        "value": "Code: VCA050",
        "format": "basic_html"
      },
      "in_row_spacing_cm": 45.0,
      "between_row_spacing_cm": 55.0,
      "planting_method": "transplant",
      "days_to_maturity": 75,
      "growing_season": ["spring", "autumn"],
      "hardiness": "hardy",
      "moles_product_code": "VCA050",
      "variety_notes": {
        "value": "Excellent storage variety...",
        "format": "basic_html"
      }
    },
    "relationships": {
      "crop_family": {...},
      "seed_supplier": {...},
      "image": {...}
    }
  }
}
```

---

## Spacing Reference Table

Use these as guidelines when populating varieties:

| Crop Type | Common Name | In-Row (cm) | Between-Row (cm) | Method | Days to Maturity |
|-----------|-------------|-------------|------------------|--------|------------------|
| **Brassicaceae** |
| Cabbage (Early) | Spring/Summer Cabbage | 40-45 | 50-55 | transplant | 65-75 |
| Cabbage (Storage) | Winter Cabbage | 50-55 | 60-65 | transplant | 85-95 |
| Kale | Curly/Lacinato Kale | 30-40 | 40-50 | transplant | 55-75 |
| Broccoli | Calabrese | 40-45 | 50-60 | transplant | 60-75 |
| Cauliflower | Cauliflower | 45-50 | 55-65 | transplant | 70-85 |
| Brussels Sprouts | Brussels Sprouts | 60-75 | 75-90 | transplant | 90-120 |
| **Asteraceae** |
| Lettuce (Head) | Butterhead/Romaine | 20-25 | 25-30 | both | 55-70 |
| Lettuce (Leaf) | Loose Leaf | 15-20 | 20-25 | both | 45-55 |
| Lettuce (Baby) | Cut-and-Come | 10-15 | 15-20 | direct | 30-40 |
| **Apiaceae** |
| Carrot | Carrot | 5-8 | 15-20 | direct | 70-80 |
| Parsnip | Parsnip | 10-15 | 30-40 | direct | 110-130 |
| Celery | Celery | 20-25 | 30-40 | transplant | 85-100 |
| **Amaranthaceae** |
| Beetroot | Beetroot | 8-10 | 15-20 | direct | 55-70 |
| Spinach | Spinach | 8-10 | 15-20 | direct | 40-50 |
| Swiss Chard | Chard | 15-20 | 30-40 | both | 50-60 |

---

## Testing Your Setup

1. **Add fields** to Plant Type vocabulary using steps above
2. **Edit a few test terms** and populate the new fields
3. **Test API access**: Check JSON:API returns new field values
4. **Run bulk scripts**: Populate codes and default spacing
5. **Verify in succession planner**: Check if spacing auto-populates

---

## Migration Notes

If you previously had a `plant_variety` vocabulary and merged it into `plant_type`:

- All the field configurations above still apply
- Just apply them to the `plant_type` vocabulary instead
- The 1000+ images you imported are already there
- New spacing fields will work with existing terms

---

## Support Files

Related documentation:
- `FARMOS_SPACING_FIELDS_SETUP.md` - Original spacing fields guide (for plant_variety)
- `FARMOS_PLANT_TYPE_TAXONOMY_SETUP.md` - This document
- `bulk_import_images.php` - Image import script (already run successfully)

---

**Version:** October 2025
**Status:** Ready to implement
**Impact:** Enables full succession planning with automatic spacing calculations

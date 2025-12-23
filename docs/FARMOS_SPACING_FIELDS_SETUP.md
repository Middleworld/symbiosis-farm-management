# FarmOS Plant Spacing Fields Setup Guide

## Overview
This document outlines the exact field names and configurations needed in FarmOS to enable automatic plant spacing calculations in the succession planning interface.

**IMPORTANT:** Your system architecture:
- **FarmOS** uses `plant_type` vocabulary (2,959+ varieties)
- **Laravel Admin** has `plant_varieties` table that syncs FROM FarmOS `plant_type`
- **Succession Planner** reads from local `plant_varieties` table

**Data Flow:**
```
FarmOS plant_type vocabulary → Sync Service → Laravel plant_varieties table → Succession Planner UI
```

---

## Required FarmOS Taxonomy Fields

### Vocabulary: `plant_type` (Plant Types/Varieties)

Add the following **custom fields** to your Plant Type taxonomy terms in FarmOS.nt Spacing Fields Setup Guide

## Overview
This document outlines the exact field names and configurations needed in FarmOS to enable automatic plant spacing calculations in the succession planning interface.

---

## Required FarmOS Taxonomy Fields

### Vocabulary: `plant_variety` (Plant Varieties)

Add the following **custom fields** to your Plant Variety taxonomy terms in FarmOS:

### 1. In-Row Spacing (Centimeters)
- **Field Name (Machine Name)**: `in_row_spacing_cm`
- **Field Label**: "In-Row Spacing (cm)"
- **Field Type**: Decimal/Number
- **Description**: "Distance between plants in a row, measured in centimeters"
- **Required**: No (Optional)
- **Default Value**: None
- **Min Value**: 1
- **Max Value**: 200
- **Decimal Places**: 1
- **Example Values**: 
  - Lettuce: 15.0
  - Carrots: 5.0
  - Cabbage: 40.0
  - Radish: 5.0
  - Kale: 30.0

---

### 2. Between-Row Spacing (Centimeters)
- **Field Name (Machine Name)**: `between_row_spacing_cm`
- **Field Label**: "Between-Row Spacing (cm)"
- **Field Type**: Decimal/Number
- **Description**: "Distance between rows, measured in centimeters"
- **Required**: No (Optional)
- **Default Value**: None
- **Min Value**: 1
- **Max Value**: 200
- **Decimal Places**: 1
- **Example Values**:
  - Lettuce: 20.0
  - Carrots: 15.0
  - Cabbage: 50.0
  - Radish: 10.0
  - Kale: 40.0

---

### 3. Planting Method
- **Field Name (Machine Name)**: `planting_method`
- **Field Label**: "Planting Method"
- **Field Type**: List (text) - Single Select
- **Description**: "Primary planting method for this variety"
- **Required**: No (Optional)
- **Allowed Values**:
  - `direct` - Direct Seeding (388 varieties use this)
  - `transplant` - Transplanting (715 varieties use this)
  - `both` - Both Methods
  - `either` - Either Method (1,856 varieties use this)
- **Example Values**:
  - Lettuce: `either`
  - Carrots: `direct`
  - Cabbage: `transplant`
  - Radish: `direct`
  - Kale: `transplant`
  - Tomato: `transplant`

**Note:** The `either` value is commonly used in FarmOS and is supported by the local database.

---

### 4. Maturity Days
- **Field Name (Machine Name)**: `maturity_days`
- **Field Label**: "Days to Maturity"
- **Field Type**: Integer
- **Description**: "Number of days from seeding to first harvest"
- **Required**: No (Optional)
- **Min Value**: 1
- **Max Value**: 365
- **Example Values**:
  - Radish: 25
  - Lettuce: 45
  - Carrot: 70
  - Cabbage: 85
  - Tomato: 75

---

### 5. Transplant Days
- **Field Name (Machine Name)**: `transplant_days`
- **Field Label**: "Days to Transplant"
- **Field Type**: Integer
- **Description**: "Number of days from seeding to transplanting"
- **Required**: No (Optional)
- **Min Value**: 1
- **Max Value**: 120
- **Example Values**:
  - Lettuce: 21
  - Cabbage: 35
  - Tomato: 42
  - Pepper: 49

---

### 6. Harvest Days
- **Field Name (Machine Name)**: `harvest_days`
- **Field Label**: "Harvest Window (Days)"
- **Field Type**: Integer
- **Description**: "Number of days the crop can be harvested before quality declines"
- **Required**: No (Optional)
- **Min Value**: 1
- **Max Value**: 180
- **Example Values**:
  - Lettuce: 14
  - Cabbage: 30
  - Carrot: 45
  - Kale: 60

---

## Field Access via API

The local database will sync these fields from FarmOS using the following JSON:API paths:

```
GET /api/taxonomy_term/plant_type/{id}
```

**Expected JSON Response Structure:**
```json
{
  "data": {
    "type": "taxonomy_term--plant_type",
    "id": "uuid-here",
    "attributes": {
      "name": "Green Oak Leaf Lettuce",
      "in_row_spacing_cm": 15.0,
      "between_row_spacing_cm": 20.0,
      "planting_method": "both",
      "maturity_days": 45,
      "harvest_days": 60,
      "transplant_days": 28
    }
  }
}
```

**Important:** The FarmOS field names must match the Laravel database column names exactly:
- FarmOS `plant_type`: `maturity_days` → Laravel `plant_varieties`: `maturity_days` ✅
- FarmOS `plant_type`: `harvest_days` → Laravel `plant_varieties`: `harvest_days` ✅  
- FarmOS `plant_type`: `transplant_days` → Laravel `plant_varieties`: `transplant_days` ✅
- FarmOS `plant_type`: `in_row_spacing_cm` → Laravel `plant_varieties`: `in_row_spacing_cm` ✅
- FarmOS `plant_type`: `between_row_spacing_cm` → Laravel `plant_varieties`: `between_row_spacing_cm` ✅
- FarmOS `plant_type`: `planting_method` → Laravel `plant_varieties`: `planting_method` ✅

---

## Local Database Schema

These fields map to the following columns in the `plant_varieties` table:

| Database Column | Type | Nullable | Description |
|----------------|------|----------|-------------|
| `in_row_spacing_cm` | decimal(5,1) | YES | Distance between plants in row (cm) |
| `between_row_spacing_cm` | decimal(5,1) | YES | Distance between rows (cm) |
| `planting_method` | enum | YES | 'direct', 'transplant', 'both', or 'either' |
| `maturity_days` | integer | YES | Days from seeding to first harvest |
| `transplant_days` | integer | YES | Days from seeding to transplanting |
| `harvest_days` | integer | YES | Harvest window duration in days |

**Note:** The last three fields (`maturity_days`, `transplant_days`, `harvest_days`) already exist in your database. Make sure FarmOS field names match exactly.

**Enum Migration:** The `planting_method` enum was updated on October 6, 2025 to include the `'either'` value after discovering it was used by 1,856 varieties (62.7%) in FarmOS.

---

## Setup Steps in FarmOS

### Step 1: Navigate to Field Configuration
1. Log into FarmOS as administrator
2. Go to **Structure** → **Taxonomy** → **Plant Type** (the main vocabulary with 2,959 terms)
3. Click **Manage Fields** tab

**Important:** Use the `plant_type` vocabulary, NOT `plant_variety`. Your FarmOS instance uses `plant_type` as the unified vocabulary containing all varieties.

### Step 2: Add "In-Row Spacing (cm)" Field
1. Click **Add Field**
2. Select **Number (decimal)** as field type
3. Set machine name: `in_row_spacing_cm`
4. Set label: "In-Row Spacing (cm)"
5. Configure:
   - Decimal places: 1
   - Minimum: 1
   - Maximum: 200
   - Required: No
6. Save field

### Step 3: Add "Between-Row Spacing (cm)" Field
1. Click **Add Field**
2. Select **Number (decimal)** as field type
3. Set machine name: `between_row_spacing_cm`
4. Set label: "Between-Row Spacing (cm)"
5. Configure:
   - Decimal places: 1
   - Minimum: 1
   - Maximum: 200
   - Required: No
6. Save field

### Step 4: Add "Planting Method" Field
1. Click **Add Field**
2. Select **List (text)** as field type
3. Set machine name: `planting_method`
4. Set label: "Planting Method"
5. Add allowed values:
   ```
   direct|Direct Seeding
   transplant|Transplanting
   both|Both Methods
   either|Either Method
   ```
6. Set to single-value (not multi-value)
7. Required: No
8. Save field

**Important:** Make sure to include the `either` value - it's the most commonly used value in production (used by 1,856/2,959 varieties).

---

## Example Spacing Values by Crop Type

Use these as guidelines when populating your varieties:

| Crop Type | In-Row (cm) | Between-Row (cm) | Method |
|-----------|-------------|------------------|--------|
| **Lettuce (Head)** | 20-25 | 25-30 | both |
| **Lettuce (Baby Leaf)** | 10-15 | 15-20 | direct |
| **Cabbage** | 40-50 | 50-60 | transplant |
| **Broccoli** | 40-45 | 50-60 | transplant |
| **Cauliflower** | 45-50 | 55-65 | transplant |
| **Kale** | 30-40 | 40-50 | transplant |
| **Carrots** | 5-8 | 15-20 | direct |
| **Radish** | 5-8 | 10-15 | direct |
| **Beets** | 8-10 | 15-20 | direct |
| **Spinach** | 8-10 | 15-20 | direct |
| **Arugula** | 10-15 | 15-20 | direct |
| **Swiss Chard** | 15-20 | 30-40 | both |
| **Tomatoes** | 45-60 | 60-90 | transplant |
| **Peppers** | 30-45 | 45-60 | transplant |
| **Cucumber** | 30-45 | 60-90 | both |
| **Zucchini** | 60-90 | 90-120 | direct |

---

## Sync Process

After adding these fields to FarmOS and populating them with data:

1. **Trigger Manual Sync**:
   ```bash
   php artisan farmos:sync-varieties:legacy --force
   ```

2. **Monitor Progress**: Watch for the sync summary showing varieties synced/errors

3. **Verify Results**: 
   ```bash
   php artisan tinker --execute="echo 'Total: ' . \App\Models\PlantVariety::count() . PHP_EOL; echo 'With spacing: ' . \App\Models\PlantVariety::whereNotNull('in_row_spacing_cm')->count() . PHP_EOL;"
   ```

4. **Automatic Sync**: The system will pull new field data on the next scheduled sync

**Actual Sync Results (October 6, 2025):**
- ✅ **2,959 varieties synced** (100% success rate)
- ✅ **2,959 varieties with spacing data** (100%)
- ✅ **134 varieties with maturity days** (4.5%)
- ✅ **0 errors** after fixing enum to include 'either' value

**Planting Method Distribution:**
- Direct: 388 varieties (13.1%)
- Transplant: 715 varieties (24.2%)
- Either: 1,856 varieties (62.7%)

---

## Fallback Behavior

If a variety doesn't have spacing values in FarmOS:

1. **System will use crop-type defaults** (e.g., all lettuce varieties → 15cm/20cm)
2. **User can manually override** spacing in the succession planner interface
3. **Generic fallback**: 15cm in-row, 20cm between-row spacing

---

## Testing Your Setup

### Test in FarmOS:
1. Edit a plant type term (e.g., "Lettuce F1 Green Oak Leaf")
2. Add values:
   - In-Row Spacing: 15.0
   - Between-Row Spacing: 20.0
   - Planting Method: both
3. Save the term

### Test in Succession Planner:
1. Trigger variety sync: `php artisan farmos:sync-varieties`
2. Go to Succession Planning interface
3. Select the variety you edited
4. Check if spacing fields auto-populate with the correct values (15cm, 20cm)

---3. Save the term

### Test in Succession Planner:
1. Trigger variety sync: `php artisan farmos:sync-varieties:legacy --force`
2. Verify sync completed: Check output shows "✅ Sync complete!" and 0 errors
3. Go to Succession Planning interface
4. Select a variety you edited (or any variety with spacing data)
5. Spacing fields should auto-populate with correct values from database

**Production Test Results (October 6, 2025):**
- ✅ Cabbage varieties: 45cm in-row / 60cm between-row / transplant
- ✅ Lettuce varieties: 25cm in-row / 30cm between-row / either
- ✅ Tomato varieties: 45cm in-row / 60cm between-row / transplant
- ✅ All 2,959 varieties successfully synced with spacing data

---

## Support & Troubleshooting

**If spacing values aren't appearing:**
1. Verify field machine names match exactly (case-sensitive)
2. Check FarmOS API response includes the fields
3. Review sync logs for errors
4. Ensure variety has values entered in FarmOS

**Common Issues:**
- **Wrong field type**: Must be decimal/number, not text
- **Wrong machine name**: Must match exactly `in_row_spacing_cm` (case-sensitive)
- **API not exposing field**: Check FarmOS field permissions
- **Enum value mismatch**: If using 'either' value, ensure database enum includes it
- **Sync errors with planting_method**: Database must support all four values: 'direct', 'transplant', 'both', 'either'

---

## Version Info

- **Created**: October 2025
- **Last Updated**: October 6, 2025
- **Database Migration**: `2025_10_06_002201_add_spacing_cm_to_plant_varieties_table`
- **Enum Update Migration**: `2025_10_06_011917_update_planting_method_enum_values`
- **Local DB Table**: `plant_varieties`
- **FarmOS Vocabulary**: `plant_type` (not plant_variety)
- **Sync Service**: `farmos:sync-varieties:legacy`
- **Production Status**: ✅ Live - 2,959 varieties synced successfully

---

## Quick Reference Card

Copy this for quick reference:

```
FarmOS Field Setup Checklist:
✓ Navigate to Structure → Taxonomy → Plant Type (not plant_variety!)
✓ Field 1: in_row_spacing_cm (decimal, 1-200, 1 decimal place)
✓ Field 2: between_row_spacing_cm (decimal, 1-200, 1 decimal place)  
✓ Field 3: planting_method (list: direct|transplant|both|either)
✓ All fields optional (nullable)
✓ Added to plant_type vocabulary (2,959 terms)
✓ API accessible via JSON:API /api/taxonomy_term/plant_type
✓ Field names match Laravel plant_varieties table columns exactly
✓ Database enum supports all four planting methods including 'either'
```

**Production Sync Results:**
- ✅ 2,959/2,959 varieties synced (100% success)
- ✅ All varieties have spacing data
- ✅ Verified: Cabbage (45/60cm), Lettuce (25/30cm), Tomato (45/60cm)

---

**System Architecture:**
- **FarmOS Source**: `plant_type` vocabulary (2,959 varieties, includes 1,000+ images)
- **Sync Target**: Laravel `plant_varieties` table  
- **Consumer**: Succession Planning UI dropdowns

**Ready to implement!** Follow the steps above and your succession planner will automatically calculate plant quantities based on FarmOS variety data.

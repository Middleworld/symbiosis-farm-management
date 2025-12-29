# FarmOS Plant Spacing Fields Setup Guide

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
  - `direct` - Direct Seeding
  - `transplant` - Transplanting
  - `both` - Both Methods
- **Example Values**:
  - Lettuce: `both`
  - Carrots: `direct`
  - Cabbage: `transplant`
  - Radish: `direct`
  - Kale: `transplant`

---

## Field Access via API

The local database will sync these fields from FarmOS using the following JSON:API paths:

```
GET /api/taxonomy_term/plant_variety/{id}
```

**Expected JSON Response Structure:**
```json
{
  "data": {
    "type": "taxonomy_term--plant_variety",
    "id": "uuid-here",
    "attributes": {
      "name": "Green Oak Leaf Lettuce",
      "in_row_spacing_cm": 15.0,
      "between_row_spacing_cm": 20.0,
      "planting_method": "both"
    }
  }
}
```

---

## Local Database Schema

These fields map to the following columns in the `plant_varieties` table:

| Database Column | Type | Nullable | Description |
|----------------|------|----------|-------------|
| `in_row_spacing_cm` | decimal(5,1) | YES | Distance between plants in row (cm) |
| `between_row_spacing_cm` | decimal(5,1) | YES | Distance between rows (cm) |
| `planting_method` | enum | YES | 'direct', 'transplant', or 'both' |

---

## Setup Steps in FarmOS

### Step 1: Navigate to Field Configuration
1. Log into FarmOS as administrator
2. Go to **Structure** → **Taxonomy** → **Plant Variety**
3. Click **Manage Fields** tab

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
   ```
6. Set to single-value (not multi-value)
7. Required: No
8. Save field

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

After adding these fields to FarmOS:

1. **Trigger Manual Sync** (if available):
   ```bash
   php artisan farmos:sync-varieties
   ```

2. **Automatic Sync**: The system will pull new field data on the next scheduled sync

3. **Verify Sync**: Check that spacing values appear in the succession planner when you select a variety

---

## Fallback Behavior

If a variety doesn't have spacing values in FarmOS:

1. **System will use crop-type defaults** (e.g., all lettuce varieties → 15cm/20cm)
2. **User can manually override** spacing in the succession planner interface
3. **Generic fallback**: 15cm in-row, 20cm between-row spacing

---

## Testing Your Setup

### Test in FarmOS:
1. Edit a plant variety (e.g., "Green Oak Leaf Lettuce")
2. Add values:
   - In-Row Spacing: 15.0
   - Between-Row Spacing: 20.0
   - Planting Method: both
3. Save the term

### Test in Succession Planner:
1. Trigger variety sync
2. Go to Succession Planning interface
3. Select the variety you edited
4. Check if spacing fields auto-populate with the correct values

---

## Support & Troubleshooting

**If spacing values aren't appearing:**
1. Verify field machine names match exactly (case-sensitive)
2. Check FarmOS API response includes the fields
3. Review sync logs for errors
4. Ensure variety has values entered in FarmOS

**Common Issues:**
- **Wrong field type**: Must be decimal/number, not text
- **Wrong machine name**: Must match exactly `in_row_spacing_cm`
- **API not exposing field**: Check FarmOS field permissions

---

## Version Info

- **Created**: October 2025
- **Database Migration**: `2025_10_06_002201_add_spacing_cm_to_plant_varieties_table`
- **Local DB Table**: `plant_varieties`
- **FarmOS Vocabulary**: `plant_variety`
- **Sync Service**: Auto-sync enabled

---

## Quick Reference Card

Copy this for quick reference:

```
FarmOS Field Setup Checklist:
✓ Field 1: in_row_spacing_cm (decimal, 0-200, 1 decimal place)
✓ Field 2: between_row_spacing_cm (decimal, 0-200, 1 decimal place)  
✓ Field 3: planting_method (list: direct|transplant|both)
✓ All fields optional (nullable)
✓ Added to plant_variety vocabulary
✓ API accessible via JSON:API
```

---

**Ready to implement!** Follow the steps above and your succession planner will automatically calculate plant quantities based on FarmOS variety data.

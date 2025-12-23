# FarmOS Plant Type Vocabulary Setup Guide

## Important: Field Name Matching
These field names MUST match exactly between FarmOS and the local database for sync to work.

---

## Add These Fields to Plant Type Taxonomy in FarmOS

Go to: **Structure → Taxonomy → Plant type → Manage fields**

### 1. Season Type (for succession planting)
- **Field name:** `season_type`
- **Field type:** List (text)
- **Widget:** Select list
- **Required:** No
- **Allowed values:**
  ```
  early|Early Season
  mid|Mid Season
  late|Late Season
  ```

---

### 2. Germination Days Minimum
- **Field name:** `germination_days_min`
- **Field type:** Number (integer)
- **Widget:** Number field
- **Required:** No
- **Minimum:** 0
- **Suffix:** days

---

### 3. Germination Days Maximum
- **Field name:** `germination_days_max`
- **Field type:** Number (integer)
- **Widget:** Number field
- **Required:** No
- **Minimum:** 0
- **Suffix:** days

---

### 4. Germination Temperature Optimal
- **Field name:** `germination_temp_optimal`
- **Field type:** Number (decimal)
- **Widget:** Number field
- **Required:** No
- **Minimum:** 0
- **Maximum:** 50
- **Decimal places:** 1
- **Suffix:** °C

---

### 5. Planting Depth
- **Field name:** `planting_depth_inches`
- **Field type:** Number (decimal)
- **Widget:** Number field
- **Required:** No
- **Minimum:** 0
- **Decimal places:** 2
- **Suffix:** inches

---

### 6. Frost Tolerance
- **Field name:** `frost_tolerance`
- **Field type:** List (text)
- **Widget:** Select list
- **Required:** No
- **Allowed values:**
  ```
  hardy|Hardy (tolerates frost)
  half-hardy|Half Hardy (light frost only)
  tender|Tender (no frost)
  ```

---

### 7. Harvest Method
- **Field name:** `harvest_method`
- **Field type:** List (text)
- **Widget:** Select list
- **Required:** No
- **Allowed values:**
  ```
  single|Single Harvest
  continuous|Continuous Harvest
  cut-and-come-again|Cut and Come Again
  succession|Succession Harvest
  ```

---

### 8. Harvest Window Days
- **Field name:** `harvest_window_days`
- **Field type:** Number (integer)
- **Widget:** Number field
- **Required:** No
- **Minimum:** 0
- **Suffix:** days
- **Description:** Number of days the crop can be harvested

---

### 9. Harvest Start Month
- **Field name:** `harvest_start_month`
- **Field type:** Number (integer)
- **Widget:** Number field
- **Required:** No
- **Minimum:** 1
- **Maximum:** 12
- **Placeholder:** e.g. 6 (June)
- **Description:** Month (1-12) when harvest typically begins

---

### 10. Harvest End Month
- **Field name:** `harvest_end_month`
- **Field type:** Number (integer)
- **Widget:** Number field
- **Required:** No
- **Minimum:** 1
- **Maximum:** 12
- **Placeholder:** e.g. 9 (September)
- **Description:** Month (1-12) when harvest typically ends

---

## Fields Already in FarmOS (verify these exist)

These should already be present from your initial setup:

- ✅ `maturity_days` (Number - integer)
- ✅ `propagation_days` (Number - integer) - *renamed from transplant_days*
- ✅ `in_row_spacing_cm` (Number - decimal)
- ✅ `between_row_spacing_cm` (Number - decimal)
- ✅ `planting_method` (Text/List)

---

## After Adding Fields

1. **Save all field configurations**
2. **Clear FarmOS cache:** Configuration → Performance → Clear all caches
3. **Verify fields appear** on Plant type edit forms
4. **Test with one variety** - manually add data to verify fields work
5. **Return here** and let me know - I'll update the push command to sync all 2,945 varieties!

---

## Database Field Mapping Reference

| FarmOS Field Name          | Local DB Column Name       | Type     |
|---------------------------|----------------------------|----------|
| `season_type`             | `season_type`              | enum     |
| `germination_days_min`    | `germination_days_min`     | integer  |
| `germination_days_max`    | `germination_days_max`     | integer  |
| `germination_temp_optimal`| `germination_temp_optimal` | decimal  |
| `planting_depth_inches`   | `planting_depth_inches`    | decimal  |
| `frost_tolerance`         | `frost_tolerance`          | enum     |
| `harvest_method`          | `harvest_method`           | string   |
| `maturity_days`           | `maturity_days`            | integer  |
| `propagation_days`        | `propagation_days`         | integer  |
| `harvest_window_days`     | `harvest_window_days`      | integer  |

---

## Notes

- All field names use **snake_case** (underscores)
- List fields use **pipe-separated** values in FarmOS: `key|Label`
- Temperature is in **Celsius** (°C)
- Depth is in **inches** (matches seed packet standards)
- Spacing is in **centimeters** (cm)

---

## Success Checklist

- [ ] All 7 new fields added to Plant type taxonomy
- [ ] Allowed values configured for select lists (season_type, frost_tolerance, harvest_method)
- [ ] Field names match exactly (snake_case, no typos)
- [ ] FarmOS cache cleared
- [ ] Test variety edited successfully with new fields
- [ ] Ready to run sync command

---

**When complete, the system will:**
- ✅ Push 2,945 varieties from local DB → FarmOS
- ✅ Pull future varieties from FarmOS → local DB
- ✅ Keep production data synced in both directions
- ✅ Provide complete succession planting data for all users

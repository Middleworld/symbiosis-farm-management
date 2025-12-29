# Plant Type Field Mapping for Admin Copilot

## Summary
FarmOS already provides dedicated numeric fields on `taxonomy_term--plant_type` for timing metrics and crop families. The admin app currently looks for different attribute names, so it never reads or writes the values. Aligning the field names will let admin sync push data back into FarmOS and populate the taxonomy form.

## FarmOS Field Names
| Concept                | FarmOS attribute (JSON:API) | Field type               |
| ---------------------- | --------------------------- | ------------------------ |
| Crop family            | `crop_family`               | Entity reference (term)  |
| Days to maturity       | `maturity_days`             | Integer                  |
| Days to harvest        | `harvest_days`              | Integer                  |
| Days to transplant     | `transplant_days`           | Integer                  |

> Tip: you can confirm these via `drush field:info taxonomy_term plant_type` or by fetching any term without filtering the JSON:API response.

## Required Admin Changes
1. Update the admin data layer to map:
   - `days_to_maturity` → `maturity_days`
   - `days_to_harvest` → `harvest_days`
   - `days_to_transplant` → `transplant_days`
2. Treat `crop_family` as an entity reference (UUID) when pushing data to FarmOS.
3. Re-run the sync so cached metadata is written back to FarmOS.

## After the Mapping
- Drupal’s term edit form will show populated values in the existing fields.
- JSON:API responses will include the numeric values, ready for downstream integrations.
- No changes are required on the FarmOS side; the fields already exist and are exposed.

## Optional Validation Commands
```bash
# Show field definitions
./vendor/bin/drush field:info taxonomy_term plant_type

# Inspect raw attributes for a specific term
curl -s -H "Authorization: Bearer <TOKEN>" \
  -H "Accept: application/vnd.api+json" \
  https://farmos.middleworldfarms.org/api/taxonomy_term/plant_type/<UUID> | jq '.data.attributes'
```

# FarmOS Plant Type Metadata Issues

## Context
- Local admin sync (`farmos:sync-varieties`) now ingests **2,913** `taxonomy_term--plant_type` records from FarmOS.
- After the latest inference pass, **2,894** varieties in `PlantVariety` have a populated `crop_family`; the remaining 19 are non-plant SKUs (labels, tools, etc.).
- Timing fields (`maturity_days`, `transplant_days`, `harvest_days`) remain `NULL` because FarmOS descriptions do not include numeric durations and no dedicated fields exist upstream.

## Current Pain Points
1. **FarmOS vocabulary lacks custom fields**
   - `taxonomy_term--plant_type` only exposes `name`, `description`, `status`, and parent relations.
   - There are no JSON:API fields to store inferred metadata (crop family, maturity days, transplant days, harvest days).
2. **FarmOS UI appears empty**
   - Because the vocabulary has no fields for these metrics, FarmOS continues to show blank values even though the admin cache has them.
3. **Timing data absent in source**
   - Descriptions contain qualitative language (e.g., “maturing late August to October”) but no numeric ranges.
   - Without numeric input, the parser cannot generate reliable day counts.

## Required Upstream Actions (FarmOS)
1. **Add custom term fields** to the `plant_type` vocabulary:
   - `field_crop_family` (plain text)
   - `field_days_to_transplant` (integer)
   - `field_days_to_maturity` (integer)
   - `field_days_to_harvest` (integer)
2. **Expose fields via JSON:API** (automatic once fields are created).
3. **Populate initial values** by allowing the admin sync to PATCH FarmOS terms (see next section).

## Proposed Sync Enhancements (Admin App)
- Extend `SyncPlantVarieties` to:
  1. Continue caching inferred metadata locally (already working).
  2. Detect presence of the new FarmOS fields.
  3. Issue JSON:API PATCH requests for each term to push `crop_family`, `maturity_days`, `transplant_days`, `harvest_days` back to FarmOS once the fields exist.
- Add safeguards (e.g., `--push-to-farmos` flag) so upstream writes are intentional.

## Optional Follow-Ups
- **Heuristic timing estimates**: derive numeric day ranges from known crop presets (e.g., map “early Brussels sprout” to 110–120 days) and mark them as inferred.
- **Manual override table**: allow curated values for varieties with no reliable description cues.
- **Non-plant SKUs**: decide whether to exclude or deactivate the 19 utility items still missing `crop_family`.

## Next Steps Checklist
- [ ] Create the four term fields inside FarmOS (Drupal admin).
- [ ] Confirm JSON:API responses now include the new fields.
- [ ] Update `FarmOSApi` / sync command to PATCH term data when fields are available.
- [ ] (Optional) Implement heuristics or overrides for timing fields.

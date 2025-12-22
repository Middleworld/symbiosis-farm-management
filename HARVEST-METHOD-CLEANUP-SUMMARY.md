# HARVEST METHOD DATA CLEANUP - COMPLETE SUMMARY

## Overview
Fixed 103 vegetable varieties that had incorrect harvest method classifications.

## What We Fixed

### Phase 1: Obvious Single-Harvest Crops (59 varieties)
Changed from `continuous` to `single-harvest`:
- **Cabbage** (14) - Forms one head per plant
- **Brussels Sprouts** (19) - Harvest once when sprouts mature
- **Cauliflower** (9) - Single head formation
- **Potato** (2) - Single harvest when foliage dies
- **Turnip** (2) - Root harvest once
- **Kohl Rabi** (2) - Single bulb formation
- **Florence Fennel** (3) - Single bulb harvest
- **Chicory** (2) - Single head/root harvest
- **Celery** (1) - Single plant harvest
- **Broad Bean** (2) - One main harvest window
- **Onion** (1) - Single bulb harvest
- **Sweetcorn** (1) - Each cob ripens once
- **Artichoke** (1) - Single head per stem

### Phase 2A: Peas (26 varieties)
**Continuous harvest** (mangetout/sugar snap - eat whole pod, pick repeatedly):
- Pea Oregon Sugar Pod
- Pea Purple Magnolia
- Pea Sweet Horizon
- Pea Norli (organic)
- Pea Shiraz

**Single harvest** (shelling peas - harvest once when pods fill):
- 21 varieties including Alderman, Kelvedon Wonder, Progress No. 9, etc.

### Phase 2B: Sprouting Seeds (18 varieties)
Changed from `continuous` to `single-harvest`:
- All microgreens (Alfalfa, Broccoli, Kale, Chickpea, Fenugreek, Mung Bean, etc.)
- Cut once at 2-4 weeks, then crop finished

### Phase 2C: Calabrese vs Broccoli (26 varieties)
**Calabrese** (11) - Changed to `single-harvest`:
- Main head only, side shoots not worth the effort

**Broccoli** (15) - Changed to `continuous`:
- Purple sprouting and other varieties where side shoots are valuable
- Were incorrectly marked as single-harvest

## Key Insights

### Correct Classifications:
- **Single-harvest**: Cut once, plant is done (cabbages, root veg, shelling peas)
- **Continuous**: Pick repeatedly from same plant (courgettes, tomatoes, kale, mangetout)
- **Cut-and-come-again**: Similar to continuous (salad greens)

### Confusion Source:
The original data seemed to confuse:
- "Long harvest window" (can wait in field) with "continuous harvest" (pick repeatedly)
- This led to winter storage crops being marked continuous when they're actually single-harvest

### Crops That ARE Correctly Continuous (95+ varieties):
- Courgette (15), Cucumber (20), Marrow (3)
- Aubergine (16), Pepper (2), Chilli (1)
- Tomato (61)
- Runner beans (24), French beans (1)
- Kale (15), Broccoli (15)
- Asparagus (11), Rhubarb (2)

## Final Statistics
- **Phase 1**: 59 varieties fixed
- **Phase 2A (Peas)**: 26 varieties classified (21 changed to single, 1 corrected to continuous)
- **Phase 2B (Microgreens)**: 18 varieties changed to single
- **Phase 2C (Brassicas)**: 26 varieties fixed (11 calabrese to single, 15 broccoli to continuous)

**TOTAL: 103 varieties corrected**

All changes pushed to FarmOS successfully (2949/2949 varieties synced, 0 errors).

## Next Steps Recommended
1. Add validation system to flag suspicious harvest method assignments
2. Create crop type rules configuration
3. Add tooltips in succession planner explaining harvest methods
4. Document propagation_days meaning in database comments

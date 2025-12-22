# AI Variety Audit System - User Guide

## Overview
Automated AI-powered validation and enrichment of all 2,959 plant varieties in the database.

**Time Savings**: This system can audit all varieties overnight (~40 hours), saving months of manual checking and research.

## Two Ways to Run Audits

### Option 1: Web Interface (Settings Page) 🌐
**Location:** Admin Settings → AI Variety Audit Review section

The settings page provides a **full UI for managing the audit**:

**Control Panel:**
- **Start/Pause/Resume**: One-click audit control
- **Live Progress Bar**: X/2,959 varieties with time remaining estimate
- **Auto-refresh**: Status updates every 30 seconds
- **Stats Dashboard**: Critical/Warning/Info counts

**Review Interface:**
- Table showing all AI suggestions with Current → Suggested values
- Edit suggestions before approving (click pencil icon)
- Bulk actions: Approve Selected, Reject Selected, Approve All High Confidence
- Filter by severity: Critical/Warning/All
- "Apply All Approved Changes" button when ready

**Why Use the UI:**
- Visual progress tracking
- Review/edit suggestions before applying
- Batch approve safe changes (high confidence)
- Reject bad suggestions
- No command line needed

### Option 2: Command Line Interface 💻

## Quick Start (CLI)

### 1. Test Run (ALWAYS DO THIS FIRST!)
```bash
php artisan varieties:audit --limit=10 --dry-run
```
This will:
- Process first 10 varieties
- Show what issues are found
- NOT make any changes
- Show estimated time for full run

### 2. Check the Results
```bash
# View issues found
cat storage/logs/variety-audit/issues_*.log

# View main log
tail -100 storage/logs/variety-audit/audit_*.log
```

### 3. Run Overnight (Dry-Run First!)
```bash
# WITHOUT auto-fix (safer first run):
nohup php artisan varieties:audit > variety-audit.log 2>&1 &

# WITH auto-fix (after verifying dry-run results):
nohup php artisan varieties:audit --fix > variety-audit.log 2>&1 &

# Check it's running:
tail -f variety-audit.log

# Check progress:
./check-audit-status.sh
```

**💡 TIP: Use the Settings Page UI to start/pause/resume with visual progress!**

---

## CLI vs UI: Which to Use?

| Feature | CLI | Settings Page UI |
|---------|-----|------------------|
| Start audit | ✅ `./run-full-audit.sh` | ✅ Click "Start Audit" button |
| Monitor progress | ✅ `./check-audit-status.sh` | ✅ Auto-refreshing progress bar |
| Pause/Resume | ✅ `./pause-audit.sh` | ✅ Click "Pause" / "Resume" buttons |
| Review suggestions | ❌ Check logs manually | ✅ Full table with Current → Suggested |
| Edit suggestions | ❌ Not possible | ✅ Click edit icon, modify before approve |
| Approve changes | ✅ `--fix` flag (auto) | ✅ Review first, then approve individually |
| Bulk actions | ❌ One at a time | ✅ Select multiple, batch approve/reject |
| Filter by severity | ✅ Check logs | ✅ Click Critical/Warning/All buttons |
| High confidence | ❌ Manual | ✅ "Approve All High Confidence" button |
| Best for | Long background runs | Reviewing and applying suggestions |

**Recommended Workflow:**
1. **Start** audit via CLI (`./run-full-audit.sh`) or UI "Start Audit" button
2. **Let run** in background - generates suggestions into database
3. **Visit Settings Page** periodically to review suggestions as they appear
4. **Edit/Approve/Reject** via UI - modify AI suggestions if needed
5. **Apply Changes** via "Apply All Approved Changes" button when ready
6. Database updates happen in one atomic operation (safe!)

---

## Command Options

### Filter by Category
Only audit specific crops:
```bash
# Just broad beans:
php artisan varieties:audit --category="broad bean" --fix

# Just lettuces:
php artisan varieties:audit --category="lettuce" --dry-run

# Just brassicas:
php artisan varieties:audit --category="brassica" --fix
```

### Resume Interrupted Run
If the audit stops, resume from where it left off:
```bash
# Resume from variety ID 1500:
php artisan varieties:audit --start-id=1500 --fix
```

### Process in Batches
Break it into manageable chunks:
```bash
# First 500 varieties:
php artisan varieties:audit --limit=500 --fix

# Next 500 (check previous logs for last ID processed):
php artisan varieties:audit --start-id=501 --limit=500 --fix
```

## What Gets Checked

For each variety, the AI analyzes:

1. **Harvest Notes**
   - Are harvest windows realistic for UK climate?
   - Is timing information missing or generic?
   - Does it match the variety characteristics?

2. **Spacing**
   - In-row spacing appropriate for plant size?
   - Between-row spacing allows proper growth?
   - Missing spacing data flagged

3. **Maturity Days**
   - Seed to harvest time realistic?
   - Missing maturity data flagged
   - Conflicting timing data identified

4. **Planting Method**
   - Should it be direct sown, transplanted, or either?
   - Is the current method appropriate?

5. **Season Type** (NEW - Oct 2025)
   - Is this an early, mid, late, or all-season variety?
   - Used for varietal succession planning
   - Based on maturity days:
     * Early: < 100 days (quick maturing)
     * Mid: 100-140 days (standard season)
     * Late: > 140 days (long season)
     * All-season: Suitable for extended planting
   - Critical for professional succession planning

6. **Harvest Window Duration** (NEW - Oct 2025)
   - How long can you harvest from this variety?
   - Used to calculate succession spacing
   - Different from "days to harvest" (maturity_days)
   - Examples:
     * Lettuce: 14-21 days (pick and done)
     * Brussels Sprouts: 30-90 days (pick over months)
     * Tomatoes: 60+ days (continuous harvest)

7. **General Data Quality**
   - Missing descriptions
   - Placeholder text ("Estimated...", "Please verify...")
   - Obvious errors or inconsistencies

## Severity Levels

The audit assigns severity levels to help prioritize fixes:

### CRITICAL 🔴
Issues that significantly impact succession planning accuracy:
- **Missing maturity_days**: Cannot calculate succession timing
- **Missing harvest_window_days**: Cannot calculate harvest overlap
- **Missing season_type**: Cannot filter varieties for succession planning
- **Invalid planting method**: Could lead to crop failure
- **Dangerous spacing**: Could result in severe overcrowding or poor yields

### WARNING ⚠️
Data quality issues that should be reviewed:
- **Missing season type**: Limits succession planning options
- **Generic harvest notes**: "Harvest when ready" not helpful
- **Questionable spacing**: Unusually wide or narrow
- **Suspicious maturity**: Very long or short growing periods
- **Missing descriptions**: Limits variety selection decisions

### INFO ℹ️
Enhancement opportunities:
- **Could add harvest window**: Would improve succession planning
- **Could specify season type**: Would enable mixed-variety successions
- **Could improve descriptions**: Would help growers choose varieties
- **Could clarify timing**: Additional seasonal guidance helpful

## Confidence Levels

**HIGH** - AI is certain (auto-fixable with `--fix`)
- Well-known common varieties
- Standard spacing/timing
- Clear corrections

**MEDIUM** - AI is fairly confident (needs review)
- Less common varieties
- Some uncertainty in recommendations
- Manual verification recommended

**LOW** - AI is unsure (definitely needs expert review)
- Unusual varieties
- Conflicting information
- Needs human expertise

## Output Files

All logs saved to: `storage/logs/variety-audit/`

### Main Audit Log
`audit_YYYY-MM-DD_HH-MM-SS.log`
- Every variety processed
- Step-by-step progress
- Final summary

### Issues Log
`issues_YYYY-MM-DD_HH-MM-SS.log`
- Only varieties with problems
- Severity and confidence levels
- AI suggestions for fixes

### Fixes Log
`fixed_YYYY-MM-DD_HH-MM-SS.log`
- Only created when using `--fix`
- Shows what was changed
- Before/after values

## Example Workflow

### Week 1: Test and Validate
```bash
# Monday: Test on broad beans
php artisan varieties:audit --category="broad bean" --dry-run

# Review results, check they make sense
cat storage/logs/variety-audit/issues_*.log

# Tuesday: Run for real with auto-fix
php artisan varieties:audit --category="broad bean" --fix
```

### Week 2: Category by Category
```bash
# Process each crop family:
php artisan varieties:audit --category="lettuce" --fix
php artisan varieties:audit --category="carrot" --fix
php artisan varieties:audit --category="tomato" --fix
# etc.
```

### Week 3: Full Audit
```bash
# Friday evening: Start full overnight audit
nohup php artisan varieties:audit --fix > variety-audit.log 2>&1 &

# Monday morning: Check results
tail -100 variety-audit.log
cat storage/logs/variety-audit/issues_*.log | grep "CRITICAL"
```

## Monitoring Progress

While running in background:
```bash
# Check if still running:
ps aux | grep varieties:audit

# Watch live progress:
tail -f variety-audit.log

# Count processed so far:
grep "Processing:" storage/logs/variety-audit/audit_*.log | wc -l

# Count issues found:
grep "⚠️" storage/logs/variety-audit/issues_*.log | wc -l
```

## Troubleshooting

### "AI request failed"
- AI service might be down/slow
- Resume with `--start-id=<last_successful_id>`

### Too Many Issues
- First run without `--fix` to review
- May indicate AI needs tuning for specific crops
- Check a few manually to verify AI accuracy

### Slow Performance
- Current: ~48 seconds per variety
- Full run: ~40 hours
- Can run batches of 500 overnight instead

### Want to Stop It
```bash
# Find the process:
ps aux | grep varieties:audit

# Kill it (it's resumable):
kill <process_id>
```

## Safety Features

✅ **Dry-run mode** - Preview without changes
✅ **Confidence thresholds** - Only auto-fix high-confidence items
✅ **Detailed logging** - Every change recorded
✅ **Resumable** - Can stop and restart
✅ **Batch processing** - Can do categories at a time

## Real-World Impact: Varietal Succession Planning

**Why This Matters** (Added Oct 2025)

Tonight we achieved a major milestone: submitting the first varietal succession plan to FarmOS! This demonstrates why the audit system is so important.

### The Problem
New growers often plant a single variety once, then face "feast or famine":
- All 60 brussels sprout plants ready at once
- 2-week harvest window
- Can't eat/sell 60 plants worth in 2 weeks
- Need to extend the season to 3-4 months

### The Solution: Varietal Succession
Professional growers use **different varieties** planted in succession:
- **Early variety** (e.g., Churchill F1): 90 days, harvest Sept-Oct
- **Mid variety** (e.g., Dagan F1): 125 days, harvest Oct-Dec  
- **Late variety** (e.g., Doric F1): 184 days, harvest Dec-Feb

### What the Audit Enables
For this to work, the database needs accurate:
1. **maturity_days**: Calculate when each variety will be ready
2. **harvest_window_days**: Know how long you can pick from each
3. **season_type**: Filter varieties by early/mid/late/all-season
4. **Completeness**: All varieties in a family should have season types

### Example: Brussels Sprouts
We populated 18 varieties with season types:
- 5 Early varieties (< 100 days)
- 6 Mid varieties (100-140 days)  
- 6 Late varieties (> 140 days)
- 1 All-season variety

The succession planner now:
- Shows 3 dropdowns (Early/Mid/Late)
- Filters varieties by season_type
- Uses variety-specific maturity_days for each succession
- Calculates optimal planting dates
- Creates individual FarmOS seeding/transplanting/harvest logs
- Extends harvest season from 2 weeks to 4+ months!

### Audit Validation Opportunities
The AI audit should check:
- Does season_type match maturity_days ranges?
- Do all crop families have season type coverage?
- Are maturity_days and harvest_window_days both present?
- Are these values realistic for UK climate?

This transforms the audit from "data quality checking" to **"enabling professional growing techniques"**.

---

## Expected Results

Based on initial tests:
- ~30-40% of varieties may have at least one issue flagged
- ~10-15% will have high-confidence auto-fixes
- ~20-25% will need manual review
- ~60-70% will be validated as correct

This means AI can automatically fix ~300-500 varieties and flag ~600-800 for human review, saving months of work!

## Best Practices

1. **Start with Critical Categories**
   - Focus on crops you actually grow
   - Essential succession crops first (brassicas, lettuce, beans)
   - Fix CRITICAL issues before moving to warnings

2. **Dry Run First**
   ```bash
   php artisan varieties:audit --category="brussels sprout" --dry-run
   ```
   - See what would be checked without spending API credits
   - Review the AI's approach
   - Estimate time and cost

3. **Use Batch Limits**
   ```bash
   php artisan varieties:audit --category="tomato" --limit=20
   ```
   - Process manageable chunks
   - Review results before continuing
   - Avoid overwhelming changes

4. **Check Season Type Coverage**
   ```bash
   # Find crops missing season types
   mysql -u root -p mwf_production -e "
   SELECT crop_name, COUNT(*) as total,
          SUM(season_type IS NOT NULL) as with_season,
          COUNT(*) - SUM(season_type IS NOT NULL) as missing_season
   FROM plant_varieties
   GROUP BY crop_name
   HAVING missing_season > 0
   ORDER BY missing_season DESC;"
   ```
   - Prioritize succession-friendly crops
   - Brussels sprouts, lettuce, beans, carrots
   - Complete families enable better planning

5. **Monitor Progress**
   ```bash
   tail -f storage/logs/laravel.log | grep "VARIETY AUDIT"
   ```

6. **Review High-Confidence Changes First**
   - HIGH confidence = safer to trust
   - MEDIUM = review carefully
   - LOW = definitely verify

7. **Focus on Succession Essentials**
   - maturity_days > harvest notes (for timing)
   - harvest_window_days > descriptions (for overlap)
   - season_type > spacing (for variety selection)
   - These three enable professional succession planning

8. **Incremental Improvement**
   - Don't try to fix everything at once
   - Audit one crop family completely
   - Test in succession planner
   - Move to next family

---

## Technical Lessons from Implementation (Oct 2025)

### JavaScript Gotchas
**cloneNode() doesn't preserve selected values**
```javascript
// ❌ WRONG - loses dropdown selection
const newDropdown = oldDropdown.cloneNode(true);

// ✅ RIGHT - save and restore
const oldValue = oldDropdown.value;
const newDropdown = oldDropdown.cloneNode(true);
newDropdown.value = oldValue;
```

### Form Submission Gotchas
**Unchecked checkboxes don't POST**
```javascript
// ❌ WRONG - skips unchecked boxes
if (input.name && input.value) {
    formData[input.name] = input.value;
}

// ✅ RIGHT - handle checkboxes explicitly
if (input.type === 'checkbox') {
    formData[input.name] = input.checked ? '1' : '0';
} else if (input.value) {
    formData[input.name] = input.value;
}
```

### PHP Validation Gotchas
**isset() treats '0' as true**
```php
// ❌ WRONG - '0' string passes isset()
'status' => isset($data['done']) ? 'done' : 'pending'

// ✅ RIGHT - check for truthiness too
'status' => (!empty($data['done']) && $data['done'] !== '0') ? 'done' : 'pending'
```

### Event Timing Gotchas
**Don't recalculate before user finishes selecting**
```javascript
// ❌ WRONG - triggers on first dropdown change
earlySelect.addEventListener('change', recalculateSuccession);

// ✅ RIGHT - wait for all 3 selections
function checkAllSelectionsComplete() {
    if (earlyVal && midVal && lateVal) {
        recalculateSuccession();
    }
}
```

These lessons came from debugging the varietal succession feature and are now baked into the succession planner code.

---

## Database Schema Reference

### season_type Column (Added Oct 2025)
```sql
ALTER TABLE plant_varieties 
ADD COLUMN season_type ENUM('early', 'mid', 'late', 'all-season') NULL
AFTER maturity_days;
```

**Classification Guidelines:**
- **Early**: maturity_days < 100 (quick crops)
- **Mid**: maturity_days 100-140 (standard season)
- **Late**: maturity_days > 140 (long season)
- **All-season**: Suitable for continuous planting (lettuce, radish)

**Example Population:**
```sql
-- Brussels Sprouts: 18 varieties categorized
UPDATE plant_varieties SET season_type = 'early' 
WHERE crop_name = 'Brussels Sprout' AND maturity_days < 100;

UPDATE plant_varieties SET season_type = 'mid'
WHERE crop_name = 'Brussels Sprout' AND maturity_days BETWEEN 100 AND 140;

UPDATE plant_varieties SET season_type = 'late'
WHERE crop_name = 'Brussels Sprout' AND maturity_days > 140;
```

**Used By:**
- Succession Planning UI (filters dropdown options)
- Varietal Succession feature (allows different varieties per succession)
- Professional growing guides (categorizes varieties by timing)

---



## Questions?

The audit logs contain all the AI's reasoning. If you're unsure about a suggestion:
1. Look at the variety in the database
2. Check what the AI flagged
3. Verify with seed catalogs or growing guides
4. Trust your expertise over AI when in doubt!

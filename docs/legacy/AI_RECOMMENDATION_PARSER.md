# AI Recommendation Parser - Test Examples

## How It Works

When the user clicks **"Accept Recommendations"**, the system:

1. **Parses the AI response text** for actionable recommendations
2. **Shows a confirmation modal** listing detected changes
3. **Applies changes** to `currentSuccessionPlan.plantings` array
4. **Redraws the plan table** with updated data
5. **Logs all changes** for audit trail

---

## Supported Actions

### 1. Remove Succession ✂️
**AI phrases detected:**
- "remove succession 3"
- "skip succession 2"
- "eliminate succession 4"
- "succession 3 should be removed"
- "not recommended succession 5"
- "avoid succession 1"

**What happens:**
```javascript
// Before: 5 successions
currentSuccessionPlan.plantings = [
  {succession_number: 1, ...},
  {succession_number: 2, ...},
  {succession_number: 3, ...}, // ← AI says remove this
  {succession_number: 4, ...},
  {succession_number: 5, ...}
]

// After accepting: 4 successions (renumbered)
currentSuccessionPlan.plantings = [
  {succession_number: 1, ...},
  {succession_number: 2, ...},
  {succession_number: 3, ...}, // ← was 4, renumbered
  {succession_number: 4, ...}  // ← was 5, renumbered
]

currentSuccessionPlan.total_successions = 4
```

---

### 2. Adjust Spacing 📏
**AI phrases detected:**
- "increase spacing for succession 2"
- "succession 3 spacing too tight"
- "widen spacing succession 1"
- "succession 4 is too crowded"

**What happens:**
```javascript
// Flags the planting for manual review
planting.ai_spacing_flag = true
// User can then manually adjust spacing in the plan
```

---

### 3. Adjust Timing ⏰
**AI phrases detected:**
- "delay succession 3"
- "succession 2 is too early"
- "postpone succession 4"
- "move back succession 1"

**What happens:**
```javascript
// Flags the planting for manual review
planting.ai_timing_flag = true
// User can then manually adjust dates in the plan
```

---

### 4. Add Companion Plant 🌿
**AI phrases detected:**
- "plant nasturtium as companion"
- "add basil intercrop"
- "include radish between rows"

**What happens:**
```javascript
// Recommendation logged for user to action
changeLog.push('AI suggested companion: nasturtium')
// Future: Could auto-add to companion suggestions
```

---

## Example Scenarios

### Scenario 1: AI suggests removing succession 3

**AI Response:**
```
Your succession plan looks good overall! However, I notice succession 3 
has a very short gap before succession 4. I'd recommend removing succession 3 
to give better spacing between plantings. This will reduce harvest glut risk.

Also consider planting nasturtium as a companion crop to deter aphids.
```

**Parsed Recommendations:**
```javascript
[
  {
    action: 'remove',
    successionNumber: 3,
    reason: 'AI suggested removal'
  },
  {
    action: 'add_companion',
    companion: 'nasturtium',
    reason: 'AI suggested companion plant'
  }
]
```

**Confirmation Modal Shows:**
```
Detected Changes:
• Remove succession 3
• Add companion: nasturtium
```

**User clicks "Accept & Apply Changes":**
- Succession 3 removed from plan
- Successions 4, 5, 6 renumbered to 3, 4, 5
- Total successions updated: 6 → 5
- Plan table redrawn
- Change log: ["Removed succession 3", "AI suggested companion: nasturtium"]

---

### Scenario 2: AI suggests spacing adjustments only

**AI Response:**
```
The timing looks good! However, succession 2 spacing seems quite tight 
at 15cm. I'd recommend increasing to 20cm for better air circulation 
and disease prevention.
```

**Parsed Recommendations:**
```javascript
[
  {
    action: 'adjust_spacing',
    successionNumber: 2,
    reason: 'AI suggested spacing adjustment'
  }
]
```

**Confirmation Modal Shows:**
```
Detected Changes:
• Adjust spacing for succession 2
```

**User clicks "Accept & Apply Changes":**
- Succession 2 flagged: `ai_spacing_flag = true`
- Visual indicator added to row 2 in plan table (future enhancement)
- Change log: ["Flagged succession 2 for spacing review"]
- User manually adjusts spacing in the plan form

---

### Scenario 3: No specific changes (general advice)

**AI Response:**
```
Your succession plan looks excellent! Good spacing and timing throughout. 
The harvest window coverage is ideal for market sales. Consider harvesting 
in the morning for best quality.
```

**Parsed Recommendations:**
```javascript
[]
```

**Confirmation Modal Shows:**
```
No specific structural changes detected. Accepting will mark plan as reviewed.
```

**User clicks "Accept & Apply Changes":**
- Plan marked as `ai_approved = true`
- No structural changes applied
- Change log: []
- Success message: "Plan marked as AI-reviewed"

---

## Regex Patterns Used

### Remove Patterns
```javascript
/(?:remove|skip|eliminate|drop)\s+(?:succession\s+)?(\d+)/gi
/succession\s+(\d+)\s+(?:should be|could be|can be)?\s*(?:removed|skipped|eliminated|dropped)/gi
/(?:not recommended|don't recommend|avoid)\s+succession\s+(\d+)/gi
```

### Spacing Patterns
```javascript
/(?:increase|widen|expand)\s+spacing.*?succession\s+(\d+)/gi
/succession\s+(\d+).*?(?:too\s+)?(?:close|tight|crowded)/gi
```

### Timing Patterns
```javascript
/(?:delay|postpone|move back)\s+succession\s+(\d+)/gi
/succession\s+(\d+).*?(?:too\s+)?(?:early|soon)/gi
```

---

## Visual Feedback

### Before Acceptance
```
[AI Response]
"Your plan is good but remove succession 3..."

[Accept Recommendations] [Request Modifications]
```

### During Confirmation
```
┌─────────────────────────────────────────┐
│ ✓ Accept AI Recommendations             │
├─────────────────────────────────────────┤
│ Ready to apply AI recommendations?      │
│                                         │
│ ⚠ Detected Changes:                    │
│   • Remove succession 3                 │
│                                         │
│ Knowledge base: 39+22+15 entries       │
│                                         │
│ [Cancel] [Accept & Apply Changes]      │
└─────────────────────────────────────────┘
```

### After Acceptance
```
[AI Response]
"Your plan is good but remove succession 3..."

✅ Recommendations Applied!
   1 change(s) applied to your succession plan:
   • Removed succession 3
   
   You can now proceed to submit plantings to FarmOS.
```

### Updated Plan Table
```
Succession  Bed     Seeding      Transplant    Harvest
─────────────────────────────────────────────────────
1          Bed 1    Mar 15       Apr 1         May 15
2          Bed 2    Apr 1        Apr 15        Jun 1
3          Bed 3    May 1        May 15        Jul 1  ← Was #4, renumbered
4          Bed 4    Jun 1        Jun 15        Aug 1  ← Was #5, renumbered

[Table highlights green for 2 seconds]
```

---

## Implementation Details

### Key Functions

**`parseAIRecommendations(responseText)`**
- Input: AI response string
- Output: Array of recommendation objects
- Uses regex patterns to detect actionable items
- Returns structured data for processing

**`confirmAcceptRecommendations()`**
- Applies each recommendation to `currentSuccessionPlan`
- For removals: Filters array, renumbers, updates total
- For flags: Sets `ai_spacing_flag` or `ai_timing_flag`
- Logs all changes to `ai_change_log` array
- Redraws plan table with `displaySuccessionPlan()`

**`displaySuccessionPlan(plan)`**
- Existing function that renders the plan table
- Called after changes to show updated succession list
- Shows renumbered successions

---

## Future Enhancements

### Automatic Date Adjustments
```javascript
if (rec.action === 'adjust_timing' && rec.delayDays) {
  const planting = currentSuccessionPlan.plantings[rec.successionNumber - 1];
  const seedingDate = new Date(planting.seeding_date);
  seedingDate.setDate(seedingDate.getDate() + rec.delayDays);
  planting.seeding_date = seedingDate.toISOString().split('T')[0];
}
```

### Automatic Spacing Adjustments
```javascript
if (rec.action === 'adjust_spacing' && rec.newSpacing) {
  const planting = currentSuccessionPlan.plantings[rec.successionNumber - 1];
  planting.row_spacing_cm = rec.newSpacing;
}
```

### Visual Indicators
```javascript
// Add warning icons to flagged rows in plan table
if (planting.ai_spacing_flag) {
  row.innerHTML += '<i class="fas fa-exclamation-triangle text-warning" 
                       title="AI flagged for spacing review"></i>';
}
```

---

## Testing

### Test Case 1: Remove middle succession
1. Create plan with 5 successions
2. Ask AI: "Is succession 3 necessary?"
3. AI responds: "I'd recommend removing succession 3..."
4. Click "Accept Recommendations"
5. **Expected**: Modal shows "Remove succession 3"
6. Confirm acceptance
7. **Expected**: Plan now has 4 successions (3, 4, 5 renumbered to 3, 4, 5)

### Test Case 2: Multiple recommendations
1. Create plan with spacing issues
2. Ask AI to analyze
3. AI responds: "Remove succession 2 and increase spacing on succession 4"
4. Click "Accept Recommendations"
5. **Expected**: Modal shows both changes
6. Confirm acceptance
7. **Expected**: Succession 2 removed, succession 4 flagged

### Test Case 3: General advice only
1. Create good plan
2. Ask AI to analyze
3. AI responds: "Looks great! Good work."
4. Click "Accept Recommendations"
5. **Expected**: Modal shows "No specific changes detected"
6. Confirm acceptance
7. **Expected**: Plan marked as reviewed, no structural changes

---

## Console Output Example

```javascript
📋 Parsed AI Recommendations: [
  {action: "remove", successionNumber: 3, reason: "AI suggested removal"}
]

✂️ Removed succession 3

✅ AI Recommendations applied to plan: {
  plantings: [
    {succession_number: 1, ...},
    {succession_number: 2, ...},
    {succession_number: 3, ...},  // renumbered from 4
    {succession_number: 4, ...}   // renumbered from 5
  ],
  total_successions: 4,
  ai_approved: true,
  ai_changes_applied: 1,
  ai_change_log: ["Removed succession 3"]
}

📝 Change log: ["Removed succession 3"]
```

---

## Summary

✅ **Now supports intelligent recommendation parsing and application**
- Removes successions as suggested by AI
- Renumbers remaining successions automatically
- Flags spacing/timing issues for manual review
- Shows clear before/after in confirmation modal
- Maintains full audit trail of changes
- Redraws plan table to show updated structure

The system now truly accepts and **applies** AI recommendations, not just marking them as reviewed!

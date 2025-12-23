# AI Timing Adjustments - Examples & Test Cases

## 🆕 NEW FEATURE: Automatic Date Adjustments

The system can now **automatically adjust dates** when AI suggests timing changes!

---

## Supported AI Phrases

### 1. Specific Delays/Advances
AI can specify **exact amounts**:

**Delay (push back) patterns:**
```
"delay succession 3 by 7 days"
"postpone succession 2 by 2 weeks"
"move back succession 4 by 1 week"
"push back succession 1 by 14 days"
```

**Advance (bring forward) patterns:**
```
"advance succession 3 by 7 days"
"bring forward succession 2 by 1 week"
"move forward succession 4 by 2 weeks"
"move up succession 1 by 14 days"
```

### 2. Generic Timing Issues
AI can suggest timing changes without specific amounts (defaults to 7 days):

```
"succession 3 is too early" → Delays by 7 days
"succession 2 is too late" → Advances by 7 days
"delay succession 4" → Delays by 7 days
"advance succession 1" → Advances by 7 days
```

---

## What Gets Adjusted

When timing is changed, **ALL dates shift** by the same amount:

1. **Seeding Date** ✓
2. **Transplant Date** ✓ (if present)
3. **Harvest Start Date** ✓
4. **Harvest End Date** ✓ (if present)

This keeps the entire succession timeline aligned.

---

## Example Scenarios

### Scenario 1: Delay by 1 Week (7 days)

**AI Response:**
```
"Your succession plan looks good, but succession 3 is quite close to succession 2. 
I'd recommend delaying succession 3 by 1 week to reduce harvest overlap."
```

**Parsed Recommendation:**
```javascript
{
  action: 'adjust_timing',
  successionNumber: 3,
  delayDays: 7,
  reason: 'AI suggested 1 week delay'
}
```

**Confirmation Modal Shows:**
```
Detected Changes:
📅 Delay succession 3 by 1 week
```

**Before:**
| Succession | Seeding   | Transplant | Harvest    |
|-----------|-----------|------------|------------|
| 3         | Apr 10    | Apr 25     | Jun 10     |

**After Accepting:**
| Succession | Seeding   | Transplant | Harvest    |
|-----------|-----------|------------|------------|
| 3         | Apr 17    | May 2      | Jun 17     |

**Change Log:**
```
"Delayed succession 3 by 7 days (2025-04-10 → 2025-04-17)"
```

---

### Scenario 2: Advance by 2 Weeks (14 days)

**AI Response:**
```
"Succession 2 seems too late for optimal spring growth. Consider bringing 
forward succession 2 by 2 weeks to take advantage of cooler weather."
```

**Parsed Recommendation:**
```javascript
{
  action: 'adjust_timing',
  successionNumber: 2,
  delayDays: -14,  // Negative = advance
  reason: 'AI suggested 2 weeks advance'
}
```

**Confirmation Modal Shows:**
```
Detected Changes:
📅 Advance succession 2 by 2 weeks
```

**Before:**
| Succession | Seeding   | Transplant | Harvest    |
|-----------|-----------|------------|------------|
| 2         | Apr 15    | May 1      | Jun 15     |

**After Accepting:**
| Succession | Seeding   | Transplant | Harvest    |
|-----------|-----------|------------|------------|
| 2         | Apr 1     | Apr 17     | Jun 1      |

**Change Log:**
```
"Advanced succession 2 by 14 days (2025-04-15 → 2025-04-01)"
```

---

### Scenario 3: Multiple Timing Changes

**AI Response:**
```
"I notice some timing issues:
- Succession 2 is too early - delay by 1 week
- Succession 4 should be advanced by 3 days to fill a gap"
```

**Parsed Recommendations:**
```javascript
[
  {
    action: 'adjust_timing',
    successionNumber: 2,
    delayDays: 7,
    reason: 'AI suggested 1 week delay'
  },
  {
    action: 'adjust_timing',
    successionNumber: 4,
    delayDays: -3,
    reason: 'AI suggested 3 days advance'
  }
]
```

**Confirmation Modal Shows:**
```
Detected Changes:
📅 Delay succession 2 by 1 week
📅 Advance succession 4 by 3 days
```

**Changes Applied:**
- Succession 2: All dates +7 days
- Succession 4: All dates -3 days

---

### Scenario 4: Remove + Timing Adjustment

**AI Response:**
```
"Remove succession 3 (too close to succession 2) and delay succession 4 
by 1 week to maintain good spacing."
```

**Parsed Recommendations:**
```javascript
[
  {action: 'remove', successionNumber: 3},
  {action: 'adjust_timing', successionNumber: 4, delayDays: 7}
]
```

**Confirmation Modal Shows:**
```
Detected Changes:
❌ Remove succession 3
📅 Delay succession 4 by 1 week
```

**Process:**
1. Remove succession 3
2. Renumber: succession 4 becomes 3, succession 5 becomes 4, etc.
3. Apply timing change to **NEW succession 3** (what was #4)
4. Redraw table

**Result:**
- 4 successions total (was 5)
- What was succession 4 is now succession 3 with dates delayed by 7 days

---

## Date Calculation Examples

### 7-Day Delay
```javascript
Original: 2025-04-10
+ 7 days
Result:   2025-04-17
```

### 14-Day Advance
```javascript
Original: 2025-04-15
- 14 days
Result:   2025-04-01
```

### 21-Day Delay (3 weeks)
```javascript
Original: 2025-03-15
+ 21 days
Result:   2025-04-05
```

---

## Regex Patterns Used

### Specific Amount Patterns

**Delay:**
```javascript
/(?:delay|postpone|move back|push back)\s+succession\s+(\d+)\s+by\s+(\d+)\s+(day|week|month)s?/gi
```

**Examples matched:**
- "delay succession 3 by 7 days" → succession=3, amount=7, unit=day
- "postpone succession 2 by 2 weeks" → succession=2, amount=2, unit=week
- "move back succession 1 by 1 month" → succession=1, amount=1, unit=month

**Advance:**
```javascript
/(?:advance|bring forward|move forward|move up)\s+succession\s+(\d+)\s+by\s+(\d+)\s+(day|week|month)s?/gi
```

**Examples matched:**
- "advance succession 4 by 7 days" → succession=4, amount=7, unit=day
- "bring forward succession 1 by 1 week" → succession=1, amount=1, unit=week

### Generic Patterns (default 7 days)

```javascript
/succession\s+(\d+).*?(?:too\s+)?(?:early|soon)/gi          → +7 days
/succession\s+(\d+).*?(?:too\s+)?late/gi                    → -7 days
/(?:delay|postpone)\s+succession\s+(\d+)(?!\s+by)/gi       → +7 days
/(?:advance|bring forward)\s+succession\s+(\d+)(?!\s+by)/gi → -7 days
```

---

## Unit Conversion

The system converts weeks/months to days:

```javascript
if (unit === 'week') days = amount * 7;
if (unit === 'month') days = amount * 30;
```

**Examples:**
- 1 week → 7 days
- 2 weeks → 14 days
- 3 weeks → 21 days
- 1 month → 30 days

---

## Visual Feedback

### Confirmation Modal
```
┌─────────────────────────────────────────┐
│ ✓ Accept AI Recommendations             │
├─────────────────────────────────────────┤
│ Detected Changes:                       │
│ 📅 Delay succession 3 by 1 week         │
│                                         │
│ [Cancel] [Accept & Apply Changes]      │
└─────────────────────────────────────────┘
```

### Success Message
```
✅ Recommendations Applied!
1 change(s) applied to your succession plan:
• Delayed succession 3 by 7 days (2025-04-10 → 2025-04-17)

You can now proceed to submit plantings to FarmOS.
```

### Console Log
```javascript
📅 Delayed succession 3: 2025-04-10 → 2025-04-17
✅ AI Recommendations applied to plan
📝 Change log: ["Delayed succession 3 by 7 days (2025-04-10 → 2025-04-17)"]
```

---

## Test Cases

### Test 1: Simple 7-Day Delay
**Input:** "delay succession 2 by 7 days"
**Expected:**
- Parses: delayDays = 7
- Modal: "📅 Delay succession 2 by 1 week"
- Applied: All dates +7 days
- Log: "Delayed succession 2 by 7 days (old → new)"

### Test 2: 2-Week Advance
**Input:** "bring forward succession 4 by 2 weeks"
**Expected:**
- Parses: delayDays = -14
- Modal: "📅 Advance succession 4 by 2 weeks"
- Applied: All dates -14 days
- Log: "Advanced succession 4 by 14 days (old → new)"

### Test 3: Generic "Too Early"
**Input:** "succession 3 is too early"
**Expected:**
- Parses: delayDays = 7 (default)
- Modal: "📅 Delay succession 3 by 1 week"
- Applied: All dates +7 days

### Test 4: Mixed Changes
**Input:** "remove succession 2 and delay succession 3 by 1 week"
**Expected:**
- Parses: 2 recommendations
- Modal: Shows both changes
- Applied: Removes #2, renumbers, then delays new #2 (was #3)

---

## Edge Cases Handled

### 1. No Specific Amount Given
AI says: "delay succession 3"
→ Defaults to 7 days

### 2. Multiple Units
AI says: "delay by 2 weeks and 3 days"
→ Only captures first amount (2 weeks = 14 days)
→ Future: Could sum multiple amounts

### 3. Date Boundary Crossing
Original: 2025-03-28
+7 days
Result: 2025-04-04 ✓ (crosses month boundary correctly)

### 4. Succession Already Removed
If succession removed AND timing adjusted:
1. Remove first
2. Renumber
3. Apply timing to new number

---

## Implementation Details

### Date Manipulation
```javascript
// Delay by 7 days
const seedingDate = new Date(planting.seeding_date);
seedingDate.setDate(seedingDate.getDate() + 7);
planting.seeding_date = seedingDate.toISOString().split('T')[0];

// Advance by 7 days (negative delay)
const seedingDate = new Date(planting.seeding_date);
seedingDate.setDate(seedingDate.getDate() - 7);
planting.seeding_date = seedingDate.toISOString().split('T')[0];
```

### All Dates Adjusted
```javascript
if (rec.action === 'adjust_timing' && rec.delayDays !== undefined) {
  const planting = currentSuccessionPlan.plantings.find(...);
  
  // Seeding
  seedingDate.setDate(seedingDate.getDate() + rec.delayDays);
  
  // Transplant
  transplantDate.setDate(transplantDate.getDate() + rec.delayDays);
  
  // Harvest start
  harvestDate.setDate(harvestDate.getDate() + rec.delayDays);
  
  // Harvest end
  harvestEndDate.setDate(harvestEndDate.getDate() + rec.delayDays);
}
```

---

## Summary

✅ **Now supports automatic date adjustments!**

**What AI can specify:**
- Exact delays: "delay succession 3 by 7 days"
- Exact advances: "advance succession 2 by 2 weeks"
- Generic timing: "succession 4 is too early" (defaults to 7 days)

**What gets adjusted:**
- Seeding date
- Transplant date
- Harvest start date
- Harvest end date

**User experience:**
1. AI suggests timing change
2. Modal shows: "📅 Delay succession 3 by 1 week"
3. User accepts
4. All dates automatically adjusted
5. Success message shows old → new dates
6. Plan table redraws with updated dates

**Combined with other features:**
- Can remove succession AND adjust timing
- Can adjust multiple successions at once
- Handles renumbering correctly
- Full audit trail maintained

The succession planner now has **intelligent, automatic date management**! 🎉

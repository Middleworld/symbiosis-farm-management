# Variety Audit Pause/Resume Guide

## Overview
The variety audit can now be **paused at any time** and **resumed later** without losing progress! The system automatically saves progress every 10 varieties.

## How It Works

### Automatic Progress Tracking
- Progress saved every 10 varieties to: `storage/logs/variety-audit/progress.json`
- Tracks: Last processed variety ID, total processed, timestamp, stats
- No data loss if audit is stopped

### Resume Detection
- When you restart the audit, it automatically detects saved progress
- Asks: "Resume from where you left off?"
- If yes: Continues from last variety + 1
- If no: Starts fresh (deletes progress file)

## Usage

### 1. Start Audit
```bash
cd /opt/sites/admin.middleworldfarms.org
./run-full-audit.sh
```

This runs in background and saves PID to `/tmp/variety-audit.pid`

### 2. Check Status Anytime
```bash
./check-audit-status.sh
```

Shows:
- Is audit running? (PID)
- Last progress save (variety ID, count, timestamp)
- Progress percentage (e.g., 15.2% complete)
- Estimated time remaining
- Database statistics

Example output:
```
✅ Audit is RUNNING (PID: 123456)

📊 Last Progress Save:
{
  "last_processed_id": 450,
  "processed": 450,
  "timestamp": "2025-10-10 02:30:15",
  "stats": {...}
}

Progress: 450 / 2959 varieties (15.20%)
Remaining: 2509 varieties (~34.1 hours at 49s/variety)
```

### 3. Pause Audit
```bash
./pause-audit.sh
```

- Sends graceful stop signal (SIGTERM)
- Waits for process to stop cleanly
- Shows saved progress location
- Progress file remains for resume

### 4. Resume Later
```bash
./run-full-audit.sh
```

You'll see:
```
⏸️  Previous audit found!
   Last processed: Variety ID 450
   Processed: 450 varieties
   Timestamp: 2025-10-10 02:30:15

Resume from where you left off? (yes/no) [yes]:
```

Choose:
- **Yes** → Continues from variety 451
- **No** → Starts completely fresh from variety 1

## Real-World Example

### Scenario: 40-hour audit across multiple days

**Day 1 (Morning - 8 hours)**
```bash
./run-full-audit.sh
# Let it run for 8 hours → ~588 varieties processed
./pause-audit.sh
```

**Day 1 (Evening - Check progress)**
```bash
./check-audit-status.sh
# Shows: 588 / 2959 (19.9%), ~32 hours remaining
```

**Day 2 (Morning - Resume)**
```bash
./run-full-audit.sh
# "Resume from where you left off?" → Yes
# Continues from variety 589
# Run for another 8 hours → ~588 more varieties
./pause-audit.sh
```

**Day 3 (Finish remaining ~27 hours)**
```bash
./run-full-audit.sh
# Resume → Completes remaining 1,783 varieties
```

## Monitoring While Running

### Live log tail
```bash
tail -f /tmp/variety-audit.log
```

### Process status
```bash
ps aux | grep "varieties:audit"
```

### Check database results so far
```bash
cd /opt/sites/admin.middleworldfarms.org
php artisan tinker --execute="
echo App\Models\VarietyAuditResult::count() . ' suggestions saved\n';
"
```

## Progress File Format

Located at: `storage/logs/variety-audit/progress.json`

```json
{
  "last_processed_id": 450,
  "processed": 450,
  "timestamp": "2025-10-10 02:30:15",
  "stats": {
    "total": 2959,
    "processed": 450,
    "skipped": 0,
    "issues_found": 450,
    "auto_fixed": 0,
    "needs_review": 450,
    "errors": 0
  }
}
```

## Safety Features

1. **Graceful Shutdown**: `pause-audit.sh` sends SIGTERM first (clean stop)
2. **Forced Kill**: If graceful fails after 2 seconds, sends SIGKILL
3. **Progress Saved**: Every 10 varieties (max 10 varieties lost if force-killed)
4. **Resume Prompt**: Won't accidentally overwrite progress (asks first)
5. **PID Tracking**: Prevents multiple audits running simultaneously

## Manual Resume (Advanced)

If scripts don't work, you can manually resume:

```bash
# Check progress file
cat storage/logs/variety-audit/progress.json

# Resume from specific variety ID (e.g., 451)
php artisan varieties:audit --start-id=451
```

## Troubleshooting

### "No audit currently running" but process exists
```bash
# Find the process
ps aux | grep "varieties:audit"

# Note the PID and add to file
echo "PID_NUMBER" > /tmp/variety-audit.pid

# Now pause-audit.sh will work
./pause-audit.sh
```

### Progress file corrupted
```bash
# Delete and start fresh
rm storage/logs/variety-audit/progress.json
./run-full-audit.sh
```

### Want to restart from scratch
```bash
# Delete progress file
rm storage/logs/variety-audit/progress.json

# Clear database suggestions (optional)
php artisan tinker --execute="App\Models\VarietyAuditResult::truncate();"

# Start fresh
./run-full-audit.sh
```

## Performance Expectations

- **Speed**: 49-67 seconds per variety (depends on Mistral 7B load)
- **Total Time**: 40-55 hours for all 2,959 varieties
- **Typical Schedule**: 
  - 3-4 sessions of 8-12 hours each
  - Or run continuously over a weekend (48 hours)
- **Progress Saves**: Every 10 varieties = every ~8 minutes

## Best Practices

1. **Check status before bed** - Know how much progress was made
2. **Don't restart server** while audit running (will lose current batch of 10)
3. **Use `pause-audit.sh`** instead of Ctrl+C (cleaner stop)
4. **Review results incrementally** - Check Settings page after each session
5. **Approve high-confidence** suggestions as you go (don't wait for all 2,959)

## Next Steps After Audit Completes

1. Visit Settings page → AI Variety Audit Review
2. Filter by "High Confidence" suggestions
3. Review and approve in batches
4. Use inline editing for any corrections needed
5. Click "Apply Approved Changes" to update database
6. Re-run audit on any remaining problematic varieties

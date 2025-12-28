# Dual Ollama AI Setup for Variety Auditing

## Overview
We've configured two separate Ollama instances to optimize AI performance:

### Port 8005 - General Purpose (phi3:mini)
- **Purpose**: Succession planner, general farm insights
- **Model**: phi3:mini (3.8B parameters, 2.1GB)
- **Speed**: ~23 seconds per request
- **Use**: Existing succession planning features

### Port 8006 - Variety Auditing (Mistral 7B)
- **Purpose**: Dedicated variety data validation
- **Model**: mistral:7b (7B parameters, 4.4GB)
- **Speed**: ~67 seconds per variety audit
- **Use**: Overnight variety audits for data accuracy

## Why Two Instances?

1. **Accuracy**: Mistral 7B provides much better horticultural knowledge
   - Example: Abutilon maturity - Mistral suggests 90 days (accurate), phi3:mini suggested 70 days (inaccurate)
   
2. **Resource Management**: Keep fast phi3:mini for real-time succession planning
   
3. **No Conflicts**: Audit jobs run overnight without affecting daytime farm planning

## Performance Comparison

| Model | Size | Speed | Accuracy | Use Case |
|-------|------|-------|----------|----------|
| phi3:mini | 2.1GB | 23s | Good | Real-time planning |
| Mistral 7B | 4.4GB | 67s | Excellent | Overnight audits |

## Starting/Stopping Instances

### Start Audit Instance (Port 8006)
```bash
/tmp/start-audit-ollama.sh
```

### Check Status
```bash
ps aux | grep ollama | grep -v grep
curl http://localhost:8005/api/tags  # General instance
curl http://localhost:8006/api/tags  # Audit instance
```

### Stop Audit Instance
```bash
kill $(cat /tmp/ollama-audit.pid)
```

## Running Variety Audits

### Quick Test (5 varieties)
```bash
php artisan varieties:audit --limit=5
```

### Full Overnight Audit (all 2,959 varieties)
```bash
# Estimate: ~50 hours at 67s/variety
nohup php artisan varieties:audit > /tmp/variety-audit.log 2>&1 &
```

### Check Progress
```bash
tail -f /tmp/variety-audit.log
```

## Code Configuration

The audit command automatically uses Mistral 7B:

```php
// app/Console/Commands/AuditVarieties.php line 175
$response = $this->ai->chat($messages, [
    'max_tokens' => 800, 
    'temperature' => 0.1,
    'model' => 'mistral:7b',
    'base_url' => 'http://localhost:8006/api'
]);
```

Succession planner still uses phi3:mini on port 8005 (default).

## RAM Usage

- **Available**: 5.8GB free
- **phi3:mini**: ~2.1GB
- **Mistral 7B**: ~4.4GB
- **Total when both running**: ~6.5GB (fits comfortably)

## Audit Workflow

1. Run audit overnight: `php artisan varieties:audit`
2. Review suggestions in Settings page (Admin → Settings → AI Variety Audit Review)
3. Edit any suggestions that need tweaking
4. Approve high-confidence suggestions in bulk
5. Apply approved changes to database
6. Re-run audit on any remaining problematic varieties

## Expected Results

With Mistral 7B, we expect:
- More accurate maturity day estimates (90-120 vs 60-80)
- Better spacing recommendations based on plant size
- Realistic harvest window durations
- Higher confidence scores overall

## Startup on Server Reboot

To auto-start both instances on reboot, add to crontab:
```bash
@reboot /usr/local/bin/ollama serve > /var/log/ollama-main.log 2>&1
@reboot sleep 10 && /tmp/start-audit-ollama.sh
```

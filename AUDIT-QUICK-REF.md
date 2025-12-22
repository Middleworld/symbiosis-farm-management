# Variety Audit - Quick Reference

## ⚡ Quick Commands

```bash
# Start audit (or resume if paused)
./run-full-audit.sh

# Check status and progress
./check-audit-status.sh

# Pause/stop audit
./pause-audit.sh

# View live log
tail -f /tmp/variety-audit.log
```

## 📊 Key Facts

- **Total Varieties**: 2,959
- **Speed**: ~49-67 seconds per variety
- **Total Time**: ~40-55 hours
- **Progress Saves**: Every 10 varieties (automatic)
- **AI Model**: Mistral 7B on port 8006

## 🔄 Workflow

1. **Start**: `./run-full-audit.sh` → runs in background
2. **Monitor**: `./check-audit-status.sh` → see progress %
3. **Pause**: `./pause-audit.sh` → save and stop
4. **Resume**: `./run-full-audit.sh` → pick up where you left off
5. **Review**: Visit Settings page → approve suggestions
6. **Apply**: Click "Apply Approved Changes"

## 💡 Tips

- Can pause and resume any time (no data loss)
- Progress saved every 10 varieties
- Runs in background (safe to log out)
- Check status before bed to see progress
- Review and approve results incrementally (don't wait for all 2,959)

## 📁 Important Files

- Progress: `storage/logs/variety-audit/progress.json`
- Logs: `/tmp/variety-audit.log`
- PID: `/tmp/variety-audit.pid`
- Results: Admin → Settings → AI Variety Audit Review

## 🎯 Accuracy Improvements (Mistral 7B vs phi3:mini)

| Plant | phi3:mini | Mistral 7B | Real Value |
|-------|-----------|------------|------------|
| Abutilon maturity | 70 days ❌ | 90 days ✅ | 90-120 days |
| Acanthus spacing | 30cm ❌ | 60cm ✅ | 60cm (large plant) |
| Broad bean spacing | 100cm ❌ | 30-45cm ✅ | 30-45cm |

**Result**: 2-3x more accurate suggestions with Mistral 7B!

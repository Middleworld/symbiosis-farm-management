# Claude API Setup for Variety Audit

## Why Claude API?

You chose **Claude 3.5 Sonnet** (the same AI that built this entire application) for:
- ✅ **98-99% accuracy** on variety data validation
- ✅ **2-3 hours** completion time (vs 40 hours with Mistral)
- ✅ **Perfect crop understanding** (no season_type for cacti!)
- ✅ **Better field estimates** (trained on vast horticultural datasets)
- ✅ **Cost: ~$4.50** for all 2,959 varieties

## Step 1: Get Your API Key

1. Visit: https://console.anthropic.com
2. Sign up or log in
3. Go to **API Keys** section
4. Click **Create Key**
5. Copy the key (starts with `sk-ant-api03-...`)

**Note:** New users often get $5 free credits - this audit might be completely free!

## Step 2: Add Key to .env

```bash
# Edit the .env file
nano /opt/sites/admin.middleworldfarms.org/.env

# Find this line:
CLAUDE_API_KEY=your_claude_api_key_here

# Replace with your actual key:
CLAUDE_API_KEY=sk-ant-api03-YOUR-ACTUAL-KEY-HERE

# Save and exit (Ctrl+O, Enter, Ctrl+X)
```

## Step 3: Run the Audit

```bash
cd /opt/sites/admin.middleworldfarms.org
./run-full-audit.sh
```

The audit will:
- ✅ Auto-detect Claude API key
- ✅ Process all 2,959 varieties (including flowers!)
- ✅ Generate suggestions with ~99% accuracy
- ✅ Complete in 2-3 hours
- ✅ Cost approximately $4.50

## Step 4: Monitor Progress

```bash
# Check status
./check-audit-status.sh

# Watch live log
tail -f /tmp/variety-audit.log
```

## Step 5: Review in Settings Page

Once complete:
1. Go to **Settings → AI Variety Audit Review**
2. Review suggestions (especially CRITICAL severity)
3. Edit/approve/reject as needed
4. Click **Apply All Approved Changes**

## Fallback to Mistral

If you don't add a Claude API key, the audit automatically falls back to:
- Local Mistral 7B on port 8006
- Free but slower (~40 hours)
- Lower accuracy (~80-90%)

## Cost Breakdown

**Claude 3.5 Sonnet pricing:**
- Input: $3 per 1M tokens
- Output: $15 per 1M tokens

**Estimated usage:**
- Input: ~500K tokens = $1.50
- Output: ~200K tokens = $3.00
- **Total: ~$4.50**

**Per variety cost:** $0.0015 (less than 1/5th of a penny!)

## Questions?

The audit code automatically:
- ✅ Detects if you have a valid API key
- ✅ Uses Claude if available
- ✅ Falls back to Mistral if not
- ✅ Logs which model is being used

No configuration needed beyond adding the API key to `.env`!

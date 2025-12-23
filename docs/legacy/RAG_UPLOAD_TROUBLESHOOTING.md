# RAG Upload Troubleshooting Guide

## Current Status Check:

```bash
cd /opt/sites/admin.middleworldfarms.org

# 1. Check queue worker is running
ps aux | grep "queue:work" | grep -v grep

# 2. Check Ollama port 8007
netstat -tlnp | grep :8007
curl -s http://localhost:8007/api/tags | jq -r '.models[] | .name'

# 3. Check current queue
php artisan tinker --execute="echo 'Jobs: ' . DB::table('jobs')->count();"

# 4. Watch logs in real-time
tail -f storage/logs/laravel.log
```

## To Test Upload:

1. **Hard Refresh Browser**: Press `Ctrl + F5` (or `Cmd + Shift + R` on Mac)
2. **Open Browser Console**: Press `F12`, go to Console tab
3. **Try uploading a PDF or CSV file**
4. **Check Console for errors**
5. **Check Laravel logs**: `tail -f storage/logs/laravel.log`

## Common Issues:

### Issue 1: "No files chosen" or PDF not uploading
**Solution**: 
- The file input accepts `.csv,.txt,.pdf`
- Check browser console for JavaScript errors
- Hard refresh: `Ctrl + F5`

### Issue 2: 413 Content Too Large
**Solution**: Already fixed - PHP limits set to 50MB per file

### Issue 3: Files not showing in queue
**Symptoms**: Upload succeeds but no files in "Files Queued for Processing"
**Solution**:
```bash
# Check if jobs were actually created
php artisan tinker --execute="
\$jobs = DB::table('jobs')->get();
foreach(\$jobs as \$job) {
    echo 'Job ID: ' . \$job->id . PHP_EOL;
}
"

# If jobs exist, refresh the page
# The table only updates on page load, not in real-time (yet)
```

### Issue 4: Upload returns 500 error
**Check logs**:
```bash
tail -50 storage/logs/laravel.log
```

**Look for validation errors or permission issues**

## Manual Upload Test:

```bash
# Create test file
cat > /tmp/test.csv << 'EOF'
name,value
item1,100
item2,200
EOF

# Test upload via command line
php artisan tinker --execute="
\App\Jobs\ProcessRagFile::dispatch(
    '/tmp/test.csv',
    'Manual Test',
    10,
    storage_path('logs/rag-ingestion/progress_manual_' . time() . '.json')
);
echo 'Job dispatched';
"

# Wait 3 seconds
sleep 3

# Check if it processed
php artisan tinker --execute="
\$count = DB::connection('pgsql_rag')->table('general_knowledge')
    ->where('source', 'test.csv')
    ->count();
echo 'Records created: ' . \$count . PHP_EOL;
"
```

## Force Refresh Everything:

```bash
cd /opt/sites/admin.middleworldfarms.org

# Clear all caches
php artisan view:clear
php artisan cache:clear  
php artisan config:clear
php artisan route:clear

# Restart queue worker
pkill -f "queue:work"
php artisan queue:work --tries=3 --timeout=600 --sleep=3 --max-jobs=1000 > /tmp/queue-worker.log 2>&1 &

# Ensure Ollama port 8007 is running
netstat -tlnp | grep :8007

# If not running:
nohup env OLLAMA_HOST=0.0.0.0:8007 ollama serve > /tmp/ollama-8007.log 2>&1 &
```

## Check What Browser Sees:

1. Open browser dev tools (F12)
2. Go to Network tab
3. Try uploading a file
4. Look for the `/admin/rag-ingestion/upload` request
5. Check:
   - Request payload (should show files)
   - Response (should show success: true)
   - Status code (should be 200)

## If Upload Button Does Nothing:

**Check JavaScript Console for errors**:
- Open Dev Tools (F12)
- Go to Console tab
- Look for red error messages
- Common causes:
  - CSRF token missing
  - JavaScript syntax errors
  - File input not found

## Verify File Input Accepts PDF:

View page source (Ctrl+U) and search for `rag-files`:
```html
<input type="file" class="form-control form-control-sm" id="rag-files" 
       accept=".csv,.txt,.pdf" multiple>
```

Should show `accept=".csv,.txt,.pdf"` ✓

## Current Configuration:

- **Port 8005**: Phi3 Small (Main AI)
- **Port 8006**: Mistral 7B (Auditing)  
- **Port 8007**: all-minilm:l6-v2 (Embeddings) ← **MUST BE RUNNING**
- **PHP Upload Limits**: 50MB per file, 200MB total
- **Queue Timeout**: 600 seconds (10 minutes)

---
Last Updated: October 24, 2025

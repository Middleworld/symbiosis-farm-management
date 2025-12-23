# OLLAMA PORT CONFIGURATION - DO NOT CHANGE

**CRITICAL: This configuration is permanent and must not be modified.**

## Port Assignment (User-Specified):

- **Port 8005**: Phi3 Small - Main AI used for general tasks
- **Port 8006**: Mistral 7B - Used for sequential running jobs like auditing (slower than Phi3)
- **Port 8007**: all-minilm:l6-v2 - Embeddings model for RAG (Retrieval-Augmented Generation)

## Service Status:

### Port 8005 (Phi3 Small)
```bash
netstat -tlnp | grep :8005
# Should show: tcp 0 0 127.0.0.1:8005 0.0.0.0:* LISTEN [pid]/ollama
```

### Port 8006 (Mistral 7B)
```bash
netstat -tlnp | grep :8006
# Should show: tcp 0 0 127.0.0.1:8006 0.0.0.0:* LISTEN [pid]/ollama
```

### Port 8007 (Embeddings - all-minilm:l6-v2)
```bash
netstat -tlnp | grep :8007
# Should show: tcp6 0 0 :::8007 :::* LISTEN [pid]/ollama
```

## Configuration Files:

### .env
```
OLLAMA_URL=http://localhost:8007
```

### config/services.php
```php
'ollama' => [
    'url' => env('OLLAMA_URL', 'http://localhost:8007'),
    'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'all-minilm:l6-v2'),
    'chat_model' => env('OLLAMA_CHAT_MODEL', 'phi3:mini'),
],
```

### app/Services/EmbeddingService.php
```php
protected $ollamaHost = 'http://localhost:8007';
protected $model = 'all-minilm:l6-v2';
```

## Starting Ollama on Port 8007 (if needed):

```bash
# Start Ollama on port 8007
nohup env OLLAMA_HOST=0.0.0.0:8007 ollama serve > /tmp/ollama-8007.log 2>&1 &

# Wait for it to start
sleep 5

# Pull the embedding model
curl -X POST http://localhost:8007/api/pull -d '{"name":"all-minilm:l6-v2"}'

# Verify it's working
curl -s http://localhost:8007/api/tags | jq -r '.models[] | .name'

# Test embeddings
curl -X POST http://localhost:8007/api/embeddings \
  -H "Content-Type: application/json" \
  -d '{"model":"all-minilm:l6-v2","prompt":"test"}' | jq '.embedding | length'
```

## Queue Worker for RAG Processing:

```bash
# Start queue worker
php artisan queue:work --tries=3 --timeout=600 --sleep=3 --max-jobs=1000

# Or run in background
php artisan queue:work --tries=3 --timeout=600 --sleep=3 --max-jobs=1000 > /tmp/queue-worker.log 2>&1 &

# Check queue status
php artisan tinker --execute="echo 'Jobs: ' . DB::table('jobs')->count();"
```

## Troubleshooting:

### If embeddings fail:
1. Check port 8007 is running: `netstat -tlnp | grep :8007`
2. Check model is available: `curl -s http://localhost:8007/api/tags | jq '.models'`
3. Test embedding API: See test command above
4. Check logs: `tail -f /tmp/ollama-8007.log`

### If port 8007 is not running:
Run the startup commands in the "Starting Ollama on Port 8007" section above.

---
**Last Updated**: October 24, 2025
**Configuration Status**: ✅ Active and Verified

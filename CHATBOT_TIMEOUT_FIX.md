# Chatbot Timeout Fix & Performance Improvements

## Issue Resolved
The chatbot was timing out after 60 seconds on longer questions, showing:
> "Sorry, I encountered an error: file_get_contents(http://localhost:8005/api/generate): Failed to open stream: HTTP request failed!"

## Changes Made

### 1. Increased PHP Timeouts
**File:** `/opt/plesk/php/8.3/etc/php.ini`

**Before:**
```ini
max_execution_time = 30
default_socket_timeout = 60
```

**After:**
```ini
max_execution_time = 180  # 3 minutes
default_socket_timeout = 180  # 3 minutes
```

**Why:** PHP was killing the request after 30 seconds, even though Phi-3 needed more time for complex questions.

### 2. Updated Chatbot API Route
**File:** `routes/web.php`

**Improvements:**
- ✅ Increased HTTP timeout: 60s → 120s
- ✅ Added system prompt for context and conciseness
- ✅ Limited response tokens: 400 tokens (~300 words)
- ✅ Added temperature control (0.7) for balanced creativity
- ✅ Better error handling and logging
- ✅ Added `ignore_errors` flag for better timeout detection

**New System Prompt:**
```
You are Sybiosis, a helpful farm management AI assistant for Middle World Farms. 
Provide clear, practical answers about farming, biodynamic practices, and agriculture. 
Keep responses focused and concise (2-3 paragraphs max) unless asked for detailed information.
```

**API Parameters:**
```php
'options' => [
    'temperature' => 0.7,      // Balanced creativity
    'num_predict' => 400,      // Max tokens (shorter = faster)
    'top_p' => 0.9            // Sampling strategy
]
```

### 3. Restarted Services
- ✅ PHP-FPM restarted to apply new timeouts
- ✅ OPcache cleared to load updated routes

## Expected Improvements

### Response Speed
**Before:**
- First question: 45 seconds ✅
- Second question: TIMEOUT ❌

**After:**
- Simple questions: 10-20 seconds
- Complex questions: 30-60 seconds
- Maximum timeout: 120 seconds (2 minutes)
- Concise responses (2-3 paragraphs) = faster generation

### Response Quality
**With System Prompt:**
- Identifies as "Sybiosis" farm assistant
- Stays on-topic (farming/biodynamic practices)
- More concise (400 tokens max vs unlimited)
- Better structured responses

### Error Handling
**Before:**
- Generic error message
- No logging

**After:**
- Specific error messages
- Logged to Laravel log
- User-friendly timeout message

## Testing the Fix

### Test Question 1 (Simple)
```
"What is crop rotation?"
```
**Expected:** 10-20 second response, 2-3 paragraphs

### Test Question 2 (Previously Failed)
```
"How do I prepare biodynamic preparations?"
```
**Expected:** 30-60 second response, practical steps, no timeout

### Test Question 3 (Complex)
```
"Explain the relationship between moon phases and planting schedules in biodynamic farming."
```
**Expected:** 40-80 second response, detailed but concise

## Configuration Summary

| Setting | Before | After | Purpose |
|---------|--------|-------|---------|
| PHP max_execution_time | 30s | 180s | Allow long requests |
| PHP socket_timeout | 60s | 180s | Don't kill active connections |
| HTTP request timeout | 60s | 120s | Wait for AI response |
| Response token limit | Unlimited | 400 | Faster, more concise |
| System prompt | None | Added | Context & quality |
| Temperature | Default (1.0) | 0.7 | More focused responses |

## Monitoring

### Check Chatbot Logs
```bash
tail -f /opt/sites/admin.middleworldfarms.org/storage/logs/laravel.log | grep "Chatbot"
```

### Check PHP Errors
```bash
tail -f /var/log/nginx/error.log | grep chatbot
```

### Test Ollama Directly
```bash
curl -X POST http://localhost:8005/api/generate \
  -H "Content-Type: application/json" \
  -d '{
    "model": "phi3:mini",
    "prompt": "Test question",
    "stream": false,
    "options": {"num_predict": 400}
  }'
```

## Troubleshooting

### If Still Timing Out
1. Check Ollama is running: `netstat -tlnp | grep 8005`
2. Test direct connection: `curl http://localhost:8005/api/tags`
3. Check server load: `top` (look for high CPU/memory)
4. Increase timeout further in `routes/web.php`

### If Responses Are Too Short
Increase `num_predict` in `routes/web.php`:
```php
'num_predict' => 600,  // Longer responses
```

### If Responses Are Too Slow
Decrease `num_predict`:
```php
'num_predict' => 300,  // Faster responses
```

## Performance Tips

### For Faster Responses
1. Use shorter questions
2. Ask specific questions (avoid "tell me everything about...")
3. Use quick question buttons instead of typing
4. Consider enabling response streaming (shows partial results)

### For Better Quality
1. Ask one question at a time
2. Provide context in your question
3. Use the follow-up feature
4. Rephrase if answer isn't helpful

## Next Steps

### Optional Enhancements
1. **Add Streaming** - See response appear word-by-word
2. **Add Conversation Memory** - Remember previous questions in session
3. **Connect to RAG** - Use uploaded PDF knowledge base
4. **Add Response Caching** - Cache common questions
5. **Add Queue System** - Process multiple questions in background

### Recommended: Enable Streaming
Streaming would make responses feel instant by showing partial text as it generates:
- User sees first words in ~2 seconds
- Full response still takes same time
- Better user experience
- No timeout issues (connection stays alive)

## Summary

✅ **Fixed:** Timeout errors on complex questions
✅ **Improved:** Response speed through token limiting
✅ **Enhanced:** Response quality with system prompt
✅ **Added:** Better error handling and logging
✅ **Configured:** Proper timeouts throughout stack

**Status:** Chatbot should now handle all questions without timeouts!

Try asking "How do I prepare biodynamic preparations?" again - it should work now! 🚀

#!/bin/bash
# Restart AI service with RAG support

echo "Stopping old AI service on port 8005..."
pkill -f "simple_ai.py"
sleep 2

echo "Starting full AI service with RAG on port 8005..."
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop/ai_service
nohup python3 -m app.main > ai_service.log 2>&1 &

sleep 3
echo "Checking service status..."
if curl -s http://localhost:8005/health > /dev/null; then
    echo "✅ AI service is running on port 8005"
else
    echo "❌ AI service failed to start. Check ai_service.log"
fi

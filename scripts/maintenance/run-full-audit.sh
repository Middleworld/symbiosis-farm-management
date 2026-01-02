#!/bin/bash
#
# Launch full variety audit with Mistral 7B
# Estimated time: ~40 hours for 2,959 varieties
#

echo "🌱 Starting Full Variety Audit with Mistral 7B"
echo "⏱️  Estimated completion: ~40 hours"
echo "📊 Progress will be logged to /tmp/variety-audit.log"
echo ""
echo "Starting in 5 seconds... (Ctrl+C to cancel)"
sleep 5

APP_DIR="${APP_DIR:-/opt/sites/admin.your-domain.com}"
cd "$APP_DIR"

# Run audit in background
nohup php artisan varieties:audit > /tmp/variety-audit.log 2>&1 &

AUDIT_PID=$!
echo $AUDIT_PID > /tmp/variety-audit.pid

echo "✅ Audit started (PID: $AUDIT_PID)"
echo ""
echo "Monitor progress:"
echo "  tail -f /tmp/variety-audit.log"
echo ""
echo "Check status:"
echo "  ps aux | grep $AUDIT_PID"
echo ""
echo "View results:"
echo "  Visit https://admin.your-domain.com/admin/settings (AI Variety Audit Review section)"
echo ""
echo "Cancel audit:"
echo "  kill $AUDIT_PID"

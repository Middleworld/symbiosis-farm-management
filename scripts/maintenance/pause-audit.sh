#!/bin/bash
#
# Pause/Stop the running variety audit
#

if [ ! -f /tmp/variety-audit.pid ]; then
    echo "❌ No audit is currently running (no PID file found)"
    exit 1
fi

AUDIT_PID=$(cat /tmp/variety-audit.pid)

# Check if process is actually running
if ! ps -p $AUDIT_PID > /dev/null 2>&1; then
    echo "❌ Audit process (PID: $AUDIT_PID) is not running"
    rm /tmp/variety-audit.pid
    exit 1
fi

echo "⏸️  Pausing variety audit (PID: $AUDIT_PID)"
echo ""
echo "Sending interrupt signal..."
kill -SIGTERM $AUDIT_PID

# Wait a moment
sleep 2

# Check if it stopped
if ! ps -p $AUDIT_PID > /dev/null 2>&1; then
    echo "✅ Audit stopped successfully"
    echo ""
    
    # Check for progress file
    APP_DIR="${APP_DIR:-/opt/sites/admin.your-domain.com}"
    PROGRESS_FILE="$APP_DIR/storage/logs/variety-audit/progress.json"
    if [ -f "$PROGRESS_FILE" ]; then
        echo "📊 Progress saved:"
        cat "$PROGRESS_FILE" | jq '.'
        echo ""
        echo "To resume later, just run: ./run-full-audit.sh"
        echo "The system will ask if you want to resume from where you left off."
    fi
else
    echo "⚠️  Process still running, sending kill signal..."
    kill -9 $AUDIT_PID
    sleep 1
    echo "✅ Audit forcefully stopped"
fi

rm /tmp/variety-audit.pid
echo ""
echo "Audit paused. Progress has been saved."

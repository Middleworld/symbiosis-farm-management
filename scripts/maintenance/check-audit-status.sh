#!/bin/bash
#
# Check the status of the variety audit
#

echo "🔍 Variety Audit Status Check"
echo "=============================="
echo ""

# Check if audit is running
if [ -f /tmp/variety-audit.pid ]; then
    AUDIT_PID=$(cat /tmp/variety-audit.pid)
    
    if ps -p $AUDIT_PID > /dev/null 2>&1; then
        echo "✅ Audit is RUNNING (PID: $AUDIT_PID)"
        echo ""
        
        # Show process info
        echo "Process info:"
        ps aux | grep $AUDIT_PID | grep -v grep
        echo ""
    else
        echo "⚠️  PID file exists but process is not running"
        rm /tmp/variety-audit.pid
    fi
else
    echo "⏸️  No audit currently running"
    echo ""
fi

# Check for progress file
APP_DIR="${APP_DIR:-/opt/sites/admin.your-domain.com}"
PROGRESS_FILE="$APP_DIR/storage/logs/variety-audit/progress.json"
if [ -f "$PROGRESS_FILE" ]; then
    echo "📊 Last Progress Save:"
    echo "---------------------"
    cat "$PROGRESS_FILE" | jq '.'
    echo ""
    
    # Calculate progress percentage
    PROCESSED=$(cat "$PROGRESS_FILE" | jq -r '.processed')
    LAST_ID=$(cat "$PROGRESS_FILE" | jq -r '.last_processed_id')
    TOTAL=2959
    PERCENT=$(echo "scale=2; ($PROCESSED / $TOTAL) * 100" | bc)
    REMAINING=$((TOTAL - PROCESSED))
    
    # Estimate time remaining (at 49s per variety)
    SECONDS_REMAINING=$((REMAINING * 49))
    HOURS_REMAINING=$(echo "scale=1; $SECONDS_REMAINING / 3600" | bc)
    
    echo "Progress: $PROCESSED / $TOTAL varieties ($PERCENT%)"
    echo "Remaining: $REMAINING varieties (~$HOURS_REMAINING hours at 49s/variety)"
    echo ""
else
    echo "📄 No progress file found - audit not started or completed"
    echo ""
fi

# Check recent audit results in database
echo "📦 Recent Audit Results:"
echo "-----------------------"
cd "$APP_DIR"
php artisan tinker --execute="
\$count = App\Models\VarietyAuditResult::count();
\$pending = App\Models\VarietyAuditResult::where('status', 'pending')->count();
\$approved = App\Models\VarietyAuditResult::where('status', 'approved')->count();
echo \"Total suggestions in database: \$count\n\";
echo \"Pending: \$pending\n\";
echo \"Approved: \$approved\n\";
" 2>&1 | grep -v "Psy Shell"

echo ""
echo "Commands:"
echo "  View log: tail -f /tmp/variety-audit.log"
echo "  Pause:    ./pause-audit.sh"
echo "  Resume:   ./run-full-audit.sh"

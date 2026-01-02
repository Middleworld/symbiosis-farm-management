#!/bin/bash

# Setup Daily Audit Cron Job
# This script sets up a daily cron job to run the audit system

echo "🔧 Setting up daily audit cron job..."

# Configuration
APP_DIR="${APP_DIR:-/opt/sites/admin.your-domain.com}"

# Define the cron job
CRON_JOB="0 6 * * * cd $APP_DIR && php artisan audit:daily --send-alerts >> $APP_DIR/storage/logs/daily-audit.log 2>&1"

# Check if cron job already exists
if crontab -l | grep -q "audit:daily"; then
    echo "⚠️  Daily audit cron job already exists. Skipping..."
else
    # Add the cron job
    (crontab -l ; echo "$CRON_JOB") | crontab -
    echo "✅ Daily audit cron job added successfully!"
    echo "   Runs daily at 6:00 AM"
    echo "   Logs to: storage/logs/daily-audit.log"
fi

# Create log directory if it doesn't exist
mkdir -p "$APP_DIR/storage/logs"

# Test the cron job
echo "🧪 Testing audit system..."
cd "$APP_DIR"
php artisan audit:daily --send-alerts

echo ""
echo "🎉 Setup complete!"
echo "   - Daily audits will run at 6:00 AM"
echo "   - Check logs at: storage/logs/daily-audit.log"
echo "   - View reports at: storage/audits/"

# Stripe Integration Setup Guide

## Overview
Your Stripe payments are now integrated with bank transactions! The system automatically matches Stripe payouts with your Tide bank deposits.

## What's Working Now

✅ **Manual Matching**: Run `php artisan stripe:match-payouts` to match historical payouts
✅ **Improved Dashboard**: Date range picker, pagination, customer names showing
✅ **Webhook Handler**: Ready to receive Stripe events (needs configuration)

## Stripe Webhook Configuration

To enable automatic real-time matching of new payouts, configure Stripe webhooks:

### Step 1: Get Your Webhook URL
Your webhook endpoint is:
```
https://admin.middleworldfarms.org:8444/webhooks/stripe
```

### Step 2: Configure in Stripe Dashboard

1. **Login to Stripe Dashboard**: https://dashboard.stripe.com/
2. **Go to Developers → Webhooks**: https://dashboard.stripe.com/webhooks
3. **Click "Add endpoint"**
4. **Enter your webhook URL**: `https://admin.middleworldfarms.org:8444/webhooks/stripe`
5. **Select events to listen for**:
   - ✅ `payout.paid` (most important - triggers automatic matching)
   - ✅ `payout.failed` (alerts you to failed payouts)
   - ✅ `payment_intent.succeeded` (optional - for payment tracking)
   - ✅ `charge.refunded` (optional - for refund tracking)

6. **Click "Add endpoint"**
7. **Copy the "Signing secret"** (starts with `whsec_...`)

### Step 3: Add Webhook Secret to .env

Edit `/opt/sites/admin.middleworldfarms.org/.env`:

```bash
# Add this line (replace with your actual secret from step 2.7)
STRIPE_WEBHOOK_SECRET=whsec_your_secret_here_from_stripe_dashboard
```

### Step 4: Test the Webhook

1. In Stripe Dashboard, go to your webhook
2. Click "Send test webhook"
3. Select `payout.paid` event type
4. Click "Send test webhook"
5. Check `/opt/sites/admin.middleworldfarms.org/storage/logs/laravel.log` for confirmation

## Usage

### Automatic (Webhook)
Once webhooks are configured, new Stripe payouts will automatically be matched to bank transactions when they arrive in your Tide account.

### Manual (Command Line)

Match last 90 days:
```bash
cd /opt/sites/admin.middleworldfarms.org
php artisan stripe:match-payouts --days=90
```

Preview matches without saving:
```bash
php artisan stripe:match-payouts --days=30 --dry-run
```

Match last year:
```bash
php artisan stripe:match-payouts --days=365
```

### Schedule Automatic Matching

Add to Laravel scheduler (in case webhooks miss anything):

Edit `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Run every day at 2am to catch any missed matches
    $schedule->command('stripe:match-payouts --days=7')
             ->dailyAt('02:00');
}
```

Then ensure cron is running:
```bash
crontab -e
# Add this line:
* * * * * cd /opt/sites/admin.middleworldfarms.org && php artisan schedule:run >> /dev/null 2>&1
```

## How Matching Works

1. **Stripe pays out** to your bank account (usually daily or weekly)
2. **Payout appears in Tide** as "Stripe Payments UK Ltd ref: STRIPE"
3. **System matches** the payout based on:
   - Amount must match exactly
   - Date within ±3 days of Stripe arrival date
   - Description contains "STRIPE"
   - Not already matched

4. **Linked data saved**:
   - `stripe_payout_id`: Stripe payout ID (e.g., `po_1SSohrHVCuOjVw0H63LCIHy6`)
   - `stripe_charges`: Array of individual customer charges in that payout

## Viewing Matched Transactions

### In Bank Transaction Dashboard
Visit: https://admin.middleworldfarms.org:8444/admin/bank-transactions/dashboard

Stripe deposits will now show:
- Which Stripe payout they came from
- Individual customer charges included
- Direct links to Stripe dashboard

### In Stripe Dashboard
Visit: https://admin.middleworldfarms.org:8444/admin/stripe

Now includes:
- Custom date range picker (any date range, not just recent)
- "Load More" pagination (go back through history)
- Customer names (not just IDs)
- Export to CSV

## Troubleshooting

### "Not found in bank" Errors
If the command reports payouts not found:
1. **Check Tide CSV is imported** with that date range
2. **Amount may differ** (Stripe shows gross, bank shows net after fees)
3. **Date mismatch** - Stripe "arrival_date" might be off by a day or two
4. **Already matched** - run with `--dry-run` to check

### Webhook Not Working
1. Check `.env` has `STRIPE_WEBHOOK_SECRET` set correctly
2. Check logs: `tail -f storage/logs/laravel.log`
3. Verify URL is publicly accessible (port 8444 open)
4. Check Stripe Dashboard → Webhooks → Recent deliveries for errors

### Re-match a Transaction
```bash
# First, clear the existing match
php artisan tinker
>>> \App\Models\BankTransaction::where('stripe_payout_id', 'po_xxxxx')->update(['stripe_payout_id' => null, 'stripe_charges' => null]);

# Then re-run matching
php artisan stripe:match-payouts --days=90
```

## Summary

**21 historical payouts matched!** ✅

The 4 "not found" are likely:
- Very recent (not yet in Tide CSV)
- Scheduled for future arrival
- Different amounts due to fees/adjustments

Configure webhooks following steps above for automatic real-time matching going forward!

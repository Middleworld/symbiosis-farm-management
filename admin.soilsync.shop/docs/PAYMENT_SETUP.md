# Payment Processing Setup Guide

This guide covers setting up payment processing for the MWF Platform, which is essential for subscription revenue and customer payments.

## Quick Setup

For the fastest setup, use our automated script:

```bash
# From your Laravel directory
./scripts/setup-payments.sh
```

This script will:
- Configure Stripe API keys
- Set up payment settings
- Test connections
- Provide webhook setup instructions

## Manual Setup

### 1. Stripe Configuration

1. Create a Stripe account at [stripe.com](https://stripe.com)
2. Get your API keys from the Stripe Dashboard
3. Add to your `.env` file:

```env
# Stripe Configuration
STRIPE_KEY=pk_live_your_publishable_key_here
STRIPE_SECRET=sk_live_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

### 2. Payment Settings

Add these settings to your `.env` file:

```env
# Payment Settings
PAYMENT_CURRENCY=GBP
PAYMENT_METHOD=stripe
SUBSCRIPTION_GRACE_PERIOD_DAYS=7
SUBSCRIPTION_MAX_RETRY_ATTEMPTS=3
SUBSCRIPTION_RETRY_DELAYS="2,4,6"
AUTO_RETRY_FAILED_PAYMENTS=true
NOTIFY_CUSTOMERS_ON_PAYMENT_FAILURE=true
```

### 3. Webhook Setup

Set up webhooks in Stripe Dashboard for automatic payment processing:

- **Endpoint URL**: `https://yourdomain.com/webhooks/stripe`
- **Events to listen for**:
  - `payment_intent.succeeded`
  - `payment_intent.payment_failed`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`

### 4. Testing

Test your payment setup:

```bash
# Test payment service connections
php artisan vegbox:test-payment-processing

# Test Stripe connection specifically
php artisan tinker --execute="
\$stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
\$balance = \$stripe->balance->retrieve();
echo 'Balance: £' . \$balance->available[0]->amount / 100;
"
```

## Payment Flow

The system supports two payment methods:

1. **Stripe Direct**: Direct Stripe integration for new subscriptions
2. **MWF Payment Service**: Hosted payment processing (optional)

### Subscription Processing

- Automatic renewal attempts on billing dates
- Grace period handling for failed payments
- Configurable retry logic
- Customer notifications for payment issues

### Key Components

- `VegboxPaymentService`: Main payment processing service
- `ProcessSubscriptionRenewals`: Artisan command for batch renewals
- `RetryFailedPayments`: Command for retrying failed payments
- Webhook handlers for real-time payment updates

## Troubleshooting

### Common Issues

1. **API Key Errors**: Verify Stripe keys are correct and have proper permissions
2. **Webhook Failures**: Ensure webhook endpoint is accessible and SSL is valid
3. **Subscription Sync**: Run `php artisan vegbox:import-woo-subscriptions` to sync data

### Monitoring

- Check Stripe Dashboard for payment activity
- Monitor Laravel logs for payment processing errors
- Use admin dashboard to view failed payment queues

## Security Notes

- Never commit Stripe keys to version control
- Use webhook signatures to verify Stripe requests
- Regularly rotate API keys
- Monitor for suspicious payment activity

## Support

For payment setup issues:
- Test with Stripe test mode first
- Check webhook delivery logs in Stripe Dashboard
- Verify server firewall allows Stripe IP ranges
- Contact support with payment processing logs
#!/bin/bash

# MWF Platform Payment Setup Script
# This script helps configure payment processing for Vegbox subscriptions

set -e

echo "💳 MWF Platform Payment Setup"
echo "================================"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory"
    exit 1
fi

# Function to prompt for input
prompt_for_input() {
    local prompt="$1"
    local default="$2"
    local input

    read -p "$prompt [$default]: " input
    echo "${input:-$default}"
}

echo "📋 Step 1: Stripe Configuration"
echo "--------------------------------"

# Check if Stripe keys are already configured
if grep -q "STRIPE_KEY=" .env 2>/dev/null; then
    echo "⚠️  Stripe appears to already be configured. Skip? (y/n)"
    read -r skip_stripe
    if [[ "$skip_stripe" =~ ^[Yy]$ ]]; then
        echo "⏭️  Skipping Stripe configuration..."
    else
        configure_stripe=true
    fi
else
    configure_stripe=true
fi

if [ "$configure_stripe" = true ]; then
    echo "Enter your Stripe API keys (get these from https://dashboard.stripe.com/apikeys):"

    STRIPE_PUBLISHABLE_KEY=$(prompt_for_input "Stripe Publishable Key (pk_...)" "")
    STRIPE_SECRET_KEY=$(prompt_for_input "Stripe Secret Key (sk_...)" "")
    STRIPE_WEBHOOK_SECRET=$(prompt_for_input "Stripe Webhook Secret (whsec_...)" "")

    # Update .env file
    if [ -f .env ]; then
        # Remove existing Stripe config
        sed -i '/^STRIPE_/d' .env

        # Add new config
        cat >> .env << EOF

# Stripe Configuration
STRIPE_KEY=$STRIPE_PUBLISHABLE_KEY
STRIPE_SECRET=$STRIPE_SECRET_KEY
STRIPE_WEBHOOK_SECRET=$STRIPE_WEBHOOK_SECRET
EOF
        echo "✅ Stripe configuration added to .env"
    else
        echo "❌ Error: .env file not found"
        exit 1
    fi
fi

echo ""
echo "📋 Step 2: Payment Settings"
echo "---------------------------"

# Configure payment settings
PAYMENT_CURRENCY=$(prompt_for_input "Payment Currency" "GBP")
GRACE_PERIOD=$(prompt_for_input "Grace Period Days" "7")
MAX_RETRY=$(prompt_for_input "Max Retry Attempts" "3")
RETRY_DELAYS=$(prompt_for_input "Retry Delays (comma-separated)" "2,4,6")

# Update .env with payment settings
cat >> .env << EOF

# Payment Settings
PAYMENT_CURRENCY=$PAYMENT_CURRENCY
PAYMENT_METHOD=stripe
SUBSCRIPTION_GRACE_PERIOD_DAYS=$GRACE_PERIOD
SUBSCRIPTION_MAX_RETRY_ATTEMPTS=$MAX_RETRY
SUBSCRIPTION_RETRY_DELAYS="$RETRY_DELAYS"
AUTO_RETRY_FAILED_PAYMENTS=true
NOTIFY_CUSTOMERS_ON_PAYMENT_FAILURE=true
EOF

echo "✅ Payment settings configured"

echo ""
echo "📋 Step 3: Clear Configuration Cache"
echo "-------------------------------------"

php artisan config:clear
php artisan cache:clear

echo "✅ Configuration cache cleared"

echo ""
echo "📋 Step 4: Test Payment Connections"
echo "------------------------------------"

echo "Testing Stripe connection..."
php artisan tinker --execute="
try {
    \$stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
    \$balance = \$stripe->balance->retrieve();
    echo '✅ Stripe connection successful!';
    echo 'Available balance: £' . number_format(\$balance->available[0]->amount / 100, 2);
} catch (\Exception \$e) {
    echo '❌ Stripe connection failed: ' . \$e->getMessage();
}
"

echo ""
echo "Testing Vegbox Payment Service..."
php artisan vegbox:test-payment-processing

echo ""
echo "📋 Step 5: Webhook Setup Instructions"
echo "--------------------------------------"

echo "⚠️  IMPORTANT: Set up Stripe webhooks for automatic payment processing"
echo ""
echo "1. Go to https://dashboard.stripe.com/webhooks"
echo "2. Click 'Add endpoint'"
echo "3. Set URL to: https://yourdomain.com/webhooks/stripe"
echo "4. Select these events:"
echo "   - payment_intent.succeeded"
echo "   - payment_intent.payment_failed"
echo "   - invoice.payment_succeeded"
echo "   - invoice.payment_failed"
echo "   - customer.subscription.created"
echo "   - customer.subscription.updated"
echo "   - customer.subscription.deleted"
echo "5. Copy the webhook secret and add to STRIPE_WEBHOOK_SECRET in .env"
echo ""

echo "🎉 Payment setup completed!"
echo ""
echo "💡 Next steps:"
echo "   - Set up Stripe webhooks as described above"
echo "   - Test with a real subscription: php artisan vegbox:test-payment-processing --subscription_id=1"
echo "   - Monitor payment processing in the admin dashboard"
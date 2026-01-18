@extends('layouts.app')

@section('title', 'Setup & Installation Guide')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-cogs"></i> Setup & Installation Guide
                    </h1>
                    <a href="{{ route('admin.docs.user-manual') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to User Manual
                    </a>
                </div>
                <div class="card-body">

                    <div class="alert alert-success">
                        <i class="fas fa-rocket"></i>
                        <strong>Quick Setup Available!</strong> Use our automated setup script for the fastest installation experience.
                    </div>

                    <h2>🚀 Quick Setup (5 Minutes)</h2>

                    <h3>Option 1: Automated Setup Script (Recommended)</h3>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5>Run the automated setup script:</h5>
                            <pre><code># Navigate to your WordPress directory
cd /path/to/wordpress

# Run the setup script
/path/to/mwf-platform/scripts/setup-new-wordpress-site.sh</code></pre>

                            <p><strong>This script automatically handles:</strong></p>
                            <ul class="list-unstyled">
                                <li>✅ <strong>WooCommerce REST API</strong> - Enables and configures API access</li>
                                <li>✅ <strong>Permalinks</strong> - Sets proper URL structure for API endpoints</li>
                                <li>✅ <strong>Admin Capabilities</strong> - Adds WooCommerce permissions to admin users</li>
                                <li>✅ <strong>API Keys</strong> - Generates keys with correct read/write permissions</li>
                                <li>✅ <strong>Plugins</strong> - Activates required MWF plugins</li>
                                <li>✅ <strong>Product Sync</strong> - Syncs all variable products from WooCommerce</li>
                                <li>✅ <strong>Caches</strong> - Clears all caches for immediate effect</li>
                                <li>✅ <strong>Credentials</strong> - Saves API keys to <code>.env-woocommerce-api</code></li>
                            </ul>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-list-check"></i> After running the script:</h6>
                                <ol>
                                    <li>Copy the API credentials from <code>.env-woocommerce-api</code> to your Laravel <code>.env</code> file</li>
                                    <li>Run <code>php artisan config:clear</code> to refresh configuration</li>
                                    <li>Test the connection - you're done! 🎉</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <h3>Option 2: Payment Setup Script (For Revenue)</h3>

                    <div class="card bg-warning">
                        <div class="card-body">
                            <h5>Run the payment setup script:</h5>
                            <pre><code># From your Laravel directory
cd /path/to/laravel

# Run the payment setup script
./scripts/setup-payments.sh</code></pre>

                            <p><strong>This script configures:</strong></p>
                            <ul class="list-unstyled">
                                <li>💳 <strong>Stripe Integration</strong> - API keys and webhook configuration</li>
                                <li>⚙️ <strong>Payment Settings</strong> - Currency, grace periods, retry logic</li>
                                <li>🧪 <strong>Connection Testing</strong> - Validates Stripe and payment service connectivity</li>
                                <li>📋 <strong>Setup Instructions</strong> - Guides for webhook configuration</li>
                            </ul>

                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Required for Revenue:</h6>
                                <p>This script is essential for processing subscription payments and ensuring you get paid!</p>
                            </div>
                        </div>
                    </div>

                    <h3>Option 3: Manual Setup</h3>

                    <p>If you prefer manual setup or need to troubleshoot specific issues:</p>

                    <h4>1. Install WordPress & WooCommerce</h4>

                    <div class="card">
                        <div class="card-body">
                            <h5>Install WordPress (if not already installed):</h5>
                            <pre><code>wp core download
wp core config --dbname=database --dbuser=user --dbpass=password
wp core install --url="https://example.com" --title="Farm Shop" --admin_user=admin --admin_email=admin@example.com</code></pre>

                            <h5>Install and activate WooCommerce:</h5>
                            <pre><code>wp plugin install woocommerce --activate</code></pre>
                        </div>
                    </div>

                    <h4>2. Enable WooCommerce REST API</h4>

                    <div class="card">
                        <div class="card-body">
                            <pre><code>wp option update woocommerce_api_enabled yes</code></pre>
                        </div>
                    </div>

                    <h4>3. Configure Permalinks</h4>

                    <div class="card">
                        <div class="card-body">
                            <pre><code>wp rewrite structure '/%year%/%monthnum%/%day%/%postname%/'
wp rewrite flush</code></pre>
                        </div>
                    </div>

                    <h4>4. Add WooCommerce Capabilities to Admin</h4>

                    <div class="card">
                        <div class="card-body">
                            <pre><code>ADMIN_ID=$(wp user list --role=administrator --field=ID | head -1)
wp user add-cap $ADMIN_ID manage_woocommerce
wp user add-cap $ADMIN_ID edit_shop_orders
wp user add-cap $ADMIN_ID read_shop_orders
wp user add-cap $ADMIN_ID edit_products
wp user add-cap $ADMIN_ID read_products</code></pre>
                        </div>
                    </div>

                    <h4>5. Generate API Keys</h4>

                    <h5>Method A: Via WordPress Admin Panel</h5>
                    <ol>
                        <li>Go to <strong>WooCommerce → Settings → Advanced → REST API</strong></li>
                        <li>Click <strong>"Add key"</strong></li>
                        <li><strong>Description:</strong> <code>MWF Platform API</code></li>
                        <li><strong>User:</strong> Select an administrator user</li>
                        <li><strong>Permissions:</strong> <span class="text-danger"><strong>Read/Write</strong></span> (IMPORTANT!)</li>
                        <li>Click <strong>"Generate API key"</strong></li>
                        <li><strong>Copy</strong> Consumer Key and Consumer Secret immediately</li>
                    </ol>

                    <h5>Method B: Via WP-CLI (Automated)</h5>

                    <div class="card">
                        <div class="card-body">
                            <pre><code># Generate random API keys
CONSUMER_KEY="ck_$(openssl rand -hex 32 | cut -c1-43)"
CONSUMER_SECRET="cs_$(openssl rand -hex 32 | cut -c1-43)"

# Hash the secret for storage
HASHED_SECRET=$(php -r "echo hash('sha256', '$CONSUMER_SECRET');")

# Get admin user ID
ADMIN_ID=$(wp user list --role=administrator --field=ID | head -1)

# Get database table prefix
TABLE_PREFIX=$(wp db prefix)

# Insert API key into database
wp db query "INSERT INTO ${TABLE_PREFIX}woocommerce_api_keys (user_id, description, permissions, consumer_key, consumer_secret, truncated_key) VALUES ($ADMIN_ID, 'MWF Platform API', 'read_write', '$CONSUMER_KEY', '$HASHED_SECRET', '$(echo $CONSUMER_KEY | tail -c 8)')"

echo "Consumer Key: $CONSUMER_KEY"
echo "Consumer Secret: $CONSUMER_SECRET"</code></pre>
                        </div>
                    </div>

                    <h4>6. Laravel Configuration</h4>

                    <p>Add the API credentials to your Laravel <code>.env</code> file:</p>

                    <div class="card">
                        <div class="card-body">
                            <pre><code># WooCommerce API Configuration
WC_CONSUMER_KEY=ck_your_consumer_key_here
WC_CONSUMER_SECRET=cs_your_consumer_secret_here
WC_STORE_URL=https://yourstore.com</code></pre>
                        </div>
                    </div>

                    <h4>💳 Payment Processing Setup</h4>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Critical for Revenue:</strong> Payment processing must be configured correctly for subscription renewals and customer payments to work.
                    </div>

                    <h5>Stripe Configuration (Required)</h5>

                    <div class="card">
                        <div class="card-body">
                            <h6>1. Create Stripe Account</h6>
                            <ol>
                                <li>Go to <a href="https://stripe.com" target="_blank">stripe.com</a> and create an account</li>
                                <li>Complete account verification and enable payments</li>
                                <li>Get your API keys from the Stripe Dashboard</li>
                            </ol>

                            <h6>2. Laravel Environment Configuration</h6>
                            <p>Add to your <code>.env</code> file:</p>
                            <pre><code># Stripe Configuration
STRIPE_KEY=pk_live_your_publishable_key_here
STRIPE_SECRET=sk_live_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# Payment Settings
PAYMENT_CURRENCY=GBP
PAYMENT_METHOD=stripe
SUBSCRIPTION_GRACE_PERIOD_DAYS=7
SUBSCRIPTION_MAX_RETRY_ATTEMPTS=3
SUBSCRIPTION_RETRY_DELAYS="2,4,6"</code></pre>

                            <h6>3. Stripe Webhook Configuration</h6>
                            <p>Set up webhooks in Stripe Dashboard for payment events:</p>
                            <ul>
                                <li><strong>Endpoint URL:</strong> <code>https://yourdomain.com/webhooks/stripe</code></li>
                                <li><strong>Events to listen for:</strong></li>
                                <ul>
                                    <li><code>payment_intent.succeeded</code></li>
                                    <li><code>payment_intent.payment_failed</code></li>
                                    <li><code>invoice.payment_succeeded</code></li>
                                    <li><code>invoice.payment_failed</code></li>
                                    <li><code>customer.subscription.created</code></li>
                                    <li><code>customer.subscription.updated</code></li>
                                    <li><code>customer.subscription.deleted</code></li>
                                </ul>
                            </ul>
                        </div>
                    </div>

                    <h5>MWF Payment Service Configuration</h5>

                    <div class="card">
                        <div class="card-body">
                            <p>If using MWF's payment processing service:</p>
                            <pre><code># MWF Payment Service
MWF_PAYMENT_API_KEY=your_mwf_api_key
MWF_PAYMENT_ENDPOINT=https://api.mwf-platform.com/v1/payments
PAYMENT_METHOD=mwf</code></pre>

                            <p><strong>Note:</strong> Contact MWF support for API credentials if using hosted payment processing.</p>
                        </div>
                    </div>

                    <h5>Subscription Payment Settings</h5>

                    <div class="card">
                        <div class="card-body">
                            <p>Configure subscription renewal behavior:</p>
                            <pre><code># Subscription Management
SUBSCRIPTION_GRACE_PERIOD_DAYS=7
SUBSCRIPTION_MAX_RETRY_ATTEMPTS=3
SUBSCRIPTION_RETRY_DELAYS="2,4,6"
AUTO_RETRY_FAILED_PAYMENTS=true
NOTIFY_CUSTOMERS_ON_PAYMENT_FAILURE=true</code></pre>
                        </div>
                    </div>

                    <h4>8. Test the Connection</h4>

                    <div class="card">
                        <div class="card-body">
                            <h5>Clear Laravel config cache:</h5>
                            <pre><code>php artisan config:clear</code></pre>

                            <h5>Test WooCommerce API connection:</h5>
                            <pre><code>php artisan tinker --execute="
\$api = new \App\Services\WooCommerceApiService();
try {
    \$products = \$api->getProducts(['per_page' => 1]);
    echo '✅ Connection successful! Found ' . count(\$products) . ' products.';
} catch (\Exception \$e) {
    echo '❌ Connection failed: ' . \$e->getMessage();
}
"</code></pre>

                            <h5>Test Stripe Payment Processing:</h5>
                            <pre><code>php artisan tinker --execute="
try {
    \$stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
    \$balance = \$stripe->balance->retrieve();
    echo '✅ Stripe connection successful! Balance available: ' . (\$balance->available[0]->amount ?? 'N/A');
} catch (\Exception \$e) {
    echo '❌ Stripe connection failed: ' . \$e->getMessage();
}
"</code></pre>

                            <h5>Test Vegbox Payment Service:</h5>
                            <pre><code>php artisan tinker --execute="
\$service = app(\App\Services\VegboxPaymentService::class);
try {
    \$status = \$service->testConnection();
    echo '✅ Vegbox Payment Service: ' . \$status;
} catch (\Exception \$e) {
    echo '❌ Vegbox Payment Service failed: ' . \$e->getMessage();
}
"</code></pre>
                        </div>
                    </div>

                    <h4>9. Activate Required Plugins</h4>

                    <div class="card">
                        <div class="card-body">
                            <pre><code># Activate MWF-specific plugins
wp plugin activate mwf-subscriptions
wp plugin activate mwf-solidarity-pricing
wp plugin activate mwf-shipping-method
wp plugin activate mwf-blocks-integration

# Clear all caches
wp cache flush
wp transient delete-all</code></pre>
                        </div>
                    </div>

                    <h4>10. Sync Products & Data</h4>

                    <div class="card">
                        <div class="card-body">
                            <h5>Sync all products from WooCommerce:</h5>
                            <pre><code>php artisan woocommerce:sync-products</code></pre>

                            <h5>Sync variable products specifically:</h5>
                            <pre><code>php artisan woocommerce:sync-variable-products</code></pre>

                            <h5>Import existing subscriptions:</h5>
                            <pre><code>php artisan subscriptions:import-from-woocommerce</code></pre>

                            <h5>Test subscription payment processing:</h5>
                            <pre><code>php artisan vegbox:test-payment-processing</code></pre>
                        </div>
                    </div>

                    <h2>🔧 Troubleshooting</h2>

                    <h3>Common Issues & Solutions</h3>

                    <div class="accordion" id="troubleshootingAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#api-permissions">
                                    API Key Permissions Issue
                                </button>
                            </h2>
                            <div id="api-permissions" class="accordion-collapse collapse show" data-bs-parent="#troubleshootingAccordion">
                                <div class="accordion-body">
                                    <p><strong>Symptom:</strong> API calls fail with permission errors</p>
                                    <p><strong>Solution:</strong> Ensure API key has "Read/Write" permissions, not just "Read"</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#permalink-issue">
                                    Permalink Issues
                                </button>
                            </h2>
                            <div id="permalink-issue" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                <div class="accordion-body">
                                    <p><strong>Symptom:</strong> API endpoints return 404 errors</p>
                                    <p><strong>Solution:</strong> Ensure permalinks are set to "Post name" or custom structure</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment-failures">
                                    Payment Processing Issues
                                </button>
                            </h2>
                            <div id="payment-failures" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                <div class="accordion-body">
                                    <p><strong>Symptom:</strong> Subscription renewals failing or payments not processing</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ul>
                                        <li>Verify Stripe API keys are correct and have proper permissions</li>
                                        <li>Check webhook endpoint is accessible and webhook secret matches</li>
                                        <li>Ensure SSL certificate is valid for webhook delivery</li>
                                        <li>Test payment processing with <code>php artisan vegbox:test-payment-processing</code></li>
                                        <li>Check Stripe dashboard for failed payment events</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#subscription-sync">
                                    Subscription Synchronization Issues
                                </button>
                            </h2>
                            <div id="subscription-sync" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                <div class="accordion-body">
                                    <p><strong>Symptom:</strong> Subscriptions not appearing in admin or payments failing</p>
                                    <p><strong>Solutions:</strong></p>
                                    <ul>
                                        <li>Run <code>php artisan vegbox:import-woo-subscriptions</code> to sync WooCommerce subscriptions</li>
                                        <li>Check VegboxSubscription table for orphaned records</li>
                                        <li>Verify payment service configuration matches subscription settings</li>
                                        <li>Clear all caches: <code>php artisan config:clear && php artisan cache:clear</code></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2>📞 Support</h2>

                    <p>If you encounter issues during setup:</p>
                    <ul>
                        <li><strong>📖 Documentation:</strong> Check the <a href="{{ route('admin.docs.page', 'subscription-management') }}">Subscription Management Guide</a></li>
                        <li><strong>🆘 Support:</strong> Contact support with your setup logs</li>
                        <li><strong>👥 Community:</strong> Join our community forum for peer support</li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Auth\LoginController;
use App\Services\DeliveryScheduleService;
use Illuminate\Support\Facades\Route;

// AI Helper API routes (outside admin prefix to avoid nginx routing conflicts)
Route::middleware(['admin.auth'])->post('/ai-helper/contextual-help', [App\Http\Controllers\AIController::class, 'contextualHelp'])->name('admin.ai.contextual-help');

// Public routes (no authentication required)
Route::get('/', function () {
    return redirect('/admin');
});

// Authentication routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('admin.login.form');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');
});

// Protected admin routes (require authentication)
Route::middleware(['admin.auth'])->prefix('admin')->group(function () {
    
    // AI Helper API routes
    Route::post('/help/ai-helper/contextual-help', [App\Http\Controllers\AIController::class, 'contextualHelp'])->name('admin.ai.contextual-help');
    
    // Admin dashboard route
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Documentation routes
    Route::get('/docs/user-manual', function () {
        return view('docs.user-manual.index');
    })->name('admin.docs.user-manual');
    
    Route::get('/docs/user-manual/{page}', function ($page) {
        $validPages = [
            'subscription-management',
            'delivery-management',
            'succession-planning',
            'task-system',
            'crm-usage',
            'user-management',
            'pos-integration'
        ];
        
        if (in_array($page, $validPages)) {
            return view('docs.user-manual.' . $page);
        }
        abort(404);
    })->name('admin.docs.page');

    // Delivery management routes
    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('admin.deliveries.index');
    Route::get('/diagnostic-subscriptions', [DeliveryController::class, 'diagnosticSubscriptions'])->name('admin.diagnostic-subscriptions');
    Route::post('/customers/update-week', [DeliveryController::class, 'updateCustomerWeek'])->name('admin.customers.update-week');

    // Customer management routes
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UserSwitchingController::class, 'index'])->name('index');
        Route::get('/search', [App\Http\Controllers\Admin\UserSwitchingController::class, 'search'])->name('search');
        Route::get('/recent', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getRecentUsers'])->name('recent');
        Route::post('/switch/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchToUser'])->name('switch');
        Route::get('/switch-redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchAndRedirect'])->name('switch-redirect');
        Route::post('/switch-by-email', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchByEmail'])->name('switch-by-email');
        Route::get('/details/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getUserDetails'])->name('details');
        Route::get('/redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'redirect'])->name('redirect');
    });

    // Analytics and Reports routes (placeholders for future implementation)
    Route::get('/reports', function () {
        return view('admin.placeholder', ['title' => 'Reports', 'description' => 'Delivery and sales reports coming soon']);
    })->name('admin.reports');

    Route::get('/analytics', function () {
        return view('admin.placeholder', ['title' => 'Analytics', 'description' => 'Advanced analytics dashboard coming soon']);
    })->name('admin.analytics');

    // System routes (placeholders for future implementation)
    Route::get('/settings', function () {
        return view('admin.placeholder', ['title' => 'Settings', 'description' => 'System configuration coming soon']);
    })->name('admin.settings');

    Route::get('/logs', function () {
        return view('admin.placeholder', ['title' => 'System Logs', 'description' => 'Activity logs and debugging coming soon']);
    })->name('admin.logs');

    // Simple test route
    Route::get('/test', function () {
        return 'Test route works!';
    });

    // Route planning and optimization routes
    Route::prefix('routes')->name('admin.routes.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RouteController::class, 'index'])->name('index');
        Route::post('/optimize', [App\Http\Controllers\Admin\RouteController::class, 'optimize'])->name('optimize');
        Route::post('/send-to-driver', [App\Http\Controllers\Admin\RouteController::class, 'sendToDriver'])->name('send-to-driver');
        Route::post('/send-to-driver-sms', [App\Http\Controllers\Admin\RouteController::class, 'sendToDriverSMS'])->name('send-to-driver-sms');
        Route::get('/map-data', [App\Http\Controllers\Admin\RouteController::class, 'getMapData'])->name('map-data');
        Route::post('/create-shareable-map', [App\Http\Controllers\Admin\RouteController::class, 'createShareableMap'])->name('create-shareable-map');
        Route::get('/wp-go-maps-data', [App\Http\Controllers\Admin\RouteController::class, 'getWPGoMapsData'])->name('wp-go-maps-data');
    });
});

// Stripe webhook (must be public for Stripe to access)
Route::post('/webhooks/stripe', [App\Http\Controllers\PaymentController::class, 'handleWebhook'])
    ->name('webhooks.stripe')
    ->middleware('throttle:60,1'); // 60 requests per minute

Route::post('/webhooks/stripe-orders', [App\Http\Controllers\WebhookController::class, 'handleStripe'])
    ->name('webhooks.stripe-orders')
    ->middleware('throttle:60,1') // 60 requests per minute
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Protected admin routes (require authentication)
Route::middleware(['admin.auth'])->prefix('admin')->group(function () {
    
    // Admin dashboard route
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Weekly planting recommendations
    Route::get('/planting-recommendations', [DashboardController::class, 'plantingRecommendations'])->name('admin.planting-recommendations');

    // AI data catalog
    Route::get('/api/data-catalog', [DashboardController::class, 'dataCatalog'])->name('admin.api.data-catalog');

    // FarmOS map data endpoint
    Route::get('/farmos-map-data', [DashboardController::class, 'farmosMapData'])->name('admin.farmos-map-data');

    // Delivery management routes
    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('admin.deliveries.index');
    Route::get('/deliveries/data', [DeliveryController::class, 'getDeliveriesData'])->name('admin.deliveries.data');
    Route::get('/diagnostic-subscriptions', [DeliveryController::class, 'diagnosticSubscriptions'])->name('admin.diagnostic-subscriptions');
    
    // DEBUG: Shipping totals analysis
    Route::get('/debug-shipping-totals', [DeliveryController::class, 'debugShippingTotals'])->name('admin.debug-shipping-totals');
    
    // DEBUG: Specific customer analysis
    Route::get('/debug-customer/{email}', [DeliveryController::class, 'debugSpecificCustomer'])->name('admin.debug-customer');
    
    // DEBUG: WooCommerce subscription structure analysis
    Route::get('/debug-subscription-structure', [DeliveryController::class, 'debugSubscriptionStructure'])->name('admin.debug-subscription-structure');
    
    // Debug endpoint for specific customer analysis
    Route::get('/debug-bethany', [DeliveryController::class, 'debugBethany'])->name('debug.bethany');
    
    Route::post('/customers/update-week', [DeliveryController::class, 'updateCustomerWeek'])->name('admin.customers.update-week');

    // Customer management routes
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UserSwitchingController::class, 'index'])->name('index');
        Route::get('/search', [App\Http\Controllers\Admin\UserSwitchingController::class, 'search'])->name('search');
        Route::get('/recent', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getRecentUsers'])->name('recent');
        Route::get('/test', [App\Http\Controllers\Admin\UserSwitchingController::class, 'test'])->name('test');
        Route::post('/switch/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchToUser'])->name('switch');
        Route::get('/switch-redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchAndRedirect'])->name('switch-redirect');
        Route::post('/switch-by-email', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchByEmail'])->name('switch-by-email');
        Route::post('/get-subscription-url', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getSubscriptionUrl'])->name('get-subscription-url');
        Route::get('/subscription-redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'subscriptionRedirect'])->name('subscription-redirect');
        Route::get('/details/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getUserDetails'])->name('details');
        Route::get('/redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'redirect'])->name('redirect');
    });

    // Customer Management routes (actual customer management, not user switching)
    Route::prefix('customers')->name('admin.customers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\CustomerManagementController::class, 'index'])->name('index');
        Route::post('/switch/{userId}', [App\Http\Controllers\Admin\CustomerManagementController::class, 'switchToUser'])->name('switch');
        Route::get('/details/{userId}', [App\Http\Controllers\Admin\CustomerManagementController::class, 'details'])->name('details');
        Route::post('/sms-campaign', [App\Http\Controllers\Admin\CustomerManagementController::class, 'sendSMSCampaign'])->name('sms-campaign');
        Route::get('/sms-stats', [App\Http\Controllers\Admin\CustomerManagementController::class, 'getSMSCampaignStats'])->name('sms-stats');
    });

    // Vegbox Subscription Management routes
    Route::prefix('vegbox-subscriptions')->name('admin.vegbox-subscriptions.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\VegboxSubscriptionController::class, 'index'])->name('index');
        Route::get('/upcoming-renewals', [App\Http\Controllers\Admin\VegboxSubscriptionController::class, 'upcomingRenewals'])->name('upcoming-renewals');
        Route::get('/failed-payments', [App\Http\Controllers\Admin\VegboxSubscriptionController::class, 'failedPayments'])->name('failed-payments');
        Route::get('/{id}', [App\Http\Controllers\Admin\VegboxSubscriptionController::class, 'show'])->name('show');
        Route::post('/{id}/manual-renewal', [App\Http\Controllers\Admin\VegboxSubscriptionController::class, 'manualRenewal'])->name('manual-renewal');
        Route::post('/{id}/cancel', [App\Http\Controllers\Admin\VegboxSubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/reactivate', [App\Http\Controllers\Admin\VegboxSubscriptionController::class, 'reactivate'])->name('reactivate');
        Route::post('/{id}/change-plan', [App\Http\Controllers\Admin\VegboxSubscriptionController::class, 'changePlan'])->name('change-plan');
    });

    // Payment Method Management routes
    Route::prefix('users/{userId}/payment-methods')->name('admin.payment-methods.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PaymentMethodController::class, 'index'])->name('index');
        Route::post('/setup-intent', [App\Http\Controllers\Admin\PaymentMethodController::class, 'setupIntent'])->name('setup-intent');
        Route::post('/', [App\Http\Controllers\Admin\PaymentMethodController::class, 'store'])->name('store');
        Route::post('/{paymentMethodId}/set-default', [App\Http\Controllers\Admin\PaymentMethodController::class, 'setDefault'])->name('set-default');
        Route::delete('/{paymentMethodId}', [App\Http\Controllers\Admin\PaymentMethodController::class, 'destroy'])->name('destroy');
    });

    // Bank Transaction & Accounting routes
    Route::prefix('bank-transactions')->name('admin.bank-transactions.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\BankTransactionController::class, 'index'])->name('index');
        Route::get('/import', [App\Http\Controllers\Admin\BankTransactionController::class, 'importForm'])->name('import-form');
        Route::post('/import', [App\Http\Controllers\Admin\BankTransactionController::class, 'import'])->name('import');
        Route::get('/dashboard', [App\Http\Controllers\Admin\BankTransactionController::class, 'dashboard'])->name('dashboard');
        Route::get('/month-details', [App\Http\Controllers\Admin\BankTransactionController::class, 'monthDetails'])->name('month-details');
        Route::post('/{transaction}/category', [App\Http\Controllers\Admin\BankTransactionController::class, 'updateCategory'])->name('update-category');
    });

    // Update Tracking routes
    Route::prefix('updates')->name('admin.updates.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UpdateTrackingController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\UpdateTrackingController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\UpdateTrackingController::class, 'store'])->name('store');
        Route::get('/{update}', [App\Http\Controllers\Admin\UpdateTrackingController::class, 'show'])->name('show');
        Route::get('/{update}/script', [App\Http\Controllers\Admin\UpdateTrackingController::class, 'generateScript'])->name('script');
    });

    // Open Banking routes
    Route::prefix('openbanking')->name('admin.openbanking.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\OpenBankingController::class, 'index'])->name('index');
        Route::post('/register', [App\Http\Controllers\Admin\OpenBankingController::class, 'register'])->name('register');
        Route::get('/{connection}/authorize', [App\Http\Controllers\Admin\OpenBankingController::class, 'authorize'])->name('authorize');
        Route::get('/callback', [App\Http\Controllers\Admin\OpenBankingController::class, 'callback'])->name('callback');
        Route::post('/{connection}/sync-accounts', [App\Http\Controllers\Admin\OpenBankingController::class, 'syncAccounts'])->name('sync-accounts');
        Route::post('/{account}/sync-transactions', [App\Http\Controllers\Admin\OpenBankingController::class, 'syncTransactions'])->name('sync-transactions');
        Route::get('/{account}/details', [App\Http\Controllers\Admin\OpenBankingController::class, 'showAccount'])->name('account-details');
        Route::post('/{connection}/disconnect', [App\Http\Controllers\Admin\OpenBankingController::class, 'disconnect'])->name('disconnect');
        Route::post('/{connection}/refresh-token', [App\Http\Controllers\Admin\OpenBankingController::class, 'refreshToken'])->name('refresh-token');
    });

    // Analytics and Reports routes
    Route::get('/reports', [App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('admin.reports');
    Route::get('/reports/export', [App\Http\Controllers\Admin\ReportsController::class, 'export'])->name('admin.reports.export');
    
    Route::get('/analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('admin.analytics');
    Route::get('/analytics/realtime', [App\Http\Controllers\Admin\AnalyticsController::class, 'realtime'])->name('admin.analytics.realtime');

    // Unified Backup routes
    Route::get('/unified-backup', [App\Http\Controllers\Admin\UnifiedBackupController::class, 'index'])->name('admin.unified-backup');
    Route::post('/unified-backup/run', [App\Http\Controllers\Admin\UnifiedBackupController::class, 'run'])->name('admin.unified-backup.run');
    Route::get('/unified-backup/status', [App\Http\Controllers\Admin\UnifiedBackupController::class, 'status'])->name('admin.unified-backup.status');
    Route::get('/unified-backup/files', [App\Http\Controllers\Admin\UnifiedBackupController::class, 'files'])->name('admin.unified-backup.files');
    Route::get('/unified-backup/download/{filename}', [App\Http\Controllers\Admin\UnifiedBackupController::class, 'download'])->name('admin.unified-backup.download');
    Route::post('/unified-backup/delete', [App\Http\Controllers\Admin\UnifiedBackupController::class, 'delete'])->name('admin.unified-backup.delete');

    // System routes
    Route::get('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/settings/reset', [App\Http\Controllers\Admin\SettingsController::class, 'reset'])->name('admin.settings.reset');
    Route::get('/settings/api', [App\Http\Controllers\Admin\SettingsController::class, 'api'])->name('admin.settings.api');
    
    // Server monitoring routes for IONOS I/O throttling detection
    Route::get('/settings/server-metrics', [App\Http\Controllers\Admin\SettingsController::class, 'serverMetrics'])->name('admin.settings.server-metrics');
    Route::post('/settings/test-io-speed', [App\Http\Controllers\Admin\SettingsController::class, 'testIOSpeed'])->name('admin.settings.test-io-speed');
    Route::post('/settings/test-db-performance', [App\Http\Controllers\Admin\SettingsController::class, 'testDatabasePerformance'])->name('admin.settings.test-db-performance');

    // Email Client routes
    Route::prefix('email')->name('admin.email.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\EmailClientController::class, 'index'])->name('index');
        Route::get('/compose', [App\Http\Controllers\Admin\EmailClientController::class, 'compose'])->name('compose');
        Route::post('/send', [App\Http\Controllers\Admin\EmailClientController::class, 'send'])->name('send');
        Route::get('/show/{id}', [App\Http\Controllers\Admin\EmailClientController::class, 'show'])->name('show');
        Route::post('/sync', [App\Http\Controllers\Admin\EmailClientController::class, 'sync'])->name('sync');
        Route::post('/move', [App\Http\Controllers\Admin\EmailClientController::class, 'moveToFolder'])->name('move');
        Route::post('/delete', [App\Http\Controllers\Admin\EmailClientController::class, 'delete'])->name('delete');
        Route::post('/mark-unread', [App\Http\Controllers\Admin\EmailClientController::class, 'markAsUnread'])->name('mark-unread');
        Route::post('/toggle-flag', [App\Http\Controllers\Admin\EmailClientController::class, 'toggleFlag'])->name('toggle-flag');
        Route::get('/search', [App\Http\Controllers\Admin\EmailClientController::class, 'search'])->name('search');

        // Signature management routes
        Route::get('/signatures', [App\Http\Controllers\Admin\EmailClientController::class, 'signatures'])->name('signatures');
        Route::get('/signatures/create', [App\Http\Controllers\Admin\EmailClientController::class, 'createSignature'])->name('create-signature');
        Route::post('/signatures', [App\Http\Controllers\Admin\EmailClientController::class, 'storeSignature'])->name('store-signature');
        Route::get('/signatures/{id}/edit', [App\Http\Controllers\Admin\EmailClientController::class, 'editSignature'])->name('edit-signature');
        Route::put('/signatures/{id}', [App\Http\Controllers\Admin\EmailClientController::class, 'updateSignature'])->name('update-signature');
        Route::delete('/signatures/{id}', [App\Http\Controllers\Admin\EmailClientController::class, 'deleteSignature'])->name('delete-signature');

        // Folder management routes
        Route::get('/folders', [App\Http\Controllers\Admin\EmailClientController::class, 'folders'])->name('folders');
        Route::post('/folders', [App\Http\Controllers\Admin\EmailClientController::class, 'createFolder'])->name('create-folder');
        Route::put('/folders/{id}', [App\Http\Controllers\Admin\EmailClientController::class, 'updateFolder'])->name('update-folder');
        Route::delete('/folders/{id}', [App\Http\Controllers\Admin\EmailClientController::class, 'deleteFolder'])->name('delete-folder');

        // Account management routes
        Route::get('/accounts', [App\Http\Controllers\Admin\EmailClientController::class, 'accounts'])->name('accounts');
        Route::get('/accounts/create', [App\Http\Controllers\Admin\EmailClientController::class, 'createAccount'])->name('create-account');
        Route::post('/accounts', [App\Http\Controllers\Admin\EmailClientController::class, 'storeAccount'])->name('store-account');
        Route::get('/accounts/{id}/edit', [App\Http\Controllers\Admin\EmailClientController::class, 'editAccount'])->name('edit-account');
        Route::put('/accounts/{id}', [App\Http\Controllers\Admin\EmailClientController::class, 'updateAccount'])->name('update-account');
        Route::delete('/accounts/{id}', [App\Http\Controllers\Admin\EmailClientController::class, 'deleteAccount'])->name('delete-account');
        Route::post('/accounts/{id}/test', [App\Http\Controllers\Admin\EmailClientController::class, 'testAccountConnection'])->name('test-account');
    });

    // RAG document management routes
    Route::post('/rag/upload', [App\Http\Controllers\Admin\SettingsController::class, 'ragUpload'])->name('admin.rag.upload');
    Route::get('/rag/documents', [App\Http\Controllers\Admin\SettingsController::class, 'ragDocuments'])->name('admin.rag.documents');

    // CRM / 3CX Integration routes
    Route::get('/crm/contact', [App\Http\Controllers\Admin\CrmController::class, 'contact'])->name('admin.crm.contact');
    Route::post('/crm/add-note', [App\Http\Controllers\Admin\CrmController::class, 'addNote'])->name('admin.crm.addNote');

    // Product Management routes
    Route::prefix('products')->name('admin.products.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\ProductController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\ProductController::class, 'store'])->name('store');
        
        // Specific routes must come before generic {product} routes
        Route::get('/test-woocommerce-connection', [App\Http\Controllers\Admin\ProductController::class, 'testWooCommerceConnection'])->name('test-woocommerce-connection');
        Route::get('/export/csv', [App\Http\Controllers\Admin\ProductController::class, 'exportCsv'])->name('export.csv');
        Route::post('/import/csv', [App\Http\Controllers\Admin\ProductController::class, 'importCsv'])->name('import.csv');
        Route::get('/iframe-dashboard', [App\Http\Controllers\Admin\ProductController::class, 'iframeDashboard'])->name('iframe-dashboard');
        Route::post('/sync-all-woocommerce', [App\Http\Controllers\Admin\ProductController::class, 'syncAllWithWooCommerce'])->name('sync-all-woocommerce');
        Route::post('/bulk-sync-woocommerce', [App\Http\Controllers\Admin\ProductController::class, 'bulkSyncWithWooCommerce'])->name('bulk-sync-woocommerce');
        Route::post('/fetch-all-woocommerce', [App\Http\Controllers\Admin\ProductController::class, 'fetchAllFromWooCommerce'])->name('fetch-all-woocommerce');
        Route::post('/bulk-fetch-woocommerce', [App\Http\Controllers\Admin\ProductController::class, 'bulkFetchFromWooCommerce'])->name('bulk-fetch-woocommerce');
        Route::post('/fetch-woocommerce', [App\Http\Controllers\Admin\ProductController::class, 'fetchFromWooCommerce'])->name('fetch-woocommerce');
        
        // Generic {product} routes come after specific routes
        Route::get('/{product}', [App\Http\Controllers\Admin\ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [App\Http\Controllers\Admin\ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('destroy');
        
        // WooCommerce Integration routes
        Route::post('/{product}/sync-woocommerce', [App\Http\Controllers\Admin\ProductController::class, 'syncWithWooCommerce'])->name('sync-woocommerce');
        Route::post('/{product}/unlink-woocommerce', [App\Http\Controllers\Admin\ProductController::class, 'unlinkFromWooCommerce'])->name('unlink-woocommerce');
        Route::get('/{product}/get-woocommerce', [App\Http\Controllers\Admin\ProductController::class, 'getWooCommerceProduct'])->name('get-woocommerce');
        Route::post('/{product}/toggle-active', [App\Http\Controllers\Admin\ProductController::class, 'toggleActive'])->name('toggle-active');
        
        // WooCommerce iframe routes
        Route::get('/{product}/iframe-edit', [App\Http\Controllers\Admin\ProductController::class, 'iframeEdit'])->name('iframe-edit');
        Route::get('/{product}/api-edit', [App\Http\Controllers\Admin\ProductController::class, 'apiEdit'])->name('api-edit');
        Route::get('/{product}/woo-id', [App\Http\Controllers\Admin\ProductController::class, 'getWooProductId'])->name('get-woo-id');
        Route::get('/{product}/variations', [App\Http\Controllers\Admin\ProductController::class, 'variations'])->name('variations');
        
        // AI-powered content generation
        Route::post('/{product}/generate-seo', [App\Http\Controllers\Admin\ProductController::class, 'generateSeoSuggestions'])->name('generate-seo');
        Route::post('/{product}/generate-description', [App\Http\Controllers\Admin\ProductController::class, 'generateDescription'])->name('generate-description');
        Route::post('/{product}/generate-tags', [App\Http\Controllers\Admin\ProductController::class, 'generateTags'])->name('generate-tags');
        
        Route::post('/{product}/generate-short-description', [App\Http\Controllers\Admin\ProductController::class, 'generateShortDescription'])->name('generate-short-description');
        
        // AI Tags generation
        Route::post('/{product}/generate-tags', [App\Http\Controllers\Admin\ProductController::class, 'generateTags'])->name('generate-tags');
        
        // Product Variations nested routes
        Route::prefix('/{product}/variations')->name('variations.')->group(function () {
            Route::get('/create', [App\Http\Controllers\Admin\ProductVariationController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\ProductVariationController::class, 'store'])->name('store');
            Route::get('/{variation}/edit', [App\Http\Controllers\Admin\ProductVariationController::class, 'edit'])->name('edit');
            Route::put('/{variation}', [App\Http\Controllers\Admin\ProductVariationController::class, 'update'])->name('update');
            Route::delete('/{variation}', [App\Http\Controllers\Admin\ProductVariationController::class, 'destroy'])->name('destroy');
        });
        
        // MWF Integration API routes (for enhanced WooCommerce features)
        Route::prefix('mwf-integration')->name('mwf-integration.')->group(function () {
            Route::get('/products/{wooProductId}/edit', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'getProductForEdit'])->name('product.edit');
            Route::put('/products/{wooProductId}', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'updateProduct'])->name('product.update');
            Route::get('/products/{wooProductId}/variations', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'getProductVariations'])->name('product.variations');
            Route::post('/products/{wooProductId}/variations', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'createVariation'])->name('variation.create');
            Route::put('/products/variations/{variationId}', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'updateVariation'])->name('variation.update');
            Route::delete('/products/variations/{variationId}', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'deleteVariation'])->name('variation.delete');
            Route::get('/products/{wooProductId}/attributes', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'getProductAttributes'])->name('product.attributes');
            Route::post('/products/{wooProductId}/attributes', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'updateProductAttributes'])->name('product.attributes.update');
            Route::post('/products/bulk-update', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'bulkUpdate'])->name('products.bulk-update');
            Route::post('/products/variations/bulk-update', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'bulkUpdateVariations'])->name('variations.bulk-update');
            Route::get('/capabilities', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'getCapabilities'])->name('capabilities');
            Route::post('/actions', [App\Http\Controllers\Admin\WooCommerceIntegrationController::class, 'executeAction'])->name('actions');
        });
    });

    // Variety Audit routes
    Route::post('/variety-audit/{id}/approve', [App\Http\Controllers\Admin\SettingsController::class, 'approveAudit']);
    Route::post('/variety-audit/{id}/reject', [App\Http\Controllers\Admin\SettingsController::class, 'rejectAudit']);
    Route::post('/variety-audit/{id}/update-suggestion', [App\Http\Controllers\Admin\SettingsController::class, 'updateSuggestion']);
    Route::post('/variety-audit/bulk-approve', [App\Http\Controllers\Admin\SettingsController::class, 'bulkApproveAudit']);
    Route::post('/variety-audit/bulk-reject', [App\Http\Controllers\Admin\SettingsController::class, 'bulkRejectAudit']);
    Route::post('/variety-audit/approve-high-confidence', [App\Http\Controllers\Admin\SettingsController::class, 'approveHighConfidence']);
    Route::post('/variety-audit/apply-approved', [App\Http\Controllers\Admin\SettingsController::class, 'applyApprovedAudit']);
    Route::get('/variety-audit/stats', [App\Http\Controllers\Admin\SettingsController::class, 'auditStats']);
    Route::get('/variety-audit/status', [App\Http\Controllers\Admin\SettingsController::class, 'auditStatus']);
    Route::post('/variety-audit/start', [App\Http\Controllers\Admin\SettingsController::class, 'auditStart']);
    Route::post('/variety-audit/pause', [App\Http\Controllers\Admin\SettingsController::class, 'auditPause']);
    Route::post('/variety-audit/resume', [App\Http\Controllers\Admin\SettingsController::class, 'auditResume']);

    // RAG Ingestion routes
    Route::get('/rag-ingestion/status', [App\Http\Controllers\Admin\SettingsController::class, 'ragStatus']);
    Route::post('/rag-ingestion/start', [App\Http\Controllers\Admin\SettingsController::class, 'ragStart']);
    Route::post('/rag-ingestion/upload', [App\Http\Controllers\Admin\SettingsController::class, 'ragUpload']);
    Route::post('/rag-ingestion/stop', [App\Http\Controllers\Admin\SettingsController::class, 'ragStop']);
    Route::post('/rag-ingestion/start-queue', [App\Http\Controllers\Admin\SettingsController::class, 'startQueueProcessing']);
    Route::post('/rag-ingestion/add-to-queue', [App\Http\Controllers\Admin\SettingsController::class, 'addToQueue']);
    Route::post('/rag-ingestion/pause-queue', [App\Http\Controllers\Admin\SettingsController::class, 'pauseQueueProcessing']);
    Route::post('/rag-ingestion/resume-queue', [App\Http\Controllers\Admin\SettingsController::class, 'resumeQueueProcessing']);

    // CSV Dataset Import API routes
    Route::get('/api/datasets', [App\Http\Controllers\Admin\SettingsController::class, 'getDatasets'])->name('admin.api.datasets');
    Route::delete('/api/datasets/{tableName}', [App\Http\Controllers\Admin\SettingsController::class, 'deleteDataset'])->name('admin.api.datasets.delete');

    Route::get('/logs', function () {
        return view('admin.placeholder', ['title' => 'System Logs', 'description' => 'Activity logs and debugging coming soon']);
    })->name('admin.logs');

    // Task Management routes
    Route::prefix('tasks')->name('admin.tasks.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TaskController::class, 'index'])->name('index');
        Route::get('/kanban', [App\Http\Controllers\Admin\TaskController::class, 'kanban'])->name('kanban');
        Route::get('/create', [App\Http\Controllers\Admin\TaskController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\TaskController::class, 'store'])->name('store');
        Route::get('/{task}', [App\Http\Controllers\Admin\TaskController::class, 'show'])->name('show');
        Route::get('/{task}/edit', [App\Http\Controllers\Admin\TaskController::class, 'edit'])->name('edit');
        Route::put('/{task}', [App\Http\Controllers\Admin\TaskController::class, 'update'])->name('update');
        Route::delete('/{task}', [App\Http\Controllers\Admin\TaskController::class, 'destroy'])->name('destroy');
        Route::patch('/{task}/status', [App\Http\Controllers\Admin\TaskController::class, 'updateStatus'])->name('update-status');
        Route::post('/{task}/comments', [App\Http\Controllers\Admin\TaskController::class, 'addComment'])->name('add-comment');
        Route::post('/{task}/attachments', [App\Http\Controllers\Admin\TaskController::class, 'uploadAttachment'])->name('upload-attachment');
        Route::delete('/{task}/attachments/{attachment}', [App\Http\Controllers\Admin\TaskController::class, 'deleteAttachment'])->name('delete-attachment');
    });

    // Notes Management routes
    Route::prefix('notes')->name('admin.notes.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\NoteController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\NoteController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\NoteController::class, 'store'])->name('store');
        Route::get('/{note}', [App\Http\Controllers\Admin\NoteController::class, 'show'])->name('show');
        Route::get('/{note}/edit', [App\Http\Controllers\Admin\NoteController::class, 'edit'])->name('edit');
        Route::put('/{note}', [App\Http\Controllers\Admin\NoteController::class, 'update'])->name('update');
        Route::delete('/{note}', [App\Http\Controllers\Admin\NoteController::class, 'destroy'])->name('destroy');
        Route::post('/{note}/toggle-pin', [App\Http\Controllers\Admin\NoteController::class, 'togglePin'])->name('toggle-pin');
    });

    // Companies House Management routes
    Route::prefix('companies-house')->name('admin.companies-house.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\CompaniesHouseController::class, 'index'])->name('index');
        Route::get('/confirmation-statement', [App\Http\Controllers\Admin\CompaniesHouseController::class, 'confirmationStatementHelper'])->name('confirmation-helper');
        Route::get('/accounts', [App\Http\Controllers\Admin\CompaniesHouseController::class, 'accountsHelper'])->name('accounts-helper');
        Route::post('/accounts/generate', [App\Http\Controllers\Admin\BankTransactionController::class, 'generateAccountsPackage'])->name('generate-accounts');
    });

    // Admin User Management routes
    Route::prefix('admin-users')->name('admin.admin-users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\AdminUserController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('store');
        Route::get('/{index}/edit', [App\Http\Controllers\Admin\AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{index}', [App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('update');
        Route::delete('/{index}', [App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('destroy');
    });

    // Order Management routes
    Route::prefix('orders')->name('admin.orders.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('index');
        Route::get('/prefetch', [App\Http\Controllers\Admin\OrderController::class, 'prefetchOrders'])->name('prefetch');
        Route::get('/export', [App\Http\Controllers\Admin\OrderController::class, 'export'])->name('export');
        Route::post('/bulk-action', [App\Http\Controllers\Admin\OrderController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/search-customers', [App\Http\Controllers\Admin\OrderController::class, 'searchCustomers'])->name('search-customers');
        Route::get('/{id}', [App\Http\Controllers\Admin\OrderController::class, 'show'])->name('show');
        Route::get('/{id}/duplicate', [App\Http\Controllers\Admin\OrderController::class, 'duplicate'])->name('duplicate');
        Route::get('/{id}/invoice', [App\Http\Controllers\Admin\OrderController::class, 'downloadInvoice'])->name('download-invoice');
        Route::get('/{id}/packing-slip', [App\Http\Controllers\Admin\OrderController::class, 'downloadPackingSlip'])->name('download-packing-slip');
        Route::patch('/{id}/status', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/notes', [App\Http\Controllers\Admin\OrderController::class, 'addNote'])->name('add-note');
        Route::post('/{id}/refund', [App\Http\Controllers\Admin\OrderController::class, 'refund'])->name('refund');
        Route::post('/laravel/{id}/refund', [App\Http\Controllers\Admin\OrderController::class, 'refundLaravel'])->name('refund-laravel');
    });

    // Shipping Classes Management routes
    Route::prefix('shipping-classes')->name('admin.shipping-classes.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ShippingClassController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\ShippingClassController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\ShippingClassController::class, 'store'])->name('store');
        Route::get('/{shippingClass}', [App\Http\Controllers\Admin\ShippingClassController::class, 'show'])->name('show');
        Route::get('/{shippingClass}/edit', [App\Http\Controllers\Admin\ShippingClassController::class, 'edit'])->name('edit');
        Route::put('/{shippingClass}', [App\Http\Controllers\Admin\ShippingClassController::class, 'update'])->name('update');
        Route::delete('/{shippingClass}', [App\Http\Controllers\Admin\ShippingClassController::class, 'destroy'])->name('destroy');
    });

    // Product Attributes Management routes
    Route::prefix('product-attributes')->name('admin.product-attributes.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ProductAttributeController::class, 'index'])->name('index');
        Route::get('/api/list', [App\Http\Controllers\Admin\ProductAttributeController::class, 'apiList'])->name('api.list');
        Route::get('/create', [App\Http\Controllers\Admin\ProductAttributeController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\ProductAttributeController::class, 'store'])->name('store');
        Route::get('/{attribute}', [App\Http\Controllers\Admin\ProductAttributeController::class, 'show'])->name('show');
        Route::get('/{attribute}/edit', [App\Http\Controllers\Admin\ProductAttributeController::class, 'edit'])->name('edit');
        Route::put('/{attribute}', [App\Http\Controllers\Admin\ProductAttributeController::class, 'update'])->name('update');
        Route::delete('/{attribute}', [App\Http\Controllers\Admin\ProductAttributeController::class, 'destroy'])->name('destroy');
    });

    // Chatbot settings page
    Route::get('/chatbot-settings', function () {
        // Check AI service status
        $aiStatus = ['status' => (@file_get_contents('http://localhost:8005/health') !== false) ? 'online' : 'offline'];
        $knowledgeStatus = ['status' => 'unavailable']; // Default status, update as needed
        return view('admin.chatbot-settings', compact('aiStatus', 'knowledgeStatus'));
    })->name('admin.chatbot-settings');

    // Chatbot API endpoint
    Route::post('/chatbot-api', function (\Illuminate\Http\Request $request) {
        try {
            $message = $request->input('message');
            
            // Get current date/season context
            $now = now();
            $currentDate = $now->format('F j, Y'); // e.g., "October 25, 2025"
            $month = (int) $now->format('n');
            
            // NORTHERN HEMISPHERE SEASONS (UK, Europe, North America)
            // Determine season for Northern Hemisphere
            if ($month >= 3 && $month <= 5) {
                $season = "Spring";
            } elseif ($month >= 6 && $month <= 8) {
                $season = "Summer";
            } elseif ($month >= 9 && $month <= 11) {
                $season = "Autumn/Fall";
            } else {
                $season = "Winter";
            }
            
            // Add system context for better, more concise responses
            $systemPrompt = "You are Sybiosis, a helpful farm management AI assistant for Middle World Farms in the UK. Today is {$currentDate} and it is currently {$season} in the Northern Hemisphere. Provide clear, practical answers about farming, biodynamic practices, and agriculture appropriate for this season and UK climate. Keep responses focused and concise (2-3 paragraphs max) unless asked for detailed information.";
            
            $fullPrompt = $systemPrompt . "\n\nUser question: " . $message . "\n\nYour response:";
            
            // Call Ollama directly with Phi-3 Latest model
            $data = [
                'model' => 'phi3:latest',
                'prompt' => $fullPrompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => 400,  // Limit tokens for faster, more concise replies
                    'top_p' => 0.9
                ]
            ];
            
            $response = file_get_contents(env('AI_SERVICE_URL', 'http://localhost:8005') . '/api/generate', false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($data),
                    'timeout' => 120,  // 2 minutes
                    'ignore_errors' => true
                ]
            ]));
            
            if ($response !== false) {
                $result = json_decode($response, true);
                return response()->json([
                    'success' => true, 
                    'response' => $result['response'] ?? 'No response received',
                    'model' => 'phi3:latest'
                ]);
            } else {
                return response()->json(['success' => false, 'error' => 'AI service unavailable - timeout or connection error']);
            }
        } catch (\Exception $e) {
            \Log::error('Chatbot API error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Request timeout - try asking a shorter question or wait a moment and try again']);
        }
    })->name('admin.chatbot-api');

    // Simple test route
    Route::get('/test', function () {
        return response()->json(['message' => 'Admin system is working', 'timestamp' => now()]);
    });

    // Conversation management routes (ADMIN ONLY)
    Route::prefix('conversations')->name('admin.conversations.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ConversationAdminController::class, 'index'])->name('index');
        Route::get('/statistics', [App\Http\Controllers\Admin\ConversationAdminController::class, 'statistics'])->name('statistics');
        Route::get('/search', [App\Http\Controllers\Admin\ConversationAdminController::class, 'search'])->name('search');
        Route::get('/export-training', [App\Http\Controllers\Admin\ConversationAdminController::class, 'exportTraining'])->name('export-training');
        Route::post('/purge-old', [App\Http\Controllers\Admin\ConversationAdminController::class, 'purgeOld'])->name('purge-old');
        Route::get('/{id}', [App\Http\Controllers\Admin\ConversationAdminController::class, 'show'])->name('show');
        Route::delete('/{id}', [App\Http\Controllers\Admin\ConversationAdminController::class, 'destroy'])->name('destroy');
    });


    // Route planning and optimization routes
    Route::prefix('routes')->name('admin.routes.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RouteController::class, 'index'])->name('index');
        Route::post('/optimize', [App\Http\Controllers\Admin\RouteController::class, 'optimize'])->name('optimize');
        Route::post('/send-to-driver', [App\Http\Controllers\Admin\RouteController::class, 'sendToDriver'])->name('send-to-driver');
        Route::post('/send-to-driver-sms', [App\Http\Controllers\Admin\RouteController::class, 'sendToDriverSMS'])->name('send-to-driver-sms');
        Route::get('/map-data', [App\Http\Controllers\Admin\RouteController::class, 'getMapData'])->name('map-data');
        Route::post('/create-shareable-map', [App\Http\Controllers\Admin\RouteController::class, 'createShareableMap'])->name('create-shareable-map');
        Route::get('/wp-go-maps-data', [App\Http\Controllers\Admin\RouteController::class, 'getWPGoMapsData'])->name('wp-go-maps-data');
    });

    // New route planner page
    Route::get('/deliveries/route-planner', [\App\Http\Controllers\Admin\RouteController::class, 'index'])->name('admin.route-planner');
    
    
    // Print packing slips
    Route::get('/deliveries/print', [App\Http\Controllers\Admin\DeliveryController::class, 'print'])->name('admin.deliveries.print');
    
    // Print actual packing slips (multiple per sheet)
    Route::get('/deliveries/print-slips', [App\Http\Controllers\Admin\DeliveryController::class, 'printSlips'])->name('admin.deliveries.print-slips');

    // Completion tracking routes
    Route::post('/deliveries/mark-complete', [App\Http\Controllers\Admin\DeliveryController::class, 'markComplete'])->name('admin.deliveries.mark-complete');
    Route::post('/deliveries/unmark-complete', [App\Http\Controllers\Admin\DeliveryController::class, 'unmarkComplete'])->name('admin.deliveries.unmark-complete');

    // FarmOS Integration routes
    Route::prefix('farmos')->name('admin.farmos.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\FarmOSDataController::class, 'index'])->name('dashboard');
        Route::get('/planting-chart', [App\Http\Controllers\Admin\FarmOSDataController::class, 'plantingChart'])->name('planting-chart');
        Route::get('/harvests', [App\Http\Controllers\Admin\FarmOSDataController::class, 'harvests'])->name('harvests');
        Route::get('/stock', [App\Http\Controllers\Admin\FarmOSDataController::class, 'stock'])->name('stock');
        Route::post('/stock', [App\Http\Controllers\Admin\FarmOSDataController::class, 'storeStock'])->name('stock.store');
        
        // Data sync routes
        Route::post('/sync-harvests', [App\Http\Controllers\Admin\FarmOSDataController::class, 'syncHarvests'])->name('sync-harvests');
        Route::post('/sync-to-stock', [App\Http\Controllers\Admin\FarmOSDataController::class, 'syncToStock'])->name('sync-to-stock');
        Route::post('/sync-varieties', [App\Http\Controllers\Admin\FarmOSDataController::class, 'syncVarieties'])->name('sync-varieties');
        Route::delete('/clear-test-data', [App\Http\Controllers\Admin\FarmOSDataController::class, 'clearTestData'])->name('clear-test-data');
        
        // Succession Planning routes - AI-powered succession planting
        Route::get('/succession-planning', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'index'])->name('succession-planning');
        Route::post('/succession-planning/calculate', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'calculate'])->name('succession-planning.calculate');
        Route::post('/succession-planning/generate', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'generate'])->name('succession-planning.generate');
        Route::post('/succession-planning/create-logs', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'createLogs'])->name('succession-planning.create-logs');
        Route::post('/succession-planning/create-single-log', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'createSingleLog'])->name('succession-planning.create-single-log');
        Route::post('/succession-planning/harvest-window', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getOptimalHarvestWindow'])->name('succession-planning.harvest-window');
        Route::post('/succession-planning/seeding-transplant', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getSeedingTransplantData'])->name('succession-planning.seeding-transplant');
        Route::post('/succession-planning/chat', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'chat'])->name('succession-planning.chat');
        Route::post('/succession-planning/analyze-cash-crops', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'analyzeCashCrops'])->name('succession-planning.analyze-cash-crops');
        
        // API log submission for Quick Forms
        Route::post('/succession-planning/submit-log', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'submitLog'])->name('succession-planning.submit-log');
        Route::post('/succession-planning/submit-all-logs', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'submitAllLogs'])->name('succession-planning.submit-all-logs');
        
        // Variety details endpoint for AI processing
        Route::get('/succession-planning/varieties/{varietyId}', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getVariety'])->name('succession-planning.variety');
        
        // Varieties by season type for varietal succession
        Route::get('/succession-planning/varieties-by-season/{cropId}', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getVarietiesBySeason'])->name('succession-planning.varieties-by-season');
        
        // Bed occupancy data for timeline visualization
        Route::get('/succession-planning/bed-occupancy', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getBedOccupancy'])->name('succession-planning.bed-occupancy');
        
        // AI service management routes
        Route::get('/succession-planning/ai-status', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getAIStatus'])->name('succession-planning.ai-status');
        Route::post('/succession-planning/wake-ai', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'wakeUpAI'])->name('succession-planning.wake-ai');
        
        // Image proxy for FarmOS variety images
        Route::get('/variety-image/{fileId}', [App\Http\Controllers\Admin\FarmOSDataController::class, 'proxyVarietyImage'])->name('variety-image');
        
        // Quick Form routes - serve the unified quick form template
        Route::get('/quick/seeding', function () {
            return view('admin.farmos.quick-forms.quick-planting');
        })->name('quick.seeding');
        Route::get('/quick/transplant', function () {
            return view('admin.farmos.quick-forms.quick-planting');
        })->name('quick.transplant');
        Route::get('/quick/harvest', function () {
            return view('admin.farmos.quick-forms.quick-planting');
        })->name('quick.harvest');

        // Proxy routes for FarmOS Quick Forms with pre-filling
        Route::get('/proxy/quick/seeding', [App\Http\Controllers\Admin\FarmOSProxyController::class, 'proxySeedingForm'])->name('proxy.quick.seeding');
        Route::get('/proxy/quick/transplant', [App\Http\Controllers\Admin\FarmOSProxyController::class, 'proxyTransplantForm'])->name('proxy.quick.transplant');
        Route::get('/proxy/quick/harvest', [App\Http\Controllers\Admin\FarmOSProxyController::class, 'proxyHarvestForm'])->name('proxy.quick.harvest');
    });

    // Test route for AI timing
    Route::get('/test-ai-timing', function () {
        return view('test-ai-timing');
    });

    // Weather Integration routes
    Route::prefix('weather')->name('admin.weather.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\WeatherController::class, 'index'])->name('dashboard');
        Route::get('/current', [App\Http\Controllers\Admin\WeatherController::class, 'getCurrentWeather'])->name('current');
        Route::get('/forecast', [App\Http\Controllers\Admin\WeatherController::class, 'getForecast'])->name('forecast');
        Route::get('/frost-risk', [App\Http\Controllers\Admin\WeatherController::class, 'getFrostRisk'])->name('frost-risk');
        Route::post('/planting-analysis', [App\Http\Controllers\Admin\WeatherController::class, 'analyzePlantingWindow'])->name('planting-analysis');
        Route::get('/growing-degree-days', [App\Http\Controllers\Admin\WeatherController::class, 'getGrowingDegreeDays'])->name('growing-degree-days');
        Route::get('/historical', [App\Http\Controllers\Admin\WeatherController::class, 'getHistoricalWeather'])->name('historical');
        Route::get('/alerts', [App\Http\Controllers\Admin\WeatherController::class, 'getWeatherAlerts'])->name('alerts');
        Route::get('/field-work', [App\Http\Controllers\Admin\WeatherController::class, 'getFieldWorkRecommendations'])->name('field-work');
    });

    // AI API routes (outside farmOS group since they might be called differently)
    Route::prefix('api')->group(function () {
        Route::post('/ai/crop-timing', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getAICropTiming'])->name('api.ai.crop-timing');
        
        // 🌟 Holistic AI routes - Sacred geometry, lunar cycles, and biodynamic wisdom
        Route::post('/ai/holistic-recommendations', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getHolisticRecommendations'])->name('api.ai.holistic-recommendations');
        Route::get('/ai/moon-phase', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getMoonPhaseGuidance'])->name('api.ai.moon-phase');
        Route::post('/ai/sacred-spacing', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getSacredSpacing'])->name('api.ai.sacred-spacing');
    });

    // Stripe Payment Integration Routes
    Route::prefix('stripe')->name('admin.stripe.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\StripeController::class, 'index'])->name('dashboard');
        Route::get('/payments', [App\Http\Controllers\Admin\StripeController::class, 'getPayments'])->name('payments');
        Route::get('/statistics', [App\Http\Controllers\Admin\StripeController::class, 'getStatistics'])->name('statistics');
        Route::get('/subscriptions', [App\Http\Controllers\Admin\StripeController::class, 'getSubscriptions'])->name('subscriptions');
        Route::get('/customers/search', [App\Http\Controllers\Admin\StripeController::class, 'searchCustomers'])->name('customers.search');
        Route::get('/balance', [App\Http\Controllers\Admin\StripeController::class, 'getBalance'])->name('balance');
        Route::get('/payouts', [App\Http\Controllers\Admin\StripeController::class, 'getPayouts'])->name('payouts');
    });

    // AI Gateway test route (internal)
    Route::get('/api/ai/gateway', function(\Illuminate\Http\Request $request, \App\Services\AiGatewayService $gw) {
        $service = $request->query('service','farmos');
        $method = $request->query('method','getPlantAssets');
        $params = $request->query('params', []);
        if (is_string($params)) { // allow JSON in query
            $decoded = json_decode($params, true);
            if (json_last_error() === JSON_ERROR_NONE) $params = $decoded; else $params = [];
        }
        return response()->json($gw->call($service, $method, $params));
    })->name('admin.ai.gateway-test');

    // Debug endpoint for delivery/collection classification verification
    Route::get('/debug-classification', [DeliveryController::class, 'debugClassification'])->name('debug.classification');

    // Debug endpoint for Pauline Moore's duplicate order analysis
    Route::get('/debug-pauline', [DeliveryController::class, 'debugPauline'])->name('debug.pauline');

    // Debug route addresses for route planner (simplified)
    Route::get('/debug-route-addresses', function() {
        return response()->json([
            'message' => 'Route address debugging endpoint - contact dev team for specific delivery analysis',
            'timestamp' => now()->toDateTimeString()
        ]);
    })->name('debug.route-addresses');

    // Test route planner with this week's deliveries
    Route::get('/test-route-planner', function() {
        // The 4 correct delivery IDs for this week
        $correctIds = '227748,227726,227673,227581';
        
        // Redirect to route planner with the delivery IDs
        return redirect()->route('admin.routes.index', ['delivery_ids' => $correctIds]);
    })->name('test.route-planner');

    // Subscription management endpoint
    Route::get('/manage-subscription/{email}', [DeliveryController::class, 'manageSubscription'])->name('manage.subscription');

    // Simple planting week (raw JSON, no AI)
    Route::get('/planting-week-simple', function(\App\Services\PlantingRecommendationService $svc) {
        return response()->json($svc->forWeek());
    })->name('admin.planting-week-simple');

    // farmOS sanity check (counts only)
    Route::get('/farmos-sanity', function(\App\Services\FarmOSApi $svc) {
        $harvest = $svc->getHarvestLogs();
        $plantTypes = $svc->getPlantTypes();
        $plantCount = 0;
        if (is_array($plantTypes)) {
            if (isset($plantTypes['data']) && is_array($plantTypes['data'])) { $plantCount = count($plantTypes['data']); }
            else { $plantCount = count($plantTypes); }
        }
        $land = $svc->getGeometryAssets();
        return response()->json([
            'harvest_logs_count' => is_array($harvest)? count($harvest) : 0,
            'plant_types_count' => $plantCount,
            'land_assets_count' => is_array($land)? count($land) : 0,
            'timestamp' => now()->toDateTimeString(),
        ]);
    })->name('admin.farmos-sanity');

    // AI ingestion tasks (basic)
    Route::post('/api/ai/ingest', function(\Illuminate\Http\Request $request, \App\Services\AiIngestionService $ingest) {
        $userId = 1; // Default admin user ID for API calls
        $task = $ingest->createTask($request->input('type'), $request->input('params', []), $userId);
        return response()->json(['task_id' => $task->id, 'status' => $task->status]);
    })->name('admin.ai.ingest.create');

    Route::post('/api/ai/ingest/run-pending', function(\App\Services\AiIngestionService $ingest) {
        $count = $ingest->runPending();
        return ['ran' => $count];
    })->name('admin.ai.ingest.run');

    Route::get('/api/ai/ingest/tasks', function() {
        return \App\Models\AiIngestionTask::orderByDesc('id')->limit(50)->get();
    })->name('admin.ai.ingest.list');

    // farmOS UUID helper for creating plant assets
    Route::get('/farmos/uuid-helper', function(\App\Services\FarmOSApi $svc) {
        $plantTypes = collect($svc->getPlantTypes())->map(fn($t)=>[
            'id' => $t['id'] ?? null,
            'name' => $t['attributes']['name'] ?? null,
        ])->filter(fn($r)=>$r['id'] && $r['name'])->values();
        $varieties = collect($svc->getVarieties())->map(fn($t)=>[
            'id' => $t['id'] ?? null,
            'name' => $t['attributes']['name'] ?? null,
        ])->filter(fn($r)=>$r['id'] && $r['name'])->values();
        $land = collect($svc->getGeometryAssets(['status'=>'active']))->map(fn($a)=>[
            'id' => $a['id'] ?? null,
            'name' => $a['attributes']['name'] ?? null,
        ])->filter(fn($r)=>$r['id'] && $r['name'])->values();
        return response()->json([
            'plant_types' => $plantTypes,
            'varieties' => $varieties,
            'land_assets' => $land,
        ]);
    })->name('admin.farmos.uuid-helper');

    // WooCommerce Funds Management
    Route::prefix('funds')->name('admin.funds.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\WooCommerceFundsController::class, 'index'])->name('index');
        Route::get('/settings', [App\Http\Controllers\Admin\WooCommerceFundsController::class, 'settings'])->name('settings');
        Route::post('/settings', [App\Http\Controllers\Admin\WooCommerceFundsController::class, 'updateSettings'])->name('settings.update');
        Route::get('/customers', [App\Http\Controllers\Admin\WooCommerceFundsController::class, 'getCustomerFunds'])->name('customers');
        Route::get('/customers/{customerId}', [App\Http\Controllers\Admin\WooCommerceFundsController::class, 'showCustomer'])->name('customers.show');
        Route::post('/customers/{customerId}/adjust-balance', [App\Http\Controllers\Admin\WooCommerceFundsController::class, 'adjustCustomerBalance'])->name('customers.adjust-balance');
        Route::get('/transactions', [App\Http\Controllers\Admin\WooCommerceFundsController::class, 'getTransactions'])->name('transactions');
    });
});

// Serve product images (workaround for nginx 403 on /storage)
Route::get('/product-image/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    
    if (!file_exists($filePath)) {
        abort(404);
    }
    
    return response()->file($filePath);
})->where('path', '.*')->name('product.image');

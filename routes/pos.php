<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| POS Routes
|--------------------------------------------------------------------------
|
| These routes are for the Point of Sale system and bypass standard web
| middleware including CSRF protection for API-like functionality.
|
*/

// POS Login Routes (no authentication required)
Route::prefix('pos')->group(function () {
    Route::get('/login', [App\Http\Controllers\PosLoginController::class, 'showLoginForm'])->name('pos.login');
    Route::post('/login', [App\Http\Controllers\PosLoginController::class, 'login'])->name('pos.login.submit');
    Route::post('/logout', [App\Http\Controllers\PosLoginController::class, 'logout'])->name('pos.logout');
});

// Protected POS Routes (require POS authentication)
Route::prefix('pos')->middleware(['pos'])->group(function () {
    Route::get('/', [App\Http\Controllers\PosController::class, 'dashboard'])->name('pos.dashboard');
    Route::get('/settings', [App\Http\Controllers\PosController::class, 'getPosSettings'])->name('pos.settings');
    Route::get('/products', [App\Http\Controllers\PosController::class, 'getProducts'])->name('pos.products');
    Route::post('/orders', [App\Http\Controllers\PosController::class, 'createOrder'])->name('pos.orders.create');
    Route::get('/orders', [App\Http\Controllers\PosController::class, 'getOrders'])->name('pos.orders');
    Route::get('/stats', [App\Http\Controllers\PosController::class, 'getStats'])->name('pos.stats');

    // POS Inventory Management (which products are available at the market stall)
    Route::get('/inventory', [App\Http\Controllers\PosInventoryController::class, 'index'])->name('pos.inventory.index');
    Route::get('/inventory/products', [App\Http\Controllers\PosInventoryController::class, 'getProducts'])->name('pos.inventory.products');
    Route::post('/inventory/toggle/{id}', [App\Http\Controllers\PosInventoryController::class, 'toggleAvailability'])->name('pos.inventory.toggle');
    Route::post('/inventory/bulk-update', [App\Http\Controllers\PosInventoryController::class, 'bulkUpdate'])->name('pos.inventory.bulk-update');
    Route::get('/inventory/categories', [App\Http\Controllers\PosInventoryController::class, 'getCategoryCounts'])->name('pos.inventory.categories');

    // Subscription signup wizard
    Route::post('/subscription/create', [App\Http\Controllers\POSSubscriptionController::class, 'create'])->name('pos.subscription.create');
    Route::get('/customers/search', [App\Http\Controllers\POSSubscriptionController::class, 'searchCustomers'])->name('pos.customers.search');
    Route::get('/subscription/plan-price', [App\Http\Controllers\POSSubscriptionController::class, 'getPlanPrice'])->name('pos.subscription.plan-price');

    // Payment processing routes
    Route::get('/payment-methods', [App\Http\Controllers\PosController::class, 'getPaymentMethods'])->name('pos.payment.methods');
    Route::post('/process-card-payment', [App\Http\Controllers\PosController::class, 'processCardPayment'])->name('pos.process.card.payment');
    Route::post('/check-payment-status', [App\Http\Controllers\PosController::class, 'checkPaymentStatus'])->name('pos.check-payment-status');
    Route::get('/payment/complete', function () { return view('pos.payment-complete'); })->name('pos.payment.complete');

    // Stripe Payment Intents API (NEW - fast card payments)
    Route::get('/payments/config', [App\Http\Controllers\PaymentController::class, 'getConfig'])->name('pos.payments.config');
    Route::post('/payments/intent', [App\Http\Controllers\PaymentController::class, 'createIntent'])->name('pos.payments.intent');
    Route::get('/payments/intent/{id}', [App\Http\Controllers\PaymentController::class, 'getIntent'])->name('pos.payments.intent.get');
    Route::post('/payments/confirm', [App\Http\Controllers\PaymentController::class, 'confirmPayment'])->name('pos.payments.confirm');
    Route::post('/payments/terminal/connection-token', [App\Http\Controllers\PaymentController::class, 'createConnectionToken'])->name('pos.payments.terminal.token');
    Route::post('/payments/link', [App\Http\Controllers\PaymentController::class, 'createPaymentLink'])->name('pos.payments.link');
    Route::post('/payments/refund', [App\Http\Controllers\PaymentController::class, 'refundPayment'])->name('pos.payments.refund');
    
    // Clear test sales
    Route::post('/clear-test-sales', [App\Http\Controllers\PosController::class, 'clearTestSales'])->name('pos.clear-test-sales');
    
    // Order history (admin only)
    Route::get('/order-history', [App\Http\Controllers\PosController::class, 'orderHistory'])->name('pos.order-history');
    Route::delete('/orders/{id}', [App\Http\Controllers\PosController::class, 'deleteOrder'])->name('pos.orders.delete');
    Route::post('/payments/cancel', [App\Http\Controllers\PaymentController::class, 'cancelPayment'])->name('pos.payments.cancel');
    
    // Deliveries & Collections (mobile-friendly completion view)
    Route::get('/deliveries', [App\Http\Controllers\PosController::class, 'deliveries'])->name('pos.deliveries');
    Route::get('/deliveries/data', [App\Http\Controllers\PosController::class, 'getDeliveriesData'])->name('pos.deliveries.data');
    Route::post('/deliveries/complete', [App\Http\Controllers\PosController::class, 'completeDelivery'])->name('pos.deliveries.complete');
});
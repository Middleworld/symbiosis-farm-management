<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\Api\VegboxSubscriptionApiController;
use App\Http\Controllers\Api\BoxCustomizationApiController;
use App\Http\Controllers\Api\BrandingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Conversation API routes - SECURED with ADMIN authentication only
Route::middleware('admin.auth')->prefix('conversations')->group(function () {
    Route::post('/', [ConversationController::class, 'store']);
    Route::get('/search', [ConversationController::class, 'search']);
    Route::get('/type/{type}', [ConversationController::class, 'byType']);
    Route::get('/user/{userId}', [ConversationController::class, 'userConversations']);
    Route::get('/{conversationId}', [ConversationController::class, 'show']);
});

// Vegbox Subscription API routes - SECURED with WooCommerce API token authentication
// Used by WordPress MWF Custom Subscriptions plugin
Route::middleware(['verify.wc.api.token'])->prefix('subscriptions')->group(function () {
    
    // Get user's subscriptions
    Route::get('/user/{user_id}', [VegboxSubscriptionApiController::class, 'getUserSubscriptions']);
    
    // Create subscription after WooCommerce order
    Route::post('/create', [VegboxSubscriptionApiController::class, 'createSubscription']);
    
    // Single action endpoint (avoids ModSecurity triggers for /resume and /cancel)
    Route::post('/{id}/action', [VegboxSubscriptionApiController::class, 'handleSubscriptionAction']);
    
    // Legacy pause endpoint (kept for backward compatibility)
    Route::post('/{id}/pause', [VegboxSubscriptionApiController::class, 'pauseSubscription']);
    
    // Get subscription details
    Route::get('/{id}', [VegboxSubscriptionApiController::class, 'getSubscription']);
    
    // Migration endpoint - migrate WooCommerce subscriptions to Laravel
    Route::post('/migrate', [VegboxSubscriptionApiController::class, 'migrateFromWooCommerce']);
    
    // Update subscription address
    Route::post('/{id}/update-address', [VegboxSubscriptionApiController::class, 'updateAddress']);
    
    // Get payment history
    Route::get('/{id}/payments', [VegboxSubscriptionApiController::class, 'getPayments']);
    
    // Change delivery method (delivery <-> collection)
    Route::post('/{id}/change-delivery-method', [VegboxSubscriptionApiController::class, 'changeDeliveryMethod']);
    
    // Change billing frequency (weekly <-> monthly)
    Route::post('/{id}/change-billing-frequency', [VegboxSubscriptionApiController::class, 'changeBillingFrequency']);
});

// Box Customization API routes - SECURED with WooCommerce API token authentication
// Used by WordPress MWF Custom Subscriptions plugin for drag-and-drop box customization
Route::middleware(['verify.wc.api.token'])->prefix('box-customization')->group(function () {
    
    // Get available items for a subscription's upcoming week
    Route::get('/available-items/{subscriptionId}', [BoxCustomizationApiController::class, 'getAvailableItems']);
    
    // Get customer's current box selection
    Route::get('/customer-box/{subscriptionId}/{selectionId?}', [BoxCustomizationApiController::class, 'getCustomerBox']);
    
    // Update customer's box selection
    Route::post('/update/{subscriptionId}', [BoxCustomizationApiController::class, 'updateCustomerBox']);
    
    // Get token balance for subscription
    Route::get('/token-balance/{subscriptionId}', [BoxCustomizationApiController::class, 'getTokenBalance']);
    
    // Reset box to defaults
    Route::post('/reset/{subscriptionId}', [BoxCustomizationApiController::class, 'resetToDefault']);
});

// ===== Field Kit Webhook API Routes =====
// PUBLIC routes for Field Kit mobile app integration
Route::prefix('fieldkit')->group(function () {
    
    // Task completion webhook (called from Field Kit after QR scan)
    Route::post('/task-completed', [App\Http\Controllers\Api\FieldKitWebhookController::class, 'taskCompleted'])
        ->name('api.fieldkit.task-completed');
    
    // Generate QR code for a plant asset/task
    Route::post('/generate-qr', [App\Http\Controllers\Api\FieldKitWebhookController::class, 'generateTaskQR'])
        ->name('api.fieldkit.generate-qr')
        ->middleware('admin.auth'); // Requires admin auth
    
    // Batch generate QR codes
    Route::post('/batch-generate-qr', [App\Http\Controllers\Api\FieldKitWebhookController::class, 'batchGenerateQR'])
        ->name('api.fieldkit.batch-generate-qr')
        ->middleware('admin.auth'); // Requires admin auth
    
    // Get sync status
    Route::get('/sync-status', [App\Http\Controllers\Api\FieldKitWebhookController::class, 'syncStatus'])
        ->name('api.fieldkit.sync-status');
});

// ===== Branding API Routes =====
// PUBLIC routes for WordPress/external systems to fetch branding
Route::prefix('branding')->group(function () {
    
    // Get active branding settings (JSON)
    Route::get('/', [BrandingController::class, 'index'])
        ->name('api.branding.index');
    
    // Get CSS variables (text/css)
    Route::get('/css', [BrandingController::class, 'cssVariables'])
        ->name('api.branding.css');
    
    // Clear branding cache (admin only)
    Route::post('/clear-cache', [BrandingController::class, 'clearCache'])
        ->name('api.branding.clear-cache')
        ->middleware('admin.auth');
});

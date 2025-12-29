<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('csa_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Customer & Product Info
            $table->unsignedBigInteger('customer_id'); // WordPress user ID
            $table->string('customer_email')->index();
            $table->string('customer_name');
            $table->unsignedBigInteger('product_id'); // Laravel product ID
            $table->unsignedBigInteger('product_variation_id')->nullable(); // Laravel variation ID
            $table->unsignedBigInteger('woo_subscription_id')->nullable()->index(); // WooCommerce subscription ID
            $table->unsignedBigInteger('woo_product_id')->nullable(); // WooCommerce product ID
            
            // Selected Attributes (from product variations)
            $table->string('payment_schedule')->nullable(); // Weekly, Fortnightly, Monthly, Annually
            $table->string('delivery_frequency')->nullable(); // Weekly, Fortnightly
            $table->string('box_size')->nullable(); // Single, Couple's, Small Family, Large Family
            $table->string('fulfillment_type')->default('Delivery'); // Delivery, Collection
            
            // Pricing
            $table->decimal('price', 10, 2); // Price per billing cycle
            $table->decimal('season_total', 10, 2)->nullable(); // Total for entire season
            
            // Delivery Details
            $table->text('delivery_address')->nullable();
            $table->string('delivery_postcode')->nullable()->index();
            $table->string('delivery_day')->nullable(); // Thursday (delivery) or Friday/Saturday (collection)
            $table->string('delivery_time')->nullable(); // Time slot for deliveries
            $table->string('fortnightly_week')->nullable(); // A or B for fortnightly deliveries
            
            // Schedule
            $table->date('season_start_date')->nullable(); // When their subscription season starts
            $table->date('season_end_date')->nullable(); // When their subscription season ends
            $table->date('next_billing_date')->nullable()->index();
            $table->date('next_delivery_date')->nullable()->index();
            $table->integer('deliveries_remaining')->default(0); // How many boxes left in season
            
            // Status
            $table->enum('status', [
                'active',
                'on-hold',
                'pending',
                'cancelled',
                'expired',
                'pending-cancel'
            ])->default('pending')->index();
            $table->text('status_notes')->nullable();
            
            // Pause/Skip functionality
            $table->boolean('is_paused')->default(false);
            $table->date('paused_until')->nullable();
            $table->json('skipped_dates')->nullable(); // Array of dates customer has skipped
            
            // Payment tracking
            $table->integer('failed_payment_count')->default(0);
            $table->timestamp('last_payment_date')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable(); // Extra data (preferences, notes, etc.)
            $table->boolean('imported_from_woo')->default(false); // Track if migrated from WooCommerce
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for cancelled subscriptions
            
            // Indexes for common queries
            $table->index(['status', 'next_delivery_date']);
            $table->index(['fulfillment_type', 'delivery_day']);
            $table->index(['delivery_frequency', 'fortnightly_week']);
        });
        
        // Delivery history table - tracks each box delivered
        Schema::create('csa_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('csa_subscriptions')->onDelete('cascade');
            
            $table->date('scheduled_date')->index();
            $table->date('delivered_date')->nullable();
            
            $table->enum('status', [
                'scheduled',
                'packed',
                'out-for-delivery',
                'delivered',
                'skipped',
                'failed',
                'cancelled'
            ])->default('scheduled')->index();
            
            $table->text('contents')->nullable(); // What was in the box
            $table->text('notes')->nullable();
            $table->string('packed_by')->nullable(); // Staff member who packed it
            $table->string('delivered_by')->nullable(); // Driver who delivered it
            
            $table->timestamps();
            
            $table->index(['scheduled_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csa_deliveries');
        Schema::dropIfExists('csa_subscriptions');
    }
};

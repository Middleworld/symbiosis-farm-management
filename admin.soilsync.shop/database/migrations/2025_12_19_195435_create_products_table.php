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
        Schema::create('woocommerce_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('woo_id')->nullable(); // WooCommerce product ID
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('simple'); // simple, variable, variable-subscription
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('regular_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->boolean('is_subscription')->default(false);
            $table->unsignedBigInteger('vegbox_plan_id')->nullable();
            $table->string('billing_period')->default('week');
            $table->integer('billing_interval')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('woo_id');
            $table->index(['type', 'is_active']);
            $table->index('is_subscription');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('woocommerce_products');
    }
};

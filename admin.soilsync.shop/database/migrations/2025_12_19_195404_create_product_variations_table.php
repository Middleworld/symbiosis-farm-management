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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('woo_id')->nullable(); // WooCommerce variation ID
            $table->unsignedBigInteger('product_id'); // Parent product ID
            $table->string('sku')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('regular_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->json('attributes')->nullable(); // Variation attributes
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('stock_quantity')->nullable();
            $table->string('stock_status')->default('instock');
            $table->boolean('manage_stock')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('woo_id');
            $table->index(['product_id', 'is_active']);
            $table->foreign('product_id')->references('id')->on('woocommerce_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};

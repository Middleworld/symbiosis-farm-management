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
        Schema::table('box_configuration_items', function (Blueprint $table) {
            // Add product_id for WooCommerce products (separate from farmOS plant_variety_id)
            $table->unsignedBigInteger('product_id')->nullable()->after('plant_variety_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            // Add price_at_time to track historical prices
            $table->decimal('price_at_time', 10, 2)->nullable()->after('token_value');
            
            // Add quantity field for admin configurations
            $table->integer('quantity')->default(1)->after('quantity_allocated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('box_configuration_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'price_at_time', 'quantity']);
        });
    }
};

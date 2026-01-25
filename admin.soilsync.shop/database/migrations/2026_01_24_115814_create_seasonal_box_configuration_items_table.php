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
        Schema::create('seasonal_box_configuration_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seasonal_configuration_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('item_name');
            $table->integer('quantity');
            $table->string('unit')->default('item');
            $table->decimal('price_at_time', 8, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('seasonal_configuration_id', 'sbc_items_sbc_id_fk')->references('id')->on('seasonal_box_configurations')->onDelete('cascade');
            $table->foreign('product_id', 'sbc_items_product_id_fk')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasonal_box_configuration_items');
    }
};

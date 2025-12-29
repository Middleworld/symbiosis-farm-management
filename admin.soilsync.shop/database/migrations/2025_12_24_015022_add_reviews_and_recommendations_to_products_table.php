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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('reviews_enabled')->default(true)->after('shipping_class_id');
            $table->json('upsell_ids')->nullable()->after('reviews_enabled');
            $table->json('crosssell_ids')->nullable()->after('upsell_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['reviews_enabled', 'upsell_ids', 'crosssell_ids']);
        });
    }
};

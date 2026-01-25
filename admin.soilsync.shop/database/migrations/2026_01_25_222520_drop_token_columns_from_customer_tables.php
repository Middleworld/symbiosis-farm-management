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
        Schema::table('customer_box_selections', function (Blueprint $table) {
            $table->dropColumn(['tokens_allocated', 'tokens_used']);
        });

        Schema::table('customer_box_items', function (Blueprint $table) {
            $table->dropColumn('tokens_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_box_selections', function (Blueprint $table) {
            $table->integer('tokens_allocated')->default(0)->after('box_configuration_id');
            $table->integer('tokens_used')->default(0)->after('tokens_allocated');
        });

        Schema::table('customer_box_items', function (Blueprint $table) {
            $table->integer('tokens_used')->default(0)->after('quantity');
        });
    }
};

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
        Schema::table('vegbox_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('default_tokens')->default(10)->after('max_deliveries_per_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vegbox_plans', function (Blueprint $table) {
            $table->dropColumn('default_tokens');
        });
    }
};

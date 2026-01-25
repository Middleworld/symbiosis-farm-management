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
        Schema::table('box_configurations', function (Blueprint $table) {
            $table->boolean('is_seasonal')->default(false)->after('plan_id');
            $table->string('seasonal_name')->nullable()->after('is_seasonal');
            $table->date('start_date')->nullable()->after('seasonal_name');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('box_configurations', function (Blueprint $table) {
            $table->dropColumn(['is_seasonal', 'seasonal_name', 'start_date', 'end_date']);
        });
    }
};

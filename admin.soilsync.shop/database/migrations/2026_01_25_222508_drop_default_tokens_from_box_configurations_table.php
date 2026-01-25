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
            $table->dropColumn('default_tokens');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('box_configurations', function (Blueprint $table) {
            $table->integer('default_tokens')->default(10)->after('is_active');
        });
    }
};

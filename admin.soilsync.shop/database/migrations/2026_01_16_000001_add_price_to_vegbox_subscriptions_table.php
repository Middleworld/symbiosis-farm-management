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
        Schema::table('vegbox_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('vegbox_subscriptions', 'price')) {
                $table->decimal('price', 8, 2)->default(0.00)->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vegbox_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('vegbox_subscriptions', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};

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
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->boolean('synced_to_quickbooks')->default(false)->after('updated_at');
            $table->boolean('synced_to_xero')->default(false)->after('synced_to_quickbooks');
            $table->boolean('synced_to_sage')->default(false)->after('synced_to_xero');
            $table->boolean('synced_to_myob')->default(false)->after('synced_to_sage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn(['synced_to_quickbooks', 'synced_to_xero', 'synced_to_sage', 'synced_to_myob']);
        });
    }
};

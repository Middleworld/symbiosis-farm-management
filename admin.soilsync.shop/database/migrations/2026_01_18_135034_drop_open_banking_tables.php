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
        Schema::dropIfExists('openbanking_transactions');
        Schema::dropIfExists('openbanking_accounts');
        Schema::dropIfExists('openbanking_connections');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse migration needed as we're removing this feature entirely
    }
};

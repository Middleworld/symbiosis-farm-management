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
        Schema::create('used_jtis', function (Blueprint $table) {
            $table->id();
            $table->string('jti', 32)->unique(); // JWT ID (32 hex chars)
            $table->timestamp('expires_at'); // When this JTI expires
            $table->timestamps();

            $table->index(['jti', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('used_jtis');
    }
};

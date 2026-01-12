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
        Schema::create('vegbox_plans', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 10, 2);
            $table->decimal('signup_fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->unsignedSmallInteger('trial_period')->default(0);
            $table->enum('trial_interval', ['day', 'week', 'month', 'year'])->default('day');
            $table->unsignedSmallInteger('invoice_period')->default(1);
            $table->enum('invoice_interval', ['day', 'week', 'month', 'year'])->default('month');
            $table->unsignedSmallInteger('grace_period')->default(0);
            $table->enum('grace_interval', ['day', 'week', 'month', 'year'])->default('day');
            $table->unsignedSmallInteger('prorate_day')->nullable();
            $table->enum('prorate_period', ['day', 'week', 'month', 'year'])->nullable();
            $table->boolean('prorate_extend_due')->default(false);
            $table->unsignedInteger('active_subscribers_limit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            // Vegbox-specific fields
            $table->enum('box_size', ['small', 'medium', 'large']);
            $table->enum('delivery_frequency', ['weekly', 'bi-weekly']);
            $table->unsignedSmallInteger('max_deliveries_per_month');
            $table->text('contents_description')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vegbox_plans');
    }
};
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
        // Weekly box configurations - what's available this week
        Schema::create('box_configurations', function (Blueprint $table) {
            $table->id();
            $table->date('week_starting')->index(); // Monday of the week
            $table->foreignId('plan_id')->nullable()->constrained('vegbox_plans')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('default_tokens')->default(10); // Default tokens for this box size
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            $table->unique(['week_starting', 'plan_id']);
        });

        // Available items for each box configuration
        Schema::create('box_configuration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_configuration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plant_variety_id')->nullable()->constrained('plant_varieties')->nullOnDelete();
            $table->string('item_name'); // From FarmOS or custom
            $table->text('description')->nullable();
            $table->integer('token_value')->default(1); // 1-4 tokens
            $table->integer('quantity_available')->nullable(); // Total available for all customers
            $table->integer('quantity_allocated')->default(0); // How many customers have selected this
            $table->string('unit')->default('item'); // item, bunch, kg, etc.
            $table->string('farmos_harvest_id')->nullable(); // Link to FarmOS harvest log
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Customer box selections
        Schema::create('customer_box_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('vegbox_subscriptions')->cascadeOnDelete();
            $table->foreignId('box_configuration_id')->constrained()->cascadeOnDelete();
            $table->date('delivery_date');
            $table->integer('tokens_allocated')->default(0);
            $table->integer('tokens_used')->default(0);
            $table->boolean('is_customized')->default(false); // Did customer customize or use defaults?
            $table->boolean('is_locked')->default(false); // Locked after packing deadline
            $table->timestamp('customized_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            
            $table->index(['subscription_id', 'delivery_date']);
        });

        // Individual items in customer's box
        Schema::create('customer_box_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_box_selection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('box_configuration_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->integer('tokens_used')->default(0);
            $table->boolean('is_substitution')->default(false); // Admin substituted item
            $table->text('substitution_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_box_items');
        Schema::dropIfExists('customer_box_selections');
        Schema::dropIfExists('box_configuration_items');
        Schema::dropIfExists('box_configurations');
    }
};

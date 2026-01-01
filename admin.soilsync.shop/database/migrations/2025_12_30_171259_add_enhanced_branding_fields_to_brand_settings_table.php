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
        Schema::table('brand_settings', function (Blueprint $table) {
            $table->string('sidebar_text_color')->default('#ffffff')->after('text_color');
            $table->string('border_color')->default('#dee2e6')->after('background_color');
            $table->string('success_color')->default('#28a745')->after('border_color');
            $table->string('warning_color')->default('#ffc107')->after('success_color');
            $table->string('danger_color')->default('#dc3545')->after('warning_color');
            $table->text('custom_css')->nullable()->after('meta_keywords');
            $table->string('theme_preset')->default('default')->after('custom_css');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brand_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sidebar_text_color',
                'border_color',
                'success_color',
                'warning_color',
                'danger_color',
                'custom_css',
                'theme_preset'
            ]);
        });
    }
};

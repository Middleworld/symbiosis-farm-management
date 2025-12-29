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
        Schema::create('brand_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Middleworld Farms');
            $table->string('tagline')->nullable();
            
            // Color scheme
            $table->string('primary_color')->default('#2d5016');
            $table->string('secondary_color')->default('#5a7c3e');
            $table->string('accent_color')->default('#f5c518');
            $table->string('text_color')->default('#1a1a1a');
            $table->string('background_color')->default('#ffffff');
            
            // Logos
            $table->string('logo_path')->nullable();           // Main logo
            $table->string('logo_small_path')->nullable();     // Favicon/icon
            $table->string('logo_white_path')->nullable();     // Logo for dark backgrounds
            $table->string('logo_alt_text')->default('Middleworld Farms Logo');
            
            // Typography
            $table->json('fonts')->nullable();                 // Font family choices
            
            // Contact & Social
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('address')->nullable();
            $table->json('social_links')->nullable();          // Facebook, Instagram, etc.
            
            // SEO
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            
            // System
            $table->boolean('is_active')->default(true);
            $table->string('version')->default('1.0');
            $table->timestamps();
        });
        
        // Insert default branding
        DB::table('brand_settings')->insert([
            'company_name' => 'Middleworld Farms',
            'tagline' => 'Sustainable farming with modern technology',
            'primary_color' => '#2d5016',
            'secondary_color' => '#5a7c3e',
            'accent_color' => '#f5c518',
            'text_color' => '#1a1a1a',
            'background_color' => '#ffffff',
            'logo_alt_text' => 'Middleworld Farms Logo',
            'fonts' => json_encode([
                'heading' => 'Inter, system-ui, sans-serif',
                'body' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
            ]),
            'social_links' => json_encode([
                'facebook' => '',
                'instagram' => '',
                'twitter' => '',
                'linkedin' => ''
            ]),
            'is_active' => true,
            'version' => '1.0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_settings');
    }
};

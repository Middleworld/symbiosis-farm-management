<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrandSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'tagline',
        'primary_color',
        'secondary_color',
        'accent_color',
        'text_color',
        'sidebar_text_color',
        'background_color',
        'border_color',
        'success_color',
        'warning_color',
        'danger_color',
        'logo_path',
        'logo_small_path',
        'logo_white_path',
        'logo_alt_text',
        'fonts',
        'contact_email',
        'contact_phone',
        'address',
        'social_links',
        'meta_description',
        'meta_keywords',
        'custom_css',
        'theme_preset',
        'is_active',
        'version',
    ];

    protected $casts = [
        'fonts' => 'array',
        'social_links' => 'array',
        'meta_keywords' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active branding settings (singleton pattern)
     */
    public static function active()
    {
        return static::where('is_active', true)->first() 
            ?? static::first() 
            ?? static::create([]);
    }

    /**
     * Get branding as API-friendly array
     */
    public function toApiArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'tagline' => $this->tagline,
            'colors' => [
                'primary' => $this->primary_color,
                'secondary' => $this->secondary_color,
                'accent' => $this->accent_color,
                'text' => $this->text_color,
                'sidebar_text' => $this->sidebar_text_color,
                'background' => $this->background_color,
                'border' => $this->border_color,
                'success' => $this->success_color,
                'warning' => $this->warning_color,
                'danger' => $this->danger_color,
            ],
            'logos' => [
                'main' => $this->logo_path ? asset('storage/' . $this->logo_path) : null,
                'small' => $this->logo_small_path ? asset('storage/' . $this->logo_small_path) : null,
                'white' => $this->logo_white_path ? asset('storage/' . $this->logo_white_path) : null,
                'alt_text' => $this->logo_alt_text,
            ],
            'fonts' => $this->fonts ?? [
                'heading' => 'Inter, system-ui, sans-serif',
                'body' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            ],
            'contact' => [
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
                'address' => $this->address,
            ],
            'social' => $this->social_links ?? [],
            'meta' => [
                'description' => $this->meta_description,
                'keywords' => $this->meta_keywords ?? [],
            ],
        ];
    }

    /**
     * Get CSS variables string for injection
     */
    public function toCssVariables(): string
    {
        $headingFont = $this->fonts['heading'] ?? 'Inter, sans-serif';
        $bodyFont = $this->fonts['body'] ?? 'system-ui, sans-serif';
        
        return implode("\n", [
            "--brand-primary: {$this->primary_color};",
            "--brand-secondary: {$this->secondary_color};",
            "--brand-accent: {$this->accent_color};",
            "--brand-text: {$this->text_color};",
            "--brand-sidebar-text: {$this->sidebar_text_color};",
            "--brand-background: {$this->background_color};",
            "--brand-border: {$this->border_color};",
            "--brand-success: {$this->success_color};",
            "--brand-warning: {$this->warning_color};",
            "--brand-danger: {$this->danger_color};",
            "--brand-font-heading: {$headingFont};",
            "--brand-font-body: {$bodyFont};",
            
            "/* Legacy --mwf- prefix for backwards compatibility */",
            "--mwf-primary: {$this->primary_color};",
            "--mwf-secondary: {$this->secondary_color};",
            "--mwf-accent: {$this->accent_color};",
            "--mwf-text: {$this->text_color};",
            "--mwf-sidebar-text: {$this->sidebar_text_color};",
            "--mwf-background: {$this->background_color};",
            "--mwf-border: {$this->border_color};",
            "--mwf-success: {$this->success_color};",
            "--mwf-warning: {$this->warning_color};",
            "--mwf-danger: {$this->danger_color};",
            "--mwf-font-heading: {$headingFont};",
            "--mwf-font-body: {$bodyFont};",
        ]);
    }
}

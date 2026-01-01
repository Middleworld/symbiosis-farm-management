<?php

namespace App\Http\ViewComposers;

use App\Models\BrandSetting;
use Illuminate\View\View;

class BrandingComposer
{
    /**
     * Bind branding data to the view.
     */
    public function compose(View $view): void
    {
        $branding = BrandSetting::active();
        
        // Inject CSS variables and custom CSS into the page
        $cssVariables = $branding->toCssVariables();
        $customCss = $branding->custom_css ?? '';
        
        $brandingCss = "<style>\n:root {\n{$cssVariables}\n}\n\n/* Custom Branding CSS */\n{$customCss}\n</style>";
        
        $view->with('branding', $branding);
        $view->with('brandingCss', $brandingCss);
    }
}
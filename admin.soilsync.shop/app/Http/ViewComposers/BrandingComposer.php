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
        $view->with('branding', $branding);
    }
}
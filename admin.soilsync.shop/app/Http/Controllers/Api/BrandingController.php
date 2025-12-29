<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BrandingController extends Controller
{
    /**
     * Get active branding settings
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Cache branding for 1 hour (3600 seconds)
        $branding = Cache::remember('active_branding', 3600, function () {
            $brand = BrandSetting::active();
            return $brand ? $brand->toApiArray() : null;
        });
        
        if (!$branding) {
            return response()->json([
                'success' => false,
                'message' => 'No active branding configuration found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $branding
        ]);
    }
    
    /**
     * Get CSS variables for the active branding
     * 
     * @return \Illuminate\Http\Response
     */
    public function cssVariables()
    {
        $css = Cache::remember('branding_css_variables', 3600, function () {
            $brand = BrandSetting::active();
            return $brand ? $brand->toCssVariables() : '';
        });
        
        return response($css)
            ->header('Content-Type', 'text/css')
            ->header('Cache-Control', 'public, max-age=3600');
    }
    
    /**
     * Clear branding cache (called after branding updates)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache()
    {
        Cache::forget('active_branding');
        Cache::forget('branding_css_variables');
        
        return response()->json([
            'success' => true,
            'message' => 'Branding cache cleared successfully'
        ]);
    }
}

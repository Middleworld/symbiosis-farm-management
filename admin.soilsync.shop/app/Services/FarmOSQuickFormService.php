<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Service for building farmOS Quick Form URLs with pre-populated data
 */
class FarmOSQuickFormService
{
    protected $farmOSBaseUrl;
    protected $authToken;

    public function __construct()
    {
        // Use reverse proxy URL for Quick Forms to avoid CORS issues
        $this->farmOSBaseUrl = config('app.url', config('services.farmos.url')) . '/farmos';
        $this->authToken = session('farmos_token'); // Get from session
    }

        /**
     * Build Quick Form URL for a succession planting
     */
    public function buildSuccessionFormUrl(array $successionData, string $logType = 'seeding'): string
    {
        // Simplified: just return FarmOS URL with parameters
        return $this->buildUnifiedQuickFormUrl($successionData);
    }

    /**
     * Get the base URL for different Quick Form types
     */
    protected function getQuickFormBaseUrl(string $logType): string
    {
        // Simplified: always use FarmOS native forms
        $farmOSBase = config('services.farmos.url', 'https://farmos.example-farm.com');
        return $farmOSBase . '/quick/' . $logType;
    }

    /**
     * Format succession data for farmOS Quick Form parameters
     */
    protected function formatParametersForFarmOS(array $successionData, string $logType): array
    {
        // Simplified: use the unified parameter format
        return $this->formatParametersForUnifiedForm($successionData);
    }

    /**
     * Generate planting quick form URL for a succession
     * Uses the unified /quick/planting form instead of separate seeding/transplanting/harvest forms
     */
    public function generateAllFormUrls(array $successionData): array
    {
        // Use farmOS native /quick/planting form
        // Cookie session sharing via $cookie_domain = '.soilsync.shop' in farmOS settings.php
        $farmOSBase = config('services.farmos.url', 'https://farmos.soilsync.shop');

        $params = [
            // Season (required by planting form)
            'seasons' => $successionData['season'] ?? date('Y') . ' Succession ' . ($successionData['succession_number'] ?? 1),
            
            // Crop/variety (required)
            'crops[0]' => $successionData['variety_name'] ?? $successionData['crop_name'] ?? '',
            
            // Enable all log types
            'log_types[seeding]' => 'seeding',
            'log_types[transplanting]' => 'transplanting',
            'log_types[harvest]' => 'harvest',
            
            // Seeding log details
            'seeding[date]' => $successionData['seeding_date'] ?? '',
            'seeding[location]' => $successionData['seeding_location'] ?? 'Greenhouse',
            'seeding[quantity][0][measure]' => 'count',
            'seeding[quantity][0][value]' => $successionData['quantity'] ?? '',
            'seeding[quantity][0][units]' => 'plants',
            'seeding[done]' => 0,
            
            // Transplanting log details
            'transplanting[date]' => $successionData['transplant_date'] ?? '',
            'transplanting[location]' => $successionData['bed_name'] ?? '',
            'transplanting[done]' => 0,
            
            // Harvest log details
            'harvest[date]' => $successionData['harvest_date'] ?? '',
            'harvest[done]' => 0,
        ];

        // Add plan parameter if specified (for crop plan integration)
        if (isset($successionData['plan_id'])) {
            $params['plan'] = $successionData['plan_id'];
        }

        // Single planting quick form URL instead of three separate URLs
        return [
            'planting' => $farmOSBase . '/quick/planting?' . http_build_query($params)
        ];
    }

    /**
     * Test if Quick Forms are accessible
     */
    public function testQuickFormAccess(): bool
    {
        try {
            // Test if our Laravel quick form routes are accessible
            $response = Http::get(url('/admin/farmos/quick/seeding'));

            if ($response->successful()) {
                return true;
            }

            // Fallback: test farmOS quick forms
            $response = Http::withToken($this->authToken)
                ->get($this->farmOSBaseUrl . '/quick/seeding');

            return $response->successful();
        } catch (\Exception $e) {
            \Log::warning('Quick Form access test failed: ' . $e->getMessage());
            return false;
        }
    }
}

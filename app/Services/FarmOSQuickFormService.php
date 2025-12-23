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
        $this->farmOSBaseUrl = config('app.url', 'https://admin.middleworldfarms.org') . '/farmos';
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
        $farmOSBase = config('services.farmos.url', 'https://farmos.middleworldfarms.org');
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
     * Generate URLs for all log types for a succession
     */
    public function generateAllFormUrls(array $successionData): array
    {
        // Generate URLs for our Laravel quick forms with pre-filled parameters
        $baseUrl = config('app.url', 'https://admin.middleworldfarms.org') . '/admin/farmos';

        $params = [
            'crop_name' => $successionData['crop_name'] ?? '',
            'variety_name' => $successionData['variety_name'] ?? '',
            'bed_name' => $successionData['bed_name'] ?? '',
            'quantity' => $successionData['quantity'] ?? '',
            'succession_number' => $successionData['succession_number'] ?? 1,
            'seeding_date' => $successionData['seeding_date'] ?? '',
            'transplant_date' => $successionData['transplant_date'] ?? '',
            'harvest_date' => $successionData['harvest_date'] ?? '',
            'season' => $successionData['season'] ?? date('Y') . ' Succession'
        ];

        return [
            'seeding' => $baseUrl . '/quick/seeding?' . http_build_query($params),
            'transplanting' => $baseUrl . '/quick/transplant?' . http_build_query($params),
            'harvest' => $baseUrl . '/quick/harvest?' . http_build_query($params)
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

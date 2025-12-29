<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FarmOSProxyController extends Controller
{
    protected $farmOSBaseUrl = 'https://farmos.middleworldfarms.org';

    /**
     * Proxy FarmOS seeding Quick Form with pre-filled data
     */
    public function proxySeedingForm(Request $request)
    {
        return $this->proxyForm('seeding', $request);
    }

    /**
     * Proxy FarmOS transplant Quick Form with pre-filled data
     */
    public function proxyTransplantForm(Request $request)
    {
        return $this->proxyForm('transplant', $request);
    }

    /**
     * Proxy FarmOS harvest Quick Form with pre-filled data
     */
    public function proxyHarvestForm(Request $request)
    {
        return $this->proxyForm('harvest', $request);
    }

    /**
     * Generic proxy method for FarmOS Quick Forms
     */
    protected function proxyForm(string $formType, Request $request)
    {
        try {
            // Determine the correct FarmOS quick form URL
            $farmOSUrl = $this->getFarmOSQuickFormUrl($formType);

            // Add any query parameters from the request
            if ($request->hasAny(['crop_name', 'variety_name', 'bed_name', 'quantity', 'season'])) {
                $params = $request->only(['crop_name', 'variety_name', 'bed_name', 'quantity', 'season']);
                $queryString = http_build_query($params);
                $farmOSUrl .= '?' . $queryString;
            }

            // For iframe embedding, we need to handle CORS and authentication
            // Since the user is logged into FarmOS, we'll redirect to the FarmOS URL
            // This allows the iframe to load with proper authentication

            return redirect($farmOSUrl);

        } catch (\Exception $e) {
            Log::error('FarmOS proxy error: ' . $e->getMessage());
            return response()->view('errors.farmos-proxy', [
                'error' => 'Unable to load FarmOS form',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the correct FarmOS quick form URL for the given form type
     */
    protected function getFarmOSQuickFormUrl(string $formType): string
    {
        // FarmOS 3 quick form URLs - these may need adjustment based on actual FarmOS installation
        $urls = [
            'seeding' => '/asset/add/planting/quick/seeding',
            'transplant' => '/asset/add/planting/quick/transplant',
            'harvest' => '/asset/add/log/quick/harvest'
        ];

        $path = $urls[$formType] ?? '/asset/add/planting';

        return $this->farmOSBaseUrl . $path;
    }
}

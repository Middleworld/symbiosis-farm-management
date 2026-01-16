<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MetOfficeAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetOfficeController extends Controller
{
    protected $metOfficeAuth;

    public function __construct()
    {
        $this->metOfficeAuth = MetOfficeAuthService::getInstance();
    }

    /**
     * Redirect to Met Office OAuth2 authorization
     */
    public function redirectToProvider()
    {
        try {
            $authUrl = $this->metOfficeAuth->getAuthorizationUrl();

            Log::info('Redirecting to Met Office OAuth2 authorization', [
                'auth_url' => $authUrl
            ]);

            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to generate Met Office auth URL', [
                'error' => $e->getMessage()
            ]);

            return redirect('/admin')->with('error', 'Failed to initiate Met Office authentication: ' . $e->getMessage());
        }
    }

    /**
     * Handle OAuth2 callback from Met Office
     */
    public function handleProviderCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            $error = $request->get('error');
            $errorDescription = $request->get('error_description');

            if ($error) {
                Log::error('Met Office OAuth2 error', [
                    'error' => $error,
                    'error_description' => $errorDescription
                ]);

                return redirect('/admin')->with('error', 'Met Office authentication failed: ' . $errorDescription);
            }

            if (!$code) {
                Log::error('No authorization code received from Met Office');
                return redirect('/admin')->with('error', 'No authorization code received from Met Office');
            }

            // Exchange code for tokens
            $tokenData = $this->metOfficeAuth->exchangeCodeForToken($code);

            Log::info('Met Office OAuth2 authentication successful');

            return redirect('/admin')->with('success', 'Met Office authentication successful! Weather maps should now work.');

        } catch (\Exception $e) {
            Log::error('Met Office OAuth2 callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect('/admin')->with('error', 'Met Office authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Get authentication status
     */
    public function getAuthStatus()
    {
        try {
            $isAuthorized = $this->metOfficeAuth->isAuthorized();
            $timeRemaining = $this->metOfficeAuth->getTokenTimeRemaining();

            return response()->json([
                'authorized' => $isAuthorized,
                'time_remaining' => $timeRemaining,
                'expires_at' => $timeRemaining ? now()->addSeconds($timeRemaining)->toISOString() : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'authorized' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Verify API requests from WooCommerce plugin
 * 
 * Checks for X-MWF-API-Key header and validates against configured key
 */
class VerifyWooCommerceApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-MWF-API-Key');
        $expectedKey = env('MWF_API_KEY', config('services.mwf_api.key'));

        if (empty($apiKey)) {
            Log::warning('API: Missing API key in request', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API key is required'
            ], 401);
        }

        if ($apiKey !== $expectedKey) {
            Log::warning('API: Invalid API key attempted', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'provided_key' => substr($apiKey, 0, 10) . '...' // Log only first 10 chars for security
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        Log::debug('API: Request authenticated successfully', [
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        return $next($request);
    }
}

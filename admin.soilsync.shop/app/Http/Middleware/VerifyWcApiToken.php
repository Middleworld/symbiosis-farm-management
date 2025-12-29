<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWcApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-WC-API-Key');
        
        // Check if API key is provided
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required'
            ], 401);
        }
        
        // Get valid API key from environment
        $validApiKey = config('services.woocommerce.api_key');
        
        // Validate API key
        if ($apiKey !== $validApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 403);
        }
        
        return $next($request);
    }
}

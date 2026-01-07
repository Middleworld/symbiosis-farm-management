<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * CORS Middleware for API endpoints
 * 
 * Note: This is no longer used for SSO (iframe architecture doesn't need CORS).
 * Kept for potential future API CORS needs.
 */
class SsoCorsMiddleware
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
        $allowedOrigins = [
            'https://fieldkit.soilsync.shop',
            'http://localhost:3000',
        ];

        $origin = $request->header('Origin');

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', in_array($origin, $allowedOrigins) ? $origin : '')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Accept, Content-Type, Authorization')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        // Process the request
        $response = $next($request);

        // Add CORS headers to response if origin is allowed
        if (in_array($origin, $allowedOrigins)) {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Credentials', 'true');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Accept, Content-Type, Authorization');
        }

        return $response;
    }
}

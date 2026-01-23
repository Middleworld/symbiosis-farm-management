<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRosApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.ros.api_key', '');

        if (!$expected) {
            return $next($request);
        }

        $provided = $request->header('X-ROS-API-Key') ?? $request->query('api_key');

        if (!$provided || !hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

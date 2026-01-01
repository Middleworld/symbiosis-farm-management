<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SkipCsrfForOauth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('oauth/*')) {
            // Skip CSRF verification for OAuth routes by adding a valid token
            $request->merge(['_token' => csrf_token()]);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PosAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if POS user is authenticated
        $posAuthenticated = Session::get('pos_authenticated', false);
        $posUser = Session::get('pos_user');

        if (!$posAuthenticated || !$posUser) {
            \Log::warning('Unauthorized POS access attempt', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'user_agent' => $request->userAgent(),
                'pos_authenticated' => $posAuthenticated,
                'pos_user' => $posUser
            ]);

            // For AJAX requests, return JSON error
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Redirect to POS login
            return redirect()->route('pos.login')->with('error', 'Please log in to access the POS system.');
        }

        // Update last activity timestamp
        Session::put('last_activity', now());

        return $next($request);
    }
}

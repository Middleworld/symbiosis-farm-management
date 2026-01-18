<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Debug logging for AJAX requests
        if ($request->ajax()) {
            \Log::info('AJAX request to admin area', [
                'url' => $request->url(),
                'session_id' => Session::getId(),
                'admin_authenticated' => Session::get('admin_authenticated', false),
                'has_session_cookie' => $request->hasCookie(config('session.cookie')),
                'user_agent' => $request->userAgent()
            ]);
        }

        // Check if admin is authenticated
        if (!Session::get('admin_authenticated', false)) {
            // Store the intended URL for redirect after login
            Session::put('url.intended', $request->url());

            // Log unauthorized access attempt
            \Log::warning('Unauthorized admin access attempt', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'user_agent' => $request->userAgent()
            ]);

            // For AJAX requests, return JSON error instead of redirect
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Redirect to admin login
            return redirect()->route('admin.login')->with('error', 'Please log in to access the admin panel.');
        }

        // Check for idle timeout (30 minutes of inactivity)
        $lastActivity = Session::get('admin_last_activity');
        $idleTimeout = 30 * 60; // 30 minutes in seconds
        
        if ($lastActivity && now()->diffInSeconds($lastActivity) > $idleTimeout) {
            // Session has been idle too long, force logout
            Session::forget(['admin_authenticated', 'admin_last_activity']);
            
            // Log idle timeout
            \Log::info('Admin session expired due to idle timeout', [
                'ip' => $request->ip(),
                'last_activity' => $lastActivity,
                'idle_seconds' => now()->diffInSeconds($lastActivity)
            ]);

            // For AJAX requests, return JSON error
            if ($request->ajax()) {
                return response()->json(['error' => 'Session expired due to inactivity'], 401);
            }

            // Redirect to admin login with timeout message
            return redirect()->route('admin.login')->with('error', 'Your session has expired due to inactivity. Please log in again.');
        }

        // Update last activity timestamp
        Session::put('admin_last_activity', now());

        return $next($request);
    }
}
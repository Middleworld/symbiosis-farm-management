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

        // If already POS authenticated, allow access
        if ($posAuthenticated && $posUser) {
            // Update last activity timestamp
            Session::put('last_activity', now());
            return $next($request);
        }

        // Check if user is authenticated as admin/super admin
        $adminAuthenticated = Session::get('admin_authenticated', false);
        $adminUser = Session::get('admin_user');
        $user = Session::get('user');

        // Allow super admins and admins with POS access to bypass POS login
        if ($adminAuthenticated && $adminUser) {
            // Check if this admin user has POS staff privileges
            $adminUsers = config('admin_users.users', []);
            $currentUser = null;

            foreach ($adminUsers as $configUser) {
                if ($configUser['email'] === $adminUser['email'] && $configUser['active']) {
                    $currentUser = $configUser;
                    break;
                }
            }

            // If user is super admin or has POS staff privileges, auto-authenticate for POS
            if ($currentUser && ($currentUser['role'] === 'super_admin' || (isset($currentUser['is_pos_staff']) && $currentUser['is_pos_staff']))) {
                // Set POS authentication session
                Session::put('pos_authenticated', true);
                Session::put('pos_user', [
                    'name' => $adminUser['name'],
                    'email' => $adminUser['email'],
                    'role' => $currentUser['role'],
                    'is_pos_staff' => true,
                    'login_time' => $adminUser['login_time'] ?? now(),
                    'ip_address' => $adminUser['ip_address'] ?? $request->ip(),
                    'auto_authenticated' => true // Flag to indicate this was auto-authenticated
                ]);

                // Also set the standard authenticated flag for compatibility
                Session::put('authenticated', true);
                Session::put('user', Session::get('pos_user'));

                \Log::info('Super admin auto-authenticated for POS access', [
                    'email' => $adminUser['email'],
                    'role' => $currentUser['role'],
                    'ip' => $request->ip(),
                    'session_id' => Session::getId()
                ]);

                // Update last activity timestamp
                Session::put('last_activity', now());

                return $next($request);
            }
        }

        // If we get here, user is not authorized for POS access
        \Log::warning('Unauthorized POS access attempt', [
            'ip' => $request->ip(),
            'url' => $request->url(),
            'user_agent' => $request->userAgent(),
            'pos_authenticated' => $posAuthenticated,
            'pos_user' => $posUser,
            'admin_authenticated' => $adminAuthenticated,
            'admin_user' => $adminUser
        ]);

        // For AJAX requests, return JSON error
        if ($request->ajax()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Redirect to POS login
        return redirect()->route('pos.login')->with('error', 'Please log in to access the POS system.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Simplified SSO Controller - Iframe-Only Architecture
 * 
 * This controller handles basic authentication for the admin portal.
 * FarmOS and FieldKit are embedded via iframes and share farmOS session cookies.
 * No complex OAuth token exchange needed - just login once to farmOS and all iframes work.
 */
class SsoController extends Controller
{
    /**
     * Show login form or redirect authenticated users to dashboard
     */
    public function login(Request $request)
    {
        // Check if this is after logout
        if ($request->get('after_logout') === '1') {
            Auth::logout();
            return view('sso.login', ['after_logout' => true]);
        }

        // If already authenticated, redirect to admin dashboard
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        // Show login form
        return view('sso.login');
    }

    /**
     * Authenticate user credentials
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Set admin authentication flag for middleware
            session(['admin_authenticated' => true]);

            // Redirect to admin dashboard
            return redirect('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }

    /**
     * Logout user and clear session
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        session()->forget('admin_authenticated');
        
        return redirect('/sso/login?after_logout=1');
    }

    /**
     * Simple dashboard - just redirect to admin dashboard
     * FarmOS and FieldKit are accessed via sidebar iframes
     */
    public function dashboard(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/sso/login');
        }

        // Redirect to main admin dashboard
        return redirect('/dashboard');
    }
}

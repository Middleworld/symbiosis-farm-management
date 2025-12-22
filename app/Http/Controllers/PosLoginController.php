<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class PosLoginController extends Controller
{
    /**
     * Show POS login form
     */
    public function showLoginForm()
    {
        // If already logged in as POS staff, redirect to dashboard
        if (Session::get('pos_authenticated')) {
            return redirect()->route('pos.dashboard');
        }

        return view('pos.login');
    }

    /**
     * Handle POS login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Get admin users from config
        $adminUsers = config('admin_users.users', []);

        // Check against configured POS users only
        foreach ($adminUsers as $user) {
            if (!$user['active']) {
                continue;
            }

            // Only allow POS staff to login here
            $isPosStaff = isset($user['is_pos_staff']) && $user['is_pos_staff'];
            if (!$isPosStaff) {
                continue;
            }

            if ($request->email === $user['email'] && $request->password === $user['password']) {
                // Set POS-specific session
                Session::put('pos_authenticated', true);
                Session::put('pos_user', [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'is_pos_staff' => true,
                    'login_time' => now(),
                    'ip_address' => $request->ip()
                ]);

                // Also set the standard authenticated flag for compatibility
                Session::put('authenticated', true);
                Session::put('user', Session::get('pos_user'));

                Log::info('POS staff login successful (separate system)', [
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'ip' => $request->ip(),
                    'session_id' => Session::getId()
                ]);

                return redirect()->route('pos.dashboard');
            }
        }

        // Login failed
        Log::warning('POS login attempt failed', [
            'email' => $request->email,
            'ip' => $request->ip()
        ]);

        return back()->withErrors(['Invalid email or password. Only POS staff can login here.']);
    }

    /**
     * Logout POS user
     */
    public function logout(Request $request)
    {
        $user = Session::get('pos_user');
        
        Log::info('POS staff logout', [
            'email' => $user['email'] ?? 'unknown',
            'session_id' => Session::getId()
        ]);

        Session::forget('pos_authenticated');
        Session::forget('pos_user');
        Session::forget('authenticated');
        Session::forget('user');
        Session::flush();

        return redirect()->route('pos.login')->with('success', 'You have been logged out.');
    }
}

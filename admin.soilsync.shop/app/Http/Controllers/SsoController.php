<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class SsoController extends Controller
{
    private $jwtSecret;

    public function __construct()
    {
        $this->jwtSecret = config('app.key');
    }

    public function login(Request $request)
    {
        // Check if this is after logout - force logout and show login form
        if ($request->get('after_logout') === '1') {
            Auth::logout();
            return view('sso.login', [
                'redirect' => $request->get('redirect', '/'),
                'after_logout' => true
            ]);
        }

        // If already authenticated, redirect back with JWT token
        if (Auth::check()) {
            return $this->redirectBackWithJwt();
        }

        // Show login form
        return view('sso.login', [
            'redirect' => $request->get('redirect', '/')
        ]);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return $this->redirectBackWithJwt();
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }

    private function redirectBackWithJwt()
    {
        $user = Auth::user();
        
        // Create JWT token with user data
        $payload = [
            'iss' => config('app.url'), // Issuer
            'aud' => 'wordpress', // Audience
            'iat' => time(), // Issued at
            'exp' => time() + 3600, // Expires in 1 hour
            'sub' => $user->id, // Subject (user ID)
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name
            ]
        ];
        
        $jwt = JWT::encode($payload, $this->jwtSecret, 'HS256');
        
        // Redirect back to WordPress with JWT token
        $redirectUrl = 'https://soilsync.shop/wp-admin/admin-ajax.php?action=mwf_sso_callback&token=' . urlencode($jwt);
        
        return redirect($redirectUrl);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Return a simple response for the background logout request from WordPress
        return response('Logged out', 200);
    }
}

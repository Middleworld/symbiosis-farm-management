<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Services\FarmOSAuthService;

class SsoController extends Controller
{
    private $jwtSecret;

    public function __construct()
    {
        $this->jwtSecret = config('app.key');
    }

    public function login(Request $request)
    {
        $redirectUrl = $request->get('redirect', '/');

        // Check if this is after logout - force logout and show login form
        if ($request->get('after_logout') === '1') {
            Auth::logout();
            return view('sso.login', [
                'redirect' => $redirectUrl,
                'after_logout' => true
            ]);
        }

        // Prevent redirect loops: if redirecting to FarmOS and user is not authenticated,
        // redirect to WordPress homepage instead to prevent FarmOS -> SSO -> FarmOS loops
        if (!Auth::check() && str_contains($redirectUrl, 'farmos.soilsync.shop')) {
            return redirect('https://soilsync.shop');
        }

        // Store redirect URL in session if provided
        if ($request->has('redirect')) {
            session(['sso_redirect_url' => $request->get('redirect')]);
        }

        // If already authenticated, redirect back with JWT token
        if (Auth::check()) {
            return $this->redirectBackWithJwt();
        }

        // Show login form
        return view('sso.login', [
            'redirect' => $redirectUrl
        ]);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Also authenticate with farmOS and store tokens in session
            $this->authenticateWithFarmOS();

            // Set admin authentication flag for admin middleware
            session(['admin_authenticated' => true]);

            // Show post-login dashboard instead of immediate redirect
            return view('sso.dashboard', [
                'user' => Auth::user(),
                'redirect' => session('sso_redirect_url')
            ]);
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }    private function authenticateWithFarmOS()
    {
        try {
            $authService = FarmOSAuthService::getInstance();
            $token = $authService->getAccessToken(true); // Force refresh to get fresh token
            
            // Store farmOS OAuth token in session for Field Kit access
            session([
                'farmos_oauth_token' => $token,
                'farmos_token_expiry' => now()->addMinutes(55)->toDateTimeString(), // Tokens are valid for 1 hour
                'farmos_host' => config('farmos.url', 'https://farmos.middleworldfarms.org')
            ]);
            
            \Log::info('FarmOS OAuth token stored in session for SSO');
        } catch (\Exception $e) {
            \Log::warning('Failed to authenticate with farmOS during SSO: ' . $e->getMessage());
            // Don't fail SSO if farmOS auth fails - continue with WordPress/Laravel auth
        }
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
        
        // Check if there's a custom redirect URL (e.g., from FarmOS)
        $redirectUrl = session('sso_redirect_url') ?: 'https://soilsync.shop/wp-admin/admin-ajax.php?action=mwf_sso_callback&token=' . urlencode($jwt);
        
        // Clear the redirect URL from session after use
        session()->forget('sso_redirect_url');
        
        return redirect($redirectUrl);
    }

    public function generateJwtForUser($user)
    {
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
        
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function logout(Request $request)
    {
        // Clear farmOS tokens from session and cache
        session()->forget(['farmos_oauth_token', 'farmos_token_expiry', 'farmos_host']);
        
        // Clear FarmOS auth service cache
        $farmOSAuth = \App\Services\FarmOSAuthService::getInstance();
        $farmOSAuth->logout();
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Return a simple response for the background logout request from WordPress
        return response('Logged out', 200);
    }

    /**
     * Endpoint for Field Kit to get pre-authenticated farmOS tokens
     */
    public function getFarmOSTokens(Request $request)
    {
        // Only allow requests from Field Kit domain
        $allowedOrigins = [
            'https://fieldkit.soilsync.shop',
            'https://feildkit.soilsync.shop', // With the typo as configured in Plesk
            'http://localhost:3000', // For development
        ];
        
        $origin = $request->header('Origin');
        if (!in_array($origin, $allowedOrigins)) {
            return response()->json(['error' => 'Unauthorized origin'], 403);
        }
        
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        // Check if we have valid farmOS tokens in session
        $token = session('farmos_oauth_token');
        $expiry = session('farmos_token_expiry');
        $host = session('farmos_host');
        
        if (!$token || !$expiry || !$host) {
            return response()->json(['error' => 'No farmOS tokens available'], 404);
        }
        
        // Check if token is still valid (with 5 minute buffer)
        if (now()->greaterThanOrEqualTo(\Carbon\Carbon::parse($expiry)->subMinutes(5))) {
            // Token expired, refresh it
            try {
                $authService = FarmOSAuthService::getInstance();
                $newToken = $authService->getAccessToken(true);
                
                session([
                    'farmos_oauth_token' => $newToken,
                    'farmos_token_expiry' => now()->addMinutes(55)->toDateTimeString(),
                ]);
                
                $token = $newToken;
            } catch (\Exception $e) {
                \Log::warning('Failed to refresh farmOS token for Field Kit: ' . $e->getMessage());
                return response()->json(['error' => 'Failed to refresh token'], 500);
            }
        }
        
        return response()->json([
            'host' => $host,
            'token' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => config('farmos.oauth_scope', 'farmos_restws_access')
            ]
        ])->header('Access-Control-Allow-Origin', $origin)
          ->header('Access-Control-Allow-Credentials', 'true');
    }

    public function dashboard(Request $request)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect('/sso/login');
        }

        return view('sso.dashboard', [
            'user' => Auth::user(),
            'redirect' => session('sso_redirect_url')
        ]);
    }
}

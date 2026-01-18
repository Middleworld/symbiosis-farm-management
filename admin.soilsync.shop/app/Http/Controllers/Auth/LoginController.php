<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;

class LoginController extends Controller
{
    protected WpApiService $wpApiService;

    public function __construct(WpApiService $wpApiService)
    {
        $this->wpApiService = $wpApiService;
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Get WordPress email for admin authentication
     * Maps Laravel admin emails to their corresponding WordPress emails
     */
    private function getWordPressEmailForAdmin($adminEmail)
    {
        // Email mapping for admin users - load from config or use default mapping
        $emailMapping = config('admin_users.wordpress_email_mapping', [
            'martin@middleworldfarms.org' => 'middleworldfarms@gmail.com',
            // Add more mappings here if needed in the future
            // 'other-admin@example-farm.com' => 'their-wp-email@domain.com',
        ]);
        
        return $emailMapping[$adminEmail] ?? $adminEmail;
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // First try database-backed admin users
        $dbUser = User::where('email', $request->email)->first();
        if ($dbUser && Hash::check($request->password, $dbUser->password)) {
            Session::put('authenticated', true);
            Session::put('user', [
                'name' => $dbUser->name,
                'email' => $dbUser->email,
                'role' => 'admin',
                'is_admin' => true,
                'is_pos_staff' => false,
                'login_time' => now(),
                'ip_address' => $request->ip()
            ]);

            Session::put('admin_authenticated', true);
            Session::put('admin_user', [
                'name' => $dbUser->name,
                'email' => $dbUser->email,
                'role' => 'admin',
                'login_time' => now(),
                'ip_address' => $request->ip()
            ]);

            Auth::login($dbUser);

            $wordpressEmail = $this->getWordPressEmailForAdmin($dbUser->email);
            $wpAuthResult = $this->wpApiService->authenticateAdminWithWordPress($wordpressEmail, $dbUser->name);

            if ($wpAuthResult['success']) {
                Session::put('wp_authenticated', true);
                Session::put('wp_integration_status', 'authenticated');
                Session::put('wp_admin_url', $wpAuthResult['wp_admin_url'] ?? config('services.customer_site.url') . '/wp-admin/');
                Session::put('wp_user', $wpAuthResult['wp_user'] ?? null);
                Session::put('wp_auth_cookie', $wpAuthResult['wp_auth_cookie'] ?? null);

                Log::info('Admin login with WordPress authentication successful', [
                    'admin_email' => $dbUser->email,
                    'wordpress_email' => $wordpressEmail,
                    'role' => 'admin',
                    'wp_authentication' => 'success',
                    'wp_user_id' => $wpAuthResult['wp_user']['id'] ?? null,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            } else {
                Log::warning('WordPress authentication failed during admin login', [
                    'admin_email' => $dbUser->email,
                    'wordpress_email' => $wordpressEmail,
                    'error' => $wpAuthResult['error'] ?? 'Authentication failed'
                ]);

                Session::put('wp_authenticated', false);
                Session::put('wp_integration_status', 'failed');
                Session::put('wp_admin_url', config('services.customer_site.url') . '/wp-admin/');
            }

            Log::info('Admin login successful (database user)', [
                'email' => $dbUser->email,
                'role' => 'admin',
                'wp_integrated' => $wpAuthResult['success'] ?? false,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $welcomeMessage = 'Welcome to MWF Admin Dashboard';
            if ($wpAuthResult['success']) {
                $welcomeMessage .= ' - WordPress authentication successful!';
            } else {
                $welcomeMessage .= ' - WordPress authentication failed (manual login required)';
            }

            return redirect()->intended(route('admin.dashboard'))->with('success', $welcomeMessage);
        }

        // Get admin users from config
        $adminUsers = config('admin_users.users', []);

        // Check against configured admin users
        foreach ($adminUsers as $user) {
            if (!$user['active']) {
                continue;
            }

            if (strtolower($request->email) === strtolower($user['email']) && $request->password === $user['password']) {
                // Store user session based on role
                Session::put('authenticated', true);
                Session::put('user', [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'is_admin' => $user['is_admin'] ?? false,
                    'is_pos_staff' => $user['is_pos_staff'] ?? false,
                    'login_time' => now(),
                    'ip_address' => $request->ip()
                ]);

                // Handle different user types
                $isPosStaff = isset($user['is_pos_staff']) && $user['is_pos_staff'];
                $isAdmin = isset($user['is_admin']) && $user['is_admin'];
                
                if ($isPosStaff && !$isAdmin) {
                    // Authenticate with Laravel Auth for OAuth compatibility
                    $laravelUser = \App\Models\User::firstOrCreate(
                        ['email' => $user['email']],
                        [
                            'name' => $user['name'],
                            'password' => bcrypt($user['password']),
                        ]
                    );
                    Auth::login($laravelUser);
                    
                    // POS-only staff - redirect to POS interface
                    // Regenerate session to ensure it persists through proxy
                    Session::regenerate();
                    
                    Log::info('POS staff login successful with session regeneration', [
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'session_id' => Session::getId()
                    ]);

                    return redirect()->route('pos.dashboard')->with('success', 'Welcome to POS System, ' . $user['name'] . '!');
                }

                // Admin users (including those who can also use POS)
                Session::put('admin_authenticated', true);
                Session::put('admin_user', [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'login_time' => now(),
                    'ip_address' => $request->ip()
                ]);

                // Authenticate with Laravel Auth for OAuth compatibility
                $laravelUser = \App\Models\User::firstOrCreate(
                    ['email' => $user['email']],
                    [
                        'name' => $user['name'],
                        'password' => bcrypt($user['password']),
                    ]
                );
                Auth::login($laravelUser);

                // Regenerate session to ensure CSRF token is fresh
                // Session::regenerate(); // Removed to avoid CSRF issues

                // Authenticate admin with WordPress automatically using email mapping
                $wordpressEmail = $this->getWordPressEmailForAdmin($user['email']);
                $wpAuthResult = $this->wpApiService->authenticateAdminWithWordPress($wordpressEmail, $user['name']);
                
                if ($wpAuthResult['success']) {
                    // Store WordPress session info
                    Session::put('wp_authenticated', true);
                    Session::put('wp_integration_status', 'authenticated');
                    Session::put('wp_admin_url', $wpAuthResult['wp_admin_url'] ?? config('services.customer_site.url') . '/wp-admin/');
                    Session::put('wp_user', $wpAuthResult['wp_user'] ?? null);
                    Session::put('wp_auth_cookie', $wpAuthResult['wp_auth_cookie'] ?? null);
                    
                    // Log successful WordPress authentication
                    Log::info('Admin login with WordPress authentication successful', [
                        'admin_email' => $user['email'],
                        'wordpress_email' => $wordpressEmail,
                        'role' => $user['role'],
                        'wp_authentication' => 'success',
                        'wp_user_id' => $wpAuthResult['wp_user']['id'] ?? null,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);
                } else {
                    // Log WordPress authentication failure but continue with admin login
                    Log::warning('WordPress authentication failed during admin login', [
                        'admin_email' => $user['email'],
                        'wordpress_email' => $wordpressEmail,
                        'error' => $wpAuthResult['error'] ?? 'Authentication failed'
                    ]);
                    
                    // Store partial session info for manual login
                    Session::put('wp_authenticated', false);
                    Session::put('wp_integration_status', 'failed');
                    Session::put('wp_admin_url', config('services.customer_site.url') . '/wp-admin/');
                }

                // Log the admin login (always successful)
                Log::info('Admin login successful', [
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'wp_integrated' => $wpAuthResult['success'] ?? false,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                $welcomeMessage = $user['role'] === 'super_admin' ? 
                    'Welcome back, ' . $user['name'] . '! (Super Admin)' : 
                    'Welcome to MWF Admin Dashboard';
                
                // Add WordPress integration status to welcome message
                if ($wpAuthResult['success']) {
                    $welcomeMessage .= ' - WordPress authentication successful!';
                } else {
                    $welcomeMessage .= ' - WordPress authentication failed (manual login required)';
                }

                return redirect()->intended(route('admin.dashboard'))->with('success', $welcomeMessage);
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    /**
     * Handle logout for both admin and POS users
     */
    public function logout(Request $request)
    {
        $user = Session::get('user');
        $adminUser = Session::get('admin_user');
        $wpAuthenticated = Session::get('wp_authenticated', false);
        
        // Log logout with user type
        if ($user && isset($user['is_pos_staff']) && $user['is_pos_staff'] && !$user['is_admin']) {
            Log::info('POS staff logout', [
                'email' => $user['email'] ?? 'unknown',
                'role' => $user['role'] ?? 'unknown',
                'session_duration' => $user && isset($user['login_time']) && $user['login_time'] ? 
                    now()->diffInMinutes($user['login_time']) . ' minutes' : 'unknown'
            ]);
        } else {
            Log::info('Admin logout', [
                'email' => $adminUser['email'] ?? 'unknown',
                'wp_integrated' => $wpAuthenticated,
                'session_duration' => $adminUser && isset($adminUser['login_time']) && $adminUser['login_time'] ? 
                    now()->diffInMinutes($adminUser['login_time']) . ' minutes' : 'unknown'
            ]);
        }

        // Clear all sessions
        Auth::logout();
        Session::forget('authenticated');
        Session::forget('user');
        Session::forget('admin_authenticated');
        Session::forget('admin_user');
        Session::forget('wp_authenticated');
        Session::forget('wp_user');
        Session::forget('wp_admin_url');
        Session::invalidate();
        Session::regenerateToken();

        return redirect()->to(url('/admin/login'))->with('message', 'You have been logged out successfully.');
    }

    /**
     * Check if WordPress user has admin privileges
     */
    private function isWPUserAdmin($user)
    {
        // Check if user has administrator role
        $capabilities = $user['capabilities'] ?? '';
        return str_contains($capabilities, 'administrator') || 
               str_contains($capabilities, 'manage_options');
    }

    /**
     * Get current admin user
     */
    public static function getAdminUser()
    {
        return Session::get('admin_user');
    }

    /**
     * Check if current user is authenticated admin
     */
    public static function isAdminAuthenticated()
    {
        return Session::get('admin_authenticated', false);
    }

    /**
     * Retry WordPress authentication for current admin session
     */
    public function retryWordPressAuth(Request $request)
    {
        if (!Session::get('admin_authenticated', false)) {
            return response()->json(['success' => false, 'error' => 'Not authenticated as admin']);
        }
        
        $adminUser = Session::get('admin_user');
        if (!$adminUser) {
            return response()->json(['success' => false, 'error' => 'Admin user data not found']);
        }
        
        try {
            $wpAuthResult = $this->wpApiService->authenticateAdminWithWordPress($adminUser['email'], $adminUser['name']);
            
            if ($wpAuthResult['success']) {
                // Update session with WordPress data
                Session::put('wp_authenticated', true);
                Session::put('wp_user', $wpAuthResult['wp_user'] ?? null);
                Session::put('wp_admin_url', $wpAuthResult['wp_admin_url'] ?? null);
                
                Log::info('WordPress authentication retry successful', [
                    'email' => $adminUser['email'],
                    'wp_user_id' => $wpAuthResult['wp_user']['id'] ?? null
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'WordPress authentication successful',
                    'wp_admin_url' => $wpAuthResult['wp_admin_url'] ?? null
                ]);
            } else {
                Log::warning('WordPress authentication retry failed', [
                    'email' => $adminUser['email'],
                    'error' => $wpAuthResult['error'] ?? 'Unknown error'
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => $wpAuthResult['error'] ?? 'WordPress authentication failed'
                ]);
            }
        } catch (Exception $e) {
            Log::error('WordPress authentication retry exception', [
                'email' => $adminUser['email'],
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Authentication error: ' . $e->getMessage()
            ]);
        }
    }

}
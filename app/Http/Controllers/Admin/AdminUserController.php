<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use App\Services\FarmOSApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Exception;

class AdminUserController extends Controller
{
    protected WpApiService $wpApiService;
    protected FarmOSApi $farmosApi;

    public function __construct(WpApiService $wpApiService, FarmOSApi $farmosApi)
    {
        $this->wpApiService = $wpApiService;
        $this->farmosApi = $farmosApi;
    }

    /**
     * Display list of admin users
     */
    public function index()
    {
        $users = config('admin_users.users', []);
        
        // Convert to collection for easier handling
        $users = collect($users)->map(function($user, $index) {
            $user['index'] = $index;
            return $user;
        });
        
        return view('admin.admin-users.index', compact('users'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.admin-users.create');
    }

    /**
     * Store new admin user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,super_admin,pos_staff',
            'is_admin' => 'boolean',
            'is_webdev' => 'boolean',
            'is_pos_staff' => 'boolean',
            'wordpress_email' => 'nullable|email',
        ]);

        // Check if email already exists
        $users = config('admin_users.users', []);
        if (collect($users)->contains('email', $validated['email'])) {
            return back()->withErrors(['email' => 'This email is already registered.'])->withInput();
        }

        // Prepare new user data
        $newUser = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Stored as plain text in config (as per your current setup)
            'role' => $validated['role'],
            'is_admin' => $request->has('is_admin'),
            'is_webdev' => $request->has('is_webdev'),
            'is_pos_staff' => $request->has('is_pos_staff'),
            'created_at' => now()->toDateString(),
            'active' => true,
        ];

        $wordpressEmail = $validated['wordpress_email'] ?? $validated['email'];
        $results = [
            'user_created' => false,
            'wordpress_created' => false,
            'farmos_created' => false,
            'errors' => [],
        ];

        try {
            // Create WordPress user
            $wpResult = $this->createWordPressUser(
                $wordpressEmail,
                $validated['name'],
                $validated['password']
            );
            
            if ($wpResult['success']) {
                $results['wordpress_created'] = true;
                Log::info('WordPress user created', ['email' => $wordpressEmail]);
            } else {
                $results['errors']['wordpress'] = $wpResult['error'] ?? 'Failed to create WordPress user';
                Log::warning('WordPress user creation failed', ['error' => $wpResult['error'] ?? 'Unknown']);
            }
        } catch (Exception $e) {
            $results['errors']['wordpress'] = $e->getMessage();
            Log::error('WordPress user creation exception', ['error' => $e->getMessage()]);
        }

        try {
            // Create FarmOS user
            $farmosResult = $this->createFarmOSUser(
                $validated['email'],
                $validated['name'],
                $validated['password']
            );
            
            if ($farmosResult['success']) {
                $results['farmos_created'] = true;
                Log::info('FarmOS user created', ['email' => $validated['email']]);
            } else {
                $results['errors']['farmos'] = $farmosResult['error'] ?? 'Failed to create FarmOS user';
                Log::warning('FarmOS user creation failed', ['error' => $farmosResult['error'] ?? 'Unknown']);
            }
        } catch (Exception $e) {
            $results['errors']['farmos'] = $e->getMessage();
            Log::error('FarmOS user creation exception', ['error' => $e->getMessage()]);
        }

        // Add user to config file
        $configPath = config_path('admin_users.php');
        $configContent = file_get_contents($configPath);
        
        // Find the users array and add new user
        $userString = $this->generateUserConfigString($newUser);
        
        // Insert before the closing bracket of users array
        $pattern = '/(\s*\],\s*\/\*\s*\|--------------------------------------------------------------------------)/';
        $replacement = ",\n" . $userString . "\n    ],$1";
        $newConfigContent = preg_replace($pattern, $replacement, $configContent);
        
        if ($newConfigContent && file_put_contents($configPath, $newConfigContent)) {
            $results['user_created'] = true;
            
            // Add WordPress email mapping if different
            if ($wordpressEmail !== $validated['email']) {
                $this->addWordPressEmailMapping($validated['email'], $wordpressEmail);
            }
            
            // Clear config cache
            \Artisan::call('config:clear');
            
            Log::info('Admin user created', [
                'email' => $validated['email'],
                'wordpress_created' => $results['wordpress_created'],
                'farmos_created' => $results['farmos_created'],
            ]);
            
            $message = "Admin user created successfully! ";
            if ($results['wordpress_created'] && $results['farmos_created']) {
                $message .= "WordPress and FarmOS accounts created.";
            } elseif ($results['wordpress_created']) {
                $message .= "WordPress account created. FarmOS creation failed: " . ($results['errors']['farmos'] ?? 'Unknown error');
            } elseif ($results['farmos_created']) {
                $message .= "FarmOS account created. WordPress creation failed: " . ($results['errors']['wordpress'] ?? 'Unknown error');
            } else {
                $message .= "Warning: WordPress and FarmOS account creation failed. You may need to create these manually.";
            }
            
            return redirect()->route('admin.admin-users.index')->with('success', $message);
        }
        
        return back()->withErrors(['general' => 'Failed to save user to configuration file.'])->withInput();
    }

    /**
     * Show edit form
     */
    public function edit($index)
    {
        $users = config('admin_users.users', []);
        
        if (!isset($users[$index])) {
            return redirect()->route('admin.admin-users.index')->with('error', 'User not found.');
        }
        
        $user = $users[$index];
        $user['index'] = $index;
        
        return view('admin.admin-users.edit', compact('user'));
    }

    /**
     * Update admin user
     */
    public function update(Request $request, $index)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,super_admin,pos_staff',
            'is_admin' => 'boolean',
            'is_webdev' => 'boolean',
            'is_pos_staff' => 'boolean',
            'active' => 'boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $users = config('admin_users.users', []);
        
        if (!isset($users[$index])) {
            return back()->withErrors(['general' => 'User not found.']);
        }

        // Update user data
        $users[$index]['name'] = $validated['name'];
        $users[$index]['role'] = $validated['role'];
        $users[$index]['is_admin'] = $request->has('is_admin');
        $users[$index]['is_webdev'] = $request->has('is_webdev');
        $users[$index]['is_pos_staff'] = $request->has('is_pos_staff');
        $users[$index]['active'] = $request->has('active');
        
        if (!empty($validated['password'])) {
            $users[$index]['password'] = $validated['password'];
        }

        // Write back to config file
        if ($this->updateConfigFile($users)) {
            \Artisan::call('config:clear');
            
            return redirect()->route('admin.admin-users.index')
                ->with('success', 'Admin user updated successfully!');
        }
        
        return back()->withErrors(['general' => 'Failed to update configuration file.']);
    }

    /**
     * Delete admin user
     */
    public function destroy($index)
    {
        $users = config('admin_users.users', []);
        
        if (!isset($users[$index])) {
            return back()->withErrors(['general' => 'User not found.']);
        }

        // Don't allow deleting yourself
        $currentUser = Session::get('admin_user');
        if ($users[$index]['email'] === $currentUser['email']) {
            return back()->withErrors(['general' => 'You cannot delete your own account.']);
        }

        // Remove user
        array_splice($users, $index, 1);

        // Write back to config file
        if ($this->updateConfigFile($users)) {
            \Artisan::call('config:clear');
            
            return redirect()->route('admin.admin-users.index')
                ->with('success', 'Admin user deleted successfully!');
        }
        
        return back()->withErrors(['general' => 'Failed to update configuration file.']);
    }

    /**
     * Create WordPress user via API
     */
    private function createWordPressUser($email, $name, $password)
    {
        try {
            // Use WordPress REST API to create user
            $username = str_replace(['@', '.', ' '], ['_', '_', '_'], strtolower($email));
            
            $response = $this->wpApiService->makeRequest('POST', 'wp/v2/users', [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'roles' => ['administrator'], // Give admin role
            ]);
            
            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'user_id' => $response['id'],
                ];
            }
            
            return [
                'success' => false,
                'error' => $response['message'] ?? 'Unknown error',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create FarmOS user via API
     * Note: FarmOS user creation not implemented - requires manual creation in FarmOS admin
     */
    private function createFarmOSUser($email, $name, $password)
    {
        // FarmOS user creation not available via API
        // Users must be created manually in FarmOS admin interface
        return [
            'success' => false,
            'error' => 'FarmOS user creation not implemented - create manually at farmos.middleworldfarms.org/user/register',
        ];
    }

    /**
     * Generate user config string
     */
    private function generateUserConfigString($user)
    {
        return sprintf(
            "        [\n" .
            "            'name' => '%s',\n" .
            "            'email' => '%s',\n" .
            "            'password' => '%s',\n" .
            "            'role' => '%s',\n" .
            "            'is_admin' => %s,\n" .
            "            'is_webdev' => %s,\n" .
            "            'is_pos_staff' => %s,\n" .
            "            'created_at' => '%s',\n" .
            "            'active' => %s,\n" .
            "        ]",
            $user['name'],
            $user['email'],
            $user['password'],
            $user['role'],
            $user['is_admin'] ? 'true' : 'false',
            $user['is_webdev'] ? 'true' : 'false',
            ($user['is_pos_staff'] ?? false) ? 'true' : 'false',
            $user['created_at'],
            $user['active'] ? 'true' : 'false'
        );
    }

    /**
     * Update entire config file
     */
    private function updateConfigFile($users)
    {
        $configPath = config_path('admin_users.php');
        $configContent = file_get_contents($configPath);
        
        // Generate all user strings
        $usersString = '';
        foreach ($users as $user) {
            $usersString .= $this->generateUserConfigString($user) . ",\n";
        }
        
        // Replace users array
        $pattern = '/(\'users\'\s*=>\s*\[)(.*?)(\s*\],\s*\/\*\s*\|--------------------------------------------------------------------------)/s';
        $replacement = "$1\n" . $usersString . "    $3";
        $newConfigContent = preg_replace($pattern, $replacement, $configContent);
        
        return $newConfigContent && file_put_contents($configPath, $newConfigContent);
    }

    /**
     * Add WordPress email mapping
     */
    private function addWordPressEmailMapping($adminEmail, $wpEmail)
    {
        $configPath = config_path('admin_users.php');
        $configContent = file_get_contents($configPath);
        
        $mapping = sprintf(
            "        '%s' => '%s',",
            $adminEmail,
            $wpEmail
        );
        
        // Add to wordpress_email_mapping array
        $pattern = '/(\'wordpress_email_mapping\'\s*=>\s*\[)(.*?)(\s*\],)/s';
        $replacement = "$1$2\n$mapping\n    $3";
        $newConfigContent = preg_replace($pattern, $replacement, $configContent);
        
        if ($newConfigContent) {
            file_put_contents($configPath, $newConfigContent);
        }
    }
}

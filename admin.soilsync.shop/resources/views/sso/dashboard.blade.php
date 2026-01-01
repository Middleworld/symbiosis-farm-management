<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Middle World Farms</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        :root {
            --mwf-green: #2d5016;
            --mwf-light-green: #5a7c3e;
            --mwf-yellow: #f5c518;
        }

        body {
            background: linear-gradient(135deg, var(--mwf-green) 0%, var(--mwf-light-green) 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .dashboard-header {
            background: white;
            padding: 2rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dashboard-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text {
            color: var(--mwf-green);
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .dashboard-content {
            padding: 3rem 0;
        }

        .site-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .site-card:hover {
            border-color: var(--mwf-green);
            background-color: #f8f9fa;
            text-decoration: none;
            color: inherit;
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .site-card .site-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            text-align: center;
        }

        .site-card .site-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            text-align: center;
            color: var(--mwf-green);
        }

        .site-card .site-description {
            color: #6c757d;
            text-align: center;
            font-size: 0.95rem;
        }

        .logout-btn {
            background: #dc3545;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }

        .continue-btn {
            background: var(--mwf-green);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
            font-weight: 600;
        }

        .continue-btn:hover {
            background: var(--mwf-light-green);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <h1 class="welcome-text">Welcome back, {{ $user->name }}!</h1>
            <div class="user-info">
                <span class="text-muted">{{ $user->email }}</span>
                <a href="{{ route('sso.logout') }}" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="text-center mb-4">
                        <h2 class="text-white mb-3">Choose Your Farm System</h2>
                        <p class="text-white-50">Select where you'd like to go next</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-3">
                            <a href="https://soilsync.shop/wp-admin/admin-ajax.php?action=mwf_sso_callback&token={{ urlencode(app('App\Http\Controllers\SsoController')->generateJwtForUser($user)) }}" class="site-card">
                                <span class="site-icon">🌐</span>
                                <div class="site-title">Website Admin</div>
                                <div class="site-description">Manage website content, orders & customers</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <a href="/dashboard" class="site-card">
                                <span class="site-icon">📊</span>
                                <div class="site-title">Admin Dashboard</div>
                                <div class="site-description">Farm management, reports & analytics</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <a href="https://farmos.soilsync.shop" class="site-card">
                                <span class="site-icon">🌾</span>
                                <div class="site-title">FarmOS</div>
                                <div class="site-description">Farm data, operations & planning</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <a href="https://feildkit.soilsync.shop" class="site-card" onclick="alert('Field Kit will check for your SSO authentication automatically. If you see a login screen, please authenticate with Field Kit directly.');">
                                <span class="site-icon">📱</span>
                                <div class="site-title">Field Kit</div>
                                <div class="site-description">Offline data collection (checks SSO auth)</div>
                            </a>
                        </div>
                    </div>

                    @if($redirect)
                        <div class="text-center mt-4">
                            <a href="{{ $redirect }}" class="continue-btn">
                                Continue to Original Destination →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
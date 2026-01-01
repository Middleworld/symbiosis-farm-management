<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Middle World Farms</title>

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
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }

        .login-header {
            background: var(--mwf-green);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .login-body {
            padding: 2rem;
        }

        .btn-login {
            background: var(--mwf-green);
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            width: 100%;
        }

        .btn-login:hover {
            background: var(--mwf-light-green);
        }

        .form-control:focus {
            border-color: var(--mwf-green);
            box-shadow: 0 0 0 0.2rem rgba(45, 80, 22, 0.25);
        }

        .site-tabs {
            margin-top: 2rem;
            border-top: 1px solid #dee2e6;
            padding-top: 2rem;
        }

        .site-tabs h5 {
            color: var(--mwf-green);
            margin-bottom: 1rem;
            text-align: center;
        }

        .site-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            opacity: 0.7;
        }

        .site-card .site-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
            text-align: center;
        }

        .site-card .site-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            text-align: center;
            color: #6c757d;
        }

        .site-card .site-description {
            color: #6c757d;
            font-size: 0.9rem;
            text-align: center;
        }

        .logout-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="login-container">
                    <div class="login-header">
                        <h4>🌱 Single Sign-On Login</h4>
                        <p class="mb-0 mt-2 opacity-75">Access your farm management system</p>
                    </div>
                    <div class="login-body">
                        @if(isset($after_logout) && $after_logout)
                            <div class="logout-notice">
                                <strong>Logged Out:</strong> You have been logged out. Please sign in again to continue.
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('sso.authenticate') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg btn-login">Login</button>
                            </div>
                        </form>

                        <div class="site-tabs">
                            <h5>After login, you'll be able to access:</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="site-card">
                                        <span class="site-icon">🌐</span>
                                        <div class="site-title">Website Admin</div>
                                        <div class="site-description">Manage content & orders</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="site-card">
                                        <span class="site-icon">📊</span>
                                        <div class="site-title">Admin Dashboard</div>
                                        <div class="site-description">Farm management & reports</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="site-card">
                                        <span class="site-icon">🌾</span>
                                        <div class="site-title">FarmOS</div>
                                        <div class="site-description">Farm data & operations</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="site-card">
                                        <span class="site-icon">📱</span>
                                        <div class="site-title">Field Kit</div>
                                        <div class="site-description">Offline data collection</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
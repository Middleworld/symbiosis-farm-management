<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'My Account') - Middle World Farms</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --mwf-green: #2d5016;
            --mwf-light-green: #5a7c3e;
            --mwf-yellow: #f5c518;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .navbar {
            background-color: var(--mwf-green) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.85) !important;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .btn-primary {
            background-color: var(--mwf-green);
            border-color: var(--mwf-green);
        }
        
        .btn-primary:hover {
            background-color: var(--mwf-light-green);
            border-color: var(--mwf-light-green);
        }
        
        .bg-success {
            background-color: var(--mwf-green) !important;
        }
        
        .card {
            border: none;
            border-radius: 8px;
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        footer {
            background-color: var(--mwf-green);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
        }
        
        footer a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
        }
        
        footer a:hover {
            color: white;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('customer.subscriptions.index') }}">
                🌱 Middle World Farms
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('customer.subscriptions.index') }}">My Subscriptions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://middleworldfarms.org/shop">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://middleworldfarms.org/contact">Contact</a>
                    </li>
                    @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="https://middleworldfarms.org/my-account">My Account</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('admin.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('customer.login') }}">Login</a>
                    </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main>
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5>Middle World Farms</h5>
                    <p class="small">Sustainable, local, organic vegetables delivered to your door.</p>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li><a href="https://middleworldfarms.org/shop">Shop</a></li>
                        <li><a href="https://middleworldfarms.org/about">About Us</a></li>
                        <li><a href="https://middleworldfarms.org/contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>Contact</h6>
                    <p class="small mb-0">Email: info@middleworldfarms.org</p>
                    <p class="small">Phone: 01234 567890</p>
                </div>
            </div>
            <hr class="my-3" style="border-color: rgba(255,255,255,0.2)">
            <div class="text-center small">
                <p class="mb-0">&copy; {{ date('Y') }} Middle World Farms. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>

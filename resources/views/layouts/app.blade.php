<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="format-detection" content="telephone=no">
    @stack('head')
    <title>@yield('title', 'Symbiosis')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Force Font Awesome to use web fonts instead of SVG
        window.FontAwesomeConfig = {
            autoReplaceSvg: false,
            searchPseudoElements: false
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Setup CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <link rel="stylesheet" href="/css/global.css">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/mwf-api.js'])
    @yield('styles')
    <style>
        /* Z-INDEX HIERARCHY - CRITICAL: DO NOT CHANGE WITHOUT TESTING ALL MODALS
           - Sidebar: 1040 (below modals)
           - Standard elements: < 9000
           - Custom modals: 9999 (above everything - see order-history.blade.php for implementation)
           Note: Bootstrap 5 modals have z-index conflicts with sidebar. Use custom modals with z-index: 9999.
        */
        
        body {
            overflow-x: hidden;
        }
        
        /* Bootstrap Modal Z-Index Fix (if using Bootstrap modals) */
        .modal-backdrop {
            z-index: 1055 !important;
        }
        .modal {
            z-index: 1060 !important;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: white;
            transition: all 0.3s ease;
            z-index: 1040; /* Below modal backdrop */
            overflow-y: auto;
        }
        
        .sidebar.collapsed {
            width: 60px;
        }
        
        .sidebar .sidebar-header {
            padding: 15px 20px 20px;
            background: linear-gradient(135deg, #27ae60 0%, #213b2e 100%);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed .sidebar-header {
            padding: 15px 10px 20px;
        }
        
        .sidebar-toggle-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            z-index: 1060;
        }
        
        .logo-container {
            transition: all 0.3s ease;
            padding: 10px;
            margin: 10px auto;
            max-width: 120px;
            text-align: center;
        }
        
        .logo-container img,
        .rounded-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            filter: drop-shadow(0 3px 8px rgba(0,0,0,0.3));
            border: 3px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
        }
        
        .logo-container img:hover,
        .rounded-logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));
            border-color: rgba(255,255,255,0.3);
        }
        
        .sidebar.collapsed .logo-container {
            opacity: 0;
            transform: scale(0.5);
        }
        
        .sidebar-toggle-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }
        
        .sidebar-toggle-btn:active {
            transform: scale(0.95);
        }
        
        .sidebar.collapsed .sidebar-toggle-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            width: 30px;
            height: 30px;
            background: rgba(33,59,46,0.9);
            border-radius: 50%;
        }
        
        .sidebar.collapsed .sidebar-toggle-btn:hover {
            background: rgba(33,59,46,1);
            transform: scale(1.1);
        }
        
        .sidebar .sidebar-header h4 {
            margin: 0;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            font-weight: 600;
        }
        
        .sidebar.collapsed .sidebar-header h4 {
            opacity: 0;
        }
        
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }
        
        .sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: var(--sidebar-active);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .sidebar.collapsed .nav-link {
            padding: 15px 20px;
            justify-content: center;
        }
        
        .sidebar.collapsed .nav-link span {
            display: none;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
        }
        
        .main-content {
            margin-left: var(--sidebar-width) !important;
            transition: all 0.3s ease;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }
        
        .main-content.expanded {
            margin-left: 60px !important;
        }
        
        .nav-section {
            padding: 15px 20px 5px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #7f8c8d;
            border-bottom: 1px solid #34495e;
            margin-bottom: 5px;
        }
        
        .sidebar.collapsed .nav-section {
            display: none;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        .content-wrapper {
            padding: 4px;
            width: 100%;
            box-sizing: border-box;
            margin-left: 0;
            transition: all 0.3s ease;
        }
        
        .badge-notification {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
            margin-left: auto;
        }
        
        .admin-info {
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 15px !important;
        }
        
        .admin-info .text-muted {
            color: rgba(255,255,255,0.7) !important;
            font-size: 0.75rem;
        }
        
        .admin-info .text-white {
            color: white !important;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        /* Collapsible Section Styles */
        .nav-section {
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: all 0.2s ease;
        }
        
        .nav-section:hover {
            background: rgba(255,255,255,0.08);
        }
        
        .nav-section i.section-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s ease;
            font-size: 0.75rem;
        }
        
        .nav-section.collapsed i.section-toggle {
            transform: translateY(-50%) rotate(-90deg);
        }
        
        .nav-section-items {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 1;
        }
        
        .nav-section-items.collapsed {
            max-height: 0;
            opacity: 0;
        }
        
        .sidebar.collapsed .nav-section i.section-toggle {
            display: none;
        }

        /* AI Helper Sidebar Widget Styles */
        .ai-helper-sidebar-container {
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .ai-helper-sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .ai-helper-sidebar-header .ai-helper-icon {
            font-size: 20px;
            margin-right: 8px;
        }

        .ai-helper-sidebar-header .ai-helper-context {
            color: #bdc3c7;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .ai-helper-sidebar-messages {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 10px;
        }

        .ai-message {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-start;
        }

        .ai-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            flex-shrink: 0;
            font-size: 12px;
        }

        .ai-content p {
            margin: 0;
            color: #ecf0f1;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .ai-helper-sidebar-input {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .ai-helper-sidebar-input input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            background: rgba(255,255,255,0.05);
            color: #ecf0f1;
            font-size: 0.85rem;
        }

        .ai-helper-sidebar-input input::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .ai-helper-sidebar-input input:focus {
            outline: none;
            border-color: #22c55e;
            background: rgba(255,255,255,0.08);
        }

        .ai-send-button {
            padding: 8px 12px;
            background: #22c55e;
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ai-send-button:hover {
            background: #16a34a;
            transform: scale(1.05);
        }

        .ai-send-button i {
            font-size: 0.8rem;
        }

        .ai-helper-sidebar-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            color: #bdc3c7;
            font-size: 0.85rem;
        }

        .ai-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.2);
            border-top: 2px solid #22c55e;
            border-radius: 50%;
            animation: ai-spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes ai-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Hide AI helper in collapsed sidebar */
        .sidebar.collapsed .ai-helper-sidebar-container {
            display: none;
        }

        @media (max-width: 768px) {
        }
    </style>
</head>
<body class="has-sidebar">
    @php
        $user = Session::get('user');
        $isPosOnly = isset($user['is_pos_staff']) && $user['is_pos_staff'] && !($user['is_admin'] ?? false);
    @endphp
    
    <!-- Sidebar (hidden for POS-only staff) -->
    @if(!$isPosOnly)
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo-container mt-4">
                <img src="/Middle World Logo Image White - PNG FOR SCREENS.png" alt="Middle World Farms" class="rounded-logo">
            </div>
            <h4 class="mb-0 mt-2">Symbiosis</h4>
            @php
                $adminUser = \App\Http\Controllers\Auth\LoginController::getAdminUser();
            @endphp
            @if($adminUser)
                <div class="admin-info mt-2">
                    <small class="text-muted d-block">Welcome back,</small>
                    <small class="text-white fw-bold">{{ $adminUser['name'] ?? 'Admin' }}</small>
                </div>
            @endif
        </div>
        
        <nav class="nav flex-column">
            <div class="nav-section" data-section="dashboard">
                Dashboard
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="dashboard">
                <a href="/admin" class="nav-link {{ request()->is('admin') && !request()->is('admin/*') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Overview</span>
                </a>
            </div>
            
            <div class="nav-section" data-section="operations">
                Operations
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="operations">
                <a href="/admin/tasks" class="nav-link {{ request()->is('admin/tasks*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Tasks</span>
                </a>
                
                <a href="/admin/notes" class="nav-link {{ request()->is('admin/notes*') ? 'active' : '' }}">
                    <i class="fas fa-sticky-note"></i>
                    <span>Notes</span>
                </a>
                
                <a href="/admin/deliveries" class="nav-link {{ request()->is('admin/deliveries*') ? 'active' : '' }}">
                    <i class="fas fa-truck"></i>
                    <span>Delivery Schedule</span>
                    @if(isset($totalDeliveries) && $totalDeliveries > 0)
                        <span class="badge-notification">{{ $totalDeliveries }}</span>
                    @endif
                </a>
                
                <a href="/admin/customers" class="nav-link {{ request()->is('admin/customers*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Customer Management</span>
                </a>
                
                <a href="/admin/routes" class="nav-link {{ request()->is('admin/routes*') ? 'active' : '' }}">
                    <i class="fas fa-route"></i>
                    <span>Route Planner</span>
                </a>
                
                <a href="/admin/email" class="nav-link {{ request()->is('admin/email*') ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i>
                    <span>Email Client</span>
                </a>
            </div>
            
            <div class="nav-section" data-section="woocommerce">
                WooCommerce
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="woocommerce">
                <a href="{{ route('admin.funds.index') }}" class="nav-link {{ request()->is('admin/funds*') ? 'active' : '' }}">
                    <i class="fas fa-wallet"></i>
                    <span>Funds Management</span>
                </a>
                
                <a href="/admin/products" class="nav-link {{ request()->is('admin/products*') ? 'active' : '' }}">
                    <i class="fas fa-boxes"></i>
                    <span>Product Management</span>
                </a>
                
                <a href="/admin/shipping-classes" class="nav-link {{ request()->is('admin/shipping-classes*') ? 'active' : '' }}">
                    <i class="fas fa-shipping-fast"></i>
                    <span>Shipping Classes</span>
                </a>
                
                <a href="/admin/product-attributes" class="nav-link {{ request()->is('admin/product-attributes*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    <span>Product Attributes</span>
                </a>
                
                <a href="/admin/orders" class="nav-link {{ request()->is('admin/orders*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </div>
            
            <div class="nav-section" data-section="subscriptions">
                Vegbox Subscriptions
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="subscriptions">
                <a href="{{ route('admin.vegbox-subscriptions.index') }}" class="nav-link {{ request()->is('admin/vegbox-subscriptions') && !request()->is('admin/vegbox-subscriptions/*') ? 'active' : '' }}">
                    <i class="fas fa-box"></i>
                    <span>All Subscriptions</span>
                </a>
                
                <a href="{{ route('admin.vegbox-subscriptions.upcoming-renewals') }}" class="nav-link {{ request()->is('admin/vegbox-subscriptions/upcoming-renewals*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i>
                    <span>Upcoming Renewals</span>
                </a>
                
                <a href="{{ route('admin.vegbox-subscriptions.failed-payments') }}" class="nav-link {{ request()->is('admin/vegbox-subscriptions/failed-payments*') ? 'active' : '' }}">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Failed Payments</span>
                    @php
                        $failedCount = \App\Models\VegboxSubscription::where('failed_payment_count', '>', 0)
                            ->whereNull('canceled_at')
                            ->count();
                    @endphp
                    @if($failedCount > 0)
                        <span class="badge bg-danger ms-2">{{ $failedCount }}</span>
                    @endif
                </a>
            </div>
            
            <div class="nav-section" data-section="pos">
                Point of Sale
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="pos">
                <a href="/pos" class="nav-link {{ request()->is('pos') && !request()->is('pos/*') ? 'active' : '' }}">
                    <i class="fas fa-cash-register"></i>
                    <span>POS Terminal</span>
                </a>
                
                <a href="/pos/inventory" class="nav-link {{ request()->is('pos/inventory*') ? 'active' : '' }}">
                    <i class="fas fa-box-open"></i>
                    <span>POS Inventory</span>
                </a>
                
                <a href="/pos/orders" class="nav-link {{ request()->is('pos/orders*') ? 'active' : '' }}">
                    <i class="fas fa-receipt"></i>
                    <span>POS Orders</span>
                </a>
            </div>
            
            <div class="nav-section" data-section="analytics">
                Analytics
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="analytics">
                <a href="/admin/reports" class="nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                
                <a href="/admin/analytics" class="nav-link {{ request()->is('admin/analytics*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </div>
            
            <div class="nav-section" data-section="accounting">
                Accounting
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="accounting">
                <a href="{{ route('admin.bank-transactions.dashboard') }}" class="nav-link {{ request()->is('admin/bank-transactions/dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="{{ route('admin.bank-transactions.index') }}" class="nav-link {{ request()->is('admin/bank-transactions') && !request()->is('admin/bank-transactions/import') && !request()->is('admin/bank-transactions/dashboard') ? 'active' : '' }}">
                    <i class="fas fa-list-ul"></i>
                    <span>Transactions</span>
                </a>
                
                <a href="{{ route('admin.bank-transactions.import-form') }}" class="nav-link {{ request()->is('admin/bank-transactions/import') ? 'active' : '' }}">
                    <i class="fas fa-upload"></i>
                    <span>Import CSV</span>
                </a>
                
                <a href="{{ route('admin.openbanking.index') }}" class="nav-link {{ request()->is('admin/openbanking*') ? 'active' : '' }}">
                    <i class="fas fa-university"></i>
                    <span>Open Banking</span>
                </a>
            </div>
            
            <div class="nav-section" data-section="farm">
                Farm Management
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="farm">
                <a href="/admin/farmos" class="nav-link {{ request()->is('admin/farmos') ? 'active' : '' }}">
                    <i class="fas fa-seedling"></i>
                    <span>farmOS Dashboard</span>
                </a>
                
                <a href="/admin/farmos/planting-chart" class="nav-link {{ request()->is('admin/farmos/planting-chart*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Planting Chart</span>
                </a>
                
                <a href="/admin/farmos/succession-planning" class="nav-link {{ request()->is('admin/farmos/succession-planning*') ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i>
                    <span>Succession Planning</span>
                </a>
                
                <a href="/admin/farmos/harvests" class="nav-link {{ request()->is('admin/farmos/harvests*') ? 'active' : '' }}">
                    <i class="fas fa-apple-alt"></i>
                    <span>Harvest Logs</span>
                </a>
                
                <a href="/admin/farmos/stock" class="nav-link {{ request()->is('admin/farmos/stock*') ? 'active' : '' }}">
                    <i class="fas fa-boxes"></i>
                    <span>Stock Management</span>
                </a>
                
                <a href="/admin/weather" class="nav-link {{ request()->is('admin/weather*') ? 'active' : '' }}">
                    <i class="fas fa-cloud-sun"></i>
                    <span>Weather Dashboard</span>
                </a>
            </div>
            
            <div class="nav-section" data-section="system">
                System
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="system">
                <a href="/admin/admin-users" class="nav-link {{ request()->is('admin/admin-users*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog"></i>
                    <span>Admin Users</span>
                </a>
                
                <a href="/admin/stripe" class="nav-link {{ request()->is('admin/stripe*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card"></i>
                    <span>Stripe Payments</span>
                </a>
                
                <a href="/admin/companies-house" class="nav-link {{ request()->is('admin/companies-house*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>Companies House</span>
                </a>
            
            <a href="/admin/settings" class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            
            <a href="/admin/logs" class="nav-link {{ request()->is('admin/logs*') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>System Logs</span>
            </a>

            <a href="/admin/chatbot-settings" class="nav-link {{ request()->is('admin/chatbot-settings*') ? 'active' : '' }}">
                <i class="fas fa-robot"></i>
                <span>Chatbot Settings</span>
            </a>
            
            <a href="/admin/unified-backup" class="nav-link {{ request()->is('admin/unified-backup*') ? 'active' : '' }}">
                <i class="fas fa-server"></i>
                <span>Unified Backup</span>
            </a>
            </div>
            
            <div class="nav-section" data-section="ai-helper">
                AI Helper
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="ai-helper">
                <!-- User Manuals -->
                <a href="/docs/user-manual" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>User Manual</span>
                </a>
                <a href="/docs/user-manual/subscription-management" class="nav-link">
                    <i class="fas fa-file-invoice"></i>
                    <span>Subscriptions Guide</span>
                </a>
                <a href="/docs/user-manual/delivery-management" class="nav-link">
                    <i class="fas fa-truck"></i>
                    <span>Delivery Guide</span>
                </a>
                <a href="/docs/user-manual/succession-planning" class="nav-link">
                    <i class="fas fa-seedling"></i>
                    <span>Crop Planning</span>
                </a>
                <a href="/docs/user-manual/task-system" class="nav-link">
                    <i class="fas fa-tasks"></i>
                    <span>Task System</span>
                </a>
                <a href="/docs/user-manual/crm-usage" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>CRM Guide</span>
                </a>
                <div style="margin: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);"></div>
                
                <!-- AI Chat Widget -->
                <div id="ai-helper-sidebar-widget" class="ai-helper-sidebar-container">
                    <div class="ai-helper-sidebar-header">
                        <div class="ai-helper-icon">🤖</div>
                        <span class="ai-helper-context">Contextual Help</span>
                    </div>
                    <div class="ai-helper-sidebar-messages">
                        <div class="ai-message welcome">
                            <div class="ai-avatar">🌱</div>
                            <div class="ai-content">
                                <p>Hi! I'm here to help you with admin tasks. What would you like to know?</p>
                            </div>
                        </div>
                    </div>
                    <div class="ai-helper-sidebar-input">
                        <input type="text" placeholder="Ask me anything..." />
                        <button class="ai-send-button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="ai-helper-sidebar-loading" style="display: none;">
                        <div class="ai-spinner"></div>
                        <span>Thinking...</span>
                    </div>
                </div>
            </div>
            
            <div class="nav-section" data-section="external">
                External
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="external">
                <a href="https://middleworldfarms.org" target="_blank" class="nav-link">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Visit Website</span>
                </a>
                
                <a href="https://middleworldfarms.org/wp-admin" target="_blank" class="nav-link">
                    <i class="fab fa-wordpress"></i>
                    <span>WordPress Admin</span>
                </a>
            </div>

            <!-- Logout Section -->
            <div class="nav-section mt-4" data-section="account">
                Account
                <i class="fas fa-chevron-down section-toggle"></i>
            </div>
            <div class="nav-section-items" data-section-items="account">
                <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="nav-link text-start border-0 bg-transparent w-100" style="color: inherit;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>
    @endif
    
    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="header-spacer">
                @hasSection('header-hint')
                    <small class="text-white">@yield('header-hint')</small>
                @endif
            </div>
            <div class="header-content">
                @hasSection('page-header')
                    @yield('page-header')
                @else
                    <h1>Farm Management System</h1>
                    <p class="lead">Integrated agricultural operations</p>
                @endif
            </div>
            <div class="header-logo-container">
                <img src="/Middle_World_Logo_Inverted 350px.png" alt="Middle World Farms" class="header-logo">
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="main-content" id="mainContent">
        <!-- Page content -->
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const topHeader = document.querySelector('.top-header');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const body = document.body;
            
            // Ensure all elements exist before proceeding
            if (!sidebar || !mainContent) {
                console.error('Sidebar or main content element not found');
                return;
            }
            
            // Load saved sidebar state
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed && window.innerWidth > 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                body.classList.add('sidebar-collapsed');
                if (topHeader) {
                    topHeader.style.marginLeft = '60px';
                }
            }
            
            // Function to toggle sidebar
            function toggleSidebar() {
                console.log('Toggle sidebar called'); // Debug log
                if (window.innerWidth <= 768) {
                    // Mobile toggle
                    sidebar.classList.toggle('mobile-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.toggle('show');
                    }
                } else {
                    // Desktop toggle
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    body.classList.toggle('sidebar-collapsed');
                    
                    // Update header margin
                    if (topHeader) {
                        if (sidebar.classList.contains('collapsed')) {
                            topHeader.style.marginLeft = '60px';
                        } else {
                            topHeader.style.marginLeft = 'var(--sidebar-width)';
                        }
                    }
                    
                    // Save state to localStorage
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                    console.log('Sidebar collapsed:', sidebar.classList.contains('collapsed')); // Debug log
                }
            }
            
            // Toggle sidebar from top navbar
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            
            // Toggle sidebar from sidebar button
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            
            // Close sidebar on overlay click (mobile)
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('show');
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('show');
                    }
                    
                    // Restore collapsed state on desktop
                    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (sidebarCollapsed) {
                        sidebar.classList.add('collapsed');
                        mainContent.classList.add('expanded');
                        body.classList.add('sidebar-collapsed');
                        if (topHeader) {
                            topHeader.style.marginLeft = '60px';
                        }
                    } else {
                        if (topHeader) {
                            topHeader.style.marginLeft = 'var(--sidebar-width)';
                        }
                    }
                } else {
                    // Remove collapsed state on mobile
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    body.classList.remove('sidebar-collapsed');
                    if (topHeader) {
                        topHeader.style.marginLeft = '0';
                    }
                }
            });
        });
        
        // Collapsible Sidebar Sections
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved section states from localStorage
            const savedStates = JSON.parse(localStorage.getItem('sidebarSections') || '{}');
            
            // Initialize all sections
            document.querySelectorAll('.nav-section').forEach(section => {
                const sectionName = section.getAttribute('data-section');
                const itemsContainer = document.querySelector(`[data-section-items="${sectionName}"]`);
                
                if (!itemsContainer) return;
                
                // Apply saved state (default to expanded)
                if (savedStates[sectionName] === 'collapsed') {
                    section.classList.add('collapsed');
                    itemsContainer.classList.add('collapsed');
                }
                
                // Add click handler
                section.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle collapsed state
                    section.classList.toggle('collapsed');
                    itemsContainer.classList.toggle('collapsed');
                    
                    // Save state to localStorage
                    const isCollapsed = section.classList.contains('collapsed');
                    savedStates[sectionName] = isCollapsed ? 'collapsed' : 'expanded';
                    localStorage.setItem('sidebarSections', JSON.stringify(savedStates));
                });
            });
        });
    </script>
    
    @yield('scripts')
    @stack('scripts')
    @stack('styles')
</body>
</html>

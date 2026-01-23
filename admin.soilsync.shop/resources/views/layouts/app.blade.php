<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="format-detection" content="telephone=no">
    
    <!-- Dynamic branding CSS variables and custom styles -->
    {!! $brandingCss ?? '' !!}
    
    @stack('head')
    <title>@yield('title', $branding ? $branding->company_name : 'Symbiosis')</title>
    
    <!-- Favicon -->
    @if($branding && $branding->logo_small_path)
        <link rel="icon" type="image/png" href="{{ secure_url($branding->logo_small_path) }}">
    @else
        <link rel="icon" type="image/png" href="/favicon.ico">
    @endif
    
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
    <link rel="stylesheet" href="/css/global.css?v={{ time() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/mwf-api.js'])
    @yield('styles')
    <style>
        /* Z-INDEX HIERARCHY - CRITICAL: DO NOT CHANGE WITHOUT TESTING ALL MODALS
           - Sidebar: 1040 (below modals)
           - Standard elements: < 9000
           - Custom modals: 9999 (above everything - see order-history.blade.php for implementation)
           Note: Bootstrap 5 modals have z-index conflicts with sidebar. Use custom modals with z-index: 9999.
        */
        
        :root {
            --sidebar-width: 60px; /* Much smaller for icon-only sidebar */
            --sidebar-expanded-width: 320px; /* For mega menus */
        }
        
        body {
            overflow-x: hidden;
        }

        body.pos-only .sidebar {
            display: none !important;
        }

        body.pos-only .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        /* Bootstrap Modal Z-Index Fix (if using Bootstrap modals) */
        .modal-backdrop {
            z-index: 1055 !important;
        }
        .modal {
            z-index: 1060 !important;
        }
        
        .sidebar {
            position: sticky;
            top: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: white;
            transition: all 0.3s ease;
            z-index: 1040;
            overflow: visible;
            padding-top: 20px;
            height: 100vh; /* Full viewport height for consistent appearance */
            align-self: flex-start;
        }
        
        .sidebar .nav-link {
            color: var(--brand-sidebar-text, #bdc3c7);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }
        
        .sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: var(--brand-sidebar-text, white);
        }
        
        .sidebar .nav-link.active {
            background: var(--sidebar-active);
            color: var(--brand-sidebar-text, white);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .main-content {
            flex: 1;
            transition: all 0.3s ease;
            min-height: calc(100vh - var(--header-height, 200px));
            position: relative;
            z-index: 1;
        }
        
        .nav-section {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #34495e;
            margin-bottom: 5px;
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            color: var(--brand-sidebar-text, #7f8c8d);
        }
        
        .nav-section:hover {
            background: rgba(255,255,255,0.08);
            color: var(--brand-sidebar-accent, #3498db);
        }
        
        .nav-section i {
            font-size: 1rem;
            display: block;
            margin: 0 auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-section:hover i {
            transform: scale(1.1);
            color: var(--brand-sidebar-accent, #3498db);
        }
        
        /* Main layout container for sidebar and content */
        .main-layout-container {
            display: flex;
            min-height: calc(100vh - var(--header-height, 200px));
        }

        body.pos-only .main-layout-container {
            display: block;
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

        /* Mega Menu Styles */
        .nav-section {
            position: relative;
        }

        .nav-section:hover .mega-menu-overlay {
            opacity: 1;
            visibility: visible;
            transform: translateX(0) scale(1);
        }

        .mega-menu-overlay {
            position: fixed;
            left: var(--sidebar-width);
            top: auto;
            width: auto;
            min-width: 280px;
            max-width: 400px;
            height: auto;
            max-height: 80vh;
            background: var(--sidebar-bg);
            border-left: 1px solid rgba(255,255,255,0.1);
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            z-index: 1030;
            opacity: 0;
            visibility: hidden;
            transform: translateX(-20px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            padding: 20px;
            border-radius: 0 8px 8px 0;
        }

        .mega-menu-title {
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        /* Ensure mega menu content fits properly in dynamic sizing */
        .mega-menu-overlay .mega-menu-grid {
            min-height: auto;
        }

        .mega-menu-overlay .ai-helper-sidebar-container {
            max-height: none;
            overflow: visible;
        }

        .mega-menu-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .mega-menu-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: var(--brand-sidebar-text, #bdc3c7);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.85rem;
            opacity: 0;
            transform: translateX(-10px);
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .mega-menu-item:nth-child(1) { animation-delay: 0.05s; }
        .mega-menu-item:nth-child(2) { animation-delay: 0.1s; }
        .mega-menu-item:nth-child(3) { animation-delay: 0.15s; }
        .mega-menu-item:nth-child(4) { animation-delay: 0.2s; }
        .mega-menu-item:nth-child(5) { animation-delay: 0.25s; }
        .mega-menu-item:nth-child(6) { animation-delay: 0.3s; }
        .mega-menu-item:nth-child(7) { animation-delay: 0.35s; }
        .mega-menu-item:nth-child(8) { animation-delay: 0.4s; }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .mega-menu-item:hover {
            background: var(--sidebar-hover);
            color: var(--brand-sidebar-text, white);
            text-decoration: none;
            transform: translateX(5px);
        }

        .mega-menu-item.active {
            background: var(--sidebar-active);
            color: var(--brand-sidebar-text, white);
        }

        .mega-menu-item i {
            width: 18px;
            margin-right: 12px;
            font-size: 1rem;
            opacity: 0.8;
        }

        .mega-menu-item .badge {
            margin-left: auto;
            font-size: 0.75rem;
        }

        /* Hide original nav-section-items when mega menu is enabled */
        .nav-section-items {
            display: none !important;
        }

        /* Admin welcome message in header */
        .admin-welcome {
            text-align: center;
            margin-top: 0.5rem;
        }

        .admin-welcome small {
            font-size: 0.85rem;
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

        /* Header Calendar Styles */
        .header-calendar {
            display: flex;
            align-items: center;
            color: white;
            font-size: 0.9rem;
        }
        
        .header-calendar i {
            color: rgba(255,255,255,0.8);
        }
        
        .header-calendar .fw-bold {
            color: white;
        }

        @media (max-width: 768px) {
        }
    </style>
</head>
@php
    $user = Session::get('user');
    $isPosOnly = (($user['role'] ?? null) === 'pos_staff') && empty($user['auto_authenticated']);
@endphp
<body class="has-sidebar{{ $isPosOnly ? ' pos-only' : '' }}">
    @php

        $customerSiteUrl = \App\Models\Setting::get('customer_site_url', config('services.customer_site.url'));
        $appUrl = config('app.url');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $appScheme = parse_url($appUrl, PHP_URL_SCHEME) ?: request()->getScheme();
        
        if (empty($customerSiteUrl) || str_contains($customerSiteUrl, 'example-farm.com')) {
            if ($appHost && strpos($appHost, 'admin.') === 0) {
                $customerSiteUrl = $appScheme . '://' . substr($appHost, 6);
            } elseif ($appHost) {
                $customerSiteUrl = $appScheme . '://' . $appHost;
            }
        }

        $customerSiteUrl = $customerSiteUrl ? rtrim($customerSiteUrl, '/') : null;
        $wpAdminUrl = Session::get('wp_admin_url') ?: ($customerSiteUrl ? $customerSiteUrl . '/wp-admin' : null);
        
        // Dynamic external service URLs based on current domain
        $farmosUrl = config('farmos.url');
        $fieldkitUrl = 'https://fieldkit.soilsync.shop'; // Default fallback
        
        if ($appHost && strpos($appHost, 'admin.') === 0) {
            $baseDomain = substr($appHost, 6); // Remove 'admin.' prefix
            $farmosUrl = $appScheme . '://farmos.' . $baseDomain;
            $fieldkitUrl = $appScheme . '://fieldkit.' . $baseDomain;
        }
    @endphp

    <!-- Main Layout Container -->
    <div class="main-layout-container">
        @if(!$isPosOnly)
        <div class="sidebar" id="sidebar">
            <nav class="nav flex-column">
                
                <div class="nav-section mega-menu-trigger" data-section="operations" title="Operations">
                    <i class="fas fa-cogs"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">Operations</div>
                        <div class="mega-menu-grid">
                            <a href="/admin/tasks" class="mega-menu-item {{ request()->is('admin/tasks*') ? 'active' : '' }}">
                                <i class="fas fa-clipboard-check"></i>
                                <span>Tasks</span>
                            </a>
                            <a href="/admin/tasks/kanban" class="mega-menu-item {{ request()->is('admin/tasks/kanban*') ? 'active' : '' }}">
                                <i class="fas fa-columns"></i>
                                <span>Task Board</span>
                            </a>
                            <a href="/admin/notes" class="mega-menu-item {{ request()->is('admin/notes*') ? 'active' : '' }}">
                                <i class="fas fa-sticky-note"></i>
                                <span>Notes</span>
                            </a>
                            <a href="/admin/deliveries" class="mega-menu-item {{ request()->is('admin/deliveries*') ? 'active' : '' }}">
                                <i class="fas fa-truck"></i>
                                <span>Deliveries & Collections</span>
                                @if(isset($totalDeliveries) && $totalDeliveries > 0)
                                    <span class="badge bg-danger">{{ $totalDeliveries }}</span>
                                @endif
                            </a>
                            <a href="/admin/customers" class="mega-menu-item {{ request()->is('admin/customers*') ? 'active' : '' }}">
                                <i class="fas fa-users"></i>
                                <span>Customer Management</span>
                            </a>
                            <a href="/admin/routes" class="mega-menu-item {{ request()->is('admin/routes*') ? 'active' : '' }}">
                                <i class="fas fa-route"></i>
                                <span>Route Planner</span>
                            </a>
                            <a href="/admin/email" class="mega-menu-item {{ request()->is('admin/email*') ? 'active' : '' }}">
                                <i class="fas fa-envelope"></i>
                                <span>Email Client</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="woocommerce" title="WooCommerce">
                    <i class="fab fa-wordpress"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">WooCommerce</div>
                        <div class="mega-menu-grid">
                            <a href="/admin/products" class="mega-menu-item {{ request()->is('admin/products*') ? 'active' : '' }}">
                                <i class="fas fa-boxes"></i>
                                <span>Product Management</span>
                            </a>
                            <a href="/admin/shipping-classes" class="mega-menu-item {{ request()->is('admin/shipping-classes*') ? 'active' : '' }}">
                                <i class="fas fa-shipping-fast"></i>
                                <span>Shipping Classes</span>
                            </a>
                            <a href="/admin/product-attributes" class="mega-menu-item {{ request()->is('admin/product-attributes*') ? 'active' : '' }}">
                                <i class="fas fa-tags"></i>
                                <span>Product Attributes</span>
                            </a>
                            <a href="/admin/orders" class="mega-menu-item {{ request()->is('admin/orders*') ? 'active' : '' }}">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Orders</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="subscriptions" title="Vegbox Subscriptions">
                    <i class="fas fa-box"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">Vegbox Subscriptions</div>
                        <div class="mega-menu-grid">
                            <a href="{{ route('admin.vegbox-subscriptions.index') }}" class="mega-menu-item {{ request()->is('admin/vegbox-subscriptions') && !request()->is('admin/vegbox-subscriptions/*') ? 'active' : '' }}">
                                <i class="fas fa-box"></i>
                                <span>All Subscriptions</span>
                            </a>
                            
                            <a href="{{ route('admin.vegbox-subscriptions.upcoming-renewals') }}" class="mega-menu-item {{ request()->is('admin/vegbox-subscriptions/upcoming-renewals*') ? 'active' : '' }}">
                                <i class="fas fa-calendar-check"></i>
                                <span>Upcoming Renewals</span>
                            </a>
                            
                            <a href="{{ route('admin.vegbox-subscriptions.failed-payments') }}" class="mega-menu-item {{ request()->is('admin/vegbox-subscriptions/failed-payments*') ? 'active' : '' }}">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Failed Payments</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="pos" title="Point of Sale">
                    <i class="fas fa-cash-register"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">Point of Sale</div>
                        <div class="mega-menu-grid">
                            <a href="/pos" class="mega-menu-item {{ request()->is('pos') && !request()->is('pos/*') ? 'active' : '' }}">
                                <i class="fas fa-cash-register"></i>
                                <span>POS Terminal</span>
                            </a>
                            
                            <a href="/pos/inventory" class="mega-menu-item {{ request()->is('pos/inventory*') ? 'active' : '' }}">
                                <i class="fas fa-box-open"></i>
                                <span>POS Inventory</span>
                            </a>
                            
                            <a href="/pos/deliveries" class="mega-menu-item {{ request()->is('pos/deliveries*') ? 'active' : '' }}">
                                <i class="fas fa-truck"></i>
                                <span>POS Deliveries</span>
                            </a>
                            
                            <a href="/pos/order-history" class="mega-menu-item {{ request()->is('pos/order-history*') ? 'active' : '' }}">
                                <i class="fas fa-history"></i>
                                <span>Order History</span>
                            </a>
                            
                            <a href="/pos/customers/search" class="mega-menu-item {{ request()->is('pos/customers*') ? 'active' : '' }}">
                                <i class="fas fa-search"></i>
                                <span>Customer Search</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="analytics" title="Analytics">
                    <i class="fas fa-chart-bar"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">Analytics</div>
                        <div class="mega-menu-grid">
                            <a href="/admin/reports" class="mega-menu-item {{ request()->is('admin/reports*') ? 'active' : '' }}">
                                <i class="fas fa-chart-bar"></i>
                                <span>Reports</span>
                            </a>
                            
                            <a href="/admin/analytics" class="mega-menu-item {{ request()->is('admin/analytics*') ? 'active' : '' }}">
                                <i class="fas fa-chart-line"></i>
                                <span>Analytics Dashboard</span>
                            </a>
                            
                            <a href="/admin/analytics/realtime" class="mega-menu-item {{ request()->is('admin/analytics/realtime*') ? 'active' : '' }}">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Real-time Analytics</span>
                            </a>
                            
                            <a href="/admin/reports/export" class="mega-menu-item {{ request()->is('admin/reports/export*') ? 'active' : '' }}">
                                <i class="fas fa-download"></i>
                                <span>Export Reports</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="accounting" title="Accounting">
                    <i class="fas fa-chart-pie"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">Accounting</div>
                        <div class="mega-menu-grid">
                            <a href="{{ route('admin.bank-transactions.dashboard') }}" class="mega-menu-item {{ request()->is('admin/bank-transactions/dashboard') ? 'active' : '' }}">
                                <i class="fas fa-chart-pie"></i>
                                <span>Dashboard</span>
                            </a>
                            
                            <a href="{{ route('admin.bank-transactions.index') }}" class="mega-menu-item {{ request()->is('admin/bank-transactions') && !request()->is('admin/bank-transactions/import') && !request()->is('admin/bank-transactions/dashboard') ? 'active' : '' }}">
                                <i class="fas fa-list-ul"></i>
                                <span>Transactions</span>
                            </a>
                            
                            <a href="{{ route('admin.bank-transactions.import-form') }}" class="mega-menu-item {{ request()->is('admin/bank-transactions/import') ? 'active' : '' }}">
                                <i class="fas fa-upload"></i>
                                <span>Import CSV</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="farm" title="Farm Management">
                    <i class="fas fa-seedling"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">Farm Management</div>
                        <div class="mega-menu-grid">
                            <a href="/admin/farmos" class="mega-menu-item {{ request()->is('admin/farmos') ? 'active' : '' }}">
                                <i class="fas fa-seedling"></i>
                                <span>farmOS Dashboard</span>
                            </a>
                            
                            <a href="/admin/farmos/crop-plans" class="mega-menu-item {{ request()->is('admin/farmos/crop-plans*') ? 'active' : '' }}">
                                <i class="fas fa-calendar-check"></i>
                                <span>Crop Plans</span>
                            </a>
                            
                            <a href="/admin/farmos/fieldkit" class="mega-menu-item {{ request()->is('admin/farmos/fieldkit*') ? 'active' : '' }}">
                                <i class="fas fa-satellite"></i>
                                <span>FieldKit Sensors</span>
                            </a>
                            
                            <a href="/admin/farmos/planting-chart" class="mega-menu-item {{ request()->is('admin/farmos/planting-chart*') ? 'active' : '' }}">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Planting Chart</span>
                            </a>
                            
                            <a href="/admin/farmos/succession-planning" class="mega-menu-item {{ request()->is('admin/farmos/succession-planning*') ? 'active' : '' }}">
                                <i class="fas fa-layer-group"></i>
                                <span>Succession Planning</span>
                            </a>
                            
                            <a href="/admin/farmos/harvests" class="mega-menu-item {{ request()->is('admin/farmos/harvests*') ? 'active' : '' }}">
                                <i class="fas fa-apple-alt"></i>
                                <span>Harvest Logs</span>
                            </a>
                            
                            <a href="/admin/farmos/stock" class="mega-menu-item {{ request()->is('admin/farmos/stock*') ? 'active' : '' }}">
                                <i class="fas fa-boxes"></i>
                                <span>Stock Management</span>
                            </a>
                            
                            <a href="/admin/weather" class="mega-menu-item {{ request()->is('admin/weather*') ? 'active' : '' }}">
                                <i class="fas fa-cloud-sun"></i>
                                <span>Weather Dashboard</span>
                            </a>

                            <a href="/admin/ros" class="mega-menu-item {{ request()->is('admin/ros*') ? 'active' : '' }}">
                                <i class="fas fa-robot"></i>
                                <span>ROS Swarm</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="system" title="System">
                    <i class="fas fa-cog"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">System</div>
                        <div class="mega-menu-grid">
                            <a href="/admin/admin-users" class="mega-menu-item {{ request()->is('admin/admin-users*') ? 'active' : '' }}">
                                <i class="fas fa-users-cog"></i>
                                <span>Admin Users</span>
                            </a>
                            
                            <a href="/admin/stripe" class="mega-menu-item {{ request()->is('admin/stripe*') ? 'active' : '' }}">
                                <i class="fas fa-credit-card"></i>
                                <span>Stripe Payments</span>
                            </a>
                            
                            <a href="/admin/companies-house" class="mega-menu-item {{ request()->is('admin/companies-house*') ? 'active' : '' }}">
                                <i class="fas fa-building"></i>
                                <span>Companies House</span>
                            </a>
                        
                            <a href="/admin/settings" class="mega-menu-item {{ request()->is('admin/settings*') ? 'active' : '' }}">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                            
                            <a href="/admin/logs" class="mega-menu-item {{ request()->is('admin/logs*') ? 'active' : '' }}">
                                <i class="fas fa-file-alt"></i>
                                <span>System Logs</span>
                            </a>

                            <a href="/admin/system/updates" class="mega-menu-item {{ request()->is('admin/system/updates*') ? 'active' : '' }}">
                                <i class="fas fa-download"></i>
                                <span>System Updates</span>
                            </a>

                            <a href="/admin/chatbot-settings" class="mega-menu-item {{ request()->is('admin/chatbot-settings*') ? 'active' : '' }}">
                                <i class="fas fa-robot"></i>
                                <span>Chatbot Settings</span>
                            </a>
                            
                            <a href="/admin/unified-backup" class="mega-menu-item {{ request()->is('admin/unified-backup*') ? 'active' : '' }}">
                                <i class="fas fa-server"></i>
                                <span>Unified Backup</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="ai-helper" title="AI Helper">
                    <i class="fas fa-brain"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">AI Helper</div>
                        <div class="ai-helper-sidebar-container">
                            <div class="ai-helper-sidebar-header">
                                <i class="fas fa-robot ai-helper-icon"></i>
                                <div class="ai-helper-context">Ask me anything about your farm operations</div>
                            </div>
                            <div class="ai-helper-sidebar-messages" id="aiMessages">
                                <div class="ai-message">
                                    <div class="ai-avatar">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div class="ai-content">
                                        <p>Hello! I'm your AI farming assistant. I can help you with crop planning, succession planning, weather analysis, and farm management questions.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="ai-helper-sidebar-input">
                                <input type="text" id="aiInput" placeholder="Ask me about your farm...">
                                <button class="btn btn-primary btn-sm ai-send-button" id="aiSendButton">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="nav-section mega-menu-trigger" data-section="external" title="External">
                    <i class="fas fa-external-link-alt"></i>
                    <div class="mega-menu-overlay">
                        <div class="mega-menu-title">External Links</div>
                        <div class="mega-menu-grid">
                            <a href="{{ $customerSiteUrl ?? '#' }}" class="mega-menu-item" target="_blank">
                                <i class="fas fa-store"></i>
                                <span>Customer Site</span>
                            </a>
                            
                            <a href="{{ $wpAdminUrl ?? '#' }}" class="mega-menu-item" target="_blank">
                                <i class="fab fa-wordpress"></i>
                                <span>WordPress Admin</span>
                            </a>
                            
                            <a href="{{ $fieldkitUrl }}" class="mega-menu-item" target="_blank">
                                <i class="fas fa-satellite"></i>
                                <span>FieldKit</span>
                            </a>
                            
                            <a href="{{ $farmosUrl }}" class="mega-menu-item" target="_blank">
                                <i class="fas fa-seedling"></i>
                                <span>farmOS</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
        @endif
        
        <!-- Main content -->
        <div class="main-content" id="mainContent">
            <!-- Page content -->
            <div class="content-wrapper">
                @yield('content')
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const body = document.body;
            
            // Ensure all elements exist before proceeding
            if (!sidebar || !mainContent) {
                console.error('Sidebar or main content element not found');
                return;
            }
            
            // Handle window resize for mobile responsiveness
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('show');
                    }
                }
            });
        });
    </script>
    
    <!-- Mega Menu Positioning Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to position mega menus relative to their nav sections
            function positionMegaMenus() {
                const navSections = document.querySelectorAll('.nav-section');
                
                navSections.forEach(section => {
                    const megaMenu = section.querySelector('.mega-menu-overlay');
                    if (megaMenu) {
                        const sectionRect = section.getBoundingClientRect();
                        const sidebarRect = document.querySelector('.sidebar').getBoundingClientRect();
                        const menuHeight = megaMenu.offsetHeight || 400; // Estimate height if not yet rendered
                        const viewportHeight = window.innerHeight;
                        
                        // Calculate where the menu would appear
                        let proposedTop = sectionRect.top;
                        
                        // If the menu would extend below the viewport, position it above the section
                        if (proposedTop + menuHeight > viewportHeight) {
                            proposedTop = Math.max(10, viewportHeight - menuHeight - 10); // Leave some margin
                        }
                        
                        // Position the mega menu
                        megaMenu.style.top = proposedTop + 'px';
                    }
                });
            }
            
            // Position mega menus on page load
            positionMegaMenus();
            
            // Reposition on window resize
            window.addEventListener('resize', positionMegaMenus);
            
            // Reposition on scroll (in case sidebar scrolls)
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.addEventListener('scroll', positionMegaMenus);
            }
        });
    </script>
    
    <!-- AI Helper Widget Script -->
    <script src="{{ asset('js/ai-helper-widget.js?v=5') }}"></script>
    
    @yield('scripts')
    @stack('scripts')
    @stack('styles')
</body>
</html>

@extends('layouts.app')

@section('title', 'MWF Admin Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">This Week's Deliveries</h6>
                        <h2 class="mb-0">{{ $deliveryStats['active'] ?? '0' }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-truck fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/deliveries" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">This Week's Collections</h6>
                        <h2 class="mb-0">{{ $deliveryStats['collections'] ?? '0' }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/deliveries?tab=collections" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Customers</h6>
                        <h2 class="mb-0">{{ $customerStats['total'] ?? '0' }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/users" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">System Status</h6>
                        <h2 class="mb-0"><i class="fas fa-check-circle"></i></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-server fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/settings" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="/admin/deliveries" class="btn btn-outline-primary w-100 p-3">
                            <i class="fas fa-truck mb-2 d-block fa-2x"></i>
                            <h6>Manage Deliveries</h6>
                            <small class="text-muted">View and manage delivery schedules</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="/admin/users" class="btn btn-outline-success w-100 p-3">
                            <i class="fas fa-users mb-2 d-block fa-2x"></i>
                            <h6>Customer Management</h6>
                            <small class="text-muted">Search and manage customers</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="/admin/reports" class="btn btn-outline-info w-100 p-3">
                            <i class="fas fa-chart-bar mb-2 d-block fa-2x"></i>
                            <h6>Generate Reports</h6>
                            <small class="text-muted">View delivery and sales reports</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="/admin/settings" class="btn btn-outline-warning w-100 p-3">
                            <i class="fas fa-cog mb-2 d-block fa-2x"></i>
                            <h6>System Settings</h6>
                            <small class="text-muted">Configure system preferences</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="activity-item d-flex align-items-center mb-3">
                    <div class="activity-icon bg-primary text-white rounded-circle me-3">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div>
                        <div class="fw-bold">New delivery scheduled</div>
                        <small class="text-muted">2 minutes ago</small>
                    </div>
                </div>
                <div class="activity-item d-flex align-items-center mb-3">
                    <div class="activity-icon bg-success text-white rounded-circle me-3">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Customer registration</div>
                        <small class="text-muted">15 minutes ago</small>
                    </div>
                </div>
                <div class="activity-item d-flex align-items-center mb-3">
                    <div class="activity-icon bg-info text-white rounded-circle me-3">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Order completed</div>
                        <small class="text-muted">1 hour ago</small>
                    </div>
                </div>
                <div class="text-center">
                    <a href="/admin/logs" class="btn btn-sm btn-outline-secondary">View All Activity</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- System Information -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>System Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-database fa-2x text-success mb-2"></i>
                            <h6>Database Status</h6>
                            <span class="badge bg-success">Connected</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-server fa-2x text-primary mb-2"></i>
                            <h6>Laravel Version</h6>
                            <span class="badge bg-primary">{{ app()->version() }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                            <h6>Last Updated</h6>
                            <span class="badge bg-info">{{ date('M j, Y') }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-leaf fa-2x text-success mb-2"></i>
                            <h6>Environment</h6>
                            <span class="badge bg-{{ app()->environment() === 'production' ? 'success' : 'warning' }}">
                                {{ ucfirst(app()->environment()) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Fortnightly Delivery Information --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card" style="border-left: 4px solid #27ae60;">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-week me-2" style="color: #27ae60;"></i>
                    Fortnightly Delivery Schedule
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <h6 class="mb-1">Current Week Information</h6>
                            <p class="mb-1">
                                <strong>ISO Week {{ $fortnightlyInfo['current_iso_week'] ?? date('W') }} of {{ date('Y') }}</strong> - 
                                <span class="badge bg-{{ ($fortnightlyInfo['current_week'] ?? 'A') === 'A' ? 'success' : 'info' }} ms-1">
                                    Week {{ $fortnightlyInfo['current_week'] ?? 'A' }}
                                </span>
                            </p>
                            <small class="text-muted">
                                {{ ($fortnightlyInfo['current_iso_week'] ?? date('W')) % 2 === 1 ? 'Odd' : 'Even' }} week numbers = Week {{ $fortnightlyInfo['current_week'] ?? 'A' }}
                            </small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stat-item">
                                    <h4 class="mb-0 text-primary">{{ $fortnightlyInfo['weekly_count'] ?? 0 }}</h4>
                                    <small class="text-muted">Weekly Subscriptions</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-item">
                                    <h4 class="mb-0 text-success">{{ $fortnightlyInfo['fortnightly_count'] ?? 0 }}</h4>
                                    <small class="text-muted">Fortnightly (Active This Week)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 text-end">
                        <div class="bg-light p-3 rounded">
                            <h6 class="mb-2">Next Week</h6>
                            <span class="badge bg-{{ ($fortnightlyInfo['next_week_type'] ?? 'B') === 'A' ? 'success' : 'info' }} fs-6">
                                Week {{ $fortnightlyInfo['next_week_type'] ?? 'B' }}
                            </span>
                            <div class="mt-2">
                                <small class="text-muted">Fortnightly deliveries</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if(isset($fortnightlyInfo['error']))
                <div class="alert alert-warning mt-3 mb-0">
                    <small><i class="fas fa-exclamation-triangle me-1"></i>
                    Unable to load fortnightly data: {{ $fortnightlyInfo['error'] }}</small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Planting Recommendations --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-primary" id="planting-rec-card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-seedling me-2"></i>What To Plant This Week</h5>
                <button class="btn btn-sm btn-outline-light" id="refresh-planting-recs" type="button"><i class="fas fa-sync"></i></button>
            </div>
            <div class="card-body" id="planting-recs-body">
                <div class="text-muted" id="planting-recs-loading"><i class="fas fa-spinner fa-spin me-1"></i>Loading recommendations…</div>
                <div id="planting-recs-content" class="d-none">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <h6 class="text-primary mb-2">Direct Sow</h6>
                            <ul class="list-unstyled small mb-0" id="direct-sow-list"></ul>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-success mb-2">Transplant Out</h6>
                            <ul class="list-unstyled small mb-0" id="transplant-out-list"></ul>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-info mb-2">Start In Trays</h6>
                            <ul class="list-unstyled small mb-0" id="start-trays-list"></ul>
                        </div>
                    </div>
                    <div id="planting-warnings" class="mt-2"></div>
                    <div class="mt-2 text-end"><small class="text-muted" id="planting-generated-at"></small></div>
                </div>
                <div id="planting-recs-error" class="alert alert-warning d-none mb-0"></div>
            </div>
        </div>
    </div>
</div>

<!-- FarmOS Map Integration -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-map me-2"></i>FarmOS Map
                </h5>
            </div>
            <div class="card-body">
                <div id="farmos-map-error" class="alert alert-warning d-none"></div>
                <div id="farmos-map" style="height: 400px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- WordPress Integration -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fab fa-wordpress me-2"></i>WordPress Integration
                </h5>
            </div>
            <div class="card-body">
                @if(session('wp_authenticated'))
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>WordPress Access Integrated!</strong> 
                        You're automatically logged into WordPress admin.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ session('wp_admin_url') }}" target="_blank" class="btn btn-success w-100 p-3">
                                <i class="fab fa-wordpress mb-2 d-block fa-2x"></i>
                                <h6>WordPress Admin</h6>
                                <small class="text-white-50">Manage WordPress backend</small>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ str_replace('/wp-admin/', '/wp-admin/edit.php?post_type=product', session('wp_admin_url')) }}" target="_blank" class="btn btn-outline-success w-100 p-3">
                                <i class="fas fa-shopping-cart mb-2 d-block fa-2x"></i>
                                <h6>WooCommerce Products</h6>
                                <small class="text-muted">Manage products & orders</small>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ str_replace('/wp-admin/', '/wp-admin/users.php', session('wp_admin_url')) }}" target="_blank" class="btn btn-outline-success w-100 p-3">
                                <i class="fas fa-users mb-2 d-block fa-2x"></i>
                                <h6>WordPress Users</h6>
                                <small class="text-muted">Manage WordPress users</small>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ str_replace('/wp-admin/', '/wp-admin/plugins.php', session('wp_admin_url')) }}" target="_blank" class="btn btn-outline-success w-100 p-3">
                                <i class="fas fa-plug mb-2 d-block fa-2x"></i>
                                <h6>Plugins</h6>
                                <small class="text-muted">Manage WordPress plugins</small>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>WordPress Integration Unavailable</strong><br>
                        WordPress authentication failed during login. You can still access WordPress manually.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ config('services.wp_api.url') ? str_replace('/wp-json', '/wp-admin/', config('services.wp_api.url')) : '#' }}" target="_blank" class="btn btn-outline-secondary w-100 p-3">
                                <i class="fab fa-wordpress mb-2 d-block fa-2x"></i>
                                <h6>WordPress Admin</h6>
                                <small class="text-muted">Manual login required</small>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-info w-100 p-3" onclick="retryWordPressAuth()">
                                <i class="fas fa-sync mb-2 d-block fa-2x"></i>
                                <h6>Retry Integration</h6>
                                <small class="text-white-50">Attempt WordPress login again</small>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .activity-icon {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }
    
    /* Enhanced map layer control styling */
    .leaflet-control-layers {
        background: rgba(255, 255, 255, 0.95) !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2) !important;
        border: 1px solid #ccc !important;
    }
    
    .leaflet-control-layers-toggle {
        background-color: #27ae60 !important;
        width: 36px !important;
        height: 36px !important;
    }
    
    .leaflet-control-layers-expanded {
        padding: 8px 12px !important;
        min-width: 150px;
    }
    
    .leaflet-control-layers label {
        font-weight: 500 !important;
        margin: 6px 0 !important;
        cursor: pointer !important;
    }
    
    #farmos-map {
        border-radius: 8px;
        overflow: hidden;
    }
    
    /* Enhanced popup styling */
    .farmos-popup .leaflet-popup-content {
        margin: 0 !important;
        line-height: 1.4;
    }
    
    .farmos-popup-content {
        font-family: inherit;
    }
    
    .farmos-popup-content .popup-header {
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }
    
    .farmos-popup-content .popup-header h6 {
        color: #27ae60;
        margin: 0;
    }
    
    .farmos-popup-content .detail-item {
        margin-bottom: 4px;
        font-size: 0.9em;
    }
    
    .farmos-popup-content .asset-item {
        padding: 2px 0;
        border-left: 2px solid #27ae60;
        padding-left: 8px;
        margin: 2px 0;
        background: rgba(39, 174, 96, 0.05);
    }
    
    .farmos-popup-content .badge-sm {
        font-size: 0.75em;
        padding: 2px 6px;
    }
    
    .leaflet-popup-content-wrapper {
        border-radius: 8px;
    }
    
    .leaflet-popup-tip {
        background: white;
    }
    
    /* Polygon styling improvements */
    .leaflet-interactive {
        cursor: pointer;
    }
    
    .leaflet-interactive:hover {
        filter: brightness(1.1);
    }
    
    /* Ensure beds appear above blocks */
    .leaflet-overlay-pane svg g:last-child {
        pointer-events: auto;
    }
</style>
@endsection

@section('scripts')
<script>
// Retry WordPress authentication
function retryWordPressAuth() {
    // Show loading state
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mb-2 d-block fa-2x"></i><h6>Connecting...</h6><small class="text-white-50">Please wait...</small>';
    
    // Make request to retry WordPress authentication
    fetch('/admin/auth/retry-wordpress', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated status
            location.reload();
        } else {
            // Show error message
            alert('WordPress authentication failed: ' + (data.error || 'Unknown error'));
            // Restore button
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('WordPress auth retry error:', error);
        alert('Connection failed. Please try again.');
        // Restore button
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}

// Show WordPress integration status in console
@if(session('wp_authenticated'))
console.log('✅ WordPress Integration: Active');
console.log('🔗 WordPress Admin URL:', '{{ session('wp_admin_url') }}');
@else
console.log('⚠️ WordPress Integration: Not Available');
@endif

// --- FarmOS Map Integration ---
document.addEventListener('DOMContentLoaded', function() {
    function loadLeafletAssets(callback) {
        if (window.L && window.L.map) { callback(); return; }
        
        // Load Leaflet CSS
        var leafletCss = document.createElement('link');
        leafletCss.rel = 'stylesheet';
        leafletCss.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(leafletCss);
        
        // Load Fullscreen plugin CSS
        var fullscreenCss = document.createElement('link');
        fullscreenCss.rel = 'stylesheet';
        fullscreenCss.href = 'https://unpkg.com/leaflet-fullscreen@1.0.1/dist/leaflet.fullscreen.css';
        document.head.appendChild(fullscreenCss);
        
        // Load Leaflet JS
        var leafletJs = document.createElement('script');
        leafletJs.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        leafletJs.onload = function() {
            // Load Fullscreen plugin JS
            var fullscreenJs = document.createElement('script');
            fullscreenJs.src = 'https://unpkg.com/leaflet-fullscreen@1.0.1/dist/Leaflet.fullscreen.min.js';
            fullscreenJs.onload = callback;
            document.body.appendChild(fullscreenJs);
        };
        document.body.appendChild(leafletJs);
    }

    function showMapError(msg) {
        var err = document.getElementById('farmos-map-error');
        if (err) { err.textContent = msg; err.classList.remove('d-none'); }
    }

    function createEnhancedPopup(properties) {
        var popup = '<div class="farmos-popup-content">';
        
        // Header with asset name and FarmOS link
        popup += '<div class="popup-header">';
        popup += '<h6 class="mb-1"><i class="fas fa-map-marker-alt text-success me-1"></i>' + 
                 (properties.name || 'Unnamed Area') + '</h6>';
        
        popup += '<div class="btn-group-vertical w-100 mb-2">';
        
        // Primary FarmOS link using numeric ID
        if (properties.farmos_url) {
            popup += '<a href="' + properties.farmos_url + '" target="_blank" class="btn btn-sm btn-outline-success">';
            popup += '<i class="fas fa-external-link-alt me-1"></i>View in FarmOS';
            popup += '</a>';
        } else {
            popup += '<span class="btn btn-sm btn-outline-secondary disabled">';
            popup += '<i class="fas fa-exclamation-triangle me-1"></i>FarmOS URL unavailable';
            popup += '</span>';
        }
        popup += '</div>';
        popup += '</div>';
        
        // Asset details
        popup += '<div class="popup-details">';
        
        if (properties.land_type) {
            popup += '<div class="detail-item"><strong>Type:</strong> ' + properties.land_type + '</div>';
        }
        
        if (properties.status) {
            var statusClass = properties.status === 'active' ? 'success' : 'secondary';
            popup += '<div class="detail-item"><strong>Status:</strong> <span class="badge bg-' + statusClass + '">' + 
                     properties.status + '</span></div>';
        }
        
        // Related assets information
        if (properties.asset_details && properties.asset_details.asset_count > 0) {
            popup += '<div class="detail-item mt-2">';
            popup += '<strong><i class="fas fa-seedling text-success me-1"></i>Assets in this location:</strong>';
            popup += '<div class="mt-1">';
            
            var assets = properties.asset_details.related_assets || [];
            var displayLimit = 3; // Show first 3 assets
            
            for (var i = 0; i < Math.min(assets.length, displayLimit); i++) {
                var asset = assets[i];
                popup += '<div class="asset-item">';
                popup += '<i class="fas fa-leaf text-success me-1"></i>';
                popup += '<small>' + (asset.name || 'Unnamed Asset') + '</small>';
                if (asset.status) {
                    popup += ' <span class="badge bg-info badge-sm">' + asset.status + '</span>';
                }
                popup += '</div>';
            }
            
            if (assets.length > displayLimit) {
                popup += '<small class="text-muted">+ ' + (assets.length - displayLimit) + ' more assets</small>';
            }
            
            popup += '</div></div>';
        }
        
        if (properties.notes) {
            popup += '<div class="detail-item mt-2"><strong>Notes:</strong><br><small>' + 
                     properties.notes + '</small></div>';
        }
        
        popup += '</div></div>';
        
        return popup;
    }

    function handleSmartPolygonClick(clickEvent, geoJsonLayer, mapInstance) {
        // Prevent event bubbling
        L.DomEvent.stopPropagation(clickEvent);
        
        var clickPoint = clickEvent.latlng;
        var clickedLayers = [];
        
        // Find all layers that contain the click point
        geoJsonLayer.eachLayer(function(layer) {
            if (layer.feature && layer.feature.geometry) {
                if (isPointInPolygon(clickPoint, layer.feature)) {
                    var props = layer.feature.properties;
                    var area = props.area_size || calculatePolygonArea(layer.feature.geometry);
                    var isBed = props.is_bed || (props.name && props.name.toLowerCase().includes('bed'));
                    var isBlock = props.is_block || (props.name && props.name.toLowerCase().includes('block'));
                    var distanceToEdge = getDistanceToPolygonEdge(clickPoint, layer.feature);
                    
                    clickedLayers.push({
                        layer: layer,
                        feature: layer.feature,
                        area: area,
                        isBed: isBed,
                        isBlock: isBlock,
                        distanceToEdge: distanceToEdge,
                        name: props.name || 'Unknown',
                        hierarchy: props.asset_hierarchy || {}
                    });
                }
            }
        });
        
        if (clickedLayers.length === 0) return;
        
        // Smart selection logic:
        // 1. If clicked near edge (< 15 meters) of a block, prefer the block
        // 2. Otherwise, prefer beds over blocks
        // 3. Among beds, prefer smaller area (more specific)
        // 4. Among blocks, prefer smaller area
        clickedLayers.sort(function(a, b) {
            var edgeThreshold = 15; // meters
            
            // If clicked near edge of a block, strongly prefer the block
            if (a.isBlock && a.distanceToEdge < edgeThreshold && !b.isBlock) {
                return -1;
            }
            if (b.isBlock && b.distanceToEdge < edgeThreshold && !a.isBlock) {
                return 1;
            }
            
            // Prefer beds over blocks when not near block edge
            if (a.isBed && !b.isBed && b.distanceToEdge >= edgeThreshold) {
                return -1;
            }
            if (b.isBed && !a.isBed && a.distanceToEdge >= edgeThreshold) {
                return 1;
            }
            
            // Among same type, prefer smaller area (more specific)
            return a.area - b.area;
        });
        
        var selectedLayer = clickedLayers[0];
        console.log('Smart selection:', selectedLayer.name, {
            area: selectedLayer.area,
            isBed: selectedLayer.isBed,
            isBlock: selectedLayer.isBlock,
            distanceToEdge: selectedLayer.distanceToEdge,
            totalCandidates: clickedLayers.length
        });
        
        // Open the popup for the selected layer
        selectedLayer.layer.openPopup(clickPoint);
        
        // Highlight the selected polygon temporarily
        var originalStyle = selectedLayer.layer.options;
        selectedLayer.layer.setStyle({
            color: '#ff6b6b',
            weight: 4,
            fillOpacity: 0.6
        });
        
        // Reset style after 2 seconds
        setTimeout(function() {
            selectedLayer.layer.setStyle(originalStyle);
        }, 2000);
    }

    function isPointInPolygon(point, feature) {
        // Simple point-in-polygon check for GeoJSON
        if (feature.geometry.type === 'Polygon') {
            var coords = feature.geometry.coordinates[0];
            return pointInPolygon([point.lng, point.lat], coords);
        }
        return false;
    }

    function pointInPolygon(point, polygon) {
        var x = point[0], y = point[1];
        var inside = false;
        
        for (var i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            var xi = polygon[i][0], yi = polygon[i][1];
            var xj = polygon[j][0], yj = polygon[j][1];
            
            if (((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi)) {
                inside = !inside;
            }
        }
        
        return inside;
    }

    function calculatePolygonArea(geometry) {
        // Simple area calculation for polygon comparison
        if (geometry.type === 'Polygon') {
            var coords = geometry.coordinates[0];
            var area = 0;
            for (var i = 0; i < coords.length - 1; i++) {
                area += (coords[i][0] * coords[i + 1][1] - coords[i + 1][0] * coords[i][1]);
            }
            return Math.abs(area) / 2;
        }
        return 0;
    }

    function getDistanceToPolygonEdge(point, feature) {
        // Calculate approximate distance to polygon edge
        if (feature.geometry.type === 'Polygon') {
            var coords = feature.geometry.coordinates[0];
            var minDistance = Infinity;
            
            for (var i = 0; i < coords.length - 1; i++) {
                var p1 = L.latLng(coords[i][1], coords[i][0]);
                var p2 = L.latLng(coords[i + 1][1], coords[i + 1][0]);
                var distance = distanceToLineSegment(point, p1, p2);
                minDistance = Math.min(minDistance, distance);
            }
            
            return minDistance;
        }
        return 0;
    }

    function distanceToLineSegment(point, lineStart, lineEnd) {
        var A = point.lng - lineStart.lng;
        var B = point.lat - lineStart.lat;
        var C = lineEnd.lng - lineStart.lng;
        var D = lineEnd.lat - lineStart.lat;

        var dot = A * C + B * D;
        var lenSq = C * C + D * D;
        var param = -1;
        
        if (lenSq !== 0) {
            param = dot / lenSq;
        }

        var xx, yy;
        if (param < 0) {
            xx = lineStart.lng;
            yy = lineStart.lat;
        } else if (param > 1) {
            xx = lineEnd.lng;
            yy = lineEnd.lat;
        } else {
            xx = lineStart.lng + param * C;
            yy = lineStart.lat + param * D;
        }

        var dx = point.lng - xx;
        var dy = point.lat - yy;
        return Math.sqrt(dx * dx + dy * dy) * 111000; // Convert to meters approximately
    }

    function initFarmOSMap() {
        var map = L.map('farmos-map').setView([53.215252, -0.419950], 15); // Middle World Farms front gates
        
        // Base layers
        var openStreetMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        });
        
        var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: '© Esri, Maxar, Earthstar Geographics, and the GIS User Community'
        });
        
        var hybrid = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: '© Esri, Maxar, Earthstar Geographics, and the GIS User Community'
        });
        
        // Add labels overlay for hybrid view
        var labels = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
            opacity: 0.3
        });
        
        // Add default layer
        openStreetMap.addTo(map);
        
        // Layer control
        var baseLayers = {
            "🗺️ Street Map": openStreetMap,
            "🛰️ Satellite": satellite,
            "🌍 Hybrid": L.layerGroup([hybrid, labels])
        };
        
        var layerControl = L.control.layers(baseLayers, null, {
            position: 'topright',
            collapsed: false
        }).addTo(map);
        
        // Add fullscreen control
        map.addControl(new L.Control.Fullscreen({
            title: {
                'false': 'View Fullscreen',
                'true': 'Exit Fullscreen'
            }
        }));
        
        // Add scale control
        L.control.scale({
            position: 'bottomleft',
            metric: true,
            imperial: false
        }).addTo(map);

        fetch(window.location.origin + '/admin/farmos-map-data', {credentials:'same-origin'})
            .then(function(response) { 
                console.log('farmOS map response status:', response.status);
                if(!response.ok) throw new Error('HTTP '+response.status);
                return response.json(); 
            })
            .then(function(data) {
                console.log('farmOS map data received:', data);
                if (!data || !data.features || !Array.isArray(data.features)) {
                    console.warn('Invalid or empty features data:', data);
                    if (data && data.warning) {
                        showMapError('Warning: ' + data.warning);
                    } else if (data && data.error) {
                        showMapError('Error: ' + data.error);
                    } else {
                        showMapError('No geometry data received from FarmOS.');
                    }
                    return;
                }
                
                console.log('Adding', data.features.length, 'features to map');
                
                // Use standard Leaflet GeoJSON processing with enhanced popups and smart selection
                var geojson = L.geoJSON(data, {
                    style: function(feature) {
                        var props = feature.properties;
                        var isBed = props.is_bed || (props.name && props.name.toLowerCase().includes('bed'));
                        var isBlock = props.is_block || (props.name && props.name.toLowerCase().includes('block'));
                        
                        if (isBed) {
                            return { 
                                color: '#e74c3c', 
                                weight: 2, 
                                fillOpacity: 0.4,
                                fillColor: '#e74c3c',
                                pane: 'overlayPane'  // Beds on top
                            };
                        } else if (isBlock) {
                            return { 
                                color: '#27ae60', 
                                weight: 2, 
                                fillOpacity: 0.2,
                                fillColor: '#27ae60',
                                pane: 'tilePane'  // Blocks below
                            };
                        } else {
                            return { 
                                color: '#3498db', 
                                weight: 2, 
                                fillOpacity: 0.2,
                                fillColor: '#3498db'
                            };
                        }
                    },
                    onEachFeature: function(feature, layer) {
                        // Inject area into popup details
                        if(feature.properties && feature.properties.area_size_sqm){
                            feature.properties.area_hectares = (feature.properties.area_size_sqm/10000).toFixed(3);
                        }
                        if (feature.properties && feature.properties.name) {
                            var popupContent = createEnhancedPopup(feature.properties);
                            layer.bindPopup(popupContent, {
                                maxWidth: 400,
                                className: 'farmos-popup'
                            });
                            layer.feature = feature;
                            layer.on('click', function(e) {
                                // Lazy load related plant assets if not loaded yet
                                var props = e.target.feature.properties;
                                if(props.lazy_details && !props.asset_details_loading && !props.asset_details_loaded){
                                    props.asset_details_loading = true;
                                    fetch(window.location.origin + '/admin/farmos-map-data?asset='+encodeURIComponent(props.id), {credentials:'same-origin'})
                                        .then(r=> r.json())
                                        .then(extra => {
                                            if(extra && extra.asset_details){
                                                props.asset_details = extra.asset_details;
                                                props.asset_details_loaded = true;
                                                var newContent = createEnhancedPopup(props);
                                                e.target.setPopupContent(newContent);
                                            }
                                        })
                                        .catch(err=>console.warn('Lazy detail load failed', err));
                                }
                                handleSmartPolygonClick(e, geojson, map);
                            });
                        }
                    }
                }).addTo(map);
                
                // Fit map to show all features
                if (geojson.getBounds && geojson.getBounds().isValid()) {
                    map.fitBounds(geojson.getBounds(), { padding: [10, 10] });
                } else {
                    console.log('Using default zoom for Middle World Farms');
                    map.setView([53.215252, -0.419950], 15);
                }
            })
            .catch(function(err) {
                console.error('FarmOS Map Error:', err);
                showMapError('Failed to load map data: ' + err.message + ' - retrying...');
                setTimeout(function(){
                    fetch(window.location.origin + '/admin/farmos-map-data', {credentials:'same-origin'})
                        .then(r=> r.json())
                        .then(d=>{ console.log('Retry received', d); })
                        .catch(e=> console.error('Retry failed', e));
                },1500);
            });
    }

    if (document.getElementById('farmos-map')) {
        loadLeafletAssets(initFarmOSMap);
    }
});
</script>

<script>
// Planting recommendations fetch
function loadPlantingRecommendations(){
    const loading = document.getElementById('planting-recs-loading');
    const content = document.getElementById('planting-recs-content');
    const errBox = document.getElementById('planting-recs-error');
    if(!loading) return;
    loading.classList.remove('d-none');
    content.classList.add('d-none');
    errBox.classList.add('d-none');
    fetch('/admin/planting-recommendations', {credentials:'same-origin'})
        .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(data=>{
            loading.classList.add('d-none');
            content.classList.remove('d-none');
            fillCropList('direct-sow-list', data.direct_sow);
            fillCropList('transplant-out-list', data.transplant_out);
            fillCropList('start-trays-list', data.start_in_trays);
            const gen = document.getElementById('planting-generated-at');
            if(gen) gen.textContent = 'Week '+data.week+' ('+data.date_range+')';
            const warn = document.getElementById('planting-warnings');
            if(warn){
                warn.innerHTML='';
                if(data.warnings && data.warnings.length){
                    data.warnings.forEach(w=>{
                        const div=document.createElement('div');
                        div.className='small text-warning';
                        div.innerHTML='<i class="fas fa-exclamation-triangle me-1"></i>'+w;
                        warn.appendChild(div);
                    });
                }
            }
        })
        .catch(e=>{
            loading.classList.add('d-none');
            errBox.textContent='Failed to load: '+e.message;
            errBox.classList.remove('d-none');
        });
}
function fillCropList(id, items){
    const el = document.getElementById(id);
    if(!el) return;
    el.innerHTML='';
    if(!items || !items.length){
        el.innerHTML='<li class="text-muted">None due</li>'; return;
    }
    items.forEach(c=>{
        const li=document.createElement('li');
        const dens = c.est_plants_per_m2 ? (' <span class="badge bg-secondary ms-1">~'+c.est_plants_per_m2+'/m²</span>') : '';
        li.innerHTML='<i class="fas fa-leaf text-success me-1"></i>'+c.name+dens+(c.notes?'<br><small class="text-muted">'+c.notes+'</small>':'');
        el.appendChild(li);
    });
}

document.addEventListener('DOMContentLoaded', function(){
    if(document.getElementById('planting-rec-card')){
        loadPlantingRecommendations();
        const btn = document.getElementById('refresh-planting-recs');
        if(btn) btn.addEventListener('click', function(){ loadPlantingRecommendations(); });
    }
});
</script>
@endsection

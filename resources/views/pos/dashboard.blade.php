@extends('layouts.app')

@section('title', $title ?? 'POS System')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            @if(isset($user['is_admin']) && $user['is_admin'])
                <button class="btn btn-warning btn-sm" onclick="clearTestSales()" title="Clear all test POS orders">
                    <i class="fas fa-trash-alt me-1"></i>Clear Test Sales
                </button>
            @endif
        </div>
        <div class="text-center flex-grow-1">
            <h2 class="mb-0">Point of Sale</h2>
            <small class="text-white-50">Market Stall System</small>
        </div>
        <div style="width: 150px;"></div>
    </div>
@endsection

@section('styles')
<style>
    /* Mobile-First POS Styles */
    
    /* Touch-friendly buttons */
    @media (max-width: 768px) {
        /* Hide sidebar on mobile POS */
        .sidebar {
            display: none !important;
        }
        
        /* Full-width content on mobile */
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        /* Reduce container padding on mobile */
        .container-fluid {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        
        /* Tighter padding for POS interface */
        .col-12.p-4 {
            padding: 0.5rem !important;
        }
        
        /* Reduce card body padding */
        .card-body {
            padding: 0.75rem !important;
        }
        
        /* Tighter row margins */
        .row.mb-4 {
            margin-bottom: 0.75rem !important;
        }
        
        .mb-3 {
            margin-bottom: 0.5rem !important;
        }
        
        /* Reduce gap in button rows */
        .row.g-3 {
            gap: 0.5rem !important;
        }
        
        .row.g-2 {
            gap: 0.25rem !important;
        }
        
        /* Larger touch targets (minimum 44px) */
        .btn-lg {
            min-height: 56px;
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
        }
        
        .btn {
            min-height: 44px;
            padding: 0.5rem 0.75rem;
        }
        
        .btn-sm {
            min-height: 38px;
            padding: 0.35rem 0.6rem;
        }
        
        /* Compact fonts for mobile */
        body {
            font-size: 14px;
        }
        
        .card-title {
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }
        
        h3, h4, h5 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        /* Mobile modal sizing */
        .custom-modal-dialog {
            width: 95% !important;
            max-width: 95% !important;
            margin: 0.5rem !important;
        }
        
        .modal-dialog {
            max-width: 95% !important;
            margin: 0.5rem !important;
        }
        
        /* Subscription wizard - tighter spacing */
        .custom-modal-body {
            padding: 0.75rem !important;
        }
        
        .modal-header {
            padding: 0.75rem !important;
        }
        
        .box-card {
            margin-bottom: 0.25rem;
        }
        
        /* Subscription wizard box cards - very compact mobile view */
        .box-card .card-body {
            padding: 0.35rem !important;
        }
        
        .box-card .fa-3x {
            font-size: 1.25rem !important;
            margin-bottom: 0.15rem !important;
        }
        
        .box-card h5.card-title {
            font-size: 0.75rem !important;
            margin-bottom: 0.15rem !important;
            line-height: 1.1;
        }
        
        .box-card p.text-muted {
            font-size: 0.65rem !important;
            margin-bottom: 0.15rem !important;
            display: none; /* Hide description on mobile to save space */
        }
        
        .box-card h3 {
            font-size: 1rem !important;
            margin-bottom: 0.05rem !important;
        }
        
        .box-card small {
            font-size: 0.6rem !important;
        }
        
        /* POS page top section - minimal padding */
        #stats-section {
            margin-bottom: 2px !important;
        }
        
        #stats-section .card {
            margin-bottom: 2px !important;
        }
        
        #stats-section .card-body {
            padding: 0.5rem !important;
        }
        
        /* Quick actions section - minimal spacing */
        #main-content > .row.mb-4 {
            margin-bottom: 2px !important;
        }
        
        #main-content > .row.mb-4 .btn {
            margin-bottom: 2px !important;
        }
        
        /* Product cards - tighter spacing */
        .card {
            margin-bottom: 0.5rem;
        }
        
        .card-img-top {
            height: 120px !important;
        }
        
        /* Reduce product grid gaps */
        .col-6, .col-md-3, .col-md-4 {
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
        }
        
        /* Add left padding to product grid to center it */
        #products-grid {
            padding-left: 5px !important;
        }
        
        /* Product card spacing */
        #products-grid .card {
            margin-bottom: 0.5rem;
        }
        
        .card-body {
            padding: 0.75rem !important;
        }
        
        /* Stack form fields on mobile */
        .input-group {
            width: 100% !important;
        }
        
        /* Responsive search bar */
        #product-search {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        /* Cart button positioning */
        .btn-primary.position-relative {
            padding: 0.5rem 1rem;
        }
        
        /* Category filter buttons - more compact */
        .d-flex.flex-wrap.gap-2 {
            gap: 0.25rem !important;
        }
        
        /* Modal close buttons */
        .btn-close,
        .custom-close-btn {
            min-width: 44px;
            min-height: 44px;
        }
        
        /* Payment method buttons */
        .payment-method-card {
            min-height: 80px;
        }
        
        /* Scale controls */
        .scale-control-btn {
            min-height: 50px;
        }
    }
    
    /* Tablet adjustments */
    @media (min-width: 769px) and (max-width: 1024px) {
        .custom-modal-dialog {
            width: 90% !important;
            max-width: 800px !important;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- POS Interface -->
        <div class="col-12 p-4">
            <!-- Stats Cards -->
            <div class="row mb-4" id="stats-section">
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Today's Orders</h5>
                            <h3 id="today-orders">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Today's Sales</h5>
                            <h3 id="today-sales">£0.00</h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Orders</h5>
                            <h3 id="total-orders">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <h3 id="total-sales">£0.00</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->

            <!-- Main Content Area -->
            <div id="main-content">
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-6 col-md-3">
                        <button class="btn btn-success btn-lg w-100 mb-3" onclick="startSubscriptionWizard()">
                            <i class="fas fa-repeat fa-2x d-block mb-2"></i>
                            Subscription
                        </button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-primary btn-lg w-100 mb-3" onclick="showNewSale()">
                            <i class="fas fa-shopping-cart fa-2x d-block mb-2"></i>
                            New Sale
                        </button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-info btn-lg w-100 mb-3" onclick="showOrderHistory()">
                            <i class="fas fa-history fa-2x d-block mb-2"></i>
                            Order History
                        </button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-success btn-lg w-100 mb-3" onclick="showDeliveries()">
                            <i class="fas fa-truck fa-2x d-block mb-2"></i>
                            Weekly Schedule
                        </button>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
                            <h5 class="mb-2 mb-md-0">Available Products</h5>
                            <div class="d-flex gap-2 align-items-center w-100 w-md-auto">
                                <div class="input-group flex-grow-1" style="max-width: 250px;">
                                    <input type="text" class="form-control" id="product-search" placeholder="Search...">
                                    <button class="btn btn-outline-secondary" onclick="searchProducts()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <button class="btn btn-primary position-relative" onclick="showNewSale()">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-badge" style="display: none;">
                                        0
                                    </span>
                                </button>
                            </div>
                        </div>
                        <!-- Category Filter Buttons -->
                        <div class="d-none d-md-flex flex-wrap gap-2" id="main-category-filters">
                            <button class="btn btn-sm btn-primary" onclick="filterMainProducts('')">
                                <i class="fas fa-th-large"></i> All Products
                            </button>
                            <!-- Category buttons loaded dynamically -->
                        </div>
                        <!-- Mobile Category Dropdown -->
                        <div class="d-md-none">
                            <select class="form-select form-select-sm" id="mobile-category-filter" onchange="filterMainProducts(this.value)">
                                <option value="">All Products</option>
                                <!-- Options loaded dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="products-grid" class="row">
                            <!-- Products will be loaded here -->
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading products...</span>
                                </div>
                                <p class="mt-2">Loading products...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Subscription Wizard Modal -->
<div id="subscriptionWizardModal" class="custom-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="custom-modal-dialog" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 1200px; max-height: 90vh; overflow-y: auto;">
        <div class="custom-modal-content" style="background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title">
                    <i class="fas fa-repeat text-success"></i> New Subscription
                </h5>
                <button type="button" class="btn-close custom-close-btn" onclick="cancelSubscriptionWizard()"></button>
            </div>
            <div class="custom-modal-body" style="padding: 1.5rem;">
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted" id="wizard-step-text">Step 1 of 5</small>
                        <small class="text-muted">Create Subscription</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div id="wizard-progress" class="progress-bar bg-success" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Step 1: Box Selection -->
                <div id="subscription-step-1" class="subscription-step">
                    <h4 class="mb-4">Select Vegetable Box Size</h4>
                    <div class="row g-3">
                        <div class="col-6 col-md-6">
                            <div class="card box-card h-100 shadow-sm" data-box-type="Single Person Vegetable Box" style="cursor: pointer;" onclick="selectBoxType('Single Person Vegetable Box', 10.00)">
                                <div class="card-body text-center">
                                    <i class="fas fa-user fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Single Person Vegetable Box</h5>
                                    <p class="text-muted">Perfect for one person</p>
                                    <h3 class="text-success">£10.00</h3>
                                    <small class="text-muted">per delivery</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6">
                            <div class="card box-card h-100 shadow-sm" data-box-type="Couple's Vegetable box" style="cursor: pointer;" onclick="selectBoxType('Couple\\'s Vegetable box', 15.00)">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Couple's Vegetable Box</h5>
                                    <p class="text-muted">Ideal for two people</p>
                                    <h3 class="text-success">£15.00</h3>
                                    <small class="text-muted">per delivery</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6">
                            <div class="card box-card h-100 shadow-sm" data-box-type="Small Family Vegetable Box" style="cursor: pointer;" onclick="selectBoxType('Small Family Vegetable Box', 22.00)">
                                <div class="card-body text-center">
                                    <i class="fas fa-home fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Small Family Vegetable Box</h5>
                                    <p class="text-muted">Great for 3-4 people</p>
                                    <h3 class="text-success">£22.00</h3>
                                    <small class="text-muted">per delivery</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6">
                            <div class="card box-card h-100 shadow-sm" data-box-type="Large Family Vegetable Box" style="cursor: pointer;" onclick="selectBoxType('Large Family Vegetable Box', 25.00)">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                                    <h5 class="card-title">Large Family Vegetable Box</h5>
                                    <p class="text-muted">Perfect for 5+ people</p>
                                    <h3 class="text-success">£25.00</h3>
                                    <small class="text-muted">per delivery</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-success btn-lg" onclick="nextToFrequency()">
                            Next: Delivery Frequency <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Frequency Selection -->
                <div id="subscription-step-2" class="subscription-step" style="display: none;">
                    <h4 class="mb-4">Select Delivery Frequency</h4>
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="card frequency-card h-100 shadow-sm" data-frequency="weekly" style="cursor: pointer;" onclick="selectFrequency('weekly')">
                                <div class="card-body text-center p-5">
                                    <i class="fas fa-calendar-week fa-4x text-primary mb-3"></i>
                                    <h4 class="card-title">Weekly</h4>
                                    <p class="text-muted mb-0">Fresh vegetables every week</p>
                                    <p class="text-muted">Delivered same day each week</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card frequency-card h-100 shadow-sm" data-frequency="biweekly" style="cursor: pointer;" onclick="selectFrequency('biweekly')">
                                <div class="card-body text-center p-5">
                                    <i class="fas fa-calendar-alt fa-4x text-info mb-3"></i>
                                    <h4 class="card-title">Bi-weekly</h4>
                                    <p class="text-muted mb-0">Delivered every 2 weeks</p>
                                    <p class="text-muted">Fortnightly delivery schedule</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="showSubscriptionStep(1)">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="nextToPaymentPlan()">
                            Next: Payment Plan <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Payment Plan -->
                <div id="subscription-step-3" class="subscription-step" style="display: none;">
                    <h4 class="mb-4">Choose Payment Plan</h4>
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <div class="card payment-plan-card h-100 shadow-sm" data-plan="weekly" style="cursor: pointer;" onclick="selectPaymentPlan('weekly')">
                                <div class="card-body text-center p-4">
                                    <i class="fas fa-calendar-week fa-3x text-primary mb-3"></i>
                                    <h4 class="card-title">Weekly</h4>
                                    <p class="text-muted mb-0">Pay per delivery</p>
                                    <p class="text-muted small">Most flexible option</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card payment-plan-card h-100 shadow-sm" data-plan="monthly" style="cursor: pointer;" onclick="selectPaymentPlan('monthly')">
                                <div class="card-body text-center p-4">
                                    <i class="fas fa-calendar-alt fa-3x text-success mb-3"></i>
                                    <h4 class="card-title">Monthly</h4>
                                    <p class="text-muted mb-0">Pay once per month</p>
                                    <p class="text-muted small">Convenient billing</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card payment-plan-card h-100 shadow-sm" data-plan="annually" style="cursor: pointer;" onclick="selectPaymentPlan('annually')">
                                <div class="card-body text-center p-4">
                                    <i class="fas fa-calendar fa-3x text-warning mb-3"></i>
                                    <h4 class="card-title">Annually</h4>
                                    <p class="text-muted mb-0">Pay once per year</p>
                                    <p class="text-muted small">Best value</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="showSubscriptionStep(2)">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="nextToDeliveryMethod()">
                            Next: Delivery Method <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Delivery Method -->
                <div id="subscription-step-4" class="subscription-step" style="display: none;">
                    <h4 class="mb-4">Choose Delivery Method</h4>
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="card delivery-method-card h-100 shadow-sm" data-method="delivery" style="cursor: pointer;" onclick="selectDeliveryMethod('delivery')">
                                <div class="card-body text-center p-5">
                                    <i class="fas fa-truck fa-4x text-primary mb-3"></i>
                                    <h4 class="card-title">Delivery</h4>
                                    <p class="text-muted mb-3">We'll deliver to your door</p>
                                    <p class="text-success fw-bold">Delivery charges apply</p>
                                    <small class="text-muted">Convenient home delivery service</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card delivery-method-card h-100 shadow-sm" data-method="collection" style="cursor: pointer;" onclick="selectDeliveryMethod('collection')">
                                <div class="card-body text-center p-5">
                                    <i class="fas fa-store fa-4x text-success mb-3"></i>
                                    <h4 class="card-title">Collection</h4>
                                    <p class="text-muted mb-3">Collect from Middle World Farms</p>
                                    <p class="text-success fw-bold">No delivery charge</p>
                                    <small class="text-muted">Pick up at the farm</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="showSubscriptionStep(3)">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="nextToStartDate()">
                            Next: Start Date <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 5: Start Date Selection -->
                <div id="subscription-step-5" class="subscription-step" style="display: none;">
                    <h4 class="mb-4">When should this subscription start?</h4>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Payment will be taken today. You can schedule the first delivery for a future date (e.g., when season reopens).
                    </div>
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="card start-date-card h-100 shadow-sm" data-start="immediate" style="cursor: pointer;" onclick="selectStartDate('immediate')">
                                <div class="card-body text-center p-5">
                                    <i class="fas fa-play-circle fa-4x text-success mb-3"></i>
                                    <h4 class="card-title">Start Immediately</h4>
                                    <p class="text-muted mb-0">First delivery this week</p>
                                    <p class="text-muted">Subscription begins now</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card start-date-card h-100 shadow-sm" data-start="future" style="cursor: pointer;" onclick="selectStartDate('future')">
                                <div class="card-body text-center p-4">
                                    <i class="fas fa-calendar-plus fa-4x text-primary mb-3"></i>
                                    <h4 class="card-title">Schedule for Later</h4>
                                    <p class="text-muted mb-3">Choose a future start date</p>
                                    <div id="custom-date-picker" style="display: none;">
                                        <label class="form-label">Start Date:</label>
                                        <input type="date" class="form-control form-control-lg" id="subscription-start-date" 
                                               min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                                        <small class="text-muted">Payment taken today, deliveries start on this date</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="showSubscriptionStep(4)">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="nextToDetails()">
                            Next: Your Details <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 6: Customer Details -->
                <div id="subscription-step-6" class="subscription-step" style="display: none;">
                    <h4 class="mb-4">Your Contact & Delivery Details</h4>
                    
                    <!-- Quick Customer Search -->
                    <div class="alert alert-info mb-4">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-8">
                                <label for="customer-search" class="form-label mb-2">
                                    <i class="fas fa-search"></i> Search Existing Customer
                                </label>
                                <input type="text" class="form-control form-control-lg" id="customer-search" placeholder="Type name, email, or phone number...">
                                <small class="text-muted">Start typing to search for existing customers and auto-fill details</small>
                            </div>
                            <div class="col-md-4 text-end">
                                <div id="search-status" class="mt-3"></div>
                            </div>
                        </div>
                        <!-- Search Results Dropdown -->
                        <div id="customer-search-results" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-user"></i> Contact Information
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="customer-name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control form-control-lg" id="customer-name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="customer-email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control form-control-lg" id="customer-email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="customer-phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control form-control-lg" id="customer-phone" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-map-marker-alt"></i> Delivery Address
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="use-separate-shipping" onchange="toggleSeparateShipping()">
                                            <label class="form-check-label" for="use-separate-shipping">
                                                <strong>Use different delivery address</strong>
                                                <br><small class="text-muted">Check if delivery address differs from billing</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div id="shipping-address-fields">
                                        <div class="mb-3">
                                            <label for="customer-address" class="form-label">Delivery Address *</label>
                                            <textarea class="form-control form-control-lg" id="customer-address" rows="4" required placeholder="Street, Town, County"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="customer-postcode" class="form-label">Postcode *</label>
                                            <input type="text" class="form-control form-control-lg" id="customer-postcode" required>
                                        </div>
                                    </div>
                                    <div id="separate-shipping-fields" style="display: none;">
                                        <div class="alert alert-warning mb-3">
                                            <small><i class="fas fa-info-circle"></i> Billing address will be used for invoices, delivery address for shipments</small>
                                        </div>
                                        <h6 class="mb-3">Billing Address</h6>
                                        <div class="mb-3">
                                            <label for="billing-address" class="form-label">Billing Address *</label>
                                            <textarea class="form-control" id="billing-address" rows="3" placeholder="Street, Town, County"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="billing-postcode" class="form-label">Billing Postcode *</label>
                                            <input type="text" class="form-control" id="billing-postcode">
                                        </div>
                                        <hr>
                                        <h6 class="mb-3">Delivery Address</h6>
                                        <div class="mb-3">
                                            <label for="shipping-address" class="form-label">Shipping Address *</label>
                                            <textarea class="form-control" id="shipping-address" rows="3" placeholder="Street, Town, County"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="shipping-postcode" class="form-label">Shipping Postcode *</label>
                                            <input type="text" class="form-control" id="shipping-postcode">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="showSubscriptionStep(4)">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="nextToCheckout()">
                            Next: Review Order <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 7: Checkout Summary -->
                <div id="subscription-step-7" class="subscription-step" style="display: none;">
                    <h4 class="mb-4">Review Your Subscription</h4>
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-box"></i> Subscription Details
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <td class="fw-bold">Box Type:</td>
                                            <td id="checkout-box-type"></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Frequency:</td>
                                            <td id="checkout-frequency"></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Delivery Method:</td>
                                            <td id="checkout-delivery-method"></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Price:</td>
                                            <td id="checkout-price" class="text-success fw-bold"></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Estimated Monthly:</td>
                                            <td id="checkout-monthly" class="text-muted"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-user"></i> Customer Details
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <td class="fw-bold">Name:</td>
                                            <td id="checkout-name"></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Email:</td>
                                            <td id="checkout-email"></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Phone:</td>
                                            <td id="checkout-phone"></td>
                                        </tr>
                                    </table>
                                    <div id="checkout-addresses">
                                        <!-- Will be populated by updateCheckoutSummary -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Reader Status -->
                    <div id="card-reader-status" class="alert alert-info mt-3 d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-credit-card me-2"></i>
                                <span id="reader-status">Connecting to card reader...</span>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" onclick="reconnectReader()" title="Reconnect to card reader">
                                <i class="fas fa-sync-alt"></i> Reconnect
                            </button>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Next Steps:</strong> After completing this order, the customer will receive a confirmation email with their subscription details and first delivery date.
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="showSubscriptionStep(6)">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="complete-subscription-btn" onclick="completeSubscription()">
                            <i class="fas fa-check-circle me-2"></i> Complete Subscription
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Sale Modal -->
<div id="newSaleModal" class="custom-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="custom-modal-dialog" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 1200px; max-height: 90vh; overflow-y: auto;">
        <div class="custom-modal-content" style="background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title">New Sale</h5>
                <button type="button" class="btn-close custom-close-btn" onclick="forceCloseModal()"></button>
            </div>
            <div class="custom-modal-body" style="padding: 1rem 1.5rem;">
                <div class="row">
                    <!-- Cart -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6>Current Order</h6>
                                <div>
                                    <button class="btn btn-outline-primary btn-sm me-2" onclick="toggleScalePanel()">
                                        <i class="fas fa-balance-scale"></i> Scale
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" onclick="togglePrinterPanel()">
                                        <i class="fas fa-print"></i> Printer
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Scale Panel -->
                                <div id="scale-panel" class="mb-3 d-none">
                                    <div class="border rounded p-3 mb-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Digital Scale</h6>
                                            <div id="scale-status" class="badge bg-secondary">Disconnected</div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <select class="form-select form-select-sm" id="scale-type">
                                                    <option value="generic">Generic Scale</option>
                                                    <option value="a_and_d">A&D Company</option>
                                                    <option value="ohaus">Ohaus</option>
                                                    <option value="sartorius">Sartorius</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-success btn-sm w-100" id="connect-scale-btn" onclick="connectScale()">
                                                    <i class="fas fa-link me-1"></i>Connect
                                                </button>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-8">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Weight</span>
                                                    <input type="text" class="form-control text-center fw-bold" id="current-weight" readonly value="0.000 kg">
                                                    <select class="form-select" id="weight-unit" onchange="changeWeightUnit()">
                                                        <option value="kg">kg</option>
                                                        <option value="g">g</option>
                                                        <option value="lbs">lbs</option>
                                                        <option value="oz">oz</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <button class="btn btn-warning btn-sm w-100" onclick="readWeight()">
                                                    <i class="fas fa-sync me-1"></i>Read
                                                </button>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-4">
                                                <button class="btn btn-info btn-sm w-100" onclick="setTare()">
                                                    <i class="fas fa-tachometer-alt me-1"></i>Tare
                                                </button>
                                            </div>
                                            <div class="col-4">
                                                <button class="btn btn-secondary btn-sm w-100" onclick="zeroScale()">
                                                    <i class="fas fa-circle me-1"></i>Zero
                                                </button>
                                            </div>
                                            <div class="col-4">
                                                <button class="btn btn-outline-danger btn-sm w-100" onclick="disconnectScale()">
                                                    <i class="fas fa-unlink me-1"></i>Disconnect
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Weight-based pricing -->
                                        <div class="mt-3 pt-3 border-top">
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="form-label small">Price per kg</label>
                                                    <input type="number" class="form-control form-control-sm" id="price-per-kg" step="0.01" placeholder="0.00">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small">Total Price</label>
                                                    <input type="text" class="form-control form-control-sm fw-bold" id="calculated-price" readonly value="£0.00">
                                                </div>
                                            </div>
                                            <button class="btn btn-success btn-sm w-100 mt-2" onclick="addWeightedItem()">
                                                <i class="fas fa-plus me-1"></i>Add Weighted Item
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Printer Panel -->
                                <div id="printer-panel" class="mb-3 d-none">
                                    <div class="border rounded p-3 mb-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0"><i class="fas fa-print me-2"></i>Receipt Printer</h6>
                                            <div id="printer-status" class="badge bg-secondary">Disconnected</div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <select class="form-select form-select-sm" id="printer-type">
                                                    <option value="epson">Epson</option>
                                                    <option value="star">Star Micronics</option>
                                                    <option value="citizen">Citizen</option>
                                                    <option value="brother">Brother</option>
                                                    <option value="generic">Generic ESC/POS</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <select class="form-select form-select-sm" id="connection-type">
                                                    <option value="usb">USB</option>
                                                    <option value="serial">Serial</option>
                                                    <option value="network">Network</option>
                                                    <option value="bluetooth">Bluetooth</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Connection config (shown based on connection type) -->
                                        <div id="connection-config" class="mb-2 d-none">
                                            <div id="network-config" class="d-none">
                                                <div class="row">
                                                    <div class="col-8">
                                                        <input type="text" class="form-control form-control-sm" id="printer-ip" placeholder="192.168.1.100">
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="number" class="form-control form-control-sm" id="printer-port" placeholder="9100" value="9100">
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="serial-config" class="d-none">
                                                <input type="text" class="form-control form-control-sm" id="serial-device" placeholder="/dev/ttyUSB0" value="/dev/ttyUSB0">
                                            </div>
                                            <div id="usb-config" class="d-none">
                                                <input type="text" class="form-control form-control-sm" id="usb-device" placeholder="/dev/usb/lp0" value="/dev/usb/lp0">
                                            </div>
                                            <div id="bluetooth-config" class="d-none">
                                                <input type="text" class="form-control form-control-sm" id="bluetooth-mac" placeholder="AA:BB:CC:DD:EE:FF">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <button class="btn btn-success btn-sm w-100" id="connect-printer-btn" onclick="connectPrinter()">
                                                    <i class="fas fa-link me-1"></i>Connect
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-warning btn-sm w-100" onclick="testPrinter()">
                                                    <i class="fas fa-vial me-1"></i>Test Print
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-2">
                                            <button class="btn btn-outline-danger btn-sm w-100" onclick="disconnectPrinter()">
                                                <i class="fas fa-unlink me-1"></i>Disconnect
                                            </button>
                                        </div>

                                        <!-- Print options -->
                                        <div class="mt-3 pt-3 border-top">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="auto-print-receipt" checked>
                                                <label class="form-check-label small" for="auto-print-receipt">
                                                    Auto-print receipt after payment
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="open-drawer-after-payment" checked>
                                                <label class="form-check-label small" for="open-drawer-after-payment">
                                                    Open cash drawer after payment
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="cart-items">
                                    <p class="text-muted">No items in cart</p>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button type="button" class="btn btn-success w-100" id="cash-payment-btn" onclick="showCashPaymentModal()">
                                                <i class="fas fa-money-bill-wave me-2"></i>
                                                Cash Payment
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" class="btn btn-primary w-100" id="digital-payment-btn" onclick="processDigitalPayment()">
                                                <i class="fas fa-credit-card me-2"></i>
                                                Digital Payment
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Customer Ready & Cancel Buttons (shown during payment) -->
                                    <div class="row g-2 mt-2" id="cancel-card-payment-container" style="display: none;">
                                        <div class="col-6">
                                            <button type="button" class="btn btn-success w-100" id="customer-ready-btn" onclick="customerReady()">
                                                <i class="fas fa-check me-2"></i>
                                                Customer Ready
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" class="btn btn-danger w-100" id="cancel-card-payment-btn" onclick="cancelCardPayment()">
                                                <i class="fas fa-times me-2"></i>
                                                Cancel Payment
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong id="cart-total">£0.00</strong>
                                </div>
                                <div id="card-reader-status" class="mt-2 d-none">
                                    <small class="text-muted">
                                        <i class="fas fa-bluetooth"></i>
                                        <span id="reader-status">Connecting to card reader...</span>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-none" id="checkout-section">
                                    <button class="btn btn-success w-100" id="checkout-btn" disabled onclick="checkout()">
                                        <i class="fas fa-credit-card me-2"></i>
                                        <span id="checkout-text">Complete Payment</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="custom-modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-danger me-2" onclick="forceCloseModal()">
                    <i class="fas fa-times-circle me-1"></i>Force Close
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cash Payment Modal -->
<div id="cashPaymentModal" class="custom-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="custom-modal-dialog" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px;">
        <div class="custom-modal-content" style="background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Cash Payment
                </h5>
                <button type="button" class="btn-close" onclick="closeCashPaymentModal()"></button>
            </div>
            <div class="custom-modal-body" style="padding: 2rem 1.5rem;">
                <div class="text-center mb-4">
                    <h4 class="text-success">Total Due: <span id="cash-total-due">£0.00</span></h4>
                </div>

                <div class="mb-3">
                    <label for="cash-received" class="form-label">Cash Received</label>
                    <div class="input-group">
                        <span class="input-group-text">£</span>
                        <input type="number" class="form-control form-control-lg text-center" id="cash-received" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="row">
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="setExactAmount()">
                                Exact Amount
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-primary w-100" onclick="addQuickAmount(10)">
                                +£10
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-primary w-100" onclick="addQuickAmount(20)">
                                +£20
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-primary w-100" onclick="addQuickAmount(50)">
                                +£50
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="mb-0">Change Due: <span id="change-due" class="text-primary">£0.00</span></h5>
                    </div>
                </div>
            </div>
            <div class="custom-modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeCashPaymentModal()">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" id="complete-cash-payment-btn" onclick="completeCashPayment()" disabled>
                    <i class="fas fa-check me-1"></i>Complete Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Stripe Terminal SDK -->
<script src="https://js.stripe.com/terminal/v1/"></script>

<!-- Browse Products Modal -->
<div id="browseProductsModal" class="custom-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="custom-modal-dialog" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 800px;">
        <div class="custom-modal-content" style="background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title">Select Product Category</h5>
                <button type="button" class="btn-close" onclick="closeBrowseModal()"></button>
            </div>
            <div class="custom-modal-body" style="padding: 2rem 1.5rem;">
                <div class="row g-3" id="category-grid">
                    <!-- Category cards loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let cart = [];
let products = [];
let posSettings = {};
let terminal = null;
let discoveredReaders = [];
let connectedReader = null;
let currentPaymentProcess = null; // Track ongoing payment for cancellation
let customerReadyResolve = null; // Promise resolver for customer ready confirmation

// Clear all test POS sales
function clearTestSales() {
    if (!confirm('⚠️ Delete ALL POS sales?\n\nThis will permanently remove all orders from the POS system.\n\nAre you sure?')) {
        return;
    }
    
    $.ajax({
        url: '/pos/clear-test-sales',
        method: 'POST',
        success: function(response) {
            if (response.success) {
                alert('✓ Deleted ' + response.deleted + ' test orders');
                // Reload stats
                loadStats();
            } else {
                alert('Failed to clear sales: ' + (response.error || 'Unknown error'));
            }
        },
        error: function(xhr) {
            alert('Error clearing sales. Check console.');
            console.error('Clear sales error:', xhr);
        }
    });
}

// Setup CSRF token for AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Custom modal implementation - no Bootstrap dependency

// Force close modal function
function forceCloseModal() {
    console.log('Force closing modal');
    $('#newSaleModal').css('display', 'none');
    $('body').removeClass('modal-open').css('overflow', '');
    // Reset modal state
    $('#checkout-btn').prop('disabled', false);
    $('#checkout-text').text('Checkout');
    $('#card-reader-status').addClass('d-none');
    $('#scale-panel').addClass('d-none');
    $('#printer-panel').addClass('d-none');
    cart = [];
    updateCartDisplay();
}

// Custom modal event handlers

// Manual close handlers
$(document).on('click', '#newSaleModal .custom-close-btn, #newSaleModal .custom-modal-footer .btn-secondary', function(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Close button clicked');
    forceCloseModal();
});

// Force close button
$(document).on('click', '#newSaleModal .btn-danger', function(e) {
    e.preventDefault();
    e.stopPropagation();
    console.log('Force close button clicked');
    forceCloseModal();
});

// Allow clicking backdrop to close
$(document).on('click', '#newSaleModal.custom-modal-overlay', function(e) {
    if (e.target === this) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Backdrop clicked');
        forceCloseModal();
    }
});

// Allow ESC key to close modal
$(document).on('keydown', function(e) {
    if (e.keyCode === 27 && $('#newSaleModal').css('display') === 'block') {
        console.log('ESC key pressed');
        forceCloseModal();
    }
});

// Load initial data
$(document).ready(function() {
    loadStats();
    loadProducts();
    loadPosSettings();
});

// Load POS statistics
function loadStats() {
    $.get('{{ route("pos.stats") }}')
        .done(function(data) {
            $('#today-orders').text(data.today_orders);
            $('#today-sales').text('{{ config('pos_payments.currency_symbol', '£') }}' + parseFloat(data.today_sales).toFixed(2));
            $('#total-orders').text(data.total_orders);
            $('#total-sales').text('{{ config('pos_payments.currency_symbol', '£') }}' + parseFloat(data.total_sales).toFixed(2));
        })
        .fail(function(xhr, status, error) {
            console.error('Failed to load stats:', error);
            console.error('Response:', xhr.responseText);
        });
}

// Load POS settings
function loadPosSettings() {
    $.get('{{ route("pos.settings") }}')
        .done(function(data) {
            posSettings = data;
            console.log('POS settings loaded:', posSettings);
            
            // Initialize hardware panels based on settings
            initializeHardwarePanels();
        })
        .fail(function(xhr, status, error) {
            console.error('Failed to load POS settings:', error);
            console.error('Response:', xhr.responseText);
        });
}

// Initialize hardware panels based on settings
function initializeHardwarePanels() {
    // Show/hide scale panel based on integration type
    if (posSettings.scale_integration && posSettings.scale_integration !== 'manual') {
        $('#scale-panel').removeClass('d-none');
        console.log('Scale panel activated:', posSettings.scale_integration);
        
        // Auto-connect scale if enabled
        if (posSettings.scale_auto_connect) {
            console.log('Auto-connecting scale...');
        }
    }
    
    // Initialize Stripe Terminal if configured
    if (posSettings.stripe_publishable_key && posSettings.stripe_location_id) {
        console.log('Initializing Stripe Terminal...');
        initializeStripeTerminal();
    } else {
        console.warn('Stripe Terminal not configured - missing publishable key or location ID');
    }
}

// ==========================================
// STRIPE TERMINAL INTEGRATION
// ==========================================

/**
 * Initialize Stripe Terminal SDK
 */
function initializeStripeTerminal() {
    if (!window.StripeTerminal) {
        console.error('Stripe Terminal SDK not loaded');
        return;
    }
    
    terminal = StripeTerminal.create({
        onFetchConnectionToken: fetchConnectionToken,
        onUnexpectedReaderDisconnect: unexpectedDisconnect,
    });
    
    console.log('✓ Stripe Terminal initialized');
    
    // Auto-discover and connect to reader
    discoverReaders();
}

/**
 * Fetch connection token from backend
 */
async function fetchConnectionToken() {
    try {
        const response = await fetch('/pos/payments/terminal/connection-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        const data = await response.json();
        return data.secret;
    } catch (error) {
        console.error('Error fetching connection token:', error);
        throw error;
    }
}

/**
 * Discover available card readers
 */
async function discoverReaders() {
    try {
        console.log('🔍 Discovering readers at location:', posSettings.stripe_location_id);
        
        const config = { 
            simulated: false, 
            location: posSettings.stripe_location_id 
        };
        
        const discoverResult = await terminal.discoverReaders(config);
        
        if (discoverResult.error) {
            console.error('Discover error:', discoverResult.error);
            showReaderStatus('❌ No readers found', 'danger');
        } else if (discoverResult.discoveredReaders.length === 0) {
            console.warn('No readers found at location');
            showReaderStatus('No card readers found', 'warning');
        } else {
            discoveredReaders = discoverResult.discoveredReaders;
            console.log('✓ Found readers:', discoveredReaders);
            
            // Auto-connect to first reader
            if (discoveredReaders.length > 0) {
                connectToReader(discoveredReaders[0]);
            }
        }
    } catch (error) {
        console.error('Discovery failed:', error);
        showReaderStatus('Reader discovery failed', 'danger');
    }
}

/**
 * Connect to a specific reader
 */
async function connectToReader(reader) {
    try {
        console.log('🔌 Connecting to reader:', reader.label || reader.id);
        showReaderStatus('Connecting to ' + (reader.label || 'reader') + '...', 'info');
        
        const connectResult = await terminal.connectReader(reader);
        
        if (connectResult.error) {
            console.error('Connection error:', connectResult.error);
            showReaderStatus('❌ Connection failed', 'danger');
        } else {
            connectedReader = connectResult.reader;
            console.log('✓ Connected to reader:', connectedReader.label);
            showReaderStatus('✓ ' + connectedReader.label + ' ready', 'success');
        }
    } catch (error) {
        console.error('Connection failed:', error);
        showReaderStatus('Connection failed', 'danger');
    }
}

/**
 * Handle unexpected reader disconnection
 */
function unexpectedDisconnect() {
    console.warn('⚠️  Reader disconnected unexpectedly');
    connectedReader = null;
    showReaderStatus('Reader disconnected - Click Reconnect', 'warning');
}

/**
 * Manual reconnect function
 */
function reconnectReader() {
    console.log('🔄 Manual reconnect initiated...');
    showReaderStatus('Reconnecting...', 'info');
    connectedReader = null;
    discoveredReaders = [];
    
    // Discover and reconnect
    setTimeout(() => {
        discoverReaders();
    }, 500);
}

/**
 * Show reader status in UI
 */
function showReaderStatus(message, type = 'info') {
    const $status = $('#reader-status');
    const $panel = $('#card-reader-status');
    
    if ($status.length) {
        $status.text(message);
        $panel.removeClass('d-none alert-info alert-success alert-warning alert-danger')
              .addClass('alert-' + type);
    }
    
    console.log('Reader status:', message);
}

/**
 * Customer confirms they're ready to pay
 */
function customerReady() {
    console.log('✅ Customer confirmed ready');
    if (customerReadyResolve) {
        customerReadyResolve();
        customerReadyResolve = null;
    }
    
    // Update button states
    $("#customer-ready-btn").prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
    showReaderStatus('Present card to reader...', 'info');
}

/**
 * Cancel card payment in progress
 */
async function cancelCardPayment() {
    if (!terminal || !currentPaymentProcess) {
        console.log('No payment to cancel');
        return;
    }
    
    try {
        console.log('🚫 Canceling payment...');
        showReaderStatus('Canceling payment...', 'warning');
        
        // Cancel the payment collection
        await terminal.cancelCollectPaymentMethod();
        
        console.log('✓ Payment canceled');
        showReaderStatus('Payment canceled', 'secondary');
        
        // Clear reader display
        try {
            await terminal.clearReaderDisplay();
        } catch (e) {
            console.warn('Failed to clear display:', e);
        }
        
        // Reset payment state
        currentPaymentProcess = null;
        
        // Re-enable payment buttons
        $("#digital-payment-btn").prop("disabled", false);
        $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
        
        // Hide cancel button
        $("#cancel-card-payment-container").hide();
        
        return true;
    } catch (error) {
        console.error('Failed to cancel payment:', error);
        showReaderStatus('Cancel failed: ' + error.message, 'danger');
        return false;
    }
}

/**
 * Process payment with connected reader
 */
async function processCardPaymentWithReader(clientSecret, amount, cartItems = []) {
    if (!connectedReader) {
        throw new Error('No card reader connected');
    }
    
    if (!clientSecret) {
        throw new Error('No client secret provided');
    }
    
    try {
        console.log('💳 Processing payment on reader...');
        console.log('📦 Cart items:', cartItems);
        showReaderStatus('Present card to reader...', 'info');
        
        // Show cancel button
        console.log('🔴 Showing control buttons...');
        $("#cancel-card-payment-container").show();
        
        // Enable customer ready button, disable cancel for now
        $("#customer-ready-btn").prop("disabled", false).html('<i class="fas fa-check me-2"></i>Customer Ready');
        $("#cancel-card-payment-btn").prop("disabled", false);
        
        // Track this payment process
        currentPaymentProcess = { clientSecret, amount, status: 'collecting' };
        console.log('📌 Payment process tracked:', currentPaymentProcess);
        
        // Set reader display with merchant branding and cart items
        try {
            const lineItems = cartItems.length > 0 
                ? cartItems.map(item => ({
                    description: item.name,
                    amount: Math.round(item.price * 100),
                    quantity: item.quantity
                }))
                : [{
                    description: 'Total',
                    amount: Math.round(amount * 100),
                    quantity: 1
                }];
            
            console.log('📺 Setting reader display with items:', lineItems);
                
            await terminal.setReaderDisplay({
                type: 'cart',
                cart: {
                    line_items: lineItems,
                    tax: 0,
                    total: Math.round(amount * 100),
                    currency: 'gbp'
                }
            });
            console.log('✓ Reader display updated with cart');
            
            // Wait for customer to press "Customer Ready" button
            console.log('⏳ Waiting for customer to confirm they\'re ready...');
            showReaderStatus('📋 Customer reviewing cart - Click "Customer Ready" when done', 'warning');
            
            await new Promise(resolve => {
                customerReadyResolve = resolve;
            });
            
            console.log('✓ Customer confirmed, proceeding to payment...');
        } catch (displayError) {
            console.error('❌ Failed to set reader display:', displayError);
            // Continue anyway - not critical
        }
        
        // Collect payment method
        const result = await terminal.collectPaymentMethod(clientSecret);
        
        if (result.error) {
            console.error('Payment collection error:', result.error);
            throw new Error(result.error.message || 'Payment collection failed');
        }
        
        console.log('✓ Payment method collected');
        showReaderStatus('Processing payment...', 'info');
        
        // Process the payment
        const processResult = await terminal.processPayment(result.paymentIntent);
        
        if (processResult.error) {
            console.error('Payment processing error:', processResult.error);
            throw new Error(processResult.error.message || 'Payment processing failed');
        }
        
        console.log('✓ Payment processed successfully');
        showReaderStatus('✓ Payment successful!', 'success');
        
        // Clear reader display
        try {
            await terminal.clearReaderDisplay();
        } catch (e) {
            console.warn('Failed to clear display:', e);
        }
        
        // Clear payment process tracking
        currentPaymentProcess = null;
        $("#cancel-card-payment-container").hide();
        
        return processResult.paymentIntent;
    } catch (error) {
        console.error('Card payment failed:', error);
        showReaderStatus('❌ Payment failed', 'danger');
        
        // Clear reader display
        try {
            await terminal.clearReaderDisplay();
        } catch (e) {
            console.warn('Failed to clear display:', e);
        }
        
        // Clear payment process tracking
        currentPaymentProcess = null;
        $("#cancel-card-payment-container").hide();
        
        throw error;
    }
}

// ==========================================
// END STRIPE TERMINAL INTEGRATION
// ==========================================

function loadProducts(search = "", category = "") {
    const params = { search: search };
    if (category) {
        params.category = category;
    }

    $.get("{{ route("pos.products") }}", params)
        .done(function(data) {
            products = data;
            displayProducts(data);

            // Load category filters on main page (only once)
            if (!category) {
                loadMainCategoryFilters();
            }
        })
        .fail(function(xhr, status, error) {
            console.error("Failed to load products:", error);
            console.error("Response:", xhr.responseText);
        });
}
//
// Display products in grid
function displayProducts(products) {
    const $productsGrid = $("#products-grid");

    if (products.length === 0) {
        $productsGrid.html('<div class="col-12"><p class="text-center text-muted">No products found.</p></div>');
        return;
    }

    // Use document fragment for better performance
    const fragment = document.createDocumentFragment();
    const row = document.createElement("div");
    row.className = "row";

    products.forEach(product => {
        const col = document.createElement("div");
        col.className = "col-6 col-sm-6 col-md-4 col-lg-3 mb-3";

        // Get product image or use inline SVG placeholder
        const placeholderSVG = "data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%2328a745%22 width=%22400%22 height=%22300%22/%3E%3Ctext fill=%22%23ffffff%22 font-family=%22Arial%22 font-size=%2224%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image%3C/text%3E%3C/svg%3E";
        
        // Use placeholder directly if no valid image URL
        let imageUrl = placeholderSVG;
        if (product.image_url && product.image_url.startsWith('http')) {
            imageUrl = product.image_url;
        } else if (product.featured_image && product.featured_image.startsWith('http')) {
            imageUrl = product.featured_image;
        }

        col.innerHTML = `
            <div class="card h-100">
                <img src="${imageUrl}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;" onerror="this.onerror=null;this.src='${placeholderSVG}'">
                <div class="card-body">
                    <h6 class="card-title">${product.name}</h6>
                    <div class="mb-2">
                        <strong class="text-primary">£${parseFloat(product.price).toFixed(2)}</strong>
                        ${product.unit ? `
                            <span class="badge ${product.unit.toLowerCase() === "kg" ? "bg-warning text-dark" : "bg-info"} ms-1" 
                                  title="${product.unit.toLowerCase() === "kg" ? "Requires weighing" : "Fixed price"}">
                                ${product.unit.toLowerCase() === "kg" ? "<i class='fas fa-balance-scale'></i> per KG" : 
                                  product.unit.toLowerCase() === "each" ? "each" : 
                                  "/ " + product.unit}
                            </span>
                        ` : ""}
                    </div>
                    <button class="btn btn-primary btn-sm w-100" onclick="addToCart(${product.id}, '${product.name.replace(/'/g, '\\\'')}', ${product.price}, '${product.unit || 'each'}')">
                        ${product.unit && product.unit.toLowerCase() === 'kg' ? '<i class=\'fas fa-balance-scale\'></i> Weigh & Add' : '<i class=\'fas fa-plus\'></i> Add to Cart'}
                    </button>
                </div>
            </div>
        `;

        row.appendChild(col);
    });

    fragment.appendChild(row);
    $productsGrid.html("").append(fragment);
}
//
// Search products
function searchProducts() {
    const searchTerm = $("#product-search").val();
    loadProducts(searchTerm);
}
//
// Add item to cart
function addToCart(productId, productName, price, unit = "each", quantity = 1, extraData = null) {
    // If it"s a KG item and no weight provided, show weight input dialog
    if (unit && unit.toLowerCase() === "kg" && !extraData) {
        showWeightInputDialog(productId, productName, price, unit);
        return;
    }

    // Find existing item in cart
    const existingItemIndex = cart.findIndex(item => item.product_id === productId && item.unit === unit);

    if (existingItemIndex !== -1) {
        // Update existing item
        cart[existingItemIndex].quantity += quantity;
        if (extraData && extraData.weight) {
            cart[existingItemIndex].weight = (cart[existingItemIndex].weight || 0) + extraData.weight;
            cart[existingItemIndex].price = (cart[existingItemIndex].weight * (extraData.price_per_kg || price));
        }
    } else {
        // Add new item
        const newItem = {
            product_id: productId, // Changed from 'id' to 'product_id' to match backend validation
            name: productName,
            price: price,
            unit: unit,
            quantity: quantity,
            ...extraData
        };
        cart.push(newItem);
    }

    updateCartDisplay();
    updateCartBadge();
    showAddToCartAnimation(productName);
}
//
// Update cart badge
function updateCartBadge() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const $badge = $("#cart-badge");

    if (totalItems > 0) {
        $badge.text(totalItems).show();
    } else {
        $badge.hide();
    }
}
//
// Update cart display
function updateCartDisplay() {
    const $cartItems = $("#cart-items");
    const $cartTotal = $("#cart-total");
    const $checkoutBtn = $("#checkout-btn");

    if (cart.length === 0) {
        $cartItems.html('<li class="list-group-item text-center text-muted">Cart is empty</li>');
        $cartTotal.text("£0.00");
        $checkoutBtn.prop("disabled", true);
        return;
    }

    let cartHtml = "";
    let total = 0;

    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        cartHtml += `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <strong>${item.name}</strong>
                    ${item.weight ? `<br><small class="text-muted">${item.weight}kg @ £${item.price_per_kg}/kg</small>` : ""}
                    <br><small class="text-muted">£${parseFloat(item.price).toFixed(2)} × ${item.quantity}</small>
                </div>
                <div class="text-end">
                    <strong>£${itemTotal.toFixed(2)}</strong>
                    <button class="btn btn-sm btn-outline-danger ms-2" onclick="removeFromCart(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </li>
        `;
    });

    $cartItems.html(cartHtml);
    $cartTotal.text("£" + total.toFixed(2));
    $checkoutBtn.prop("disabled", false);
}
//
// Remove item from cart
function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
    updateCartBadge();
}
//
// Checkout
function checkout() {
    if (cart.length === 0) {
        alert("Please add items to cart first");
        return;
    }

    // Process digital payment by default
    processDigitalPayment();
}
// Load main category filters
function loadMainCategoryFilters() {
    console.log("Loading category filters...");
    $.get("{{ route('pos.inventory.categories') }}")
        .done(function(data) {
            console.log("Category API response:", data);
            const $filtersContainer = $("#main-category-filters");
            const $mobileSelect = $("#mobile-category-filter");
            console.log("Filters container found:", $filtersContainer.length);

            // Clear existing filters
            $filtersContainer.empty();
            $mobileSelect.find('option:not(:first)').remove();

            // Add "All" button for desktop
            const $allButton = $(`
                <button class="btn btn-sm btn-primary" onclick="filterMainProducts('')">
                    <i class="fas fa-th-large me-1"></i>All
                </button>
            `);
            $filtersContainer.append($allButton);

            // Add category buttons and dropdown options
            if (data && Array.isArray(data)) {
                data.forEach(category => {
                    if (category.category && category.total > 0) {
                        const escapedName = category.category.replace(/'/g, '\\\'').replace(/"/g, '&quot;');
                        
                        // Desktop button
                        const $button = $(`
                            <button class="btn btn-sm btn-outline-secondary" onclick="filterMainProducts('${escapedName}')">
                                ${category.category} (${category.total})
                            </button>
                        `);
                        $filtersContainer.append($button);
                        
                        // Mobile dropdown option
                        const $option = $(`<option value="${escapedName}">${category.category} (${category.total})</option>`);
                        $mobileSelect.append($option);
                    }
                });
            }

            console.log("Category filters loaded:", data);
        })
        .fail(function(xhr, status, error) {
            console.error("Failed to load categories:", error);
            // Fallback: just show "All" button
            const $filtersContainer = $("#main-category-filters");
            $filtersContainer.html(`
                <button class="btn btn-sm btn-primary" onclick="filterMainProducts('')">
                    <i class="fas fa-th-large me-1"></i>All
                </button>
            `);
        });
}
//
// Filter products by category
function filterMainProducts(category = "") {
    const searchTerm = $("#product-search").val();
    loadProducts(searchTerm, category);
}
//
// Modal Functions
function showNewSale() {
    // Show the main POS interface (new sale modal)
    $("#newSaleModal").show();
}

function showProducts() {
    // Show browse products modal
    $("#browseProductsModal").show();
}

function showOrderHistory() {
    // Navigate to order history page
    window.location.href = "/pos/order-history";
}

function showDeliveries() {
    // Navigate to deliveries page
    window.location.href = "/pos/deliveries";
}
//
// Payment Functions
function showCashPaymentModal() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    $("#cash-total-due").text("£" + total.toFixed(2));
    $("#cash-received").val("");
    $("#change-due").text("£0.00");
    $("#complete-cash-payment-btn").prop("disabled", true);
    $("#cashPaymentModal").show();
}

function closeCashPaymentModal() {
    $("#cashPaymentModal").hide();
}

function setExactAmount() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    $("#cash-received").val(total.toFixed(2));
    calculateChange();
}

function addQuickAmount(amount) {
    const current = parseFloat($("#cash-received").val()) || 0;
    $("#cash-received").val((current + amount).toFixed(2));
    calculateChange();
}

function calculateChange() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const received = parseFloat($("#cash-received").val()) || 0;
    const change = received - total;
    $("#change-due").text("£" + Math.max(0, change).toFixed(2));
    $("#complete-cash-payment-btn").prop("disabled", change < 0);
}

function completeCashPayment() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const received = parseFloat($("#cash-received").val()) || 0;
    const change = received - total;

    if (change < 0) {
        alert("Insufficient cash received");
        return;
    }

    // Process the payment
    processOrder("cash", { received: received, change: change });
}

function processDigitalPayment() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    // Disable button and show processing
    $("#digital-payment-btn").prop("disabled", true);
    $("#digital-payment-btn").html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');

    // Create payment intent first
    $.ajax({
        url: '/pos/payments/intent',
        method: 'POST',
        data: JSON.stringify({
            amount: total,
            customer_name: 'POS Customer',
            order_id: null,
            items: cart.map(item => ({
                name: item.name,
                quantity: item.quantity,
                price: item.price
            }))
        }),
        contentType: 'application/json',
        success: function(paymentResponse) {
            if (!paymentResponse.success) {
                alert('Failed to initialize payment: ' + (paymentResponse.error || 'Unknown error'));
                $("#digital-payment-btn").prop("disabled", false);
                $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
                return;
            }
            
            const paymentIntentId = paymentResponse.payment_intent_id;
            
            // If card reader connected, process payment on reader
            if (connectedReader) {
                $("#digital-payment-btn").html('<i class="fas fa-spinner fa-spin me-1"></i>Processing on card reader...');
                showReaderStatus('Present card to reader...', 'info');
                
                processCardPaymentWithReader(paymentResponse.client_secret, total, cart)
                    .then(() => {
                        // Payment successful - create order
                        $("#digital-payment-btn").html('<i class="fas fa-spinner fa-spin me-1"></i>Completing order...');
                        processOrder("card", { amount: total, payment_intent_id: paymentIntentId });
                    })
                    .catch(error => {
                        console.error('Card payment error:', error);
                        // Don't create order on payment failure/cancellation
                        alert('Payment canceled or failed: ' + error.message);
                        $("#digital-payment-btn").prop("disabled", false);
                        $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
                        
                        // Cancel the payment intent on backend
                        $.post('/pos/payments/cancel', {
                            payment_intent_id: paymentIntentId
                        }).fail(function(xhr) {
                            console.error('Failed to cancel payment intent:', xhr);
                        });
                    });
            } else {
                // No card reader - fall back to polling (existing behavior)
                processOrder("card", { amount: total, payment_intent_id: paymentIntentId });
            }
        },
        error: function(xhr) {
            console.error('Payment intent error:', xhr);
            console.error('Response:', xhr.responseText);
            const errorMsg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Unknown error';
            alert('Failed to initialize payment: ' + errorMsg + '\n\nPlease check console for details.');
            $("#digital-payment-btn").prop("disabled", false);
            $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
        }
    });
}

function processOrder(paymentMethod, paymentData) {
    $.post("{{ route('pos.orders.create') }}", {
        items: cart,
        payment_method: paymentMethod,
        payment_data: paymentData
    })
    .done(function(response) {
        if (response.success) {
            // Check if payment is pending (digital payments)
            if (response.status === 'payment_pending' && response.payment_intent_id) {
                // Start polling for payment completion
                pollPaymentStatus(response.payment_intent_id, response.order.id);
                return;
            }

            // Payment completed immediately (cash payments)
            completeOrderProcessing(response.order.id, paymentMethod);
        } else {
            alert("Payment failed: " + (response.message || "Unknown error"));
            // Reset payment buttons
            $("#digital-payment-btn").prop("disabled", false);
            $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
        }
    })
    .fail(function(xhr, status, error) {
        console.error("Payment failed:", error);
        alert("Payment failed. Please try again.");
        // Reset payment buttons
        $("#digital-payment-btn").prop("disabled", false);
        $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
    });
}

function pollPaymentStatus(paymentIntentId, orderId) {
    let attempts = 0;
    const maxAttempts = 60; // 60 seconds max wait
    const pollInterval = 1000; // Check every 1 second

    // Show waiting message
    $("#digital-payment-btn").html('<i class="fas fa-spinner fa-spin me-1"></i>Waiting for payment...');

    const poll = function() {
        attempts++;

        $.post("{{ route('pos.check-payment-status') }}", {
            payment_intent_id: paymentIntentId
        })
        .done(function(statusResponse) {
            if (statusResponse.success && statusResponse.captured) {
                // Payment completed successfully
                completeOrderProcessing(orderId, 'card');
            } else if (statusResponse.success && statusResponse.status === 'requires_payment_method') {
                // Payment intent created but not yet processed
                if (attempts < maxAttempts) {
                    setTimeout(poll, pollInterval);
                } else {
                    // Timeout
                    alert("Payment timeout. Please try again.");
                    $("#digital-payment-btn").prop("disabled", false);
                    $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
                }
            } else if (statusResponse.success && statusResponse.status === 'canceled') {
                // Payment was canceled
                alert("Payment was canceled.");
                $("#digital-payment-btn").prop("disabled", false);
                $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
            } else {
                // Still processing or other status
                if (attempts < maxAttempts) {
                    setTimeout(poll, pollInterval);
                } else {
                    alert("Payment processing timeout. Please check payment status manually.");
                    $("#digital-payment-btn").prop("disabled", false);
                    $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
                }
            }
        })
        .fail(function() {
            // Network error, retry
            if (attempts < maxAttempts) {
                setTimeout(poll, pollInterval);
            } else {
                alert("Payment status check failed. Please try again.");
                $("#digital-payment-btn").prop("disabled", false);
                $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');
            }
        });
    };

    // Start polling
    poll();
}

function completeOrderProcessing(orderId, paymentMethod) {
    // Clear cart
    cart = [];
    updateCartDisplay();
    updateCartBadge();

    // Close modals
    $("#cashPaymentModal").hide();
    $("#paymentModal").hide();

    // Reset payment buttons
    $("#digital-payment-btn").prop("disabled", false);
    $("#digital-payment-btn").html('<i class="fas fa-credit-card me-1"></i>Digital Payment');

    // Show success message
    alert("Payment successful!");

    // Print receipt if enabled
    if ($("#auto-print-receipt").is(":checked")) {
        printReceipt(orderId);
    }

    // Open cash drawer if enabled
    if ($("#open-drawer-after-payment").is(":checked") && paymentMethod === "cash") {
        openCashDrawer();
    }

    // Refresh the page to update badges and reset till for next transaction
    setTimeout(function() {
        location.reload();
    }, 1000); // Small delay to allow user to see success message
}

// Event listeners
$(document).ready(function() {
    // Calculate change when cash received changes
    $("#cash-received").on("input", calculateChange);
});

// Add to cart animation function
function showAddToCartAnimation(productName) {
    // Create a toast notification
    const $toast = $(`
        <div class="toast align-items-center text-white bg-success border-0 position-fixed"
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    Added "${productName}" to cart
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="$(this).parent().parent().remove()"></button>
            </div>
        </div>
    `);

    // Add to body
    $('body').append($toast);

    // Show the toast
    setTimeout(() => {
        $toast.addClass('show');
    }, 100);

    // Auto-hide after 3 seconds
    setTimeout(() => {
        $toast.removeClass('show');
        setTimeout(() => {
            $toast.remove();
        }, 300);
    }, 3000);

    // Also update cart badge with a brief highlight
    const $cartBadge = $("#cart-badge");
    $cartBadge.addClass('bg-warning text-dark');
    setTimeout(() => {
        $cartBadge.removeClass('bg-warning text-dark');
    }, 500);
}

// Hardware Functions (stubs for future implementation)
function toggleScalePanel() {
    const $panel = $("#scale-panel");
    if ($panel.is(":visible")) {
        $panel.hide();
    } else {
        $panel.show();
    }
}

function togglePrinterPanel() {
    const $panel = $("#printer-panel");
    if ($panel.is(":visible")) {
        $panel.hide();
    } else {
        $panel.show();
    }
}

function connectScale() {
    // Stub for scale connection
    console.log("Scale connection not yet implemented");
    alert("Scale integration coming soon!");
}

function changeWeightUnit() {
    // Stub for weight unit change
    console.log("Weight unit changed");
}

function readWeight() {
    // Stub for reading weight from scale
    console.log("Reading weight from scale");
    alert("Scale reading not yet implemented");
}

function setTare() {
    // Stub for setting tare weight
    console.log("Setting tare weight");
    alert("Tare setting not yet implemented");
}

function zeroScale() {
    // Stub for zeroing scale
    console.log("Zeroing scale");
    alert("Scale zeroing not yet implemented");
}

function disconnectScale() {
    // Stub for disconnecting scale
    console.log("Disconnecting scale");
    alert("Scale disconnection not yet implemented");
}

function connectPrinter() {
    // Stub for printer connection
    console.log("Printer connection not yet implemented");
    alert("Printer integration coming soon!");
}

function testPrinter() {
    // Stub for printer test
    console.log("Testing printer");
    alert("Printer test not yet implemented");
}

function disconnectPrinter() {
    // Stub for disconnecting printer
    console.log("Disconnecting printer");
    alert("Printer disconnection not yet implemented");
}

// Show weight input dialog for KG items
function showWeightInputDialog(productId, productName, price, unit) {
    // Create the weight input modal
    const $modal = $(`
        <div id="weightInputModal" class="custom-modal-overlay" style="display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000;">
            <div class="custom-modal-dialog" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 400px;">
                <div class="custom-modal-content" style="background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6;">
                        <h5 class="modal-title">
                            <i class="fas fa-balance-scale me-2"></i>
                            Weigh Item: ${productName}
                        </h5>
                        <button type="button" class="btn-close" onclick="closeWeightInputModal()"></button>
                    </div>
                    <div class="custom-modal-body" style="padding: 2rem 1.5rem;">
                        <div class="text-center mb-4">
                            <h6 class="text-muted">Price per KG: £${parseFloat(price).toFixed(2)}</h6>
                        </div>

                        <div class="mb-3">
                            <label for="item-weight" class="form-label">Weight (KG)</label>
                            <div class="input-group">
                                <input type="number" class="form-control form-control-lg text-center" id="item-weight" step="0.001" min="0.001" placeholder="0.000">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="readScaleWeight()">
                                        <i class="fas fa-sync me-1"></i>Read Scale
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="setScaleTare()">
                                        <i class="fas fa-tachometer-alt me-1"></i>Tare
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="mb-1">Total Price: <span id="weight-total-price" class="text-primary">£0.00</span></h5>
                                <small class="text-muted">Calculated automatically</small>
                            </div>
                        </div>
                    </div>
                    <div class="custom-modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeWeightInputModal()">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success" id="add-weighted-item-btn" onclick="addWeightedItem(${productId}, '${productName.replace(/'/g, '\\\'')}', ${price})" disabled>
                            <i class="fas fa-plus me-1"></i>Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `);

    // Add to body
    $('body').append($modal);

    // Focus on weight input
    setTimeout(() => {
        $('#item-weight').focus();
    }, 100);

    // Calculate price when weight changes
    $('#item-weight').on('input', function() {
        calculateWeightPrice(price);
    });
}

function closeWeightInputModal() {
    $('#weightInputModal').remove();
}

function calculateWeightPrice(pricePerKg) {
    const weight = parseFloat($('#item-weight').val()) || 0;
    const totalPrice = weight * pricePerKg;
    $('#weight-total-price').text('£' + totalPrice.toFixed(2));

    // Enable/disable add button
    const $addBtn = $('#add-weighted-item-btn');
    if (weight > 0) {
        $addBtn.prop('disabled', false);
    } else {
        $addBtn.prop('disabled', true);
    }
}

function readScaleWeight() {
    // Try to read from connected scale first
    if (typeof readWeight === 'function') {
        // This would integrate with the scale hardware
        readWeight();
        // For now, just show a message
        alert('Scale reading not yet implemented. Please enter weight manually.');
    } else {
        alert('Scale not connected. Please enter weight manually.');
    }
}

function setScaleTare() {
    // Try to set tare on connected scale
    if (typeof setTare === 'function') {
        setTare();
        alert('Tare set on scale.');
    } else {
        alert('Scale not connected. Cannot set tare.');
    }
}

function addWeightedItem(productId, productName, pricePerKg) {
    let weight, price;

    // If called from scale panel (no parameters)
    if (arguments.length === 0) {
        weight = parseFloat($('#current-weight').val()) || 0;
        price = parseFloat($('#price-per-kg').val()) || 0;

        if (weight <= 0) {
            alert('Please enter a valid weight.');
            return;
        }

        if (price <= 0) {
            alert('Please enter a valid price per kg.');
            return;
        }

        // Find product details (this would need to be improved with actual product selection)
        // For now, create a generic weighted item
        productId = productId || 'weighted-item';
        productName = productName || 'Weighted Item';
        pricePerKg = price;
    } else {
        // Called from weight input modal
        weight = parseFloat($('#item-weight').val()) || 0;

        if (weight <= 0) {
            alert('Please enter a valid weight.');
            return;
        }
    }

    const totalPrice = weight * pricePerKg;

    // Add to cart with weight data
    addToCart(productId, productName, totalPrice, 'kg', 1, {
        weight: weight,
        price_per_kg: pricePerKg,
        unit_price: totalPrice
    });

    // Close modal if it exists
    if ($('#weightInputModal').length) {
        closeWeightInputModal();
    }

    // Reset scale panel values
    $('#current-weight').val('0.000');
    $('#calculated-price').val('£0.00');
}

function closeBrowseModal() {
    $("#browseProductsModal").hide();
}

// ============ SUBSCRIPTION WIZARD ============
let subscriptionData = {
    box_type: '',
    box_price: 0,
    frequency: '',
    delivery_method: '',
    customer: {
        name: '',
        email: '',
        phone: '',
        address: '',
        postcode: '',
        billing_address: '',
        billing_postcode: '',
        shipping_address: '',
        shipping_postcode: '',
        separate_shipping: false
    }
};

function startSubscriptionWizard() {
    subscriptionData = {
        box_type: '',
        box_price: 0,
        frequency: '',
        delivery_method: '',
        customer: { 
            name: '', 
            email: '', 
            phone: '', 
            address: '', 
            postcode: '',
            billing_address: '',
            billing_postcode: '',
            shipping_address: '',
            shipping_postcode: '',
            separate_shipping: false
        }
    };
    showSubscriptionStep(1);
    $('#subscriptionWizardModal').show();
    
    // Reset separate shipping toggle
    $('#use-separate-shipping').prop('checked', false);
    $('#separate-shipping-fields').hide();
    $('#shipping-address-fields').show();
}

function showSubscriptionStep(step) {
    // Hide all steps
    $('.subscription-step').hide();
    
    // Show requested step
    $(`#subscription-step-${step}`).show();
    
    // Update progress bar
    const progress = (step / 5) * 100;
    $('#wizard-progress').css('width', progress + '%').attr('aria-valuenow', progress);
    $('#wizard-step-text').text(`Step ${step} of 5`);
}

function selectBoxType(type, price) {
    subscriptionData.box_type = type;
    subscriptionData.box_price = price;
    
    // Visual feedback
    $('.box-card').removeClass('border-success border-3');
    $(`[data-box-type="${type}"]`).addClass('border-success border-3');
}

function nextToFrequency() {
    if (!subscriptionData.box_type) {
        alert('Please select a vegetable box size');
        return;
    }
    showSubscriptionStep(2);
}

function selectFrequency(freq) {
    subscriptionData.frequency = freq;
    
    // Visual feedback
    $('.frequency-card').removeClass('border-success border-3');
    $(`[data-frequency="${freq}"]`).addClass('border-success border-3');
}

function nextToPaymentPlan() {
    if (!subscriptionData.frequency) {
        alert('Please select delivery frequency');
        return;
    }
    showSubscriptionStep(3);
}

function selectPaymentPlan(plan) {
    subscriptionData.payment_plan = plan;
    
    // Visual feedback
    $('.payment-plan-card').removeClass('border-success border-3');
    $(`[data-plan="${plan}"]`).addClass('border-success border-3');
    
    // Fetch actual price from VegboxPlan based on box_type, frequency, and payment_plan
    if (subscriptionData.box_type && subscriptionData.frequency && plan) {
        $.get('/pos/subscription/plan-price', {
            box_type: subscriptionData.box_type,
            frequency: subscriptionData.frequency,
            payment_plan: plan
        })
        .done(function(response) {
            if (response.success) {
                subscriptionData.plan_price = response.price;
                subscriptionData.plan_id = response.plan_id;
                subscriptionData.plan_name = response.plan_name;
                subscriptionData.currency = response.currency || 'GBP';
                console.log('Fetched plan price:', response);
            } else {
                console.error('Failed to fetch plan price:', response.message);
                alert('Could not load pricing for selected plan. Please try again or contact support.');
            }
        })
        .fail(function(xhr) {
            console.error('Failed to fetch plan price:', xhr.responseJSON);
            alert('Could not load pricing for selected plan. Please try again or contact support.');
        });
    }
}

function nextToDeliveryMethod() {
    if (!subscriptionData.payment_plan) {
        alert('Please select payment plan (Weekly, Monthly, or Annually)');
        return;
    }
    showSubscriptionStep(5);
}

function selectDeliveryMethod(method) {
    subscriptionData.delivery_method = method;
    
    // Visual feedback
    $('.delivery-method-card').removeClass('border-success border-3');
    $(`[data-method="${method}"]`).addClass('border-success border-3');
}

function nextToStartDate() {
    if (!subscriptionData.delivery_method) {
        alert('Please select delivery method (Delivery or Collection)');
        return;
    }
    showSubscriptionStep(5);
}

function selectStartDate(option) {
    // Visual feedback
    $('.start-date-card').removeClass('border-success border-3');
    $(`[data-start="${option}"]`).addClass('border-success border-3');
    
    if (option === 'immediate') {
        subscriptionData.start_date = null; // Will start immediately
        $('#custom-date-picker').hide();
    } else if (option === 'future') {
        $('#custom-date-picker').show();
        // Get the date from input
        const startDate = $('#subscription-start-date').val();
        subscriptionData.start_date = startDate;
        
        // Update when date changes
        $('#subscription-start-date').off('change').on('change', function() {
            subscriptionData.start_date = $(this).val();
        });
    }
}

function nextToDetails() {
    // Check if start date option is selected
    if (!$('.start-date-card').hasClass('border-success')) {
        alert('Please select when the subscription should start');
        return;
    }
    
    // If future is selected, ensure date is chosen
    if ($('[data-start="future"]').hasClass('border-success')) {
        const startDate = $('#subscription-start-date').val();
        if (!startDate) {
            alert('Please select a start date');
            return;
        }
        subscriptionData.start_date = startDate;
    }
    
    showSubscriptionStep(6);
    
    // Set up customer search when entering step 6
    setupCustomerSearch();
}

let customerSearchTimeout;

function setupCustomerSearch() {
    $('#customer-search').off('input').on('input', function() {
        clearTimeout(customerSearchTimeout);
        const query = $(this).val().trim();
        
        if (query.length < 2) {
            $('#customer-search-results').hide().empty();
            $('#search-status').empty();
            return;
        }
        
        $('#search-status').html('<i class="fas fa-spinner fa-spin"></i> Searching...');
        
        customerSearchTimeout = setTimeout(function() {
            searchCustomers(query);
        }, 300); // Debounce 300ms
    });
}

function toggleSeparateShipping() {
    const isChecked = $('#use-separate-shipping').is(':checked');
    
    if (isChecked) {
        // Copy current address to billing before showing separate fields
        const currentAddress = $('#customer-address').val();
        const currentPostcode = $('#customer-postcode').val();
        
        $('#billing-address').val(currentAddress);
        $('#billing-postcode').val(currentPostcode);
        $('#shipping-address').val(currentAddress);
        $('#shipping-postcode').val(currentPostcode);
        
        // Show separate fields, hide combined field
        $('#shipping-address-fields').hide();
        $('#separate-shipping-fields').show();
    } else {
        // Copy shipping back to main field when unchecking
        const shippingAddress = $('#shipping-address').val();
        const shippingPostcode = $('#shipping-postcode').val();
        
        if (shippingAddress) {
            $('#customer-address').val(shippingAddress);
            $('#customer-postcode').val(shippingPostcode);
        }
        
        // Hide separate fields, show combined field
        $('#separate-shipping-fields').hide();
        $('#shipping-address-fields').show();
    }
}

function searchCustomers(query) {
    $.ajax({
        url: '/pos/customers/search',
        method: 'GET',
        data: { q: query },
        success: function(response) {
            if (response.customers && response.customers.length > 0) {
                displayCustomerResults(response.customers);
                $('#search-status').html(`<small class="text-success"><i class="fas fa-check-circle"></i> Found ${response.customers.length} customer(s)</small>`);
            } else {
                $('#customer-search-results').hide().empty();
                $('#search-status').html('<small class="text-muted">No customers found</small>');
            }
        },
        error: function() {
            $('#search-status').html('<small class="text-danger"><i class="fas fa-exclamation-circle"></i> Search error</small>');
        }
    });
}

function displayCustomerResults(customers) {
    const $results = $('#customer-search-results');
    $results.empty();
    
    customers.forEach(customer => {
        const $item = $(`
            <a href="#" class="list-group-item list-group-item-action" data-customer-id="${customer.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${customer.name}</h6>
                        <small class="text-muted">
                            <i class="fas fa-envelope"></i> ${customer.email || 'No email'}<br>
                            <i class="fas fa-phone"></i> ${customer.phone || 'No phone'}<br>
                            ${customer.delivery_address ? '<i class="fas fa-map-marker-alt"></i> ' + customer.delivery_address.substring(0, 50) + '...' : ''}
                        </small>
                    </div>
                    <span class="badge bg-success">Select</span>
                </div>
            </a>
        `);
        
        $item.on('click', function(e) {
            e.preventDefault();
            fillCustomerDetails(customer);
        });
        
        $results.append($item);
    });
    
    $results.show();
}

function fillCustomerDetails(customer) {
    // Fill in all the form fields
    $('#customer-name').val(customer.name || '');
    $('#customer-email').val(customer.email || '');
    $('#customer-phone').val(customer.phone || '');
    $('#customer-address').val(customer.address || '');
    $('#customer-postcode').val(customer.postcode || '');
    
    // Hide results and show success message
    $('#customer-search-results').hide();
    $('#search-status').html('<small class="text-success"><i class="fas fa-check-circle"></i> Customer details loaded!</small>');
    $('#customer-search').val(customer.name);
    
    // Highlight the fields briefly
    $('.form-control').addClass('border-success');
    setTimeout(function() {
        $('.form-control').removeClass('border-success');
    }, 1000);
}

function nextToCheckout() {
    // Validate customer details
    const name = $('#customer-name').val().trim();
    const email = $('#customer-email').val().trim();
    const phone = $('#customer-phone').val().trim();
    
    if (!name || !email || !phone) {
        alert('Please fill in name, email, and phone');
        return;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }
    
    const useSeparateShipping = $('#use-separate-shipping').is(':checked');
    
    if (useSeparateShipping) {
        // Validate separate addresses
        const billingAddress = $('#billing-address').val().trim();
        const billingPostcode = $('#billing-postcode').val().trim();
        const shippingAddress = $('#shipping-address').val().trim();
        const shippingPostcode = $('#shipping-postcode').val().trim();
        
        if (!billingAddress || !billingPostcode || !shippingAddress || !shippingPostcode) {
            alert('Please fill in both billing and shipping addresses');
            return;
        }
        
        // Store separate addresses
        subscriptionData.customer = {
            name,
            email,
            phone,
            billing_address: billingAddress,
            billing_postcode: billingPostcode,
            shipping_address: shippingAddress,
            shipping_postcode: shippingPostcode,
            separate_shipping: true,
            address: billingAddress, // Default for compatibility
            postcode: billingPostcode
        };
    } else {
        // Single address mode
        const address = $('#customer-address').val().trim();
        const postcode = $('#customer-postcode').val().trim();
        
        if (!address || !postcode) {
            alert('Please fill in delivery address and postcode');
            return;
        }
        
        subscriptionData.customer = {
            name,
            email,
            phone,
            address,
            postcode,
            billing_address: address,
            billing_postcode: postcode,
            shipping_address: address,
            shipping_postcode: postcode,
            separate_shipping: false
        };
    }
    
    // Update checkout summary
    updateCheckoutSummary();
    showSubscriptionStep(7);
}

function updateCheckoutSummary() {
    const frequencyText = subscriptionData.frequency === 'weekly' ? 'Weekly' : 'Bi-weekly (Fortnightly)';
    const deliveryMethodText = subscriptionData.delivery_method === 'delivery' ? 
        '<i class="fas fa-truck text-primary"></i> Delivery to your door' : 
        '<i class="fas fa-store text-success"></i> Collection from Middle World Farms';
    const pricePerDelivery = subscriptionData.box_price;
    
    // Payment plan text with actual price
    let paymentPlanText = '';
    let totalBillingAmount = '';
    
    if (subscriptionData.plan_price) {
        // We have the actual price from VegboxPlan
        const currency = subscriptionData.currency || 'GBP';
        const symbol = currency === 'GBP' ? '£' : '$';
        
        if (subscriptionData.payment_plan === 'weekly') {
            paymentPlanText = `Billed Weekly - ${symbol}${subscriptionData.plan_price.toFixed(2)}/week`;
        } else if (subscriptionData.payment_plan === 'monthly') {
            paymentPlanText = `Billed Monthly - ${symbol}${subscriptionData.plan_price.toFixed(2)}/month`;
        } else if (subscriptionData.payment_plan === 'annually') {
            paymentPlanText = `Billed Annually - ${symbol}${subscriptionData.plan_price.toFixed(2)}/year`;
        }
        
        totalBillingAmount = `Total: ${symbol}${subscriptionData.plan_price.toFixed(2)}`;
    } else {
        // Fallback if price not loaded yet
        if (subscriptionData.payment_plan === 'weekly') {
            paymentPlanText = 'Billed Weekly';
        } else if (subscriptionData.payment_plan === 'monthly') {
            paymentPlanText = 'Billed Monthly';
        } else if (subscriptionData.payment_plan === 'annually') {
            paymentPlanText = 'Billed Annually';
        }
    }
    
    // Format start date info
    let startDateText = 'Starts immediately';
    if (subscriptionData.start_date) {
        const startDate = new Date(subscriptionData.start_date);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        startDateText = `Starts on ${startDate.toLocaleDateString('en-GB', options)}`;
    }
    
    $('#checkout-box-type').text(subscriptionData.box_type);
    $('#checkout-frequency').text(frequencyText);
    $('#checkout-delivery-method').html(deliveryMethodText);
    $('#checkout-price').text(`£${pricePerDelivery.toFixed(2)} per delivery`);
    $('#checkout-monthly').html(paymentPlanText);
    
    // Update the payment plan label to be more accurate
    $('#checkout-monthly').closest('tr').find('td.fw-bold').text('Payment Plan:');
    
    // Add total billing amount row if we have the price
    if (subscriptionData.plan_price && totalBillingAmount) {
        if (!$('#checkout-total-amount').length) {
            $('#checkout-monthly').parent().after(`
                <tr class="border-top">
                    <td class="fw-bold">Total Billing Amount:</td>
                    <td id="checkout-total-amount" class="text-success fw-bold fs-5"></td>
                </tr>
            `);
        }
        $('#checkout-total-amount').text(totalBillingAmount);
    }
    
    // Add start date to summary if not already there
    if (!$('#checkout-start-date').length) {
        $('#checkout-monthly').parent().after(`
            <tr>
                <td class="fw-bold">Start Date:</td>
                <td id="checkout-start-date" class="text-muted"></td>
            </tr>
        `);
    }
    $('#checkout-start-date').html(`<i class="fas fa-calendar-check text-success"></i> ${startDateText}`);
    
    $('#checkout-name').text(subscriptionData.customer.name);
    $('#checkout-email').text(subscriptionData.customer.email);
    $('#checkout-phone').text(subscriptionData.customer.phone);
    
    // Display addresses based on whether separate shipping is used
    let addressHtml = '';
    if (subscriptionData.customer.separate_shipping) {
        addressHtml = `
            <hr>
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <td class="fw-bold" colspan="2"><i class="fas fa-file-invoice"></i> Billing Address:</td>
                </tr>
                <tr>
                    <td colspan="2"><small>${subscriptionData.customer.billing_address}<br>${subscriptionData.customer.billing_postcode}</small></td>
                </tr>
                <tr>
                    <td class="fw-bold" colspan="2"><i class="fas fa-truck"></i> Shipping Address:</td>
                </tr>
                <tr>
                    <td colspan="2"><small>${subscriptionData.customer.shipping_address}<br>${subscriptionData.customer.shipping_postcode}</small></td>
                </tr>
            </table>
        `;
    } else {
        addressHtml = `
            <hr>
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <td class="fw-bold"><i class="fas fa-map-marker-alt"></i> Address:</td>
                </tr>
                <tr>
                    <td><small>${subscriptionData.customer.address}<br>${subscriptionData.customer.postcode}</small></td>
                </tr>
            </table>
        `;
    }
    
    $('#checkout-addresses').html(addressHtml);
}

function completeSubscription() {
    const btn = $('#complete-subscription-btn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing Payment...');
    
    // Use plan_price (billing amount) instead of box_price (per-delivery amount)
    const billingAmount = subscriptionData.plan_price || subscriptionData.box_price;
    const currency = subscriptionData.currency || 'GBP';
    const currencySymbol = currency === 'GBP' ? '£' : '$';
    
    console.log('Sending subscription data:', subscriptionData);
    console.log('Creating payment intent for amount:', billingAmount);
    
    // First, create payment intent
    $.ajax({
        url: '/pos/payments/intent',
        method: 'POST',
        data: JSON.stringify({
            amount: billingAmount,
            customer_name: subscriptionData.customer.name,
            order_id: null,
            items: [{
                name: subscriptionData.box_type + ' - ' + (subscriptionData.payment_plan === 'weekly' ? 'Weekly' : subscriptionData.payment_plan === 'monthly' ? 'Monthly' : 'Annual') + ' Billing',
                quantity: 1,
                price: billingAmount
            }]
        }),
        contentType: 'application/json',
        success: function(paymentResponse) {
            console.log('Payment intent response:', paymentResponse);
            
            if (!paymentResponse.success) {
                alert('Failed to initialize payment: ' + (paymentResponse.error || 'Unknown error'));
                btn.prop('disabled', false).html('Complete Subscription');
                return;
            }
            
            // Show payment modal or process payment
            const paymentIntentId = paymentResponse.payment_intent_id;
            
            // Process payment with card reader if connected
            if (connectedReader) {
                btn.html('<i class="fas fa-spinner fa-spin"></i> Processing Card Payment...');
                
                // Create cart display for subscription
                const subscriptionCart = [{
                    name: subscriptionData.box_type + ' Subscription',
                    price: billingAmount,
                    quantity: 1
                }];
                
                processCardPaymentWithReader(paymentResponse.client_secret, billingAmount, subscriptionCart)
                    .then(() => {
                        // Payment successful - create subscription
                        btn.html('<i class="fas fa-spinner fa-spin"></i> Creating Subscription...');
                        subscriptionData.payment_intent_id = paymentIntentId;
                        createSubscriptionAfterPayment();
                    })
                    .catch(error => {
                        alert('Payment failed: ' + error.message);
                        btn.prop('disabled', false).html('Complete Subscription');
                    });
            } else {
                // No card reader - fall back to confirmation dialog
                const paymentPlanLabel = subscriptionData.payment_plan === 'weekly' ? 'Weekly' : subscriptionData.payment_plan === 'monthly' ? 'Monthly' : 'Annual';
                if (confirm(`Take payment of ${currencySymbol}${billingAmount.toFixed(2)} from customer?\n\nBox: ${subscriptionData.box_type}\nFrequency: ${subscriptionData.frequency}\nPayment Plan: ${paymentPlanLabel}\nDelivery: ${subscriptionData.delivery_method}\n\n⚠️ No card reader connected - payment will need to be taken manually`)) {
                    btn.html('<i class="fas fa-spinner fa-spin"></i> Creating Subscription...');
                    subscriptionData.payment_intent_id = paymentIntentId;
                    createSubscriptionAfterPayment();
                } else {
                    alert('Payment cancelled');
                    btn.prop('disabled', false).html('Complete Subscription');
                }
            }
        },
        error: function(xhr) {
            console.error('Payment intent error:', xhr);
            console.error('Status:', xhr.status);
            console.error('Response:', xhr.responseText);
            console.error('Response JSON:', xhr.responseJSON);
            
            let errorMsg = 'Failed to initialize payment. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMsg += '\n\n' + xhr.responseJSON.error;
            } else if (xhr.responseText) {
                errorMsg += '\n\nDetails: ' + xhr.responseText.substring(0, 200);
            }
            
            alert(errorMsg);
            btn.prop('disabled', false).html('Complete Subscription');
        }
    });
}

function createSubscriptionAfterPayment() {
    const btn = $('#complete-subscription-btn');
    
    $.ajax({
        url: '/pos/subscription/create',
        method: 'POST',
        data: JSON.stringify(subscriptionData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                $('#subscriptionWizardModal').hide();
                
                // Build success message with start date info
                let successMsg = 'Payment received! Subscription created successfully!\n\n';
                if (subscriptionData.start_date) {
                    const startDate = new Date(subscriptionData.start_date);
                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    successMsg += `✓ Payment of £${subscriptionData.box_price.toFixed(2)} taken today\n`;
                    successMsg += `✓ Subscription scheduled to start: ${startDate.toLocaleDateString('en-GB', options)}\n\n`;
                } else {
                    successMsg += `✓ Payment of £${subscriptionData.box_price.toFixed(2)} taken\n`;
                    successMsg += `✓ First delivery scheduled for this week\n\n`;
                }
                successMsg += 'Customer will receive confirmation email.';
                
                alert(successMsg);
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to create subscription'));
                btn.prop('disabled', false).html('Complete Subscription');
            }
        },
        error: function(xhr) {
            console.error('Subscription error:', xhr);
            console.error('Response:', xhr.responseJSON);
            console.error('Validation errors:', xhr.responseJSON?.errors);
            
            let errorMsg = 'Error creating subscription. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                console.log('Error details:', JSON.stringify(xhr.responseJSON.errors, null, 2));
                const errors = Object.entries(xhr.responseJSON.errors).map(([field, messages]) => {
                    return `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`;
                });
                errorMsg += '\n\n' + errors.join('\n');
            }
            
            alert(errorMsg);
            btn.prop('disabled', false).html('Complete Subscription');
        }
    });
}

function cancelSubscriptionWizard() {
    if (confirm('Are you sure you want to cancel? All entered information will be lost.')) {
        $('#subscriptionWizardModal').hide();
    }
}

</script>

@endsection

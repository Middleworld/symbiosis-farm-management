@extends('layouts.app')

@section('title', $title ?? 'POS System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- POS Interface -->
        <div class="col-12 p-4">
            <!-- Stats Cards -->
            <div class="row mb-4" id="stats-section">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Today's Orders</h5>
                            <h3 id="today-orders">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Today's Sales</h5>
                            <h3 id="today-sales">£0.00</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Orders</h5>
                            <h3 id="total-orders">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <h3 id="total-sales">£0.00</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div id="main-content">
                <!-- Stats Cards -->
                <div class="row mb-4" id="stats-section">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Today's Orders</h5>
                                <h3 id="today-orders">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Today's Sales</h5>
                                <h3 id="today-sales">£0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Orders</h5>
                                <h3 id="total-orders">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Sales</h5>
                                <h3 id="total-sales">£0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <button class="btn btn-primary btn-lg w-100 mb-3" onclick="showNewSale()">
                            <i class="fas fa-shopping-cart fa-2x d-block mb-2"></i>
                            New Sale
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-info btn-lg w-100 mb-3" onclick="showOrderHistory()">
                            <i class="fas fa-history fa-2x d-block mb-2"></i>
                            Order History
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-secondary btn-lg w-100 mb-3" onclick="showProducts()">
                            <i class="fas fa-box fa-2x d-block mb-2"></i>
                            Browse Products
                        </button>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Available Products</h5>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="input-group" style="width: 250px;">
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
                        <div class="d-flex flex-wrap gap-2" id="main-category-filters">
                            <button class="btn btn-sm btn-primary" onclick="filterMainProducts('')">
                                <i class="fas fa-th-large"></i> All Products
                            </button>
                            <!-- Category buttons loaded dynamically -->
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
                    <div class="col-md-12">
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
            setTimeout(() => connectScale(), 1000); // Delay to allow UI to update
        }
    } else {
        $('#scale-panel').addClass('d-none');
    }
    
    // Show/hide printer panel based on printer type
    if (posSettings.printer_type && posSettings.printer_type !== 'browser') {
        $('#printer-panel').removeClass('d-none');
        console.log('Printer panel activated:', posSettings.printer_type);
    } else {
        $('#printer-panel').addClass('d-none');
    }
    
    // Show/hide card reader status based on card reader type
    if (posSettings.card_reader_type && posSettings.card_reader_type !== 'manual') {
        $('#card-reader-status').removeClass('d-none');
        console.log('Card reader status activated:', posSettings.card_reader_type);
    } else {
        $('#card-reader-status').addClass('d-none');
    }
}

// Load products
function loadProducts(search = '') {
    $.get('{{ route("pos.products") }}', { search: search })
        .done(function(data) {
            products = data;
            displayProducts(data);
            
            // Load category filters on main page
            loadMainCategoryFilters();
        })
        .fail(function(xhr, status, error) {
            console.error('Failed to load products:', error);
            console.error('Response:', xhr.responseText);
        });
}

// Display products in grid
function displayProducts(products) {
    const $productsGrid = $('#products-grid');

    if (products.length === 0) {
        $productsGrid.html('<div class="col-12"><p class="text-center text-muted">No products found.</p></div>');
        return;
    }

    // Use document fragment for better performance
    const fragment = document.createDocumentFragment();
    const row = document.createElement('div');
    row.className = 'row';

    products.forEach(product => {
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3';

        // Get product image or use inline SVG placeholder
        const placeholderSVG = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%2328a745" width="400" height="300"/%3E%3Ctext fill="%23ffffff" font-family="Arial" font-size="24" x="50%25" y="50%25" text-anchor="middle" dy=".3em"%3ENo Image%3C/text%3E%3C/svg%3E';
        const imageUrl = product.image_url || product.featured_image || placeholderSVG;

        col.innerHTML = `
            <div class="card h-100">
                <img src="${imageUrl}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;" onerror="this.src='${placeholderSVG}'" crossorigin="anonymous">
                <div class="card-body">
                    <h6 class="card-title">${product.name}</h6>
                    <div class="mb-2">
                        <strong class="text-primary">£${parseFloat(product.price).toFixed(2)}</strong>
                        ${product.unit ? `
                            <span class="badge ${product.unit.toLowerCase() === 'kg' ? 'bg-warning text-dark' : 'bg-info'} ms-1" 
                                  title="${product.unit.toLowerCase() === 'kg' ? 'Requires weighing' : 'Fixed price'}">
                                ${product.unit.toLowerCase() === 'kg' ? '<i class="fas fa-balance-scale"></i> per KG' : 
                                  product.unit.toLowerCase() === 'each' ? 'each' : 
                                  '/ ' + product.unit}
                            </span>
                        ` : ''}
                    </div>
                    <button class="btn btn-primary btn-sm w-100" onclick="addToCart(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price}, '${product.unit || 'each'}')">
                        ${product.unit && product.unit.toLowerCase() === 'kg' ? '<i class="fas fa-balance-scale"></i> Weigh & Add' : '<i class="fas fa-plus"></i> Add to Cart'}
                    </button>
                </div>
            </div>
        `;

        row.appendChild(col);
    });

    fragment.appendChild(row);
    $productsGrid.html('').append(fragment);
}

// Search products
function searchProducts() {
    filterMainProducts(mainCategoryFilter);
}

// Add item to cart
function addToCart(productId, productName, price, unit = 'each', quantity = 1, extraData = null) {
    // If it's a KG item and no weight provided, show weight input dialog
    if (unit && unit.toLowerCase() === 'kg' && !extraData) {
        showWeightInputDialog(productId, productName, price, unit);
        return;
    }
    
    const existingItem = cart.find(item => item.product_id === productId && !item.weighted);
    if (existingItem && !extraData) {
        existingItem.quantity += quantity;
    } else {
        const cartItem = {
            product_id: productId,
            product_name: productName,
            price: price,
            quantity: quantity,
            unit: unit,
            weighted: extraData ? true : false
        };

        if (extraData) {
            cartItem.weight = extraData.weight;
            cartItem.weight_unit = extraData.weight_unit;
            cartItem.price_per_kg = extraData.price_per_kg;
        }

        cart.push(cartItem);
    }
    
    updateCartDisplay();
    updateCartBadge();
    
    // Show visual feedback
    showAddToCartAnimation(productName);
    
    // Reset to "All Products" view
    setTimeout(() => {
        filterMainProducts('');
    }, 800);
}

// Show weight input dialog for KG items
function showWeightInputDialog(productId, productName, price, unit) {
    const modal = $(`
        <div class="modal fade" id="weightInputModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-balance-scale"></i> Weigh Item
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

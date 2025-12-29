@extends('layouts.app')

@section('title', 'POS Order History')

@section('content')
<style>
/* Custom modal with z-index: 9999 to avoid conflicts with sidebar and other elements
   This pattern should be used for all modals across the app to prevent Bootstrap modal z-index issues.
   See app.blade.php for z-index hierarchy documentation.
*/
.custom-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999 !important; /* Above everything - prevents sidebar/backdrop conflicts */
    overflow-y: auto;
}
.custom-modal.show {
    display: flex !important;
    align-items: center;
    justify-content: center;
}
.custom-modal-dialog {
    background: white;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    margin: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.custom-modal-dialog.modal-lg {
    max-width: 800px;
}
.custom-modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.custom-modal-body {
    padding: 1.5rem;
}
.custom-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}
.custom-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-history text-info"></i> POS Order History
            </h1>
            <p class="text-muted mb-0">View and manage all point of sale transactions</p>
        </div>
        <div>
            <a href="/pos" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to POS
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <select id="date-filter" class="form-select" onchange="loadOrders()">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="status-filter" class="form-select" onchange="loadOrders()">
                        <option value="">All Statuses</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="refunded">Refunded</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Method</label>
                    <select id="payment-filter" class="form-select" onchange="loadOrders()">
                        <option value="">All Methods</option>
                        <option value="card">Card</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" id="search-input" class="form-control" placeholder="Order #, customer..." onkeyup="searchOrders()">
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4" id="stats-cards">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Sales</h6>
                            <h3 class="mb-0" id="stat-total">£0.00</h3>
                        </div>
                        <i class="fas fa-pound-sign fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Orders</h6>
                            <h3 class="mb-0" id="stat-orders">0</h3>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Avg Order</h6>
                            <h3 class="mb-0" id="stat-average">£0.00</h3>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50 mb-1">Items Sold</h6>
                            <h3 class="mb-0" id="stat-items">0</h3>
                        </div>
                        <i class="fas fa-box fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Orders</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Date/Time</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body">
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="mt-2 text-muted">Loading orders...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="custom-modal" id="orderDetailsModal">
    <div class="custom-modal-dialog modal-lg">
        <div class="custom-modal-header">
            <h5>Order Details</h5>
            <button type="button" class="custom-modal-close" onclick="closeModal('orderDetailsModal')">&times;</button>
        </div>
        <div class="custom-modal-body" id="order-details-content">
            <!-- Will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div class="custom-modal" id="refundModal">
    <div class="custom-modal-dialog">
        <div class="custom-modal-header bg-warning">
            <h5><i class="fas fa-undo"></i> Issue Refund</h5>
            <button type="button" class="custom-modal-close" onclick="closeModal('refundModal')">&times;</button>
        </div>
        <div class="custom-modal-body">
            <div id="refund-order-details"></div>
            
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> This will process a refund through Stripe and update the order status. This action cannot be undone.
            </div>
            
            <div class="form-group">
                <label for="refund-reason" class="form-label">Refund Reason (optional)</label>
                <textarea class="form-control" id="refund-reason" rows="3" placeholder="Enter reason for refund..."></textarea>
            </div>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('refundModal')">Cancel</button>
            <button type="button" class="btn btn-warning" onclick="processRefund()">
                <i class="fas fa-undo"></i> Process Refund
            </button>
        </div>
    </div>
</div>

<script>
let allOrders = [];
let currentRefundOrder = null;

// Modal functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
    document.body.style.overflow = '';
}

function showModal(modalId) {
    document.getElementById(modalId).classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('custom-modal')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

// Load orders on page load
$(document).ready(function() {
    console.log('Page loaded, jQuery version:', $.fn.jquery);
    console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
    loadOrders();
    
    // Event delegation for dynamically added buttons
    $(document).on('click', '.view-order-btn', function() {
        const orderId = $(this).data('order-id');
        viewOrder(orderId);
    });
    
    $(document).on('click', '.refund-order-btn', function() {
        const orderId = $(this).data('order-id');
        refundOrder(orderId);
    });
});

function loadOrders() {
    $('#orders-table-body').html(`
        <tr>
            <td colspan="8" class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                <p class="mt-2 text-muted">Loading orders...</p>
            </td>
        </tr>
    `);
    
    $.get('/pos/orders', {
        per_page: 1000,
        status: $('#status-filter').val()
    })
    .done(function(response) {
        allOrders = response.data || [];
        filterAndDisplayOrders();
        updateStats();
    })
    .fail(function() {
        $('#orders-table-body').html(`
            <tr>
                <td colspan="8" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle"></i> Failed to load orders
                </td>
            </tr>
        `);
    });
}

function filterAndDisplayOrders() {
    const dateFilter = $('#date-filter').val();
    const paymentFilter = $('#payment-filter').val();
    const searchQuery = $('#search-input').val().toLowerCase();
    
    let filtered = allOrders.filter(order => {
        // Date filter
        const orderDate = new Date(order.created_at);
        const now = new Date();
        
        if (dateFilter === 'today') {
            if (orderDate.toDateString() !== now.toDateString()) return false;
        } else if (dateFilter === 'week') {
            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            if (orderDate < weekAgo) return false;
        } else if (dateFilter === 'month') {
            const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            if (orderDate < monthAgo) return false;
        }
        
        // Payment filter
        if (paymentFilter && order.payment_method !== paymentFilter) return false;
        
        // Search filter
        if (searchQuery) {
            const searchable = `${order.order_number} ${order.customer_name} ${order.customer_email}`.toLowerCase();
            if (!searchable.includes(searchQuery)) return false;
        }
        
        return true;
    });
    
    displayOrders(filtered);
}

function searchOrders() {
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(filterAndDisplayOrders, 300);
}

function displayOrders(orders) {
    if (orders.length === 0) {
        $('#orders-table-body').html(`
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    No orders found
                </td>
            </tr>
        `);
        return;
    }
    
    const html = orders.map(order => {
        const date = new Date(order.created_at);
        const orderItems = order.order_items || order.orderItems || [];
        const itemCount = orderItems.length;
        const totalItems = orderItems.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
        
        const statusBadge = getStatusBadge(order.order_status);
        const paymentBadge = getPaymentBadge(order.payment_method);
        const isRefunded = order.order_status === 'refunded';
        const rowClass = isRefunded ? 'table-secondary' : '';
        
        return `
            <tr class="${rowClass}">
                <td><strong>${order.order_number || '#' + order.id}</strong></td>
                <td>
                    <div>${date.toLocaleDateString('en-GB')}</div>
                    <small class="text-muted">${date.toLocaleTimeString('en-GB', {hour: '2-digit', minute: '2-digit'})}</small>
                </td>
                <td>
                    <div>${order.customer_name || 'Guest'}</div>
                    ${order.customer_email ? `<small class="text-muted">${order.customer_email}</small>` : ''}
                </td>
                <td>${totalItems} item${totalItems !== 1 ? 's' : ''}</td>
                <td><strong ${isRefunded ? 'style="text-decoration: line-through; opacity: 0.6;"' : ''}>£${parseFloat(order.total_amount).toFixed(2)}</strong></td>
                <td>${paymentBadge}</td>
                <td>${statusBadge}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary view-order-btn" data-order-id="${order.id}" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${order.order_status === 'completed' && order.payment_method === 'card' && order.stripe_payment_intent_id && order.payment_status === 'paid' ? `
                    <button class="btn btn-sm btn-outline-warning refund-order-btn" data-order-id="${order.id}" title="Issue Refund">
                        <i class="fas fa-undo"></i>
                    </button>
                    ` : ''}
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteOrder(${order.id})" title="Delete Order">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    $('#orders-table-body').html(html);
}

function getStatusBadge(status) {
    const badges = {
        'completed': '<span class="badge bg-success">Completed</span>',
        'pending': '<span class="badge bg-warning">Pending</span>',
        'refunded': '<span class="badge bg-info">Refunded</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>'
    };
    return badges[status] || `<span class="badge bg-secondary">${status || 'Unknown'}</span>`;
}

function getPaymentBadge(method) {
    const badges = {
        'card': '<span class="badge bg-primary"><i class="fas fa-credit-card"></i> Card</span>',
        'cash': '<span class="badge bg-success"><i class="fas fa-money-bill-wave"></i> Cash</span>'
    };
    return badges[method] || `<span class="badge bg-secondary">${method || 'Unknown'}</span>`;
}

function updateStats() {
    const dateFilter = $('#date-filter').val();
    const paymentFilter = $('#payment-filter').val();
    
    let filtered = allOrders.filter(order => {
        const orderDate = new Date(order.created_at);
        const now = new Date();
        
        if (dateFilter === 'today' && orderDate.toDateString() !== now.toDateString()) return false;
        if (dateFilter === 'week' && orderDate < new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)) return false;
        if (dateFilter === 'month' && orderDate < new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000)) return false;
        if (paymentFilter && order.payment_method !== paymentFilter) return false;
        
        return true;
    });
    
    // Exclude refunded orders from financial stats
    const completedOrders = filtered.filter(order => order.order_status !== 'refunded');
    
    const total = completedOrders.reduce((sum, order) => sum + parseFloat(order.total_amount || 0), 0);
    const count = completedOrders.length;
    const average = count > 0 ? total / count : 0;
    const items = completedOrders.reduce((sum, order) => {
        const orderItems = order.order_items || order.orderItems || [];
        return sum + orderItems.reduce((s, i) => s + parseInt(i.quantity || 0), 0);
    }, 0);
    
    $('#stat-total').text('£' + total.toFixed(2));
    $('#stat-orders').text(count);
    $('#stat-average').text('£' + average.toFixed(2));
    $('#stat-items').text(items);
}

function viewOrder(orderId) {
    console.log('viewOrder called with ID:', orderId);
    try {
        const order = allOrders.find(o => o.id === orderId);
        console.log('Order found:', order);
        if (!order) {
            console.error('Order not found:', orderId);
            alert('Order not found');
            return;
        }
        
        const date = new Date(order.created_at);
        const orderItems = order.order_items || order.orderItems || [];
        const itemsHtml = orderItems.length > 0 ? orderItems.map(item => `
            <tr>
                <td>${item.product_name || 'Unknown Product'}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-end">£${parseFloat(item.unit_price).toFixed(2)}</td>
                <td class="text-end"><strong>£${parseFloat(item.total_price || item.line_total || 0).toFixed(2)}</strong></td>
            </tr>
        `).join('') : '<tr><td colspan="4" class="text-center text-muted">No items</td></tr>';
        
        const html = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted">Order Information</h6>
                    <table class="table table-sm table-borderless">
                        <tr><td><strong>Order Number:</strong></td><td>${order.order_number || '#' + order.id}</td></tr>
                        <tr><td><strong>Date:</strong></td><td>${date.toLocaleString('en-GB')}</td></tr>
                        <tr><td><strong>Status:</strong></td><td>${getStatusBadge(order.order_status)}</td></tr>
                        <tr><td><strong>Payment:</strong></td><td>${getPaymentBadge(order.payment_method)}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Customer Information</h6>
                    <table class="table table-sm table-borderless">
                        <tr><td><strong>Name:</strong></td><td>${order.customer_name || 'Guest'}</td></tr>
                        <tr><td><strong>Email:</strong></td><td>${order.customer_email || '-'}</td></tr>
                        <tr><td><strong>Phone:</strong></td><td>${order.customer_phone || '-'}</td></tr>
                    </table>
                </div>
            </div>
            
            <hr>
            
            <h6 class="text-muted mb-3">Order Items</h6>
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td class="text-end"><strong>£${parseFloat(order.total_amount).toFixed(2)}</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            ${order.notes ? `<div class="alert alert-info mt-3"><strong>Notes:</strong> ${order.notes}</div>` : ''}
        `;
        
        console.log('About to set modal content');
        $('#order-details-content').html(html);
        console.log('Modal content set');
        
        showModal('orderDetailsModal');
        console.log('Modal shown');
    } catch (error) {
        console.error('Error in viewOrder:', error);
        alert('Failed to load order details: ' + error.message);
    }
}

function deleteOrder(orderId) {
    const order = allOrders.find(o => o.id === orderId);
    if (!order) return;
    
    if (!confirm(`Are you sure you want to delete order ${order.order_number || '#' + orderId}?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    $.ajax({
        url: `/pos/orders/${orderId}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .done(function(response) {
        if (response.success) {
            alert('Order deleted successfully');
            loadOrders(); // Reload the list
        } else {
            alert('Failed to delete order: ' + (response.error || 'Unknown error'));
        }
    })
    .fail(function(xhr) {
        alert('Failed to delete order: ' + (xhr.responseJSON?.error || xhr.statusText));
    });
}

function refundOrder(orderId) {
    try {
        const order = allOrders.find(o => o.id === orderId);
        if (!order) {
            console.error('Order not found:', orderId);
            return;
        }
        
        // Check if already refunded
        if (order.order_status === 'refunded') {
            alert('This order has already been refunded.');
            return;
        }
        
        // Check if it's a card payment
        if (order.payment_method !== 'card') {
            alert('Only card payments can be refunded through this system. For cash refunds, please handle manually and update the order status.');
            return;
        }
        
        // Check if it has a payment intent
        if (!order.stripe_payment_intent_id) {
            alert('No payment intent found for this order. Cannot process refund.');
            return;
        }
        
        // Additional warning for potentially incomplete payments
        if (order.payment_status !== 'paid') {
            alert('This payment may not have been successfully completed. Payment status: ' + order.payment_status + '. Please verify in Stripe before attempting refund.');
            return;
        }
        
        currentRefundOrder = order;
        
        const date = new Date(order.created_at);
        const paymentBadgeText = order.payment_method === 'card' ? 'Card' : 'Cash';
        const detailsHtml = `
            <table class="table table-sm table-borderless">
                <tr><td><strong>Order Number:</strong></td><td>${order.order_number || '#' + order.id}</td></tr>
                <tr><td><strong>Date:</strong></td><td>${date.toLocaleString('en-GB')}</td></tr>
                <tr><td><strong>Customer:</strong></td><td>${order.customer_name || 'Guest'}</td></tr>
                <tr><td><strong>Amount:</strong></td><td class="text-danger"><strong>£${parseFloat(order.total_amount).toFixed(2)}</strong></td></tr>
                <tr><td><strong>Payment Method:</strong></td><td>${paymentBadgeText}</td></tr>
            </table>
        `;
        
        $('#refund-order-details').html(detailsHtml);
        $('#refund-reason').val('');
        
        showModal('refundModal');
    } catch (error) {
        console.error('Error in refundOrder:', error);
        alert('Failed to open refund modal: ' + error.message);
    }
}

function processRefund() {
    if (!currentRefundOrder) return;
    
    const reason = $('#refund-reason').val().trim();
    
    // Disable button during processing
    const btn = $('.btn-warning[onclick="processRefund()"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    
    $.ajax({
        url: '/pos/payments/refund',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data: JSON.stringify({
            payment_intent_id: currentRefundOrder.stripe_payment_intent_id,
            order_id: currentRefundOrder.id,
            reason: reason || 'Customer refund request'
        }),
        contentType: 'application/json'
    })
    .done(function(response) {
        if (response.success) {
            alert('Refund processed successfully!');
            closeModal('refundModal');
            loadOrders(); // Reload the list
        } else {
            alert('Failed to process refund: ' + (response.error || 'Unknown error'));
            btn.prop('disabled', false).html('<i class="fas fa-undo"></i> Process Refund');
        }
    })
    .fail(function(xhr) {
        alert('Failed to process refund: ' + (xhr.responseJSON?.error || xhr.statusText));
        btn.prop('disabled', false).html('<i class="fas fa-undo"></i> Process Refund');
    });
}
</script>
@endsection

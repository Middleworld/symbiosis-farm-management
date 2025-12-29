@extends('layouts.app')

@section('title', 'Orders')

@section('header-hint')
<a href="{{ route('admin.orders.export', ['status' => $status ?? '']) }}" class="btn btn-outline-light btn-sm">
    <i class="fas fa-file-export"></i> Export CSV
</a>
@endsection

@section('page-header')
<h1>Order Management</h1>
<p class="lead mb-0">Manage and track all customer orders</p>
@endsection

@section('content')

<style>
.customer-orders-row {
    display: none;
}
.customer-orders-row.show {
    display: table-row;
}
.customer-orders-toggle {
    cursor: pointer;
    text-decoration: none;
}
.customer-orders-toggle:hover {
    text-decoration: underline;
}
.customer-orders-toggle i {
    transition: transform 0.2s ease;
}
#pdfModal .modal-dialog {
    max-width: 90%;
    margin: 1.75rem auto;
}
#pdfModal iframe {
    width: 100%;
    height: 80vh;
    border: none;
}
</style>

<!-- PDF Viewer Modal -->
<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel">PDF Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfFrame" src="" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
                <a id="pdfDownloadLink" href="#" target="_blank" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download PDF
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@if(isset($error))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> {{ $error }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<div class="card border-warning">
    <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="fas fa-tools"></i> WooCommerce Connection Setup Required</h5>
    </div>
    <div class="card-body">
        <p>To use the Order Management system, you need to configure your WooCommerce API credentials.</p>
        <ol>
            <li>Go to <strong>System > Settings</strong></li>
            <li>Scroll to <strong>WooCommerce Integration</strong></li>
            <li>Enter your WooCommerce REST API credentials</li>
            <li>Save settings and return to this page</li>
        </ol>
        <a href="{{ route('admin.settings') }}" class="btn btn-primary">
            <i class="fas fa-cog"></i> Go to Settings
        </a>
    </div>
</div>
@else

<!-- Order Stats -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                <small class="text-muted">Total Orders</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h3 class="mb-0 text-warning">{{ $stats['pending'] ?? 0 }}</h3>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-info">
            <div class="card-body">
                <h3 class="mb-0 text-info">{{ $stats['processing'] ?? 0 }}</h3>
                <small class="text-muted">Processing</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-success">
            <div class="card-body">
                <h3 class="mb-0 text-success">{{ $stats['completed'] ?? 0 }}</h3>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-secondary">
            <div class="card-body">
                <h3 class="mb-0 text-secondary">{{ $stats['cancelled'] ?? 0 }}</h3>
                <small class="text-muted">Cancelled</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h3 class="mb-0 text-danger">{{ $stats['refunded'] ?? 0 }}</h3>
                <small class="text-muted">Refunded</small>
            </div>
        </div>
    </div>
</div>

<!-- Financial Summary Dashboard -->
@if(isset($financialSummary))
<div class="card bg-success text-white mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center">
                <h4>£{{ number_format($financialSummary['todayRevenue'] ?? 0, 2) }}</h4>
                <small>Today's Revenue</small>
            </div>
            <div class="col-md-3 text-center">
                <h4>£{{ number_format($financialSummary['weekRevenue'] ?? 0, 2) }}</h4>
                <small>This Week</small>
            </div>
            <div class="col-md-3 text-center">
                <h4>£{{ number_format($financialSummary['monthRevenue'] ?? 0, 2) }}</h4>
                <small>This Month</small>
            </div>
            <div class="col-md-3 text-center">
                <h4>{{ number_format($financialSummary['conversionRate'] ?? 0, 1) }}%</h4>
                <small>Conversion Rate</small>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Top Products Dashboard -->
@if(isset($topProducts) && !empty($topProducts))
<div class="card mb-3">
    <div class="card-header bg-light">
        <strong>Top 5 Products</strong> <span class="text-muted">(based on current filters)</span>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $product)
                    <tr>
                        <td>{{ $product['name'] }}</td>
                        <td class="text-end">{{ $product['quantity'] }}</td>
                        <td class="text-end">£{{ number_format($product['revenue'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $status === 'all' ? 'active' : '' }}" 
           href="{{ route('admin.orders.index', ['status' => 'all']) }}">
            All Orders
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}" 
           href="{{ route('admin.orders.index', ['status' => 'pending']) }}">
            Pending
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'processing' ? 'active' : '' }}" 
           href="{{ route('admin.orders.index', ['status' => 'processing']) }}">
            Processing
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'on-hold' ? 'active' : '' }}" 
           href="{{ route('admin.orders.index', ['status' => 'on-hold']) }}">
            On Hold
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'completed' ? 'active' : '' }}" 
           href="{{ route('admin.orders.index', ['status' => 'completed']) }}">
            Completed
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'cancelled' ? 'active' : '' }}" 
           href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}">
            Cancelled
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'refunded' ? 'active' : '' }}" 
           href="{{ route('admin.orders.index', ['status' => 'refunded']) }}">
            Refunded
        </a>
    </li>
</ul>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        @if(isset($error))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ $error }}
        </div>
        @endif
        
        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        @endif
        
        <!-- Bulk Actions Form -->
        <form action="{{ route('admin.orders.bulk-action') }}" method="POST" id="bulkActionForm">
            @csrf
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Bulk Actions</label>
                    <div class="input-group input-group-lg">
                        <select name="action" class="form-select" required>
                            <option value="">Select Action</option>
                            <option value="mark_processing">Mark as Processing</option>
                            <option value="mark_completed">Mark as Completed</option>
                            <option value="mark_on_hold">Mark as On Hold</option>
                            <option value="mark_cancelled">Mark as Cancelled</option>
                            <option value="delete">Delete</option>
                            <option value="">────────────────</option>
                            <option value="pdf_invoices">📄 Download PDF Invoices</option>
                            <option value="pdf_packing_slips">📦 Download PDF Packing Slips</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </div>
                <div class="col-md-9">
                    <label class="form-label small text-muted mb-1">Search Orders</label>
                    <div class="input-group input-group-lg">
                        <input type="text" 
                               name="search" 
                               form="searchForm"
                               class="form-control" 
                               placeholder="Search orders (ID, customer name, email)..." 
                               value="{{ $search }}"
                               id="orderSearchInput">
                        <button type="submit" form="searchForm" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        @if($search)
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        
        <!-- Separate search form -->
        <form action="{{ route('admin.orders.index') }}" method="GET" id="searchForm" style="display: none;">
            <!-- Search input is rendered above but belongs to this form -->
        </form>
        
        <!-- Quick Date Range Filters -->
        <div class="btn-group btn-group-sm mb-3">
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['date_range' => 'today'])) }}" 
               class="btn btn-outline-secondary {{ request('date_range') === 'today' ? 'active' : '' }}">Today</a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['date_range' => 'yesterday'])) }}" 
               class="btn btn-outline-secondary {{ request('date_range') === 'yesterday' ? 'active' : '' }}">Yesterday</a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['date_range' => 'this_week'])) }}" 
               class="btn btn-outline-secondary {{ request('date_range') === 'this_week' ? 'active' : '' }}">This Week</a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['date_range' => 'this_month'])) }}" 
               class="btn btn-outline-secondary {{ request('date_range') === 'this_month' ? 'active' : '' }}">This Month</a>
            <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['date_range' => 'last_month'])) }}" 
               class="btn btn-outline-secondary {{ request('date_range') === 'last_month' ? 'active' : '' }}">Last Month</a>
        </div>
        
        <!-- Advanced Search -->
        <button class="btn btn-link mb-3" type="button" data-bs-toggle="collapse" 
                data-bs-target="#advancedSearch">
            <i class="fas fa-sliders-h"></i> Advanced Search
        </button>
        
        <div class="collapse" id="advancedSearch">
            <div class="card card-body mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label>Product</label>
                            <input type="text" name="product" class="form-control" 
                                   placeholder="Search by product" value="{{ request('product') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="">Any payment method</option>
                                <option value="stripe" {{ request('payment_method') === 'stripe' ? 'selected' : '' }}>
                                    Stripe
                                </option>
                                <option value="paypal" {{ request('payment_method') === 'paypal' ? 'selected' : '' }}>
                                    PayPal
                                </option>
                                <option value="bacs" {{ request('payment_method') === 'bacs' ? 'selected' : '' }}>
                                    Bank Transfer
                                </option>
                                <option value="cod" {{ request('payment_method') === 'cod' ? 'selected' : '' }}>
                                    Cash on Delivery
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label>Amount Range</label>
                            <div class="input-group">
                                <input type="number" name="min_amount" class="form-control" 
                                       placeholder="Min" value="{{ request('min_amount') }}" step="0.01">
                                <input type="number" name="max_amount" class="form-control" 
                                       placeholder="Max" value="{{ request('max_amount') }}" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label>Order Notes</label>
                            <input type="text" name="note" class="form-control" 
                                   placeholder="Search order notes" value="{{ request('note') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        </form> <!-- Close bulk action form temporarily -->
        
        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.orders.index') }}" id="filterForm">
            <div class="row mb-3">
                <div class="col-md-2">
                    <select name="month" class="form-select" id="dateFilter">
                        <option value="">All dates</option>
                        @php
                            $currentDate = new DateTime();
                            $currentYear = (int)$currentDate->format('Y');
                            $previousYear = $currentYear - 1;
                            $months = [];
                            
                            // Generate months from January of previous year to current month
                            $startDate = new DateTime($previousYear . '-01-01');
                            $date = clone $currentDate;
                            
                            while ($date >= $startDate) {
                                $monthYear = $date->format('Y-m');
                                $monthName = $date->format('F Y');
                                $months[] = ['value' => $monthYear, 'label' => $monthName];
                                $date->modify('-1 month');
                            }
                            
                            $selectedMonth = request('month');
                        @endphp
                        @foreach($months as $month)
                            <option value="{{ $month['value'] }}" {{ $selectedMonth === $month['value'] ? 'selected' : '' }}>
                                {{ $month['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select" id="orderTypeFilter">
                        @php
                            $selectedType = request('type');
                            
                            $orderTypes = [
                                '' => 'All order types',
                                'shop_order' => 'Shop orders',
                                'shop_subscription' => 'Subscriptions',
                                'subscription_renewal' => 'Subscription renewals',
                                'subscription_switch' => 'Subscription switches',
                            ];
                        @endphp
                        @foreach($orderTypes as $typeValue => $typeLabel)
                            <option value="{{ $typeValue }}" {{ $selectedType === $typeValue ? 'selected' : '' }}>
                                {{ $typeLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="created_via" class="form-select" id="salesChannelFilter">
                        @php
                            $selectedChannel = request('created_via');
                            
                            $salesChannels = [
                                '' => 'All sales channels',
                                'admin' => 'Admin',
                                'checkout' => 'Checkout',
                                'pos' => 'Point of Sale',
                            ];
                        @endphp
                        @foreach($salesChannels as $channelValue => $channelLabel)
                            <option value="{{ $channelValue }}" {{ $selectedChannel === $channelValue ? 'selected' : '' }}>
                                {{ $channelLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="source" class="form-select" id="orderSourceFilter">
                        @php
                            $selectedSource = request('source', 'woocommerce');
                            
                            $orderSources = [
                                'woocommerce' => 'WooCommerce',
                                'laravel' => 'Subscription Renewals',
                                'pos' => 'POS Orders',
                                'all' => 'All Orders',
                            ];
                        @endphp
                        @foreach($orderSources as $sourceValue => $sourceLabel)
                            <option value="{{ $sourceValue }}" {{ $selectedSource === $sourceValue ? 'selected' : '' }}>
                                {{ $sourceLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="position-relative">
                        <input 
                            type="text" 
                            class="form-control" 
                            id="customerSearchInput" 
                            placeholder="Filter by registered customer"
                            value="{{ request('customer_name', '') }}"
                            autocomplete="off"
                        >
                        <input type="hidden" name="customer_id" id="customerIdInput" value="{{ request('customer_id', '') }}">
                        <div id="customerDropdown" class="dropdown-menu w-100" style="max-height: 300px; overflow-y: auto;"></div>
                    </div>
                    <!-- Preserve status filter -->
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    <!-- Preserve search filter -->
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>
                            <a href="{{ route('admin.orders.index', array_merge(request()->query(), [
                                'sort' => 'number',
                                'dir' => request('sort') === 'number' && request('dir') === 'asc' ? 'desc' : 'asc'
                            ])) }}" class="text-dark text-decoration-none">
                                Order
                                @if(request('sort') === 'number')
                                    <i class="fas fa-sort-{{ request('dir') === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.orders.index', array_merge(request()->query(), [
                                'sort' => 'date',
                                'dir' => request('sort') === 'date' && request('dir') === 'asc' ? 'desc' : 'asc'
                            ])) }}" class="text-dark text-decoration-none">
                                Date
                                @if(request('sort') === 'date')
                                    <i class="fas fa-sort-{{ request('dir') === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </a>
                        </th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>
                            <a href="{{ route('admin.orders.index', array_merge(request()->query(), [
                                'sort' => 'total',
                                'dir' => request('sort') === 'total' && request('dir') === 'asc' ? 'desc' : 'asc'
                            ])) }}" class="text-dark text-decoration-none">
                                Total
                                @if(request('sort') === 'total')
                                    <i class="fas fa-sort-{{ request('dir') === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </a>
                        </th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th width="40">Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedOrders as $order)
                    <tr>
                        <td>
                            <input type="checkbox" name="order_ids[]" value="{{ $order['id'] ?? 0 }}" class="form-check-input order-checkbox" form="bulkActionForm">
                        </td>
                        <td>
                            @php
                                $orderId = $order['id'] ?? 0;
                                $isLaravel = str_starts_with($orderId, 'L-');
                                $displayId = $isLaravel ? str_replace('L-', '', $orderId) : $orderId;
                            @endphp
                            <strong><a href="{{ route('admin.orders.show', $orderId) }}">#{{ $isLaravel ? 'L-' : '' }}{{ $displayId }}</a></strong>
                        </td>
                        <td>
                            <small>{{ isset($order['date_created']) ? \Carbon\Carbon::parse($order['date_created'])->format('M d, Y') : 'N/A' }}</small><br>
                            <small class="text-muted">{{ isset($order['date_created']) ? \Carbon\Carbon::parse($order['date_created'])->format('h:i A') : '' }}</small>
                        </td>
                        <td>
                            <strong>{{ $order['billing']['first_name'] ?? '' }} {{ $order['billing']['last_name'] ?? '' }}</strong><br>
                            <small class="text-muted">{{ $order['billing']['email'] ?? '' }}</small>
                            @if(isset($order['customer_id']) && $order['customer_id'])
                                <br>
                                <a href="#" class="small text-primary customer-orders-toggle" 
                                   data-customer-id="{{ $order['customer_id'] }}"
                                   data-order-id="{{ $order['id'] }}"
                                   onclick="toggleCustomerOrders(event, {{ $order['customer_id'] }}, {{ $order['id'] }})">
                                    <i class="fas fa-chevron-right me-1"></i>
                                    View all orders ({{ $customerOrderCounts[$order['customer_id']] ?? 0 }})
                                </a>
                            @endif
                        </td>
                        <td>
                            <small>
                                @foreach(array_slice($order['line_items'] ?? [], 0, 2) as $item)
                                    {{ $item['name'] }} <span class="text-muted">x{{ $item['quantity'] }}</span><br>
                                @endforeach
                                @if(count($order['line_items'] ?? []) > 2)
                                    <span class="text-muted">+{{ count($order['line_items']) - 2 }} more</span>
                                @endif
                            </small>
                        </td>
                        <td>
                            <strong>£{{ number_format($order['total'], 2) }}</strong>
                        </td>
                        <td class="text-center">
                            @switch($order['status'])
                                @case('processing')
                                    <span class="badge bg-warning" title="Needs shipping">
                                        <i class="fas fa-truck"></i> Pending
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success" title="Shipped">
                                        <i class="fas fa-check-circle"></i> Shipped
                                    </span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst(str_replace('-', ' ', $order['status'])) }}</span>
                            @endswitch
                        </td>
                        <td>
                            <small>{{ $order['payment_method_title'] ?? 'N/A' }}</small>
                        </td>
                        <td class="text-center">
                            @if(!empty($order['customer_note']))
                                <i class="fas fa-comment text-info" title="Has customer notes"></i>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.orders.show', $order['id']) }}" 
                                   class="btn btn-outline-primary" title="View Order">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-info" title="Download Invoice"
                                        onclick="openPDF('{{ route('admin.orders.download-invoice', $order['id']) }}', 'Invoice #{{ $order['id'] }}')">
                                    <i class="fas fa-file-invoice"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" title="Download Packing Slip"
                                        onclick="openPDF('{{ route('admin.orders.download-packing-slip', $order['id']) }}', 'Packing Slip #{{ $order['id'] }}')">
                                    <i class="fas fa-box"></i>
                                </button>
                                <a href="{{ route('admin.orders.duplicate', $order['id']) }}" 
                                   class="btn btn-outline-secondary" title="Duplicate Order">
                                    <i class="fas fa-copy"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <!-- Collapsible customer orders row -->
                    @if(isset($order['customer_id']) && $order['customer_id'])
                    <tr id="customer-orders-{{ $order['id'] }}" class="customer-orders-row collapse">
                        <td colspan="9" class="p-0">
                            <div class="p-3 bg-light border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user me-2"></i>
                                        Order History for {{ $order['billing']['first_name'] ?? '' }} {{ $order['billing']['last_name'] ?? '' }}
                                    </h6>
                                    <span class="badge bg-secondary">{{ $customerOrderCounts[$order['customer_id']] ?? 0 }} total orders</span>
                                </div>
                                <div id="customer-orders-content-{{ $order['id'] }}" class="customer-orders-content">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <span class="ms-2 text-muted">Loading orders...</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No orders found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Cards View -->
        <div class="d-md-none">
            @forelse($paginatedOrders as $order)
                <div class="card mb-2">
                    <div class="card-header d-flex justify-content-between">
                        <span>#{{ $order['number'] ?? $order['id'] }}</span>
                        @php
                            $statusColors = [
                                'pending' => 'warning',
                                'processing' => 'info',
                                'on-hold' => 'secondary',
                                'completed' => 'success',
                                'cancelled' => 'dark',
                                'refunded' => 'danger',
                                'failed' => 'danger',
                            ];
                            $color = $statusColors[$order['status']] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $color }}">
                            {{ ucfirst(str_replace('-', ' ', $order['status'])) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">
                            <strong>{{ $order['billing']['first_name'] ?? '' }} {{ $order['billing']['last_name'] ?? '' }}</strong>
                        </p>
                        <p class="mb-1">
                            <i class="far fa-calendar"></i> {{ isset($order['date_created']) ? \Carbon\Carbon::parse($order['date_created'])->format('M j, Y') : 'N/A' }}
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-pound-sign"></i> {{ number_format($order['total'] ?? 0, 2) }}
                        </p>
                        <div class="btn-group btn-group-sm mt-2">
                            <a href="{{ route('admin.orders.show', $order['id']) }}" 
                               class="btn btn-primary">Details</a>
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="openPDF('{{ route('admin.orders.download-invoice', $order['id']) }}', 'Invoice #{{ $order['id'] }}')">
                                Invoice
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No orders found.</p>
                    </div>
                </div>
            @endforelse
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-3">
            <div>
                {{ $paginatedOrders->links() }}
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Customer filter script loaded');
    
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const bulkActionForm = document.getElementById('bulkActionForm');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            orderCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Update select all checkbox when individual checkboxes change
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(orderCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(orderCheckboxes).some(cb => cb.checked);
            
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });
    
    // Confirm delete action
    if (bulkActionForm) {
        console.log('Bulk action form found and listener attached');
        
        bulkActionForm.addEventListener('submit', function(e) {
            const action = this.querySelector('select[name="action"]').value;
            const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
            
            console.log('Bulk action form submitting:', {
                action: action,
                checkedCount: checkedBoxes.length,
                checkedIds: Array.from(checkedBoxes).map(cb => cb.value)
            });
            
            // Check if action is selected
            if (!action || action === '') {
                e.preventDefault();
                alert('Please select an action from the dropdown.');
                return false;
            }
            
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one order.');
                return false;
            }
            
            if (action === 'delete') {
                if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} order(s)? This action cannot be undone.`)) {
                    e.preventDefault();
                    return false;
                }
            }
            
            console.log('Form validation passed, submitting to:', bulkActionForm.action);
            alert('Submitting bulk action: ' + action + ' for ' + checkedBoxes.length + ' order(s)');
        });
    } else {
        console.error('Bulk action form NOT found!');
    }
    
    // Customer filter with AJAX search
    const customerSearchInput = document.getElementById('customerSearchInput');
    const customerIdInput = document.getElementById('customerIdInput');
    const customerDropdown = document.getElementById('customerDropdown');
    
    console.log('Customer search elements:', {
        input: customerSearchInput,
        idField: customerIdInput,
        dropdown: customerDropdown
    });
    
    if (customerSearchInput && customerIdInput && customerDropdown) {
        console.log('Customer filter initialized successfully');
        let searchTimeout;
        let customerCache = {};
        
        // Search as user types
        customerSearchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();
            console.log('Input event triggered, value:', searchTerm);
            
            // Clear customer ID if input is cleared
            if (searchTerm.length === 0) {
                customerIdInput.value = '';
                customerDropdown.innerHTML = '';
                customerDropdown.classList.remove('show');
                return;
            }
            
            if (searchTerm.length < 2) {
                customerDropdown.innerHTML = '';
                customerDropdown.classList.remove('show');
                return;
            }
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchCustomers(searchTerm);
            }, 500);
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!customerSearchInput.contains(e.target) && !customerDropdown.contains(e.target)) {
                customerDropdown.classList.remove('show');
            }
        });
        
        function searchCustomers(searchTerm) {
            if (customerCache[searchTerm]) {
                displayCustomerResults(customerCache[searchTerm]);
                return;
            }
            
            console.log('Searching for:', searchTerm);
            customerDropdown.innerHTML = '<div class="dropdown-item text-muted">Searching...</div>';
            customerDropdown.classList.add('show');
            
            fetch(`{{ route('admin.orders.search-customers') }}?search=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Customer data received:', data);
                    customerCache[searchTerm] = data.customers || [];
                    displayCustomerResults(data.customers || []);
                })
                .catch(error => {
                    console.error('Error searching customers:', error);
                    customerDropdown.innerHTML = '<div class="dropdown-item text-danger">Error loading customers</div>';
                    customerDropdown.classList.add('show');
                });
        }
        
        function displayCustomerResults(customers) {
            if (customers.length === 0) {
                customerDropdown.innerHTML = '<div class="dropdown-item text-muted">No customers found</div>';
                customerDropdown.classList.add('show');
                return;
            }
            
            customerDropdown.innerHTML = '';
            customers.forEach(customer => {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'dropdown-item d-flex justify-content-between align-items-center';
                item.innerHTML = `
                    <div>
                        <strong>${customer.first_name} ${customer.last_name}</strong><br>
                        <small class="text-muted">${customer.email}</small>
                    </div>
                    <span class="badge bg-success">£${parseFloat(customer.lifetime_value || 0).toFixed(2)}</span>
                `;
                item.dataset.customerId = customer.id;
                item.dataset.customerName = `${customer.first_name} ${customer.last_name}`;
                
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    customerSearchInput.value = this.dataset.customerName;
                    customerIdInput.value = this.dataset.customerId;
                    customerDropdown.classList.remove('show');
                });
                
                customerDropdown.appendChild(item);
            });
            customerDropdown.classList.add('show');
        }
    }
});

// Toggle customer orders display
function toggleCustomerOrders(event, customerId, orderId) {
    event.preventDefault();
    
    const row = document.getElementById(`customer-orders-${orderId}`);
    const toggle = event.currentTarget;
    const icon = toggle.querySelector('i');
    const content = document.getElementById(`customer-orders-content-${orderId}`);
    
    // Toggle the row visibility
    if (row.classList.contains('show')) {
        row.classList.remove('show');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    } else {
        row.classList.add('show');
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
        
        // Load orders if not already loaded
        if (content.dataset.loaded !== 'true') {
            loadCustomerOrders(customerId, orderId);
        }
    }
}

// Load customer orders via AJAX
function loadCustomerOrders(customerId, currentOrderId) {
    const content = document.getElementById(`customer-orders-content-${currentOrderId}`);
    
    fetch(`{{ route('admin.orders.index') }}?customer_id=${customerId}&per_page=100`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(response => response.text())
    .then(html => {
        // Parse the HTML to extract order data
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const orderRows = doc.querySelectorAll('tbody tr:not(.customer-orders-row)');
        
        if (orderRows.length === 0) {
            content.innerHTML = '<div class="text-center py-3 text-muted">No orders found</div>';
            return;
        }
        
        let ordersHtml = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
        ordersHtml += '<thead class="table-light"><tr>';
        ordersHtml += '<th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th>';
        ordersHtml += '</tr></thead><tbody>';
        
        orderRows.forEach((row, index) => {
            if (index < 50) { // Limit to 50 orders for performance
                const cells = row.querySelectorAll('td');
                if (cells.length >= 8) {
                    const orderNum = cells[1].textContent.trim();
                    const orderDate = cells[2].textContent.trim();
                    const orderItems = cells[4].textContent.trim();
                    const orderTotal = cells[5].textContent.trim();
                    const orderStatus = cells[6].innerHTML;
                    const orderLink = cells[1].querySelector('a')?.href || '#';
                    
                    // Skip the current order
                    if (!orderNum.includes(currentOrderId)) {
                        ordersHtml += '<tr>';
                        ordersHtml += `<td><a href="${orderLink}" class="fw-bold">${orderNum}</a></td>`;
                        ordersHtml += `<td><small>${orderDate}</small></td>`;
                        ordersHtml += `<td><small>${orderItems}</small></td>`;
                        ordersHtml += `<td><strong>${orderTotal}</strong></td>`;
                        ordersHtml += `<td>${orderStatus}</td>`;
                        ordersHtml += `<td><a href="${orderLink}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>`;
                        ordersHtml += '</tr>';
                    }
                }
            }
        });
        
        ordersHtml += '</tbody></table></div>';
        content.innerHTML = ordersHtml;
        content.dataset.loaded = 'true';
    })
    .catch(error => {
        console.error('Error loading customer orders:', error);
        content.innerHTML = '<div class="alert alert-danger">Failed to load orders. Please try again.</div>';
    });
}

// Open PDF in modal
function openPDF(url, title) {
    // Open PDF in a new tab/window
    window.open(url, '_blank');
}

// Prefetch next page orders asynchronously
function prefetchNextPage() {
    const currentPage = {{ $paginatedOrders->currentPage() }};
    const lastPage = {{ $paginatedOrders->lastPage() }};
    
    if (currentPage < lastPage) {
        // Prefetch next page in background
        fetch('{{ route("admin.orders.prefetch") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        }).then(response => {
            if (response.ok) {
                console.log('Next page orders prefetched successfully');
            }
        }).catch(error => {
            console.log('Prefetch failed:', error);
        });
    }
}

// Trigger prefetch when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to not interfere with initial page load
    setTimeout(prefetchNextPage, 2000);
});
</script>

@endif
@endsection

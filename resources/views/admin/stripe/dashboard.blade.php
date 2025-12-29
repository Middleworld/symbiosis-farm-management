@extends('layouts.app')

@section('title', 'Stripe Payments Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-credit-card me-2"></i>Stripe Payments
        </h1>
        <div class="d-flex gap-2">
            <select id="timeRange" class="form-select form-select-sm">
                <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
                <option value="365">Last year</option>
                <option value="custom">Custom range...</option>
            </select>
            <button class="btn btn-primary btn-sm" onclick="refreshData()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-success btn-sm" onclick="exportPayments()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>
    
    <!-- Custom Date Range Picker (hidden by default) -->
    <div id="customDateRange" class="card mb-4" style="display: none;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="startDate" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" id="endDate" class="form-control" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary" onclick="applyCustomRange()">
                        <i class="fas fa-check"></i> Apply Range
                    </button>
                    <button class="btn btn-outline-secondary ms-2" onclick="cancelCustomRange()">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="stripeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="true">
                <i class="fas fa-credit-card"></i> Payments
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="balance-tab" data-bs-toggle="tab" data-bs-target="#balance" type="button" role="tab" aria-controls="balance" aria-selected="false">
                <i class="fas fa-wallet"></i> Balance & Payouts
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="stripeTabContent">
        <!-- Payments Tab -->
        <div class="tab-pane fade show active" id="payments" role="tabpanel" aria-labelledby="payments-tab">

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRevenue">
                                £{{ number_format($statistics['total_revenue'], 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-pound-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Successful Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalTransactions">
                                {{ number_format($statistics['total_transactions']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Average Transaction
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="averageTransaction">
                                £{{ number_format($statistics['average_transaction'], 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Failed Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="failedTransactions">
                                {{ number_format($statistics['failed_transactions']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Daily Revenue Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Revenue</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Customers</h6>
                </div>
                <div class="card-body">
                    <div id="topCustomers">
                        @foreach($statistics['top_customers'] as $customer)
                        <div class="d-flex align-items-center border-bottom pb-2 mb-2">
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $customer['name'] }}</div>
                                <div class="text-muted small">{{ $customer['email'] }}</div>
                                <div class="text-muted small">{{ $customer['count'] }} transactions</div>
                            </div>
                            <div class="text-success font-weight-bold">
                                £{{ number_format($customer['total'], 2) }}
                            </div>
                        </div>
                        @endforeach
                        @if(count($statistics['top_customers']) == 0)
                        <p class="text-muted text-center mb-0">No customer data available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Payments</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        @foreach($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment['created']->format('M j, Y g:i A') }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $payment['customer_name'] }}</div>
                                <div class="text-muted small">{{ $payment['customer_email'] }}</div>
                            </td>
                            <td class="font-weight-bold {{ $payment['refunded'] ? 'text-danger' : 'text-success' }}">
                                {{ $payment['refunded'] ? '-' : '' }}£{{ number_format($payment['amount'], 2) }} {{ $payment['currency'] }}
                            </td>
                            <td>
                                @if($payment['refunded'])
                                    <span class="badge bg-danger text-white">Refunded</span>
                                @else
                                    <span class="badge bg-{{ $payment['status'] === 'succeeded' ? 'success' : ($payment['status'] === 'failed' ? 'danger' : 'warning') }} text-white">
                                        {{ ucfirst($payment['status']) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($payment['payment_method']['type'] === 'Card')
                                    {{ $payment['payment_method']['brand'] }} •••• {{ $payment['payment_method']['last4'] }}
                                @else
                                    {{ $payment['payment_method']['type'] }}
                                @endif
                            </td>
                            <td>{{ $payment['description'] ?? 'No description' }}</td>
                            <td>
                                @if($payment['receipt_url'])
                                <a href="{{ $payment['receipt_url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-receipt"></i> Receipt
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            @if($hasMore ?? false)
            <div class="d-flex justify-content-center mt-3">
                <button class="btn btn-primary" id="loadMoreBtn" onclick="loadMorePayments()">
                    <i class="fas fa-arrow-down"></i> Load More Payments
                </button>
            </div>
            @endif
            
            <input type="hidden" id="lastPaymentId" value="{{ $lastId ?? '' }}">
        </div>
    </div>

    <!-- Subscriptions Section -->
    @if($subscriptions->count() > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Active Subscriptions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Amount</th>
                            <th>Interval</th>
                            <th>Status</th>
                            <th>Current Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscriptions as $subscription)
                        <tr>
                            <td>{{ $subscription['customer_id'] }}</td>
                            <td class="font-weight-bold">
                                £{{ number_format($subscription['amount'], 2) }} {{ $subscription['currency'] }}
                            </td>
                            <td>{{ ucfirst($subscription['interval']) }}ly</td>
                            <td>
                                <span class="badge badge-{{ $subscription['status'] === 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($subscription['status']) }}
                                </span>
                            </td>
                            <td>
                                {{ $subscription['current_period_start']->format('M j') }} - 
                                {{ $subscription['current_period_end']->format('M j, Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
        </div>
        <!-- End Payments Tab -->

        <!-- Balance & Payouts Tab -->
        <div class="tab-pane fade" id="balance" role="tabpanel" aria-labelledby="balance-tab">
            
            <!-- Balance Cards -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Available Balance
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="availableBalance">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-wallet fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending Balance
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingBalance">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Payouts (30 days)
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalPayouts">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-university fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payouts Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Payouts</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="payoutsTable">
                            <thead>
                                <tr>
                                    <th>Payout Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Arrival Date</th>
                                    <th>Destination</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody id="payoutsTableBody">
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading payouts...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="d-flex justify-content-center mt-3" id="loadMorePayoutsContainer" style="display: none !important;">
                        <button class="btn btn-primary" id="loadMorePayoutsBtn" onclick="loadMorePayouts()">
                            <i class="fas fa-arrow-down"></i> Load More Payouts
                        </button>
                    </div>
                    
                    <input type="hidden" id="lastPayoutId" value="">
                </div>
            </div>

        </div>
        <!-- End Balance & Payouts Tab -->

    </div>
    <!-- End Tab Content -->

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global variables
let currentStartDate = null;
let currentEndDate = null;
let currentDays = {{ $days }};

// Initialize revenue chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
            @foreach($statistics['daily_revenue'] as $day)
                '{{ Carbon\Carbon::parse($day['date'])->format('M j') }}',
            @endforeach
        ],
        datasets: [{
            label: 'Daily Revenue',
            data: [
                @foreach($statistics['daily_revenue'] as $day)
                    {{ $day['revenue'] }},
                @endforeach
            ],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value, index, values) {
                        return '£' + value;
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '£' + context.parsed.y.toFixed(2);
                    }
                }
            }
        }
    }
});

// Time range dropdown handler
document.getElementById('timeRange').addEventListener('change', function() {
    if (this.value === 'custom') {
        document.getElementById('customDateRange').style.display = 'block';
    } else {
        document.getElementById('customDateRange').style.display = 'none';
        currentDays = this.value;
        currentStartDate = null;
        currentEndDate = null;
        refreshData();
    }
});

// Custom date range functions
function applyCustomRange() {
    currentStartDate = document.getElementById('startDate').value;
    currentEndDate = document.getElementById('endDate').value;
    currentDays = null;
    
    document.getElementById('customDateRange').style.display = 'none';
    refreshData();
}

function cancelCustomRange() {
    document.getElementById('customDateRange').style.display = 'none';
    document.getElementById('timeRange').value = currentDays || 30;
}

// Refresh data function
function refreshData() {
    const btn = document.querySelector('.btn[onclick="refreshData()"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    btn.disabled = true;
    
    let url = '/admin/stripe/statistics?';
    if (currentStartDate && currentEndDate) {
        url += `start_date=${currentStartDate}&end_date=${currentEndDate}`;
    } else {
        url += `days=${currentDays}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatistics(data.statistics);
                updateChart(data.statistics.daily_revenue);
                updateTopCustomers(data.statistics.top_customers);
            }
        })
        .catch(error => console.error('Error:', error))
        .finally(() => {
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
            btn.disabled = false;
        });
    
    // Reload payments
    loadPayments();
}

// Load more payments
function loadMorePayments() {
    const lastId = document.getElementById('lastPaymentId').value;
    const btn = document.getElementById('loadMoreBtn');
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    btn.disabled = true;
    
    let url = '/admin/stripe/payments?limit=25&starting_after=' + lastId;
    if (currentStartDate && currentEndDate) {
        url += `&start_date=${currentStartDate}&end_date=${currentEndDate}`;
    } else {
        url += `&days=${currentDays}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.payments.length > 0) {
                appendPayments(data.payments);
                document.getElementById('lastPaymentId').value = data.last_id;
                
                if (!data.has_more) {
                    btn.style.display = 'none';
                } else {
                    btn.innerHTML = '<i class="fas fa-arrow-down"></i> Load More Payments';
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = '<i class="fas fa-arrow-down"></i> Load More Payments';
            btn.disabled = false;
        });
}

// Load fresh payments list
function loadPayments() {
    let url = '/admin/stripe/payments?limit=25';
    if (currentStartDate && currentEndDate) {
        url += `&start_date=${currentStartDate}&end_date=${currentEndDate}`;
    } else {
        url += `&days=${currentDays}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('paymentsTableBody');
                tbody.innerHTML = '';
                appendPayments(data.payments);
                document.getElementById('lastPaymentId').value = data.last_id;
                
                const loadMoreBtn = document.getElementById('loadMoreBtn');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = data.has_more ? 'inline-block' : 'none';
                }
            }
        });
}

// Append payments to table
function appendPayments(payments) {
    const tbody = document.getElementById('paymentsTableBody');
    
    payments.forEach(payment => {
        const row = document.createElement('tr');
        const createdDate = new Date(payment.created.date);
        
        let statusBadge = 'warning';
        let statusText = payment.status.charAt(0).toUpperCase() + payment.status.slice(1);
        let statusClass = `badge bg-${statusBadge} text-white`;
        
        if (payment.refunded) {
            statusClass = 'badge bg-danger text-white';
            statusText = 'Refunded';
        } else if (payment.status === 'succeeded') {
            statusClass = 'badge bg-success text-white';
        } else if (payment.status === 'failed') {
            statusClass = 'badge bg-danger text-white';
        }
        
        let paymentMethod = payment.payment_method.type;
        if (payment.payment_method.type === 'Card') {
            paymentMethod = `${payment.payment_method.brand} •••• ${payment.payment_method.last4}`;
        }
        
        row.innerHTML = `
            <td>${createdDate.toLocaleDateString('en-GB', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
            <td>
                <div class="font-weight-bold">${payment.customer_name}</div>
                <div class="text-muted small">${payment.customer_email}</div>
            </td>
            <td class="font-weight-bold ${payment.refunded ? 'text-danger' : 'text-success'}">${payment.refunded ? '-' : ''}£${parseFloat(payment.amount).toFixed(2)} ${payment.currency}</td>
            <td><span class="${statusClass}">${statusText}</span></td>
            <td>${paymentMethod}</td>
            <td>${payment.description || 'No description'}</td>
            <td>
                ${payment.receipt_url ? `<a href="${payment.receipt_url}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-receipt"></i> Receipt</a>` : ''}
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

// Export payments
function exportPayments() {
    let url = '/admin/stripe/payments?limit=1000&format=csv';
    if (currentStartDate && currentEndDate) {
        url += `&start_date=${currentStartDate}&end_date=${currentEndDate}`;
    } else {
        url += `&days=${currentDays}`;
    }
    window.location.href = url;
}

function updateStatistics(stats) {
    document.getElementById('totalRevenue').textContent = 
        '£' + new Intl.NumberFormat().format(stats.total_revenue.toFixed(2));
    document.getElementById('totalTransactions').textContent = 
        new Intl.NumberFormat().format(stats.total_transactions);
    document.getElementById('averageTransaction').textContent = 
        '£' + new Intl.NumberFormat().format(stats.average_transaction.toFixed(2));
    document.getElementById('failedTransactions').textContent = 
        new Intl.NumberFormat().format(stats.failed_transactions);
}

function updateChart(dailyRevenue) {
    revenueChart.data.labels = dailyRevenue.map(day => {
        return new Date(day.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    revenueChart.data.datasets[0].data = dailyRevenue.map(day => day.revenue);
    revenueChart.update();
}

function updateTopCustomers(customers) {
    const container = document.getElementById('topCustomers');
    container.innerHTML = '';
    
    if (customers.length === 0) {
        container.innerHTML = '<p class="text-muted text-center mb-0">No customer data available</p>';
        return;
    }
    
    customers.forEach(customer => {
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center border-bottom pb-2 mb-2';
        div.innerHTML = `
            <div class="flex-grow-1">
                <div class="font-weight-bold">${customer.name}</div>
                <div class="text-muted small">${customer.email}</div>
                <div class="text-muted small">${customer.count} transactions</div>
            </div>
            <div class="text-success font-weight-bold">
                £${parseFloat(customer.total).toFixed(2)}
            </div>
        `;
        container.appendChild(div);
    });
}

// Balance & Payouts Tab Functions
let balanceLoaded = false;

// Listen for tab changes
document.addEventListener('DOMContentLoaded', function() {
    const balanceTab = document.getElementById('balance-tab');
    if (balanceTab) {
        balanceTab.addEventListener('click', function() {
            if (!balanceLoaded) {
                loadBalanceData();
                loadPayoutsData();
                balanceLoaded = true;
            }
        });
    }
});

// Load balance data
function loadBalanceData() {
    fetch('/admin/stripe/balance')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateBalanceDisplay(data.balance);
            }
        })
        .catch(error => {
            console.error('Error loading balance:', error);
            document.getElementById('availableBalance').innerHTML = '<span class="text-danger">Error loading</span>';
            document.getElementById('pendingBalance').innerHTML = '<span class="text-danger">Error loading</span>';
        });
}

// Update balance display
function updateBalanceDisplay(balance) {
    const availableAmount = balance.available.reduce((sum, bal) => sum + bal.amount, 0) / 100;
    const pendingAmount = balance.pending.reduce((sum, bal) => sum + bal.amount, 0) / 100;
    
    document.getElementById('availableBalance').textContent = 
        '£' + new Intl.NumberFormat('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(availableAmount);
    document.getElementById('pendingBalance').textContent = 
        '£' + new Intl.NumberFormat('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(pendingAmount);
}

// Load payouts data
function loadPayoutsData() {
    fetch('/admin/stripe/payouts?limit=25')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPayouts(data.payouts);
                updateTotalPayouts(data.total_amount);
                
                if (data.has_more) {
                    document.getElementById('loadMorePayoutsContainer').style.display = 'flex';
                    document.getElementById('lastPayoutId').value = data.last_id;
                }
            }
        })
        .catch(error => {
            console.error('Error loading payouts:', error);
            const tbody = document.getElementById('payoutsTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading payouts</td></tr>';
        });
}

// Display payouts in table
function displayPayouts(payouts) {
    const tbody = document.getElementById('payoutsTableBody');
    tbody.innerHTML = '';
    
    if (payouts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No payouts found</td></tr>';
        return;
    }
    
    payouts.forEach(payout => {
        const row = document.createElement('tr');
        
        const createdDate = new Date(payout.created * 1000);
        const arrivalDate = new Date(payout.arrival_date * 1000);
        
        let statusBadge = 'warning';
        if (payout.status === 'paid') {
            statusBadge = 'success';
        } else if (payout.status === 'failed' || payout.status === 'canceled') {
            statusBadge = 'danger';
        } else if (payout.status === 'in_transit') {
            statusBadge = 'info';
        }
        
        row.innerHTML = `
            <td>${createdDate.toLocaleDateString('en-GB', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
            <td class="font-weight-bold text-success">£${(payout.amount / 100).toFixed(2)} ${payout.currency.toUpperCase()}</td>
            <td><span class="badge bg-${statusBadge} text-white">${payout.status.charAt(0).toUpperCase() + payout.status.slice(1).replace('_', ' ')}</span></td>
            <td>${arrivalDate.toLocaleDateString('en-GB', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
            <td>${payout.destination || 'Bank Account'}</td>
            <td>${payout.description || 'STRIPE PAYOUT'}</td>
        `;
        
        tbody.appendChild(row);
    });
}

// Update total payouts amount
function updateTotalPayouts(totalAmount) {
    document.getElementById('totalPayouts').textContent = 
        '£' + new Intl.NumberFormat('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(totalAmount / 100);
}

// Load more payouts
function loadMorePayouts() {
    const lastId = document.getElementById('lastPayoutId').value;
    const btn = document.getElementById('loadMorePayoutsBtn');
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    btn.disabled = true;
    
    fetch(`/admin/stripe/payouts?limit=25&starting_after=${lastId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.payouts.length > 0) {
                const tbody = document.getElementById('payoutsTableBody');
                
                data.payouts.forEach(payout => {
                    const row = document.createElement('tr');
                    const createdDate = new Date(payout.created * 1000);
                    const arrivalDate = new Date(payout.arrival_date * 1000);
                    
                    let statusBadge = 'warning';
                    if (payout.status === 'paid') {
                        statusBadge = 'success';
                    } else if (payout.status === 'failed' || payout.status === 'canceled') {
                        statusBadge = 'danger';
                    } else if (payout.status === 'in_transit') {
                        statusBadge = 'info';
                    }
                    
                    row.innerHTML = `
                        <td>${createdDate.toLocaleDateString('en-GB', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                        <td class="font-weight-bold text-success">£${(payout.amount / 100).toFixed(2)} ${payout.currency.toUpperCase()}</td>
                        <td><span class="badge bg-${statusBadge} text-white">${payout.status.charAt(0).toUpperCase() + payout.status.slice(1).replace('_', ' ')}</span></td>
                        <td>${arrivalDate.toLocaleDateString('en-GB', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td>${payout.destination || 'Bank Account'}</td>
                        <td>${payout.description || 'STRIPE PAYOUT'}</td>
                    `;
                    
                    tbody.appendChild(row);
                });
                
                document.getElementById('lastPayoutId').value = data.last_id;
                
                if (!data.has_more) {
                    btn.style.display = 'none';
                } else {
                    btn.innerHTML = '<i class="fas fa-arrow-down"></i> Load More Payouts';
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = '<i class="fas fa-arrow-down"></i> Load More Payouts';
            btn.disabled = false;
        });
}

// Auto-refresh every 5 minutes
setInterval(refreshData, 5 * 60 * 1000);
</script>
@endsection

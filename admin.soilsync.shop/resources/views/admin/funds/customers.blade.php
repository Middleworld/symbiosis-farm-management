@extends('layouts.admin')

@section('title', 'Customer Funds')

@section('styles')
<style>
.customer-card {
    transition: transform 0.2s;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
.customer-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.customer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}
.balance-amount {
    font-size: 1.5rem;
    font-weight: bold;
    color: #28a745;
}
.balance-negative {
    color: #dc3545;
}
.transaction-item {
    padding: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
}
.transaction-item:last-child {
    border-bottom: none;
}
.transaction-amount {
    font-weight: 600;
}
.transaction-positive {
    color: #28a745;
}
.transaction-negative {
    color: #dc3545;
}
.filter-section {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Customer Funds
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.funds.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Overview
                        </a>
                        <button class="btn btn-primary btn-sm" onclick="exportCustomerFunds()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="search-customer" class="form-label">Search Customer</label>
                                <input type="text" class="form-control" id="search-customer" placeholder="Name or email...">
                            </div>
                            <div class="col-md-3">
                                <label for="balance-filter" class="form-label">Balance Filter</label>
                                <select class="form-select" id="balance-filter">
                                    <option value="">All Balances</option>
                                    <option value="positive">Positive Balance</option>
                                    <option value="zero">Zero Balance</option>
                                    <option value="negative">Negative Balance</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sort-by" class="form-label">Sort By</label>
                                <select class="form-select" id="sort-by">
                                    <option value="name">Name</option>
                                    <option value="balance">Balance</option>
                                    <option value="last_transaction">Last Transaction</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-outline-primary me-2" onclick="applyFilters()">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <button class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Funds Grid -->
                    <div class="row" id="customers-grid">
                        <!-- Customers will be loaded here -->
                        <div class="col-12 text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading customer funds...</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4" id="pagination-container">
                        <!-- Pagination will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer Detail Modal -->
<div class="modal fade" id="customerDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user"></i> Customer Funds Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="customer-avatar mx-auto mb-3" id="modal-avatar">JD</div>
                            <h5 id="modal-name">John Doe</h5>
                            <p class="text-muted" id="modal-email">john@example.com</p>
                            <div class="balance-amount" id="modal-balance">£125.50</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h6>Recent Transactions</h6>
                        <div id="modal-transactions" class="mt-3">
                            <!-- Transactions will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="adjustCustomerBalance()">
                    <i class="fas fa-edit"></i> Adjust Balance
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Balance Modal -->
<div class="modal fade" id="adjustBalanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-balance-scale"></i> Adjust Customer Balance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adjust-balance-form">
                    <div class="mb-3">
                        <label for="adjustment-type" class="form-label">Adjustment Type</label>
                        <select class="form-select" id="adjustment-type" required>
                            <option value="add">Add Funds</option>
                            <option value="subtract">Subtract Funds</option>
                            <option value="set">Set Balance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment-amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                            <input type="number" class="form-control" id="adjustment-amount" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment-reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="adjustment-reason" rows="3" placeholder="Reason for adjustment..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBalanceAdjustment()">
                    <i class="fas fa-save"></i> Apply Adjustment
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentPage = 1;
let currentFilters = {};

document.addEventListener('DOMContentLoaded', function() {
    loadCustomerFunds();

    // Search functionality
    document.getElementById('search-customer').addEventListener('input', debounce(function() {
        currentFilters.search = this.value;
        currentPage = 1;
        loadCustomerFunds();
    }, 500));

    // Filter changes
    document.getElementById('balance-filter').addEventListener('change', function() {
        currentFilters.balance_filter = this.value;
        currentPage = 1;
        loadCustomerFunds();
    });

    document.getElementById('sort-by').addEventListener('change', function() {
        currentFilters.sort_by = this.value;
        currentPage = 1;
        loadCustomerFunds();
    });
});

async function loadCustomerFunds() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            ...currentFilters
        });

        const response = await fetch(`{{ route("admin.funds.customers") }}?${params}`);
        const data = await response.json();

        renderCustomerFunds(data.customers || []);
        renderPagination(data.pagination || {});

    } catch (error) {
        console.error('Failed to load customer funds:', error);
        document.getElementById('customers-grid').innerHTML =
            '<div class="col-12 text-center text-danger">Failed to load customer funds.</div>';
    }
}

function renderCustomerFunds(customers) {
    const grid = document.getElementById('customers-grid');

    if (customers.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center">No customers found.</div>';
        return;
    }

    const customerCards = customers.map(customer => `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="customer-card p-3" onclick="viewCustomerDetail(${customer.id})" style="cursor: pointer;">
                <div class="d-flex align-items-center mb-3">
                    <div class="customer-avatar me-3">
                        ${getInitials(customer.name)}
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">${customer.name}</h6>
                        <small class="text-muted">${customer.email}</small>
                    </div>
                </div>
                <div class="text-center">
                    <div class="balance-amount ${parseFloat(customer.balance) < 0 ? 'balance-negative' : ''}">
                        {{ config('pos_payments.currency_symbol', '£') }}${parseFloat(customer.balance).toFixed(2)}
                    </div>
                    <small class="text-muted">
                        Last transaction: ${customer.last_transaction || 'Never'}
                    </small>
                </div>
            </div>
        </div>
    `).join('');

    grid.innerHTML = customerCards;
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination-container');

    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let paginationHtml = '<nav><ul class="pagination">';

    // Previous button
    if (pagination.current_page > 1) {
        paginationHtml += `<li class="page-item">
            <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">Previous</a>
        </li>`;
    }

    // Page numbers
    for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
        paginationHtml += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
        </li>`;
    }

    // Next button
    if (pagination.current_page < pagination.total_pages) {
        paginationHtml += `<li class="page-item">
            <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">Next</a>
        </li>`;
    }

    paginationHtml += '</ul></nav>';
    container.innerHTML = paginationHtml;
}

function changePage(page) {
    currentPage = page;
    loadCustomerFunds();
}

function applyFilters() {
    currentFilters.search = document.getElementById('search-customer').value;
    currentFilters.balance_filter = document.getElementById('balance-filter').value;
    currentFilters.sort_by = document.getElementById('sort-by').value;
    currentPage = 1;
    loadCustomerFunds();
}

function clearFilters() {
    document.getElementById('search-customer').value = '';
    document.getElementById('balance-filter').value = '';
    document.getElementById('sort-by').value = 'name';
    currentFilters = {};
    currentPage = 1;
    loadCustomerFunds();
}

async function viewCustomerDetail(customerId) {
    try {
        const response = await fetch(`{{ route("admin.funds.customers") }}/${customerId}`);
        const data = await response.json();

        // Populate modal
        document.getElementById('modal-avatar').textContent = getInitials(data.customer.name);
        document.getElementById('modal-name').textContent = data.customer.name;
        document.getElementById('modal-email').textContent = data.customer.email;
        document.getElementById('modal-balance').textContent =
            `{{ config('pos_payments.currency_symbol', '£') }}${parseFloat(data.customer.balance).toFixed(2)}`;

        // Render transactions
        const transactionsHtml = (data.transactions || []).map(transaction => `
            <div class="transaction-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">${new Date(transaction.date).toLocaleDateString()}</small>
                        <div>
                            ${transaction.description && transaction.description.includes('Order #') 
                                ? transaction.description.replace(/Order #(\d+)/, '<a href="/admin/orders/$1" target="_blank" class="text-decoration-none">Order #$1</a>')
                                : (transaction.description || 'N/A')
                            }
                        </div>
                    </div>
                    <div class="transaction-amount ${transaction.type === 'deposit' ? 'transaction-positive' : 'transaction-negative'}">
                        ${transaction.type === 'deposit' ? '+' : '-'}{{ config('pos_payments.currency_symbol', '£') }}${parseFloat(transaction.amount).toFixed(2)}
                    </div>
                </div>
            </div>
        `).join('');

        document.getElementById('modal-transactions').innerHTML = transactionsHtml || '<p class="text-muted">No transactions found.</p>';

        // Show modal
        new bootstrap.Modal(document.getElementById('customerDetailModal')).show();

    } catch (error) {
        console.error('Failed to load customer detail:', error);
        alert('Failed to load customer details. Please try again.');
    }
}

function adjustCustomerBalance() {
    // Close detail modal and open adjustment modal
    bootstrap.Modal.getInstance(document.getElementById('customerDetailModal')).hide();
    new bootstrap.Modal(document.getElementById('adjustBalanceModal')).show();
}

async function submitBalanceAdjustment() {
    // TODO: Implement balance adjustment logic
    alert('Balance adjustment functionality - Coming soon!');
}

function exportCustomerFunds() {
    // TODO: Implement export functionality
    alert('Export functionality - Coming soon!');
}

function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endsection
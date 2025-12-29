@extends('layouts.admin')

@section('title', 'Funds Transactions')

@section('styles')
<style>
.transaction-card {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    transition: transform 0.2s;
}
.transaction-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.transaction-header {
    background-color: #f8f9fa;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.375rem 0.375rem 0 0;
}
.transaction-body {
    padding: 1rem;
}
.transaction-amount {
    font-size: 1.25rem;
    font-weight: bold;
}
.transaction-positive {
    color: #28a745;
}
.transaction-negative {
    color: #dc3545;
}
.transaction-type {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.type-deposit {
    background-color: #d4edda;
    color: #155724;
}
.type-withdrawal {
    background-color: #f8d7da;
    color: #721c24;
}
.type-adjustment {
    background-color: #fff3cd;
    color: #856404;
}
.filter-section {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.375rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
}
.stats-card .stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}
.stats-card .stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
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
                        <i class="fas fa-history"></i> Funds Transactions
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.funds.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Overview
                        </a>
                        <button class="btn btn-primary btn-sm" onclick="exportTransactions()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Transaction Stats -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="stats-card">
                                <div class="stat-value" id="total-transactions">0</div>
                                <div class="stat-label">Total Transactions</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stats-card">
                                <div class="stat-value" id="total-deposits">£0.00</div>
                                <div class="stat-label">Total Deposits</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stats-card">
                                <div class="stat-value" id="total-withdrawals">£0.00</div>
                                <div class="stat-label">Total Withdrawals</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="stats-card">
                                <div class="stat-value" id="net-flow">£0.00</div>
                                <div class="stat-label">Net Flow</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="date-from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date-from">
                            </div>
                            <div class="col-md-3">
                                <label for="date-to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date-to">
                            </div>
                            <div class="col-md-3">
                                <label for="transaction-type-filter" class="form-label">Transaction Type</label>
                                <select class="form-select" id="transaction-type-filter">
                                    <option value="">All Types</option>
                                    <option value="deposit">Deposits</option>
                                    <option value="withdrawal">Withdrawals</option>
                                    <option value="adjustment">Adjustments</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="customer-search" class="form-label">Customer</label>
                                <input type="text" class="form-control" id="customer-search" placeholder="Search customer...">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button class="btn btn-outline-primary me-2" onclick="applyFilters()">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <button class="btn btn-outline-secondary me-2" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Clear Filters
                                </button>
                                <button class="btn btn-outline-info" onclick="refreshTransactions()">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions List -->
                    <div id="transactions-container">
                        <!-- Transactions will be loaded here -->
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading transactions...</p>
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

<!-- Transaction Detail Modal -->
<div class="modal fade" id="transactionDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-receipt"></i> Transaction Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Transaction Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td id="detail-id">-</td>
                            </tr>
                            <tr>
                                <td><strong>Date:</strong></td>
                                <td id="detail-date">-</td>
                            </tr>
                            <tr>
                                <td><strong>Type:</strong></td>
                                <td id="detail-type">-</td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td id="detail-amount">-</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td id="detail-status">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td id="detail-customer-name">-</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td id="detail-customer-email">-</td>
                            </tr>
                            <tr>
                                <td><strong>Current Balance:</strong></td>
                                <td id="detail-customer-balance">-</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6>Description</h6>
                        <p id="detail-description" class="text-muted">-</p>
                    </div>
                </div>
                <div class="row" id="detail-metadata" style="display: none;">
                    <div class="col-12">
                        <h6>Additional Information</h6>
                        <pre id="detail-metadata-content" class="bg-light p-2 rounded"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="refundTransaction()">
                    <i class="fas fa-undo"></i> Process Refund
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
    loadTransactionStats();
    loadTransactions();

    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));

    document.getElementById('date-to').value = today.toISOString().split('T')[0];
    document.getElementById('date-from').value = thirtyDaysAgo.toISOString().split('T')[0];

    // Apply default filters
    applyFilters();
});

async function loadTransactionStats() {
    try {
        const response = await fetch('{{ route("admin.funds.transactions") }}?stats_only=true');
        const data = await response.json();

        document.getElementById('total-transactions').textContent = data.stats.total_transactions || 0;
        document.getElementById('total-deposits').textContent = `{{ config('pos_payments.currency_symbol', '£') }}${(data.stats.total_deposits || 0).toFixed(2)}`;
        document.getElementById('total-withdrawals').textContent = `{{ config('pos_payments.currency_symbol', '£') }}${(data.stats.total_withdrawals || 0).toFixed(2)}`;
        document.getElementById('net-flow').textContent = `{{ config('pos_payments.currency_symbol', '£') }}${(data.stats.net_flow || 0).toFixed(2)}`;

    } catch (error) {
        console.error('Failed to load transaction stats:', error);
    }
}

async function loadTransactions() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            per_page: 20,
            ...currentFilters
        });

        const response = await fetch(`{{ route("admin.funds.transactions") }}?${params}`);
        const data = await response.json();

        renderTransactions(data.transactions || []);
        renderPagination(data.pagination || {});

    } catch (error) {
        console.error('Failed to load transactions:', error);
        document.getElementById('transactions-container').innerHTML =
            '<div class="text-center text-danger">Failed to load transactions.</div>';
    }
}

function renderTransactions(transactions) {
    const container = document.getElementById('transactions-container');

    if (transactions.length === 0) {
        container.innerHTML = '<div class="text-center">No transactions found.</div>';
        return;
    }

    const transactionCards = transactions.map(transaction => `
        <div class="transaction-card" onclick="viewTransactionDetail(${transaction.id})" style="cursor: pointer;">
            <div class="transaction-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${transaction.customer_name}</strong>
                        <small class="text-muted ms-2">${transaction.customer_email}</small>
                    </div>
                    <div class="text-end">
                        <div class="transaction-amount ${transaction.type === 'deposit' ? 'transaction-positive' : 'transaction-negative'}">
                            ${transaction.type === 'deposit' ? '+' : '-'}{{ config('pos_payments.currency_symbol', '£') }}${parseFloat(transaction.amount).toFixed(2)}
                        </div>
                        <small class="text-muted">${new Date(transaction.date).toLocaleDateString()}</small>
                    </div>
                </div>
            </div>
            <div class="transaction-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="transaction-type type-${transaction.type}">
                            ${transaction.type}
                        </span>
                        <span class="badge bg-secondary ms-2">${transaction.status}</span>
                    </div>
                    <div>
                        <small class="text-muted">
                            ${transaction.description && transaction.description.includes('Order #') 
                                ? transaction.description.replace(/Order #(\d+)/, '<a href="/admin/orders/$1" target="_blank" class="text-decoration-none">Order #$1</a>')
                                : (transaction.description || 'No description')
                            }
                        </small>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    container.innerHTML = transactionCards;
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
    loadTransactions();
}

function applyFilters() {
    currentFilters.date_from = document.getElementById('date-from').value;
    currentFilters.date_to = document.getElementById('date-to').value;
    currentFilters.type = document.getElementById('transaction-type-filter').value;
    currentFilters.customer = document.getElementById('customer-search').value;
    currentPage = 1;
    loadTransactions();
}

function clearFilters() {
    document.getElementById('date-from').value = '';
    document.getElementById('date-to').value = '';
    document.getElementById('transaction-type-filter').value = '';
    document.getElementById('customer-search').value = '';
    currentFilters = {};
    currentPage = 1;
    loadTransactions();
}

function refreshTransactions() {
    loadTransactionStats();
    loadTransactions();
}

async function viewTransactionDetail(transactionId) {
    try {
        const response = await fetch(`{{ route("admin.funds.transactions") }}/${transactionId}`);
        const data = await response.json();

        // Populate modal
        document.getElementById('detail-id').textContent = data.transaction.id;
        document.getElementById('detail-date').textContent = new Date(data.transaction.date).toLocaleString();
        document.getElementById('detail-type').textContent = data.transaction.type;
        document.getElementById('detail-amount').textContent =
            `{{ config('pos_payments.currency_symbol', '£') }}${parseFloat(data.transaction.amount).toFixed(2)}`;
        document.getElementById('detail-status').textContent = data.transaction.status;
        document.getElementById('detail-description').textContent = data.transaction.description || 'No description';

        // Customer info
        document.getElementById('detail-customer-name').textContent = data.transaction.customer_name;
        document.getElementById('detail-customer-email').textContent = data.transaction.customer_email;
        document.getElementById('detail-customer-balance').textContent =
            `{{ config('pos_payments.currency_symbol', '£') }}${parseFloat(data.customer_balance).toFixed(2)}`;

        // Metadata if available
        if (data.transaction.metadata) {
            document.getElementById('detail-metadata').style.display = 'block';
            document.getElementById('detail-metadata-content').textContent =
                JSON.stringify(data.transaction.metadata, null, 2);
        } else {
            document.getElementById('detail-metadata').style.display = 'none';
        }

        // Show modal
        new bootstrap.Modal(document.getElementById('transactionDetailModal')).show();

    } catch (error) {
        console.error('Failed to load transaction detail:', error);
        alert('Failed to load transaction details. Please try again.');
    }
}

function refundTransaction() {
    // TODO: Implement refund functionality
    alert('Refund functionality - Coming soon!');
}

function exportTransactions() {
    // TODO: Implement export functionality
    const params = new URLSearchParams(currentFilters);
    window.open(`{{ route("admin.funds.transactions") }}/export?${params}`, '_blank');
}
</script>
@endsection
@extends('layouts.admin')

@section('title', 'WooCommerce Funds Management')

@section('styles')
<style>
.funds-card {
    transition: transform 0.2s;
}
.funds-card:hover {
    transform: translateY(-2px);
}
.funds-stat {
    font-size: 2rem;
    font-weight: bold;
}
.funds-stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.customer-balance {
    font-size: 1.25rem;
    font-weight: 600;
}
.transaction-amount {
    font-weight: 500;
}
.transaction-positive {
    color: #28a745;
}
.transaction-negative {
    color: #dc3545;
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
                        <i class="fas fa-wallet"></i> WooCommerce Funds Management
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.funds.settings') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Funds Overview Stats -->
                        <div class="col-lg-3 col-md-6">
                            <div class="card funds-card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="funds-stat" id="total-customers">0</div>
                                            <div class="funds-stat-label">Active Customers</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="card funds-card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="funds-stat" id="total-balance">£0.00</div>
                                            <div class="funds-stat-label">Total Balance</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-pound-sign fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="card funds-card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="funds-stat" id="deposits-this-month">£0.00</div>
                                            <div class="funds-stat-label">Deposits (Month)</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-up fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="card funds-card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="funds-stat" id="withdrawals-this-month">£0.00</div>
                                            <div class="funds-stat-label">Used (Month)</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-down fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Funds Table -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-users"></i> Customer Funds
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="customer-funds-table">
                                            <thead>
                                                <tr>
                                                    <th>Customer</th>
                                                    <th>Email</th>
                                                    <th>Balance</th>
                                                    <th>Last Transaction</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="customer-funds-body">
                                                <tr>
                                                    <td colspan="5" class="text-center">
                                                        <div class="spinner-border spinner-border-sm" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        Loading customer funds...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history"></i> Recent Transactions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="transactions-table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Customer</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody id="transactions-body">
                                                <tr>
                                                    <td colspan="5" class="text-center">
                                                        <div class="spinner-border spinner-border-sm" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        Loading transactions...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadFundsOverview();
    loadCustomerFunds();
    loadRecentTransactions();
});

async function loadFundsOverview() {
    try {
        // This would typically call an API endpoint to get overview stats
        // For now, we'll show placeholder data
        document.getElementById('total-customers').textContent = 'Loading...';
        document.getElementById('total-balance').textContent = 'Loading...';
        document.getElementById('deposits-this-month').textContent = 'Loading...';
        document.getElementById('withdrawals-this-month').textContent = 'Loading...';

        // TODO: Implement API call to get overview stats
        setTimeout(() => {
            document.getElementById('total-customers').textContent = '24';
            document.getElementById('total-balance').textContent = '£1,247.50';
            document.getElementById('deposits-this-month').textContent = '£385.00';
            document.getElementById('withdrawals-this-month').textContent = '£142.25';
        }, 1000);

    } catch (error) {
        console.error('Failed to load funds overview:', error);
    }
}

async function loadCustomerFunds() {
    try {
        const response = await fetch('{{ route("admin.funds.customers") }}');
        const data = await response.json();

        const tbody = document.getElementById('customer-funds-body');

        if (data.customers && data.customers.length > 0) {
            tbody.innerHTML = data.customers.map(customer => `
                <tr>
                    <td><a href="{{ route('admin.customers.index') }}?q=${encodeURIComponent(customer.email)}" class="text-decoration-none">${customer.name}</a></td>
                    <td><a href="{{ route('admin.email.compose') }}?to=${encodeURIComponent(customer.email)}" class="text-decoration-none">${customer.email}</a></td>
                    <td><span class="customer-balance">£${parseFloat(customer.balance).toFixed(2)}</span></td>
                    <td>
                        ${customer.last_transaction 
                            ? `<a href="{{ route('admin.funds.transactions') }}?customer_id=${customer.id}" class="text-decoration-none">${new Date(customer.last_transaction).toLocaleDateString()}</a>`
                            : 'Never'
                        }
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewCustomerFunds(${customer.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No customers with funds found.</td></tr>';
        }

    } catch (error) {
        console.error('Failed to load customer funds:', error);
        document.getElementById('customer-funds-body').innerHTML =
            '<tr><td colspan="5" class="text-center text-danger">Failed to load customer funds.</td></tr>';
    }
}

async function loadRecentTransactions() {
    try {
        const response = await fetch('{{ route("admin.funds.transactions") }}?per_page=10');
        const data = await response.json();

        const tbody = document.getElementById('transactions-body');

        if (data.transactions && data.transactions.length > 0) {
            tbody.innerHTML = data.transactions.map(transaction => `
                <tr>
                    <td>${new Date(transaction.date).toLocaleDateString()}</td>
                    <td>${transaction.customer_name}</td>
                    <td>
                        <span class="badge ${transaction.type === 'deposit' ? 'bg-success' : 'bg-warning'}">
                            ${transaction.type}
                        </span>
                    </td>
                    <td>
                        <span class="transaction-amount ${transaction.type === 'deposit' ? 'transaction-positive' : 'transaction-negative'}">
                            ${transaction.type === 'deposit' ? '+' : '-'}${config('pos_payments.currency_symbol', '£')}${parseFloat(transaction.amount).toFixed(2)}
                        </span>
                    </td>
                    <td>
                        ${transaction.description && transaction.description.includes('Order #') 
                            ? transaction.description.replace(/Order #(\d+)/, '<a href="/admin/orders/$1" target="_blank" class="text-decoration-none">Order #$1</a>')
                            : (transaction.description || 'N/A')
                        }
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No recent transactions found.</td></tr>';
        }

    } catch (error) {
        console.error('Failed to load transactions:', error);
        document.getElementById('transactions-body').innerHTML =
            '<tr><td colspan="5" class="text-center text-danger">Failed to load transactions.</td></tr>';
    }
}

function viewCustomerFunds(customerId) {
    window.location.href = '{{ route("admin.funds.customers.show", ":customerId") }}'.replace(':customerId', customerId);
}
</script>
@endsection
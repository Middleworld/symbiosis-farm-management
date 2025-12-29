@extends('layouts.app')

@section('title', 'Tide Bank Dashboard')
@section('page-title', 'Business Banking Integration')

@section('styles')
<style>
    .account-card {
        transition: transform 0.2s ease-in-out;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    .account-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .balance-positive {
        color: #28a745;
        font-weight: bold;
    }
    .balance-negative {
        color: #dc3545;
        font-weight: bold;
    }
    .transaction-in {
        color: #28a745;
    }
    .transaction-out {
        color: #dc3545;
    }
    .financial-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
</style>
@endsection

@section('content')

{{-- Financial Summary --}}
@if($financialSummary)
<div class="financial-summary">
    <div class="row">
        <div class="col-md-3">
            <h3>£{{ number_format($financialSummary['total_balance'], 2) }}</h3>
            <p class="mb-0">Total Balance</p>
        </div>
        <div class="col-md-3">
            <h3>£{{ number_format($financialSummary['total_income'], 2) }}</h3>
            <p class="mb-0">Income ({{ $financialSummary['period_days'] }} days)</p>
        </div>
        <div class="col-md-3">
            <h3>£{{ number_format($financialSummary['total_expenses'], 2) }}</h3>
            <p class="mb-0">Expenses ({{ $financialSummary['period_days'] }} days)</p>
        </div>
        <div class="col-md-3">
            <h3 class="{{ $financialSummary['net_flow'] >= 0 ? 'text-success' : 'text-warning' }}">
                £{{ number_format($financialSummary['net_flow'], 2) }}
            </h3>
            <p class="mb-0">Net Flow</p>
        </div>
    </div>
</div>
@endif

{{-- Business Profile --}}
@if($businessProfile)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-building"></i> Business Profile</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Business Name:</strong> {{ $businessProfile['name'] ?? 'Not available' }}</p>
                        <p><strong>Type:</strong> {{ $businessProfile['type'] ?? 'Business Account' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Registration:</strong> {{ $businessProfile['registration_number'] ?? 'Not available' }}</p>
                        <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Monthly Income & Expenditure Breakdown --}}
@if($monthlyBreakdown)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Monthly Income & Expenditure (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-table"></i> Monthly Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Income</th>
                                <th class="text-end">Expenses</th>
                                <th class="text-end">Net</th>
                                <th class="text-end">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monthlyBreakdown as $month)
                            <tr>
                                <td><strong>{{ $month['month'] }}</strong></td>
                                <td class="text-end text-success">£{{ number_format($month['income'], 2) }}</td>
                                <td class="text-end text-danger">£{{ number_format($month['expenses'], 2) }}</td>
                                <td class="text-end {{ $month['net'] >= 0 ? 'text-success' : 'text-warning' }}">
                                    <strong>£{{ number_format($month['net'], 2) }}</strong>
                                </td>
                                <td class="text-end">{{ $month['transaction_count'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th>Total</th>
                                <th class="text-end text-success">
                                    £{{ number_format(collect($monthlyBreakdown)->sum('income'), 2) }}
                                </th>
                                <th class="text-end text-danger">
                                    £{{ number_format(collect($monthlyBreakdown)->sum('expenses'), 2) }}
                                </th>
                                <th class="text-end {{ (collect($monthlyBreakdown)->sum('income') - collect($monthlyBreakdown)->sum('expenses')) >= 0 ? 'text-success' : 'text-warning' }}">
                                    <strong>£{{ number_format(collect($monthlyBreakdown)->sum('income') - collect($monthlyBreakdown)->sum('expenses'), 2) }}</strong>
                                </th>
                                <th class="text-end">{{ collect($monthlyBreakdown)->sum('transaction_count') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        @if($expenseBreakdown)
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-pie-chart"></i> Expense Categories</h5>
            </div>
            <div class="card-body">
                <canvas id="expenseChart" height="200"></canvas>
                <div class="mt-3">
                    @foreach(array_slice($expenseBreakdown, 0, 5) as $category)
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ $category['category'] }}</span>
                        <strong>£{{ number_format($category['total'], 2) }}</strong>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Account Overview --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="fas fa-piggy-bank"></i> Bank Accounts</h4>
            <button class="btn btn-outline-primary btn-sm" onclick="refreshAccounts()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>

        @if($accountSummary && count($accountSummary) > 0)
            <div class="row">
                @foreach($accountSummary as $account)
                <div class="col-md-6 mb-4">
                    <div class="card account-card h-100">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ $account['name'] }}</h6>
                                <span class="badge bg-info">{{ strtoupper($account['currency']) }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <p class="text-muted small mb-1">Balance</p>
                                    <p class="balance-{{ $account['balance'] >= 0 ? 'positive' : 'negative' }} h5 mb-0">
                                        £{{ number_format($account['balance'], 2) }}
                                    </p>
                                </div>
                                <div class="col-6">
                                    <p class="text-muted small mb-1">Available</p>
                                    <p class="text-success h5 mb-0">
                                        £{{ number_format($account['available_balance'], 2) }}
                                    </p>
                                </div>
                            </div>

                            @if(count($account['recent_transactions']) > 0)
                            <hr>
                            <h6>Recent Transactions</h6>
                            <div class="transaction-list" style="max-height: 200px; overflow-y: auto;">
                                @foreach(array_slice($account['recent_transactions'], 0, 5) as $transaction)
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <div>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($transaction['date'])->format('d M') }}</small>
                                        <div class="small">{{ $transaction['description'] ?? 'Transaction' }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small transaction-{{ $transaction['amount'] >= 0 ? 'in' : 'out' }}">
                                            {{ $transaction['amount'] >= 0 ? '+' : '' }}£{{ number_format(abs($transaction['amount']), 2) }}
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <button class="btn btn-outline-primary btn-sm w-100 mt-2" onclick="viewTransactions('{{ $account['id'] }}')">
                                <i class="fas fa-list"></i> View All Transactions
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>No accounts found.</strong> Please check your Tide API configuration in Settings.
            </div>
        @endif
    </div>
</div>

{{-- Transaction Modal --}}
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Account Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="transactionContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Monthly Income & Expenditure Chart
@if($monthlyBreakdown)
const monthlyChartCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyChartCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode(collect($monthlyBreakdown)->pluck('month')->toArray()) !!},
        datasets: [
            {
                label: 'Income',
                data: {!! json_encode(collect($monthlyBreakdown)->pluck('income')->toArray()) !!},
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            },
            {
                label: 'Expenses',
                data: {!! json_encode(collect($monthlyBreakdown)->pluck('expenses')->toArray()) !!},
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1
            },
            {
                label: 'Net',
                data: {!! json_encode(collect($monthlyBreakdown)->pluck('net')->toArray()) !!},
                type: 'line',
                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += '£' + context.parsed.y.toFixed(2);
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '£' + value.toFixed(0);
                    }
                }
            }
        }
    }
});
@endif

// Expense Categories Pie Chart
@if($expenseBreakdown)
const expenseChartCtx = document.getElementById('expenseChart').getContext('2d');
const expenseChart = new Chart(expenseChartCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(collect($expenseBreakdown)->pluck('category')->toArray()) !!},
        datasets: [{
            data: {!! json_encode(collect($expenseBreakdown)->pluck('total')->toArray()) !!},
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(199, 199, 199, 0.7)',
                'rgba(83, 102, 255, 0.7)',
                'rgba(255, 99, 255, 0.7)',
                'rgba(99, 255, 132, 0.7)',
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    font: {
                        size: 10
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': £' + value.toFixed(2) + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
@endif

function refreshAccounts() {
    fetch('/admin/tide/refresh', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to refresh accounts: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error refreshing accounts:', error);
        alert('Error refreshing accounts');
    });
}

function viewTransactions(accountId) {
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    const content = document.getElementById('transactionContent');

    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    modal.show();

    fetch(`/admin/tide/transactions?account_id=${accountId}&limit=50&days=90`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.transactions) {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.transactions.forEach(transaction => {
                    const date = new Date(transaction.date).toLocaleDateString();
                    const amountClass = transaction.amount >= 0 ? 'text-success' : 'text-danger';
                    const amountPrefix = transaction.amount >= 0 ? '+' : '';

                    html += `
                        <tr>
                            <td>${date}</td>
                            <td>${transaction.description || 'Transaction'}</td>
                            <td class="text-end ${amountClass}">${amountPrefix}£${Math.abs(transaction.amount).toFixed(2)}</td>
                            <td class="text-end">£${(transaction.balance || 0).toFixed(2)}</td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="alert alert-warning">No transactions found or error loading data.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
            content.innerHTML = '<div class="alert alert-danger">Error loading transactions.</div>';
        });
}
</script>
@endsection
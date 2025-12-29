@extends('layouts.admin')

@section('title', 'Bank Transactions')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Bank Transactions</h1>
                <div class="btn-group">
                    <a href="{{ route('admin.bank-transactions.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-graph-up me-2"></i>Dashboard
                    </a>
                    <a href="{{ route('admin.bank-transactions.import-form') }}" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Import CSV
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Summary Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Total Transactions</h6>
                            <h4 class="mb-0">{{ number_format($stats['transaction_count']) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success bg-opacity-10">
                        <div class="card-body text-center">
                            <h6 class="text-success mb-1">Total Income</h6>
                            <h4 class="mb-0 text-success">£{{ number_format($stats['total_income'], 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger bg-opacity-10">
                        <div class="card-body text-center">
                            <h6 class="text-danger mb-1">Total Expenses</h6>
                            <h4 class="mb-0 text-danger">£{{ number_format($stats['total_expenses'], 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card {{ $stats['net_profit'] >= 0 ? 'bg-primary' : 'bg-warning' }} bg-opacity-10">
                        <div class="card-body text-center">
                            <h6 class="{{ $stats['net_profit'] >= 0 ? 'text-primary' : 'text-warning' }} mb-1">Net Profit</h6>
                            <h4 class="mb-0 {{ $stats['net_profit'] >= 0 ? 'text-primary' : 'text-warning' }}">
                                £{{ number_format($stats['net_profit'], 2) }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.bank-transactions.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All</option>
                                <option value="credit" {{ request('type') == 'credit' ? 'selected' : '' }}>Income</option>
                                <option value="debit" {{ request('type') == 'debit' ? 'selected' : '' }}>Expenses</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" value="{{ request('category') }}" placeholder="e.g., vegbox_income">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card shadow">
                <div class="card-body">
                    @if($transactions->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <h4 class="mt-3">No Transactions Found</h4>
                            <p class="text-muted">
                                Import your bank statement CSV to get started.
                            </p>
                            <a href="{{ route('admin.bank-transactions.import-form') }}" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>Import CSV
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th class="text-end">Amount</th>
                                        <th>Type</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                        <tr>
                                            <td>
                                                <small class="text-muted">{{ $transaction->transaction_date->format('d M Y') }}</small>
                                            </td>
                                            <td>
                                                {{ Str::limit($transaction->description, 50) }}
                                                @if($transaction->reference)
                                                    <br><small class="text-muted">Ref: {{ $transaction->reference }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($transaction->category)
                                                    <span class="badge bg-secondary">{{ $transaction->category }}</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Uncategorized</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <strong class="{{ $transaction->type == 'credit' ? 'text-success' : 'text-danger' }}">
                                                    {{ $transaction->type == 'credit' ? '+' : '-' }}£{{ number_format($transaction->amount, 2) }}
                                                </strong>
                                            </td>
                                            <td>
                                                @if($transaction->type == 'credit')
                                                    <i class="bi bi-arrow-up-circle text-success"></i> Income
                                                @else
                                                    <i class="bi bi-arrow-down-circle text-danger"></i> Expense
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $transaction->id }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

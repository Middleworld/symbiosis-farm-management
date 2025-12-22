@extends('layouts.app')

@section('title', 'Open Banking Account Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-university"></i> {{ $account->nickname ?? 'Account Details' }}
                </h1>
                <a href="{{ route('admin.openbanking.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Banks
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Account Summary Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-wallet"></i> Account Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Bank</p>
                            <h5>{{ $account->connection->bank_name }}</h5>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Account Type</p>
                            <h5>{{ $account->account_type }} - {{ $account->account_subtype }}</h5>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Account Number</p>
                            <h5>{{ $account->formatted_account_number }}</h5>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted mb-1">Current Balance</p>
                            <h5 class="text-success">
                                {{ $account->currency }} {{ number_format($account->balance, 2) }}
                            </h5>
                            <small class="text-muted">
                                Updated {{ $account->balance_updated_at?->diffForHumans() ?? 'Never' }}
                            </small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <form action="{{ route('admin.openbanking.sync-transactions', $account) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-sync"></i> Refresh Transactions
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Transactions</p>
                            <h4 class="mb-0">{{ $account->transactions->count() }}</h4>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Money In</p>
                            <h4 class="mb-0 text-success">
                                £{{ number_format($account->transactions->where('type', 'Credit')->sum('amount'), 2) }}
                            </h4>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-arrow-down fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Money Out</p>
                            <h4 class="mb-0 text-danger">
                                £{{ number_format($account->transactions->where('type', 'Debit')->sum('amount'), 2) }}
                            </h4>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-arrow-up fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Net Change</p>
                            @php
                                $net = $account->transactions->where('type', 'Credit')->sum('amount') - $account->transactions->where('type', 'Debit')->sum('amount');
                            @endphp
                            <h4 class="mb-0 {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                £{{ number_format($net, 2) }}
                            </h4>
                        </div>
                        <div class="{{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="fas fa-exchange-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Recent Transactions
                    </h5>
                </div>
                <div class="card-body">
                    @if($account->transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Merchant / Description</th>
                                        <th>Reference</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Balance After</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($account->transactions as $transaction)
                                        <tr>
                                            <td>
                                                <strong>{{ $transaction->booking_datetime->format('d M Y') }}</strong><br>
                                                <small class="text-muted">{{ $transaction->booking_datetime->format('H:i') }}</small>
                                            </td>
                                            <td>
                                                @if($transaction->isCredit())
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-arrow-down"></i> Credit
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-arrow-up"></i> Debit
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($transaction->merchant_name)
                                                    <strong>{{ $transaction->merchant_name }}</strong><br>
                                                @endif
                                                <small class="text-muted">{{ $transaction->description }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $transaction->reference ?? '-' }}</small>
                                            </td>
                                            <td class="text-end">
                                                <strong class="{{ $transaction->isCredit() ? 'text-success' : 'text-danger' }}">
                                                    {{ $transaction->isCredit() ? '+' : '-' }}£{{ number_format($transaction->amount, 2) }}
                                                </strong>
                                            </td>
                                            <td class="text-end">
                                                @if($transaction->balance_after)
                                                    £{{ number_format($transaction->balance_after, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $transaction->status === 'Booked' ? 'success' : 'warning' }}">
                                                    {{ $transaction->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No transactions found for this account.</p>
                            <form action="{{ route('admin.openbanking.sync-transactions', $account) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync"></i> Sync Transactions
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

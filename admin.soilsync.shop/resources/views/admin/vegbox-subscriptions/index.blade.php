@extends('layouts.app')

@section('title', 'Vegbox Subscriptions')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Vegbox Subscriptions</h1>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">Active Subscriptions</h5>
                    <h2 class="mb-0">{{ $stats['total_active'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">Upcoming Renewals (7 days)</h5>
                    <h2 class="mb-0">{{ $stats['upcoming_renewals'] }}</h2>
                    <a href="{{ route('admin.vegbox-subscriptions.upcoming-renewals') }}" class="btn btn-sm btn-outline-warning mt-2">View</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">Failed Payments (24h)</h5>
                    <h2 class="mb-0">{{ $stats['failed_last_24h'] }}</h2>
                    <a href="{{ route('admin.vegbox-subscriptions.failed-payments') }}" class="btn btn-sm btn-outline-danger mt-2">View</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body">
                    <h5 class="card-title text-secondary">Cancelled</h5>
                    <h2 class="mb-0">{{ $stats['total_cancelled'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.vegbox-subscriptions.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Customer</label>
                            <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Email or name...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('admin.vegbox-subscriptions.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Subscriptions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Plan</th>
                                    <th>Price</th>
                                    <th>Next Billing</th>
                                    <th>Retry Status</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptions as $subscription)
                                <tr class="{{ $subscription->isInGracePeriod() ? 'table-warning' : '' }} {{ in_array($subscription->id, [114, 124]) ? 'table-primary' : '' }}">
                                    <td>
                                        <a href="{{ route('admin.vegbox-subscriptions.show', $subscription->id) }}">#{{ $subscription->id }}</a>
                                        @if(in_array($subscription->id, [114, 124]))
                                            <span class="badge bg-danger ms-1">TEST</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $subscription->customer_name }}</div>
                                        <small class="text-muted">{{ $subscription->customer_email }}</small>
                                    </td>
                                    <td>{{ $subscription->name ?? $subscription->plan->name ?? 'N/A' }}</td>
                                    <td>£{{ number_format($subscription->price, 2) }}</td>
                                    <td>
                                        @if($subscription->next_billing_at)
                                            <span class="{{ $subscription->next_billing_at < now() ? 'text-danger' : '' }}">
                                                {{ $subscription->next_billing_at->format('d/m/Y') }}
                                            </span>
                                            @if($subscription->next_billing_at < now())
                                                <br><small class="text-danger">Overdue!</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($subscription->failed_payment_count > 0)
                                            <span class="badge bg-warning">
                                                {{ $subscription->failed_payment_count }} Failed
                                            </span>
                                            @if($subscription->next_retry_at)
                                                <br><small class="text-muted">
                                                    Retry: {{ $subscription->next_retry_at->format('d/m/Y') }}
                                                </small>
                                            @endif
                                            @if($subscription->isInGracePeriod())
                                                <br><small class="text-danger">
                                                    Grace ends: {{ $subscription->grace_period_ends_at->format('d/m/Y') }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="badge bg-success">OK</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($subscription->canceled_at)
                                            <span class="badge bg-danger">Cancelled</span>
                                        @elseif($subscription->ends_at && $subscription->ends_at < now())
                                            <span class="badge bg-secondary">Expired</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.vegbox-subscriptions.show', $subscription->id) }}" class="btn btn-outline-primary">View</a>
                                            @if(!$subscription->canceled_at)
                                                <form action="{{ route('admin.vegbox-subscriptions.manual-renewal', $subscription->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" onclick="return confirm('Process renewal now?')">Renew</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0">No subscriptions found.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($subscriptions->hasPages())
                <div class="card-footer">
                    {{ $subscriptions->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div class="toast show" role="alert">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            {{ session('success') }}
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div class="toast show" role="alert">
        <div class="toast-header bg-danger text-white">
            <strong class="me-auto">Error</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            {{ session('error') }}
        </div>
    </div>
</div>
@endif
@endsection

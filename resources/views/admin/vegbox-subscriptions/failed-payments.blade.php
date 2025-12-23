@extends('layouts.app')

@section('title', 'Failed Payments')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.vegbox-subscriptions.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <h1 class="h3 mb-0">Failed Payments</h1>
                </div>
                <div>
                    <div class="btn-group" role="group">
                        <a href="?hours=24" class="btn btn-sm btn-outline-danger {{ request('hours', 48) == 24 ? 'active' : '' }}">Last 24 Hours</a>
                        <a href="?hours=48" class="btn btn-sm btn-outline-danger {{ request('hours', 48) == 48 ? 'active' : '' }}">Last 48 Hours</a>
                        <a href="?hours=168" class="btn btn-sm btn-outline-danger {{ request('hours', 48) == 168 ? 'active' : '' }}">Last 7 Days</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Attention Required:</strong> These subscriptions have failed payment attempts.
                Please review and retry as needed.
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Subscriptions with Failed Payments</h5>
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
                                    <th>Failed Count</th>
                                    <th>Last Error</th>
                                    <th>Next Billing</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptions as $subscription)
                                    <tr class="table-danger">
                                        <td><a href="{{ route('admin.vegbox-subscriptions.show', $subscription->id) }}">#{{ $subscription->id }}</a></td>
                                        <td>
                                            <div>{{ $subscription->subscriber->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $subscription->subscriber->email ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ $subscription->plan->name ?? 'N/A' }}</td>
                                        <td>£{{ number_format($subscription->price, 2) }}</td>
                                        <td>
                                            <span class="badge bg-danger">{{ $subscription->failed_payment_count }} failed</span>
                                        </td>
                                        <td>
                                            <small class="text-muted" title="{{ $subscription->last_payment_error }}">
                                                {{ Str::limit($subscription->last_payment_error ?? 'No error message', 50) }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($subscription->next_billing_at)
                                                {{ $subscription->next_billing_at->format('d/m/Y') }}
                                                @if($subscription->next_billing_at < now())
                                                    <span class="badge bg-warning">{{ $subscription->next_billing_at->diffInDays(now()) }} days overdue</span>
                                                @endif
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.vegbox-subscriptions.show', $subscription->id) }}" class="btn btn-outline-primary">View</a>
                                                @if($subscription->woocommerce_subscription_id)
                                                    <span class="badge bg-info" title="WooCommerce manages renewals automatically">Auto-Renewal</span>
                                                @else
                                                    <form action="{{ route('admin.vegbox-subscriptions.manual-renewal', $subscription->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Retry payment for this subscription?')">
                                                            <i class="bi bi-arrow-repeat"></i> Retry
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-success">
                                                <i class="bi bi-check-circle display-4"></i>
                                                <p class="mt-3 mb-0">No failed payments found! All subscriptions are up to date.</p>
                                            </div>
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

    <!-- Bulk Actions -->
    @if($subscriptions->count() > 0)
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Bulk Actions</h5>
                    <p class="text-muted">Process all failed payment renewals at once (use with caution)</p>
                    <button type="button" class="btn btn-warning" onclick="alert('Bulk processing will be implemented in the command line. Use: php artisan vegbox:process-renewals')">
                        <i class="bi bi-lightning-fill"></i> Bulk Process All Failed Payments
                    </button>
                    <small class="d-block mt-2 text-muted">
                        Tip: Use the command <code>php artisan vegbox:process-renewals</code> to process all failed payment renewals at once.
                    </small>
                </div>
            </div>
        </div>
    </div>
    @endif
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

@extends('layouts.app')

@section('title', 'Upcoming Renewals')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.vegbox-subscriptions.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <h1 class="h3 mb-0">Upcoming Renewals</h1>
                </div>
                <div>
                    <div class="btn-group" role="group">
                        <a href="?days=3" class="btn btn-sm btn-outline-primary {{ request('days', 7) == 3 ? 'active' : '' }}">Next 3 Days</a>
                        <a href="?days=7" class="btn btn-sm btn-outline-primary {{ request('days', 7) == 7 ? 'active' : '' }}">Next 7 Days</a>
                        <a href="?days=14" class="btn btn-sm btn-outline-primary {{ request('days', 7) == 14 ? 'active' : '' }}">Next 14 Days</a>
                        <a href="?days=30" class="btn btn-sm btn-outline-primary {{ request('days', 7) == 30 ? 'active' : '' }}">Next 30 Days</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Subscriptions Due for Renewal (Next {{ $days }} Days)</h5>
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
                                    <th>Days Until</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($renewals as $subscription)
                                <tr class="{{ $subscription->next_billing_at < now() ? 'table-danger' : ($subscription->next_billing_at <= now()->addDay() ? 'table-warning' : '') }}">
                                    <td><a href="{{ route('admin.vegbox-subscriptions.show', $subscription->id) }}">#{{ $subscription->id }}</a></td>
                                    <td>
                                        <div>{{ $subscription->subscriber->name ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $subscription->subscriber->email }}</small>
                                    </td>
                                    <td>{{ $subscription->plan->name ?? 'N/A' }}</td>
                                    <td>£{{ number_format($subscription->price, 2) }}</td>
                                    <td>
                                        {{ $subscription->next_billing_at->format('d/m/Y H:i') }}
                                        @if($subscription->next_billing_at < now())
                                            <br><span class="badge bg-danger">OVERDUE</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $daysDifference = now()->diffInDays($subscription->next_billing_at, false);
                                            $roundedDays = (int) floor(abs($daysDifference));
                                        @endphp
                                        @if($daysDifference < 0)
                                            <span class="text-danger fw-bold">{{ $roundedDays }} days ago</span>
                                        @else
                                            <span class="{{ $subscription->next_billing_at <= now()->addDay() ? 'fw-bold' : '' }}">
                                                {{ $roundedDays }} days
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.vegbox-subscriptions.show', $subscription->id) }}" class="btn btn-outline-primary">View</a>
                                            <form action="{{ route('admin.vegbox-subscriptions.manual-renewal', $subscription->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" onclick="return confirm('Process renewal now?')">
                                                    Renew Now
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-muted mb-0">No renewals due in the next {{ $days }} days.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($renewals->hasPages())
                <div class="card-footer">
                    {{ $renewals->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

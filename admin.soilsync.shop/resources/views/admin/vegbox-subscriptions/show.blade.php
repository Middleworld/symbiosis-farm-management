@extends('layouts.app')

@section('title', 'Subscription #' . $subscription->id)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.vegbox-subscriptions.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                        <i class="bi bi-arrow-left"></i> Back to Subscriptions
                    </a>
                    <h1 class="h3 mb-0">Subscription #{{ $subscription->id }}</h1>
                </div>
                <div>
                    @if($subscription->canceled_at)
                        <form action="{{ route('admin.vegbox-subscriptions.reactivate', $subscription->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('Reactivate this subscription?')">
                                <i class="bi bi-arrow-repeat"></i> Reactivate
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.vegbox-subscriptions.manual-renewal', $subscription->id) }}" method="POST" class="d-inline" id="renewal-form-{{ $subscription->id }}">
                            @csrf
                            <button type="submit" class="btn btn-primary" id="renewal-btn-{{ $subscription->id }}" onclick="return handleRenewalClick({{ $subscription->id }})">
                                <i class="bi bi-credit-card"></i> Process Renewal
                            </button>
                        </form>
                        <form action="{{ route('admin.vegbox-subscriptions.cancel', $subscription->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel this subscription?')">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Customer & Plan Information -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name:</dt>
                        <dd class="col-sm-8">{{ $subscription->customer_name }}</dd>

                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8">{{ $subscription->customer_email }}</dd>

                        <dt class="col-sm-4">User ID:</dt>
                        <dd class="col-sm-8">
                            @if($subscription->subscriber_id)
                                #{{ $subscription->subscriber_id }}
                            @elseif($subscription->wordpress_user_id)
                                WordPress User #{{ $subscription->wordpress_user_id }}
                            @else
                                N/A
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Plan Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Plan Name:</dt>
                        <dd class="col-sm-8">{{ $subscription->plan->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Price:</dt>
                        <dd class="col-sm-8"><strong>£{{ number_format($subscription->price, 2) }}</strong></dd>

                        <dt class="col-sm-4">Billing:</dt>
                        <dd class="col-sm-8">{{ $subscription->billing_frequency ?? 1 }} {{ $subscription->billing_period ?? 'month' }}</dd>

                        <dt class="col-sm-4">Delivery Day:</dt>
                        <dd class="col-sm-8">{{ ucfirst($subscription->delivery_day) }}</dd>

                        <dt class="col-sm-4">Delivery Time:</dt>
                        <dd class="col-sm-8">{{ ucfirst($subscription->delivery_time) }}</dd>

                        @if($subscription->plan)
                        <dt class="col-sm-4">Vegbox Size:</dt>
                        <dd class="col-sm-8">
                            @if($subscription->plan->box_size)
                                <span class="badge bg-info fs-6 px-2 py-1">{{ $subscription->plan->box_size_display }}</span>
                            @else
                                <span class="text-muted">Not specified</span>
                            @endif
                        </dd>

                        @if($subscription->plan->contents_description)
                        <dt class="col-sm-4">Contents:</dt>
                        <dd class="col-sm-8">{{ $subscription->plan->contents_description }}</dd>
                        @endif

                        @if($subscription->plan->delivery_frequency)
                        <dt class="col-sm-4">Frequency:</dt>
                        <dd class="col-sm-8">{{ $subscription->plan->delivery_frequency_display }}</dd>
                        @endif
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <!-- Subscription Status -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Subscription Status</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            @if($subscription->canceled_at)
                                <span class="badge bg-danger fs-6 px-2 py-1">Cancelled</span>
                            @elseif($subscription->ends_at && $subscription->ends_at < now())
                                <span class="badge bg-secondary fs-6 px-2 py-1">Expired</span>
                            @else
                                <span class="badge bg-success fs-6 px-2 py-1">Active</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Started:</dt>
                        <dd class="col-sm-7">{{ $subscription->starts_at ? $subscription->starts_at->format('d/m/Y H:i') : 'N/A' }}</dd>

                        <dt class="col-sm-5">Next Billing:</dt>
                        <dd class="col-sm-7">
                            @if($subscription->next_billing_at)
                                <span class="{{ $subscription->next_billing_at < now() ? 'text-danger fw-bold' : '' }}">
                                    {{ $subscription->next_billing_at->format('d/m/Y H:i') }}
                                </span>
                                @if($subscription->next_billing_at < now())
                                    <br><span class="badge bg-danger fs-6 px-2 py-1">Overdue!</span>
                                @endif
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </dd>

                        @if($subscription->canceled_at)
                        <dt class="col-sm-5">Cancelled:</dt>
                        <dd class="col-sm-7">{{ $subscription->canceled_at->format('d/m/Y H:i') }}</dd>
                        @endif

                        @if($subscription->ends_at)
                        <dt class="col-sm-5">Ends:</dt>
                        <dd class="col-sm-7">{{ $subscription->ends_at->format('d/m/Y H:i') }}</dd>
                        @endif

                        <dt class="col-sm-5">Total Deliveries:</dt>
                        <dd class="col-sm-7">{{ $subscription->total_deliveries ?? 0 }}</dd>
                    </dl>

                    @if($subscription->special_instructions)
                    <div class="alert alert-info">
                        <strong>Special Instructions:</strong><br>
                        {{ $subscription->special_instructions }}
                    </div>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment & Balance</h5>
                </div>
                <div class="card-body">
                    <!-- Payment Methods -->
                    @if($paymentMethodsInfo && $paymentMethodsInfo['success'] && count($paymentMethodsInfo['payment_methods']) > 0)
                        <h6 class="mb-3">Saved Payment Methods</h6>
                        @foreach($paymentMethodsInfo['payment_methods'] as $method)
                            <div class="d-flex align-items-center mb-2 {{ $method->is_default ? 'border border-primary rounded p-2 bg-light' : '' }}">
                                <div class="me-3">
                                    @if($method->brand === 'visa')
                                        <i class="fab fa-cc-visa fa-2x text-primary"></i>
                                    @elseif($method->brand === 'mastercard')
                                        <i class="fab fa-cc-mastercard fa-2x text-danger"></i>
                                    @elseif($method->brand === 'amex')
                                        <i class="fab fa-cc-amex fa-2x text-blue"></i>
                                    @else
                                        <i class="fas fa-credit-card fa-2x text-secondary"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <strong>{{ ucfirst($method->brand) }} ****{{ $method->last4 }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        Expires {{ str_pad($method->meta['expiry_month'] ?? $method->exp_month ?? '??', 2, '0', STR_PAD_LEFT) }}/{{ $method->meta['expiry_year'] ?? $method->exp_year ?? '??' }}
                                        @if($method->funding)
                                            • {{ ucfirst($method->funding) }}
                                        @endif
                                    </small>
                                </div>
                                @if($method->is_default)
                                    <span class="badge bg-primary fs-6 px-2 py-1">Default</span>
                                @endif
                            </div>
                        @endforeach
                    @elseif($paymentMethodsInfo === null)
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i>
                            Payment information not available for migrated WordPress subscriptions.
                        </div>
                    @else
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            @if($paymentMethodsInfo['success'])
                                No saved payment methods found.
                            @else
                                Unable to load payment methods: {{ $paymentMethodsInfo['error'] }}
                            @endif
                        </div>
                    @endif

                    <hr>

                    <!-- Customer Balance/Funds -->
                    <h6 class="mb-3">Store Credit Balance</h6>
                    @if($balanceInfo && $balanceInfo['success'])
                        <div class="text-center">
                            <h3 class="mb-0">£{{ number_format($balanceInfo['balance'], 2) }}</h3>
                            <p class="text-muted">Available Funds</p>
                            @if($balanceInfo['balance'] < $subscription->price)
                                <div class="alert alert-danger mt-2">
                                    <i class="bi bi-exclamation-triangle"></i> Insufficient funds for next renewal!
                                    <br>Shortfall: £{{ number_format($subscription->price - $balanceInfo['balance'], 2) }}
                                </div>
                            @else
                                <div class="alert alert-success mt-2">
                                    <i class="bi bi-check-circle"></i> Sufficient funds for renewal
                                </div>
                            @endif
                        </div>
                    @elseif($balanceInfo === null)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Balance information not available for migrated WordPress subscriptions.
                        </div>
                    @else
                        <div class="alert alert-warning">
                            Unable to check customer balance: {{ $balanceInfo['error'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Payment Activity</h5>
                </div>
                <div class="card-body">
                    @if(count($paymentHistory) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentHistory as $log)
                                    <tr>
                                        <td>{{ $log['timestamp'] ?? $log['time'] ?? 'N/A' }}</td>
                                        <td>{{ $log['message'] ?? 'N/A' }}</td>
                                        <td>
                                            @if(isset($log['status']))
                                                <span class="badge bg-{{ $log['status'] === 'success' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($log['status']) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($log['amount']))
                                                Amount: £{{ $log['amount'] }}<br>
                                            @endif
                                            @if(isset($log['error']))
                                                Error: {{ $log['error'] }}<br>
                                            @endif
                                            @if(isset($log['transaction_id']))
                                                TX: {{ $log['transaction_id'] }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No payment activity found in recent logs.</p>
                    @endif
                </div>
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

<script>
function handleRenewalClick(subscriptionId) {
    const btn = document.getElementById('renewal-btn-' + subscriptionId);
    const form = document.getElementById('renewal-form-' + subscriptionId);

    // Check if already processing
    if (btn.disabled) {
        return false;
    }

    // Show confirmation
    if (!confirm('Process renewal now? This will charge the customer\'s payment method.')) {
        return false;
    }

    // Disable button and change text
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

    // Submit the form
    form.submit();

    return false; // Prevent default form submission
}
</script>

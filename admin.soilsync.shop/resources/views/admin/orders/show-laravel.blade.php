@extends('layouts.app')

@section('title', 'Order #L-' . $order->id)

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt"></i> Order #L-{{ $order->id }} - {{ $order->order_number }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Order Date:</strong><br>
                            {{ $order->created_at->format('F j, Y g:i A') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge bg-{{ $order->order_status === 'completed' ? 'success' : ($order->order_status === 'pending' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($order->order_status ?? 'completed') }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Payment Status:</strong><br>
                            <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">
                                {{ ucfirst($order->payment_status ?? 'paid') }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Payment Method:</strong><br>
                            {{ ucfirst($order->payment_method ?? 'Stripe') }}
                        </div>
                    </div>

                    @if($order->stripe_payment_intent_id)
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Payment Intent ID:</strong><br>
                            <code>{{ $order->stripe_payment_intent_id }}</code>
                            <a href="https://dashboard.stripe.com/payments/{{ $order->stripe_payment_intent_id }}" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fas fa-external-link-alt"></i> View in Stripe
                            </a>
                        </div>
                    </div>
                    @endif

                    @if($order->metadata)
                    @php
                        $metadata = is_string($order->metadata) ? json_decode($order->metadata, true) : $order->metadata;
                    @endphp
                    @if($metadata && isset($metadata['subscription_id']))
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Subscription:</strong><br>
                            <a href="{{ route('admin.vegbox-subscriptions.show', $metadata['subscription_id']) }}" 
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> View Subscription #{{ $metadata['subscription_id'] }}
                            </a>
                            @if(isset($metadata['subscription_name']))
                                <span class="ms-2">{{ $metadata['subscription_name'] }}</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    @endif

                    @if($order->notes)
                    <div class="row">
                        <div class="col-12">
                            <strong>Notes:</strong><br>
                            <div class="alert alert-info">{{ $order->notes }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Order Items</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name ?? 'Subscription Renewal' }}</td>
                                <td class="text-center">{{ $item->quantity ?? 1 }}</td>
                                <td class="text-end">£{{ number_format($item->price ?? 0, 2) }}</td>
                                <td class="text-end">£{{ number_format(($item->price ?? 0) * ($item->quantity ?? 1), 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No items found</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">£{{ number_format($order->subtotal ?? 0, 2) }}</td>
                            </tr>
                            @if($order->tax_amount > 0)
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                <td class="text-end">£{{ number_format($order->tax_amount, 2) }}</td>
                            </tr>
                            @endif
                            @if($order->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="text-end"><strong>Discount:</strong></td>
                                <td class="text-end">-£{{ number_format($order->discount_amount, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="table-success">
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>£{{ number_format($order->total_amount ?? 0, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Customer Information</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Name:</strong><br>
                        {{ $order->customer_name ?? 'N/A' }}
                    </p>
                    @if($order->customer_email)
                    <p class="mb-2">
                        <strong>Email:</strong><br>
                        <a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a>
                    </p>
                    @endif
                    @if($order->customer_phone)
                    <p class="mb-2">
                        <strong>Phone:</strong><br>
                        <a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a>
                    </p>
                    @endif
                    @if($order->customer_address)
                    <p class="mb-0">
                        <strong>Address:</strong><br>
                        {!! nl2br(e($order->customer_address)) !!}
                    </p>
                    @endif
                </div>
            </div>

            <!-- Order Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    @if($order->stripe_payment_intent_id && $order->payment_status === 'paid')
                    <form method="POST" action="{{ route('admin.orders.refund-laravel', $order->id) }}" 
                          onsubmit="return confirm('Are you sure you want to refund £{{ number_format($order->total_amount, 2) }} to {{ $order->customer_name }}? This action cannot be undone.');"
                          class="mb-2">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Refund Reason (optional)</label>
                            <select name="reason" class="form-select form-select-sm">
                                <option value="requested_by_customer">Requested by customer</option>
                                <option value="duplicate">Duplicate payment</option>
                                <option value="fraudulent">Fraudulent</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-undo"></i> Refund £{{ number_format($order->total_amount, 2) }}
                        </button>
                    </form>
                    @elseif($order->payment_status === 'refunded')
                    <div class="alert alert-success mb-2">
                        <i class="fas fa-check-circle"></i> This order has been refunded.
                    </div>
                    @endif
                    
                    <a href="https://dashboard.stripe.com/payments/{{ $order->stripe_payment_intent_id }}" 
                       target="_blank" 
                       class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-external-link-alt"></i> View in Stripe
                    </a>
                    
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-list"></i> Back to Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

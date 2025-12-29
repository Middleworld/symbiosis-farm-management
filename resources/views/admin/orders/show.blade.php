@extends('layouts.app')

@section('title', 'Order #' . $order['id'])
@section('page-title', 'Order #' . $order['id'])

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        @if($order['status'] !== 'completed' && $order['status'] !== 'cancelled' && $order['status'] !== 'refunded')
        <div class="btn-group float-end">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-edit"></i> Change Status
            </button>
            <ul class="dropdown-menu">
                <li>
                    <form action="{{ route('admin.orders.update-status', $order['id']) }}" method="POST" class="px-3 py-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="processing">
                        <button type="submit" class="btn btn-sm btn-info w-100">Mark Processing</button>
                    </form>
                </li>
                <li>
                    <form action="{{ route('admin.orders.update-status', $order['id']) }}" method="POST" class="px-3 py-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="btn btn-sm btn-success w-100">Mark Completed</button>
                    </form>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('admin.orders.update-status', $order['id']) }}" method="POST" class="px-3 py-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="on-hold">
                        <button type="submit" class="btn btn-sm btn-warning w-100">Put On Hold</button>
                    </form>
                </li>
                <li>
                    <form action="{{ route('admin.orders.update-status', $order['id']) }}" method="POST" class="px-3 py-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="btn btn-sm btn-danger w-100" 
                                onclick="return confirm('Are you sure you want to cancel this order?')">
                            Cancel Order
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        @endif
    </div>
</div>

<div class="row">
    <!-- Order Details -->
    <div class="col-md-8">
        <!-- Order Info Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    Order Details
                    @php
                        $statusColors = [
                            'pending' => 'warning',
                            'processing' => 'info',
                            'on-hold' => 'secondary',
                            'completed' => 'success',
                            'cancelled' => 'dark',
                            'refunded' => 'danger',
                            'failed' => 'danger',
                        ];
                        $color = $statusColors[$order['status']] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $color }} float-end">
                        {{ ucfirst(str_replace('-', ' ', $order['status'])) }}
                    </span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Order Date:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ \Carbon\Carbon::parse($order['date_created'])->format('F d, Y \a\t h:i A') }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Payment Method:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ $order['payment_method_title'] ?? 'N/A' }}
                    </div>
                </div>
                @if($order['transaction_id'] ?? false)
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Transaction ID:</strong>
                    </div>
                    <div class="col-md-8">
                        @php
                            // Extract payment intent from charge ID (ch_xxx -> pi_xxx)
                            $txnId = $order['transaction_id'];
                            $paymentIntentLink = null;
                            
                            if (str_starts_with($txnId, 'ch_')) {
                                // Use stored intent if available, otherwise try to construct from charge
                                $paymentIntentLink = $stripeData['_stripe_intent_id'] ?? null;
                            }
                        @endphp
                        
                        @if($paymentIntentLink)
                            <a href="https://dashboard.stripe.com/payments/{{ $paymentIntentLink }}" 
                               target="_blank" 
                               class="text-decoration-none">
                                <code>{{ $txnId }}</code>
                                <i class="fas fa-external-link-alt ms-1 small"></i>
                            </a>
                        @else
                            <code>{{ $txnId }}</code>
                        @endif
                    </div>
                </div>
                @endif
                @if(!empty($stripeData['_stripe_intent_id']))
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Stripe Payment Intent:</strong>
                    </div>
                    <div class="col-md-8">
                        <a href="https://dashboard.stripe.com/payments/{{ $stripeData['_stripe_intent_id'] }}" 
                           target="_blank" 
                           class="text-decoration-none">
                            <code>{{ $stripeData['_stripe_intent_id'] }}</code>
                            <i class="fas fa-external-link-alt ms-1 small"></i>
                        </a>
                    </div>
                </div>
                @endif
                @if(!empty($stripeData['_stripe_charge_id']))
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Stripe Charge ID:</strong>
                    </div>
                    <div class="col-md-8">
                        <code>{{ $stripeData['_stripe_charge_id'] }}</code>
                    </div>
                </div>
                @endif
                <div class="row">
                    <div class="col-md-4">
                        <strong>Customer IP:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ $order['customer_ip_address'] ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Order Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order['line_items'] ?? [] as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item['name'] }}</strong>
                                    @if($item['sku'] ?? false)
                                    <br><small class="text-muted">SKU: {{ $item['sku'] }}</small>
                                    @endif
                                </td>
                                <td>{{ $item['quantity'] }}</td>
                                <td>£{{ number_format($item['price'], 2) }}</td>
                                <td><strong>£{{ number_format($item['total'], 2) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td><strong>£{{ number_format($order['total'] - $order['total_tax'] - $order['shipping_total'], 2) }}</strong></td>
                            </tr>
                            @if($order['shipping_total'] > 0)
                            <tr>
                                <td colspan="3" class="text-end">Shipping:</td>
                                <td>£{{ number_format($order['shipping_total'], 2) }}</td>
                            </tr>
                            @endif
                            @if($order['total_tax'] > 0)
                            <tr>
                                <td colspan="3" class="text-end">Tax:</td>
                                <td>£{{ number_format($order['total_tax'], 2) }}</td>
                            </tr>
                            @endif
                            @if($order['discount_total'] > 0)
                            <tr>
                                <td colspan="3" class="text-end text-success">Discount:</td>
                                <td class="text-success">-£{{ number_format($order['discount_total'], 2) }}</td>
                            </tr>
                            @endif
                            <tr class="table-active">
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong>£{{ number_format($order['total'], 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Notes -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Order Notes</h5>
            </div>
            <div class="card-body">
                @if(!empty($order['customer_note'] ?? ''))
                <div class="alert alert-info">
                    <strong>Customer Note:</strong><br>
                    {{ $order['customer_note'] }}
                </div>
                @endif
                
                <form action="{{ route('admin.orders.add-note', $order['id']) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="note" class="form-label">Add Note</label>
                        <textarea class="form-control" id="note" name="note" rows="3" required></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="customer_note" name="customer_note" value="1">
                        <label class="form-check-label" for="customer_note">
                            Send to customer
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Note
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Customer Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Customer Information</h6>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong>{{ $order['billing']['first_name'] ?? '' }} {{ $order['billing']['last_name'] ?? '' }}</strong>
                </p>
                <p class="mb-1">
                    <i class="fas fa-envelope"></i> {{ $order['billing']['email'] ?? 'N/A' }}
                </p>
                @if($order['billing']['phone'] ?? false)
                <p class="mb-0">
                    <i class="fas fa-phone"></i> {{ $order['billing']['phone'] }}
                </p>
                @endif
            </div>
        </div>

        <!-- Billing Address -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Billing Address</h6>
            </div>
            <div class="card-body">
                <address class="mb-0">
                    {{ $order['billing']['first_name'] ?? '' }} {{ $order['billing']['last_name'] ?? '' }}<br>
                    @if($order['billing']['company'] ?? false)
                        {{ $order['billing']['company'] }}<br>
                    @endif
                    {{ $order['billing']['address_1'] ?? '' }}<br>
                    @if($order['billing']['address_2'] ?? false)
                        {{ $order['billing']['address_2'] }}<br>
                    @endif
                    {{ $order['billing']['city'] ?? '' }}, {{ $order['billing']['state'] ?? '' }} {{ $order['billing']['postcode'] ?? '' }}<br>
                    {{ $order['billing']['country'] ?? '' }}
                </address>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Shipping Address</h6>
            </div>
            <div class="card-body">
                @if(!empty($order['shipping']['address_1']))
                <address class="mb-0">
                    {{ $order['shipping']['first_name'] ?? '' }} {{ $order['shipping']['last_name'] ?? '' }}<br>
                    @if($order['shipping']['company'] ?? false)
                        {{ $order['shipping']['company'] }}<br>
                    @endif
                    {{ $order['shipping']['address_1'] ?? '' }}<br>
                    @if($order['shipping']['address_2'] ?? false)
                        {{ $order['shipping']['address_2'] }}<br>
                    @endif
                    {{ $order['shipping']['city'] ?? '' }}, {{ $order['shipping']['state'] ?? '' }} {{ $order['shipping']['postcode'] ?? '' }}<br>
                    {{ $order['shipping']['country'] ?? '' }}
                </address>
                @else
                <p class="text-muted mb-0">Same as billing address</p>
                @endif
            </div>
        </div>

        <!-- Actions -->
        @if($order['status'] !== 'refunded')
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">Refund Order</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.orders.refund', $order['id']) }}" method="POST" 
                      onsubmit="return confirm('Are you sure you want to refund this order?')">
                    @csrf
                    <div class="mb-3">
                        <label for="amount" class="form-label">Refund Amount</label>
                        <input type="number" 
                               class="form-control" 
                               id="amount" 
                               name="amount" 
                               step="0.01" 
                               max="{{ $order['total'] }}"
                               placeholder="Leave empty for full refund">
                        <small class="text-muted">Order total: £{{ number_format($order['total'], 2) }}</small>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="api_refund" name="api_refund" value="1" checked>
                        <label class="form-check-label" for="api_refund">
                            Process refund via payment gateway
                        </label>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-undo"></i> Process Refund
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

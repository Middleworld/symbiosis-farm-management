<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CRM - Contact Lookup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .crm-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .caller-info {
            background: white;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .customer-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-completed { background: #d4edda; color: #155724; }
        .status-processing { background: #fff3cd; color: #856404; }
        .status-pending { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .quick-action-btn {
            margin: 5px;
        }
        .order-item {
            padding: 15px;
            border-left: 3px solid #e9ecef;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .note-item {
            padding: 12px;
            background: #fffbea;
            border-left: 3px solid #ffc107;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .no-customer {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="crm-header">
        <div class="container">
            <h1 class="mb-0"><i class="fas fa-phone-volume"></i> Incoming Call</h1>
            <p class="mb-0 mt-2"><i class="fas fa-clock"></i> {{ now()->format('l, F j, Y - g:i A') }}</p>
        </div>
    </div>

    <div class="container">
        {{-- Caller Information --}}
        <div class="caller-info">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-2"><i class="fas fa-phone"></i> {{ $phone ?: 'Unknown Number' }}</h3>
                    @if($name)
                        <p class="mb-0 text-muted"><i class="fas fa-user"></i> {{ $name }}</p>
                    @endif
                </div>
                <div class="col-md-6 text-end">
                    @if($customer)
                        <span class="badge bg-success fs-6"><i class="fas fa-check-circle"></i> Customer Found</span>
                    @else
                        <span class="badge bg-warning fs-6"><i class="fas fa-question-circle"></i> New Caller</span>
                    @endif
                </div>
            </div>
        </div>

        @if($customer)
            {{-- Customer Information --}}
            <div class="row">
                <div class="col-md-8">
                    <div class="customer-card">
                        <h4 class="mb-3"><i class="fas fa-user-circle"></i> Customer Information</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> {{ $customer->display_name }}</p>
                                <p><strong>Email:</strong> <a href="{{ route('admin.email.compose', ['to' => $customer->user_email, 'name' => $customer->display_name]) }}">{{ $customer->user_email }}</a></p>
                                <p><strong>Phone:</strong> {{ $customer->getMeta('billing_phone') }}</p>
                                <p><strong>Customer Since:</strong> {{ $customer->user_registered->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-6">
                                @php
                                    $wcData = $customer->getWooCommerceData();
                                @endphp
                                <p><strong>Billing Address:</strong><br>
                                    {{ $wcData['billing_address_1'] }}<br>
                                    {{ $wcData['billing_city'] }}, {{ $wcData['billing_postcode'] }}
                                </p>
                                @if($wcData['shipping_address_1'])
                                    <p><strong>Shipping Address:</strong><br>
                                        {{ $wcData['shipping_address_1'] }}<br>
                                        {{ $wcData['shipping_city'] }}, {{ $wcData['shipping_postcode'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Quick Actions</h5>
                        <a href="{{ route('admin.email.compose', ['to' => $customer->user_email, 'name' => $customer->display_name]) }}" class="btn btn-primary quick-action-btn">
                            <i class="fas fa-envelope"></i> Send Email
                        </a>
                        <a href="tel:{{ $customer->getMeta('billing_phone') }}" class="btn btn-success quick-action-btn">
                            <i class="fas fa-phone"></i> Call Back
                        </a>
                        <button class="btn btn-info quick-action-btn" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                            <i class="fas fa-sticky-note"></i> Add Note
                        </button>
                        <a href="{{ env('WOOCOMMERCE_URL') }}wp-admin/user-edit.php?user_id={{ $customer->ID }}" target="_blank" class="btn btn-secondary quick-action-btn">
                            <i class="fas fa-external-link-alt"></i> View in WooCommerce
                        </a>
                    </div>

                    {{-- Recent Orders --}}
                    @if($orders->count() > 0)
                        <div class="customer-card">
                            <h4 class="mb-3"><i class="fas fa-shopping-cart"></i> Recent Orders ({{ $orders->count() }})</h4>
                            
                            @foreach($orders as $order)
                                <div class="order-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6>
                                                Order #{{ $order['order_number'] }} 
                                                <span class="status-badge status-{{ $order['status'] }}">
                                                    {{ ucfirst($order['status']) }}
                                                </span>
                                            </h6>
                                            <p class="mb-1 text-muted">
                                                <i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($order['date'])->format('M d, Y g:i A') }}
                                            </p>
                                            <p class="mb-0">
                                                <strong>Items:</strong> 
                                                @foreach($order['items'] as $item)
                                                    {{ $item['quantity'] }}x {{ $item['name'] }}{{ !$loop->last ? ', ' : '' }}
                                                @endforeach
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <h5 class="mb-0">{{ $order['currency'] }} {{ number_format($order['total'], 2) }}</h5>
                                            @if($order['payment_method'])
                                                <small class="text-muted">{{ $order['payment_method'] }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Customer Notes --}}
                    @if($notes->count() > 0)
                        <div class="customer-card">
                            <h4 class="mb-3"><i class="fas fa-comments"></i> Customer Notes</h4>
                            
                            @foreach($notes as $note)
                                <div class="note-item">
                                    <p class="mb-1"><strong>{{ ucfirst(str_replace('_', ' ', $note->note_type)) }}</strong></p>
                                    <p class="mb-1">{{ $note->note }}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($note->created_at)->diffForHumans() }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Statistics Sidebar --}}
                <div class="col-md-4">
                    <div class="customer-card">
                        <h5 class="mb-3"><i class="fas fa-chart-line"></i> Customer Stats</h5>
                        
                        <div class="stat-card">
                            <div class="stat-value">{{ $orders->count() }}</div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-value">£{{ number_format($orders->sum('total'), 2) }}</div>
                            <div class="stat-label">Lifetime Value</div>
                        </div>
                        
                        @if($orders->count() > 0)
                            <div class="stat-card">
                                <div class="stat-value">£{{ number_format($orders->avg('total'), 2) }}</div>
                                <div class="stat-label">Average Order</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-value">{{ $orders->where('status', 'completed')->count() }}</div>
                                <div class="stat-label">Completed Orders</div>
                            </div>
                        @endif
                    </div>

                    <div class="customer-card">
                        <h6 class="mb-3"><i class="fas fa-info-circle"></i> Quick Info</h6>
                        <p><strong>Customer ID:</strong> {{ $customer->ID }}</p>
                        <p><strong>Role:</strong> {{ ucfirst($customer->getPrimaryRole()) }}</p>
                        <p class="mb-0"><strong>Last Order:</strong> 
                            @if($orders->count() > 0)
                                {{ \Carbon\Carbon::parse($orders->first()['date'])->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                </div>
            </div>

        @else
            {{-- No Customer Found --}}
            <div class="customer-card no-customer">
                <i class="fas fa-user-slash fa-4x mb-3 text-muted"></i>
                <h3>No Customer Found</h3>
                <p class="text-muted">Phone number {{ $phone }} is not in our system.</p>
                
                <hr class="my-4">
                
                <h5>This might be:</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-user-plus text-success"></i> A new customer calling for the first time</li>
                    <li><i class="fas fa-question-circle text-warning"></i> A customer with a different phone number on file</li>
                    <li><i class="fas fa-phone-slash text-danger"></i> A withheld or incorrect caller ID</li>
                </ul>
                
                <div class="mt-4">
                    <a href="{{ env('WOOCOMMERCE_URL') }}wp-admin/post-new.php?post_type=shop_order" target="_blank" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Order
                    </a>
                    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="fas fa-search"></i> Search Customers
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Add Note Modal --}}
    @if($customer)
    <div class="modal fade" id="addNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.crm.addNote') }}" method="POST">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->ID }}">
                    <input type="hidden" name="customer_email" value="{{ $customer->user_email }}">
                    
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-sticky-note"></i> Add Customer Note</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Note Type</label>
                            <select class="form-select" name="note_type">
                                <option value="phone_call">Phone Call</option>
                                <option value="complaint">Complaint</option>
                                <option value="query">Query</option>
                                <option value="feedback">Feedback</option>
                                <option value="general">General</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea class="form-control" name="note" rows="4" required placeholder="Enter your note about this call..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh page every 5 minutes to keep call data current
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>

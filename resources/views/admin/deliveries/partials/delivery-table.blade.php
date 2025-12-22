{{-- Delivery Table Partial --}}

{{-- Action Buttons for Deliveries --}}
@if(isset($showDeliveryActions) && $showDeliveryActions)
<div class="mb-3">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllDeliveries">
            <i class="fas fa-check-square"></i> Select All
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAllDeliveries">
            <i class="fas fa-square"></i> Deselect All
        </button>
        <button type="button" class="btn btn-success btn-sm" id="printScheduleBtn">
            <i class="fas fa-print"></i> Print Schedule
        </button>
        <button type="button" class="btn btn-warning btn-sm" id="printPackingSlipsBtn">
            <i class="fas fa-tags"></i> Print Packing Slips
        </button>
        <button type="button" class="btn btn-info btn-sm" id="addToRouteBtn" disabled title="Add selected deliveries to route planner">
            <i class="fas fa-route"></i> Add to Route Planner
        </button>
    </div>
</div>
@endif

<div class="table-responsive mb-4">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                @if(isset($type) && ($type === 'delivery' || $type === 'collection'))
                <th>
                    <input type="checkbox" class="form-check-input {{ $type === 'delivery' ? 'select-all-checkbox' : 'select-all-collection-checkbox' }} table-select-all" title="Select all {{ $type === 'delivery' ? 'deliveries' : 'collections' }}" data-type="{{ $type }}">
                </th>
                @endif
                <th>Customer</th>
                <th>Customer Notes</th>
                <th>Address</th>
                <th>Products</th>
                <th>Last Paid</th>
                <th>Contact</th>
                <th>Frequency</th>
                <th>Day</th>
                <th>Week</th>
                <th>Status</th>
                <th>Complete</th>
                <th>Next Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $delivery)
                <tr>
                    @if(isset($type) && ($type === 'delivery' || $type === 'collection'))
                    <td>
                        <input type="checkbox" class="form-check-input {{ $type === 'delivery' ? 'delivery-checkbox' : 'collection-checkbox' }}" 
                               value="{{ $delivery['id'] ?? $delivery['order_number'] ?? $loop->index }}"
                               data-delivery-id="{{ $delivery['id'] ?? $delivery['order_number'] ?? $loop->index }}"
                               data-collection-id="{{ $delivery['id'] ?? $delivery['order_number'] ?? $loop->index }}"
                               data-order-id="{{ $delivery['order_number'] ?? '' }}"
                               data-customer-name="{{ $delivery['customer_name'] ?? 'N/A' }}"
                               data-customer-email="{{ $delivery['customer_email'] ?? '' }}"
                               data-delivery-date="{{ $delivery['delivery_date'] ?? $delivery['date'] ?? '' }}"
                               data-customer-notes="{{ $delivery['special_instructions'] ?? $delivery['delivery_notes'] ?? '' }}"
                               data-address="{{ json_encode($delivery['shipping_address'] ?? $delivery['billing_address'] ?? []) }}">
                    </td>
                    @endif
                    <td>
                        <strong>{{ $delivery['customer_name'] ?? 'N/A' }}</strong>
                        @if(isset($delivery['order_number']))
                            <br><small class="text-muted">ID: {{ $delivery['order_number'] }}</small>
                        @endif
                    </td>
                    <td>
                        {{-- Customer Notes - Dedicated Column --}}
                        @if(!empty($delivery['special_instructions']) || !empty($delivery['delivery_notes']))
                            <div class="customer-notes p-2 bg-warning-subtle border border-warning rounded">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <strong>Note:</strong> {{ $delivery['special_instructions'] ?? $delivery['delivery_notes'] }}
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['shipping_address']) && is_array($delivery['shipping_address']))
                            {{ $delivery['shipping_address']['first_name'] ?? '' }} {{ $delivery['shipping_address']['last_name'] ?? '' }}<br>
                            @if(!empty($delivery['shipping_address']['address_1']))
                                {{ $delivery['shipping_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($delivery['shipping_address']['address_2']))
                                {{ $delivery['shipping_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($delivery['shipping_address']['city']))
                                {{ $delivery['shipping_address']['city'] }}<br>
                            @endif
                            @if(!empty($delivery['shipping_address']['postcode']))
                                {{ $delivery['shipping_address']['postcode'] }}
                            @endif
                        @elseif(isset($delivery['billing_address']) && is_array($delivery['billing_address']))
                            {{ $delivery['billing_address']['first_name'] ?? '' }} {{ $delivery['billing_address']['last_name'] ?? '' }}<br>
                            @if(!empty($delivery['billing_address']['address_1']))
                                {{ $delivery['billing_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($delivery['billing_address']['address_2']))
                                {{ $delivery['billing_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($delivery['billing_address']['city']))
                                {{ $delivery['billing_address']['city'] }}<br>
                            @endif
                            @if(!empty($delivery['billing_address']['postcode']))
                                {{ $delivery['billing_address']['postcode'] }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($delivery['products']) && is_array($delivery['products']))
                            @foreach($delivery['products'] as $product)
                                <div class="mb-1">
                                    <strong>{{ $product['quantity'] ?? 1 }}x</strong> {{ $product['name'] ?? 'Unknown Product' }}
                                </div>
                            @endforeach
                        @else
                            <small class="text-muted">No products found</small>
                        @endif
                    </td>
                    <td>
                        {{-- Last Paid Amount --}}
                        <strong class="text-success">£{{ number_format($delivery['total'] ?? 0, 2) }}</strong>
                        @if(!empty($delivery['payment_date']))
                            <br><small class="text-muted">{{ date('M j', strtotime($delivery['payment_date'])) }}</small>
                        @endif
                    </td>
                    <td>
                        @if(!empty($delivery['billing_address']['phone']))
                            <i class="fas fa-phone"></i> {{ $delivery['billing_address']['phone'] }}<br>
                        @endif
                        @if(!empty($delivery['customer_email']))
                            <i class="fas fa-envelope"></i> {{ $delivery['customer_email'] }}
                        @endif
                        @if(!empty($delivery['customer_email']) || !empty($delivery['billing_address']['phone']))
                            <br>
                            <a href="{{ route('admin.crm.contact', [
                                'email' => $delivery['customer_email'] ?? '',
                                'phone' => $delivery['billing_address']['phone'] ?? '',
                                'customer_id' => $delivery['customer_id'] ?? ''
                            ]) }}" class="btn btn-sm btn-outline-primary mt-1" target="_blank" title="Open CRM Contact">
                                <i class="fas fa-address-book"></i> CRM
                            </a>
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['frequency']))
                            <span class="badge bg-{{ $delivery['frequency_badge'] ?? 'secondary' }}">
                                {{ $delivery['frequency'] }}
                            </span>
                            @if(strtolower($delivery['frequency']) === 'fortnightly')
                                <br><small class="text-muted">
                                    @if(isset($delivery['should_deliver_this_week']))
                                        {{ $delivery['should_deliver_this_week'] ? '✅ Active' : '⏸️ Skip' }} this week
                                    @endif
                                </small>
                            @endif
                        @else
                            <span class="badge bg-{{ isset($delivery['subscription_id']) ? 'success' : 'info' }}">
                                {{ isset($delivery['subscription_id']) ? 'Weekly Delivery' : 'One-time' }}
                            </span>
                        @endif
                    </td>
                    <td>
                        {{-- Day Column - Shows delivery/collection day --}}
                        @if(isset($type))
                            @if($type === 'delivery')
                                <span class="badge bg-primary">
                                    <i class="fas fa-truck"></i> Thursday
                                </span>
                            @elseif($type === 'collection')
                                @php
                                    $collectionDay = $delivery['preferred_collection_day'] ?? 'Friday';
                                    $dayBadge = $collectionDay === 'Saturday' ? 'bg-info' : 'bg-success';
                                @endphp
                                <span class="badge {{ $dayBadge }}">
                                    <i class="fas fa-calendar-check"></i> {{ $collectionDay }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['customer_week_type']) && $delivery['customer_week_type'] !== 'Weekly')
                            <div class="dropdown">
                                <button class="btn btn-sm badge bg-{{ $delivery['week_badge'] ?? 'secondary' }} dropdown-toggle week-toggle-btn"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    Week {{ $delivery['customer_week_type'] }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item week-change-btn"
                                           href="#"
                                           data-customer-id="{{ $delivery['id'] }}"
                                           data-current-week="{{ $delivery['customer_week_type'] }}"
                                           data-new-week="A">
                                        <span class="badge bg-success me-2">A</span>Week A (Odd weeks)
                                    </a></li>
                                    <li><a class="dropdown-item week-change-btn"
                                           href="#"
                                           data-customer-id="{{ $delivery['id'] }}"
                                           data-current-week="{{ $delivery['customer_week_type'] }}"
                                           data-new-week="B">
                                        <span class="badge bg-info me-2">B</span>Week B (Even weeks)
                                    </a></li>
                                </ul>
                            </div>
                            <br><small class="text-muted">
                                Current: Week {{ $delivery['current_week_type'] ?? '?' }}
                                @if(isset($delivery['should_deliver_this_week']))
                                    | {{ $delivery['should_deliver_this_week'] ? '✅ Active' : '⏸️ Skip' }} this week
                                @endif
                            </small>
                        @else
                            <span class="badge bg-primary">Every Week</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ isset($delivery['status']) && $delivery['status'] === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($delivery['status'] ?? 'pending') }}
                        </span>
                    </td>
                    <td>
                        {{-- Completion Status Button --}}
                        @php
                            $isCompleted = isset($delivery['completed']) && $delivery['completed'];
                            $completionStatus = $delivery['completion_status'] ?? 'pending';
                        @endphp
                        
                        @if($isCompleted || $completionStatus === 'completed')
                            <button class="btn btn-success btn-sm" disabled>
                                <i class="fas fa-check-circle"></i> Done
                            </button>
                            <br><small class="text-muted">{{ $delivery['completed_at'] ?? 'Today' }}</small>
                        @else
                            <button class="btn btn-outline-success btn-sm mark-complete-btn" 
                                    data-delivery-id="{{ $delivery['id'] ?? $delivery['order_number'] ?? $loop->index }}"
                                    data-customer-name="{{ $delivery['customer_name'] ?? 'N/A' }}"
                                    data-delivery-date="{{ $currentDate ?? $delivery['delivery_date'] ?? $delivery['date'] ?? date('Y-m-d') }}"
                                    title="Mark this delivery as complete">
                                <i class="fas fa-check"></i> Complete
                            </button>
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['next_payment']))
                            @if(is_numeric($delivery['next_payment']) && $delivery['next_payment'] == 0)
                                <span class="text-warning">Pending</span>
                            @else
                                <small>{{ date('Y-m-d', strtotime($delivery['next_payment'])) }}</small>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($delivery['customer_email']))
                            <div class="d-flex flex-column gap-1">
                                <button class="btn btn-sm btn-outline-primary user-switch-btn" 
                                        data-email="{{ $delivery['customer_email'] }}" 
                                        data-name="{{ $delivery['customer_name'] ?? 'Customer' }}"
                                        title="Switch to this user's account">
                                    <i class="fas fa-user-circle"></i> Switch to User
                                </button>
                                <button class="btn btn-sm btn-outline-success subscription-btn" 
                                        data-email="{{ $delivery['customer_email'] }}" 
                                        data-name="{{ $delivery['customer_name'] ?? 'Customer' }}"
                                        title="View subscription in WooCommerce">
                                    <i class="fas fa-shopping-cart"></i> View Subscription
                                </button>
                                <button class="btn btn-sm btn-outline-info profile-btn" 
                                        data-email="{{ $delivery['customer_email'] }}" 
                                        data-name="{{ $delivery['customer_name'] ?? 'Customer' }}"
                                        title="View user profile in WordPress">
                                    <i class="fas fa-user"></i> View Profile
                                </button>
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

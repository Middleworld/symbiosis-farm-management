{{-- Collection Table Partial --}}

@php
    // Create unique IDs for buttons to avoid conflicts when partial is included multiple times
    $uniqueId = $uniqueId ?? uniqid();
    $selectAllId = "selectAllCollections_{$uniqueId}";
    $deselectAllId = "deselectAllCollections_{$uniqueId}";
    $printScheduleId = "printCollectionScheduleBtn_{$uniqueId}";
    $printSlipsId = "printCollectionSlipsBtn_{$uniqueId}";
@endphp

{{-- Action Buttons for Collections --}}
@if(isset($showCollectionActions) && $showCollectionActions)
<div class="mb-3">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary btn-sm select-all-collections-btn" id="{{ $selectAllId }}">
            <i class="fas fa-check-square"></i> Select All
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm deselect-all-collections-btn" id="{{ $deselectAllId }}">
            <i class="fas fa-square"></i> Deselect All
        </button>
        <button type="button" class="btn btn-success btn-sm print-collection-schedule-btn" id="{{ $printScheduleId }}" disabled>
            <i class="fas fa-print"></i> Print Schedule
        </button>
        <button type="button" class="btn btn-warning btn-sm print-collection-slips-btn" id="{{ $printSlipsId }}" disabled>
            <i class="fas fa-tags"></i> Print Collection Slips
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
                <th>Collection Point</th>
                <th>Collection Notes</th>
                <th>Location</th>
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
            @foreach($items as $collection)
                <tr>
                    @if(isset($type) && ($type === 'delivery' || $type === 'collection'))
                    <td>
                        <input type="checkbox" class="form-check-input {{ $type === 'delivery' ? 'delivery-checkbox' : 'collection-checkbox' }}" 
                               value="{{ $collection['id'] ?? $collection['order_number'] ?? $loop->index }}"
                               data-delivery-id="{{ $collection['id'] ?? $collection['order_number'] ?? $loop->index }}"
                               data-collection-id="{{ $collection['id'] ?? $collection['order_number'] ?? $loop->index }}"
                               data-order-id="{{ $collection['order_number'] ?? '' }}"
                               data-customer-name="{{ $collection['customer_name'] ?? 'N/A' }}"
                               data-customer-email="{{ $collection['customer_email'] ?? '' }}"
                               data-delivery-date="{{ $collection['delivery_date'] ?? $collection['date'] ?? '' }}"
                               data-customer-notes="{{ $collection['special_instructions'] ?? $collection['delivery_notes'] ?? '' }}"
                               data-address="{{ json_encode($collection['shipping_address'] ?? $collection['billing_address'] ?? []) }}">
                    </td>
                    @endif
                    <td>
                        <strong>{{ $collection['customer_name'] ?? 'N/A' }}</strong>
                        @if(isset($collection['order_number']))
                            <br><small class="text-muted">ID: {{ $collection['order_number'] }}</small>
                        @endif
                    </td>
                    <td>
                        {{-- Collection Notes - Dedicated Column --}}
                        @if(!empty($collection['special_instructions']) || !empty($collection['delivery_notes']))
                            <div class="customer-notes p-2 bg-info-subtle border border-info rounded">
                                <i class="fas fa-info-circle text-info"></i>
                                <strong>Collection Note:</strong> {{ $collection['special_instructions'] ?? $collection['delivery_notes'] }}
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['shipping_address']) && is_array($collection['shipping_address']))
                            {{ $collection['shipping_address']['first_name'] ?? '' }} {{ $collection['shipping_address']['last_name'] ?? '' }}<br>
                            @if(!empty($collection['shipping_address']['address_1']))
                                {{ $collection['shipping_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['address_2']))
                                {{ $collection['shipping_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['city']))
                                {{ $collection['shipping_address']['city'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['postcode']))
                                {{ $collection['shipping_address']['postcode'] }}
                            @endif
                        @elseif(isset($collection['billing_address']) && is_array($collection['billing_address']))
                            {{ $collection['billing_address']['first_name'] ?? '' }} {{ $collection['billing_address']['last_name'] ?? '' }}<br>
                            @if(!empty($collection['billing_address']['address_1']))
                                {{ $collection['billing_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['address_2']))
                                {{ $collection['billing_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['city']))
                                {{ $collection['billing_address']['city'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['postcode']))
                                {{ $collection['billing_address']['postcode'] }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['products']) && is_array($collection['products']))
                            @foreach($collection['products'] as $product)
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
                        <strong class="text-success">£{{ number_format($collection['total'] ?? 0, 2) }}</strong>
                        @if(!empty($collection['payment_date']))
                            <br><small class="text-muted">{{ date('M j', strtotime($collection['payment_date'])) }}</small>
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['billing_address']['phone']))
                            <i class="fas fa-phone"></i> {{ $collection['billing_address']['phone'] }}<br>
                        @endif
                        @if(!empty($collection['customer_email']))
                            <i class="fas fa-envelope"></i> {{ $collection['customer_email'] }}
                        @endif
                        @if(!empty($collection['customer_email']) || !empty($collection['billing_address']['phone']))
                            <br>
                            <a href="{{ route('admin.crm.contact', [
                                'email' => $collection['customer_email'] ?? '',
                                'phone' => $collection['billing_address']['phone'] ?? '',
                                'customer_id' => $collection['customer_id'] ?? ''
                            ]) }}" class="btn btn-sm btn-outline-primary mt-1" target="_blank" title="Open CRM Contact">
                                <i class="fas fa-address-book"></i> CRM
                            </a>
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['frequency']))
                            <span class="badge bg-{{ $collection['frequency_badge'] ?? 'secondary' }}">
                                {{ $collection['frequency'] }}
                            </span>
                            @if(strtolower($collection['frequency']) === 'fortnightly')
                                <br><small class="text-muted">
                                    @if(isset($collection['should_deliver_this_week']))
                                        {{ $collection['should_deliver_this_week'] ? '✅ Active' : '⏸️ Skip' }} this week
                                    @endif
                                </small>
                            @endif
                        @else
                            <span class="badge bg-{{ isset($collection['subscription_id']) ? 'info' : 'success' }}">
                                {{ isset($collection['subscription_id']) ? 'Weekly Collection' : 'One-time Collection' }}
                            </span>
                        @endif
                    </td>
                    <td>
                        {{-- Day Column - Shows collection day --}}
                        @php
                            $collectionDay = $collection['preferred_collection_day'] ?? 'Friday';
                            $dayBadge = $collectionDay === 'Saturday' ? 'bg-info' : 'bg-success';
                        @endphp
                        <span class="badge {{ $dayBadge }}">
                            <i class="fas fa-calendar-check"></i> {{ $collectionDay }}
                        </span>
                    </td>
                    <td>
                        @if(isset($collection['customer_week_type']) && $collection['customer_week_type'] !== 'Weekly')
                            <div class="dropdown">
                                <button class="btn btn-sm badge bg-{{ $collection['week_badge'] ?? 'secondary' }} dropdown-toggle week-toggle-btn"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    Week {{ $collection['customer_week_type'] }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item week-change-btn"
                                           href="#"
                                           data-customer-id="{{ $collection['id'] }}"
                                           data-current-week="{{ $collection['customer_week_type'] }}"
                                           data-new-week="A">
                                        <span class="badge bg-success me-2">A</span>Week A (Odd weeks)
                                    </a></li>
                                    <li><a class="dropdown-item week-change-btn"
                                           href="#"
                                           data-customer-id="{{ $collection['id'] }}"
                                           data-current-week="{{ $collection['customer_week_type'] }}"
                                           data-new-week="B">
                                        <span class="badge bg-info me-2">B</span>Week B (Even weeks)
                                    </a></li>
                                </ul>
                            </div>
                            <br><small class="text-muted">
                                Current: Week {{ $collection['current_week_type'] ?? '?' }}
                                @if(isset($collection['should_deliver_this_week']))
                                    | {{ $collection['should_deliver_this_week'] ? '✅ Active' : '⏸️ Skip' }} this week
                                @endif
                            </small>
                        @else
                            <span class="badge bg-primary">Every Week</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ isset($collection['status']) && $collection['status'] === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($collection['status'] ?? 'pending') }}
                        </span>
                    </td>
                    <td>
                        {{-- Completion Status Button --}}
                        @php
                            $isCompleted = isset($collection['completed']) && $collection['completed'];
                            $completionStatus = $collection['completion_status'] ?? 'pending';
                        @endphp
                        
                        @if($isCompleted || $completionStatus === 'completed')
                            <button class="btn btn-success btn-sm" disabled>
                                <i class="fas fa-check-circle"></i> Done
                            </button>
                            <br><small class="text-muted">{{ $collection['completed_at'] ?? 'Today' }}</small>
                        @else
                            <button class="btn btn-outline-success btn-sm mark-complete-btn" 
                                    data-delivery-id="{{ $collection['id'] ?? $collection['order_number'] ?? $loop->index }}"
                                    data-customer-name="{{ $collection['customer_name'] ?? 'N/A' }}"
                                    data-delivery-date="{{ $currentDate ?? $collection['delivery_date'] ?? $collection['date'] ?? date('Y-m-d') }}"
                                    title="Mark this collection as complete">
                                <i class="fas fa-check"></i> Complete
                            </button>
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['next_payment']))
                            @if(is_numeric($collection['next_payment']) && $collection['next_payment'] == 0)
                                <span class="text-warning">Pending</span>
                            @else
                                <small>{{ date('Y-m-d', strtotime($collection['next_payment'])) }}</small>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['customer_email']))
                            <div class="d-flex flex-column gap-1">
                                <button class="btn btn-sm btn-outline-primary user-switch-btn" 
                                        data-email="{{ $collection['customer_email'] }}" 
                                        data-name="{{ $collection['customer_name'] ?? 'Customer' }}"
                                        title="Switch to this user's account">
                                    <i class="fas fa-user-circle"></i> Switch to User
                                </button>
                                <button class="btn btn-sm btn-outline-success subscription-btn" 
                                        data-email="{{ $collection['customer_email'] }}" 
                                        data-name="{{ $collection['customer_name'] ?? 'Customer' }}"
                                        title="View subscription in WooCommerce">
                                    <i class="fas fa-shopping-cart"></i> View Subscription
                                </button>
                                <button class="btn btn-sm btn-outline-info profile-btn" 
                                        data-email="{{ $collection['customer_email'] }}" 
                                        data-name="{{ $collection['customer_name'] ?? 'Customer' }}"
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

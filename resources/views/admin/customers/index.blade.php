@extends('layouts.app')

@section('title', 'Customer Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-users"></i> Customer Management
        </h1>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-search"></i> Search Customers
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ request()->url() }}" class="mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Search by name, email, or username..." value="{{ $search ?? '' }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            @if(!empty($search))
                                <a href="{{ request()->url() }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            <option value="10" {{ ($perPage ?? 25) == 10 ? 'selected' : '' }}>10 per page</option>
                            <option value="25" {{ ($perPage ?? 25) == 25 ? 'selected' : '' }}>25 per page</option>
                            <option value="50" {{ ($perPage ?? 25) == 50 ? 'selected' : '' }}>50 per page</option>
                            <option value="100" {{ ($perPage ?? 25) == 100 ? 'selected' : '' }}>100 per page</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        @if(isset($pagination))
                            <small class="text-muted">
                                Showing {{ $pagination['showing_from'] }}-{{ $pagination['showing_to'] }} of {{ $pagination['total_users'] }} customers
                            </small>
                        @endif
                    </div>
                </div>
                @if($search || ($perPage ?? 25) != 25 || ($filter ?? 'all') != 'all' || ($orderFilter ?? 'any') != 'any' || ($dateFilter ?? 'any') != 'any')
                    <input type="hidden" name="page" value="{{ request('page', 1) }}">
                    <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}">
                    <input type="hidden" name="order_filter" value="{{ $orderFilter ?? 'any' }}">
                    <input type="hidden" name="date_filter" value="{{ $dateFilter ?? 'any' }}">
                @endif
            </form>
            @if(!empty($search))
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Search results for: <strong>{{ $search }}</strong>
                </div>
            @endif
        </div>
    </div>

    <!-- Filter Tabs and Advanced Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> Filter Customers
            </h5>
        </div>
        <div class="card-body">
            <!-- Quick Filter Tabs -->
            <ul class="nav nav-tabs mb-3" id="customerFilterTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ ($filter ?? 'all') == 'all' ? 'active' : '' }}" 
                       href="{{ request()->fullUrlWithQuery(['filter' => 'all', 'page' => 1]) }}">
                        <i class="fas fa-users"></i> All Customers
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ ($filter ?? '') == 'subscribers' ? 'active' : '' }}" 
                       href="{{ request()->fullUrlWithQuery(['filter' => 'subscribers', 'page' => 1]) }}">
                        <i class="fas fa-star"></i> Active Subscribers
                        <small class="text-muted">(subscription plans)</small>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ ($filter ?? '') == 'has_orders' ? 'active' : '' }}" 
                       href="{{ request()->fullUrlWithQuery(['filter' => 'has_orders', 'page' => 1]) }}">
                        <i class="fas fa-shopping-cart"></i> Has Orders
                        <small class="text-muted">(completed purchases)</small>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ ($filter ?? '') == 'recent' ? 'active' : '' }}" 
                       href="{{ request()->fullUrlWithQuery(['filter' => 'recent', 'page' => 1]) }}">
                        <i class="fas fa-clock"></i> Recent (30 days)
                    </a>
                </li>
            </ul>
            
            <!-- Advanced Filters -->
            <form method="GET" action="{{ request()->url() }}" class="row g-3">
                <input type="hidden" name="q" value="{{ $search ?? '' }}">
                <input type="hidden" name="per_page" value="{{ $perPage ?? 25 }}">
                <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}">
                
                <div class="col-md-3">
                    <label class="form-label"><small>Order Count</small></label>
                    <select name="order_filter" class="form-select form-select-sm">
                        <option value="any" {{ ($orderFilter ?? 'any') == 'any' ? 'selected' : '' }}>Any Orders</option>
                        <option value="none" {{ ($orderFilter ?? '') == 'none' ? 'selected' : '' }}>No Orders</option>
                        <option value="some" {{ ($orderFilter ?? '') == 'some' ? 'selected' : '' }}>1-4 Orders</option>
                        <option value="many" {{ ($orderFilter ?? '') == 'many' ? 'selected' : '' }}>5+ Orders</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label"><small>Registration Date</small></label>
                    <select name="date_filter" class="form-select form-select-sm">
                        <option value="any" {{ ($dateFilter ?? 'any') == 'any' ? 'selected' : '' }}>Any Date</option>
                        <option value="today" {{ ($dateFilter ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ ($dateFilter ?? '') == 'week' ? 'selected' : '' }}>Last 7 days</option>
                        <option value="month" {{ ($dateFilter ?? '') == 'month' ? 'selected' : '' }}>Last 30 days</option>
                        <option value="older" {{ ($dateFilter ?? '') == 'older' ? 'selected' : '' }}>Older than 30 days</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label"><small>&nbsp;</small></label>
                    <div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    </div>
                </div>
                
                <div class="col-md-3">
                    @if(isset($pagination))
                        <label class="form-label"><small>&nbsp;</small></label>
                        <div>
                            <small class="text-muted">
                                @if(!empty($search) || ($filter ?? 'all') != 'all' || ($orderFilter ?? 'any') != 'any' || ($dateFilter ?? 'any') != 'any')
                                    <i class="fas fa-filter text-primary"></i> Filtered results
                                @else
                                    <i class="fas fa-users"></i> All customers
                                @endif
                            </small>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> 
                @switch($filter ?? 'all')
                    @case('subscribers')
                        Active Subscribers
                        @break
                    @case('has_orders')
                        Customers with Orders
                        @break
                    @case('recent')
                        Recent Customers (30 days)
                        @break
                    @default
                        All Customers & Subscribers
                @endswitch
            </h5>
        </div>
        <div class="card-body">
            @if(isset($recentCustomers) && count($recentCustomers) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subscribed</th>
                                <th>CRM</th>
                                <th>Orders</th>
                                <th>Last Order</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCustomers as $customer)
                                <tr>
                                    <td>{{ $customer['name'] }}</td>
                                    <td>{{ $customer['email'] }}</td>
                                    <td>
                                        @if($customer['subscribed'])
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.crm.contact', ['phone' => $customer['phone'] ?? '', 'name' => $customer['name'], 'customer_id' => $customer['id']]) }}" 
                                           class="btn btn-sm btn-info" 
                                           target="_blank"
                                           title="View CRM">
                                            <i class="fas fa-address-book"></i> CRM
                                        </a>
                                    </td>
                                    <td>{{ $customer['orders_count'] ?? 0 }}</td>
                                    <td>{{ $customer['last_order'] ?? '' }}</td>
                                    <td>{{ $customer['joined'] }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="switchToUser({{ $customer['id'] }}, '{{ $customer['name'] }}')">
                                            <i class="fas fa-sign-in-alt"></i> Switch
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(isset($pagination) && $pagination['total_pages'] > 1)
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Page {{ $pagination['current_page'] }} of {{ $pagination['total_pages'] }}
                            </small>
                        </div>
                        <nav aria-label="Customer pagination">
                            <ul class="pagination pagination-sm mb-0">
                                @if($pagination['has_prev'])
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => 1]) }}">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination['prev_page']]) }}">
                                            <i class="fas fa-angle-left"></i> Previous
                                        </a>
                                    </li>
                                @endif
                                
                                @php
                                    $start = max(1, $pagination['current_page'] - 2);
                                    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                                @endphp
                                
                                @if($start > 1)
                                    <li class="page-item"><span class="page-link">...</span></li>
                                @endif
                                
                                @for($i = $start; $i <= $end; $i++)
                                    <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                                    </li>
                                @endfor
                                
                                @if($end < $pagination['total_pages'])
                                    <li class="page-item"><span class="page-link">...</span></li>
                                @endif
                                
                                @if($pagination['has_next'])
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination['next_page']]) }}">
                                            Next <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination['total_pages']]) }}">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                @endif
            @else
                <div class="alert alert-info">
                    @switch($filter ?? 'all')
                        @case('subscribers')
                            <i class="fas fa-info-circle"></i> <strong>No active subscribers found.</strong><br>
                            <small>This means no customers currently have active subscription plans. New customers may need to sign up for subscription services.</small>
                            @break
                        @case('has_orders')
                            <i class="fas fa-info-circle"></i> <strong>No customers with orders found.</strong><br>
                            <small>This means no customers have completed purchases yet. You may have new signups who haven't made their first order.</small>
                            @break
                        @case('recent')
                            <i class="fas fa-info-circle"></i> <strong>No recent customers found.</strong><br>
                            <small>No new customer registrations in the last 30 days.</small>
                            @break
                        @default
                            <i class="fas fa-info-circle"></i> <strong>No customers found.</strong><br>
                            <small>Try adjusting your search terms or filters.</small>
                    @endswitch
                    
                    @if(($filter ?? 'all') != 'all')
                        <div class="mt-2">
                            <a href="{{ request()->fullUrlWithQuery(['filter' => 'all']) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-users"></i> View All Customers
                            </a>
                        </div>
                    @endif
                </div>
            @endif
            
            {{-- Debug info hidden by default, uncomment to show --}}
            {{-- @if(isset($debug))
            <div class="mt-3">
                <h6>Debug Info</h6>
                <pre style="background:#f8f9fa;border:1px solid #ccc;padding:10px;font-size:12px;max-height:300px;overflow:auto;">{{ print_r($debug, true) }}</pre>
            </div>
            @endif --}}
        </div>
    </div>
        </div>
    </div>
    <!-- TODO: Add customer list, action buttons, etc. -->
</div>

<script>
function switchToUser(userId, userName) {
    if (confirm(`Switch to user: ${userName}?`)) {
        // Show loading
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Switching...';
        
        fetch(`/admin/customers/switch/${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                redirect_to: '/my-account/'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Open in new tab/window
                window.open(data.switch_url, '_blank');
                alert('User switch successful! Check the new tab.');
            } else {
                alert('Error: ' + (data.error || 'User switching failed'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: Failed to switch user');
        })
        .finally(() => {
            // Restore button
            button.disabled = false;
            button.innerHTML = originalContent;
        });
    }
}
</script>
@endsection

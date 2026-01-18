@extends('layouts.app')

@section('title', 'Customer Management')

@section('content')
<style>
.full-width-container {
    width: 100% !important;
    max-width: none !important;
    padding: 0 20px;
    box-sizing: border-box;
}
</style>
<div class="full-width-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-users"></i> Customer Management
        </h1>
        <div class="btn-group" role="group">
            <a href="https://soilsync.shop/wp-admin/users.php?role=customer" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-external-link-alt"></i> Open WooCommerce Customers
            </a>
        </div>
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
            </form>

            <div class="row mb-3">
                <div class="col-md-3">
                    <select name="filter" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ ($filter ?? 'all') == 'all' ? 'selected' : '' }}>All Customers</option>
                        <option value="subscribers" {{ ($filter ?? 'all') == 'subscribers' ? 'selected' : '' }}>Subscribers Only</option>
                        <option value="has_orders" {{ ($filter ?? 'all') == 'has_orders' ? 'selected' : '' }}>Has Orders</option>
                        <option value="recent" {{ ($filter ?? 'all') == 'recent' ? 'selected' : '' }}>Recent (30 days)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="order_filter" class="form-select" onchange="this.form.submit()">
                        <option value="any" {{ ($orderFilter ?? 'any') == 'any' ? 'selected' : '' }}>Any Orders</option>
                        <option value="none" {{ ($orderFilter ?? 'any') == 'none' ? 'selected' : '' }}>No Orders</option>
                        <option value="some" {{ ($orderFilter ?? 'any') == 'some' ? 'selected' : '' }}>1-4 Orders</option>
                        <option value="many" {{ ($orderFilter ?? 'any') == 'many' ? 'selected' : '' }}>5+ Orders</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="date_filter" class="form-select" onchange="this.form.submit()">
                        <option value="any" {{ ($dateFilter ?? 'any') == 'any' ? 'selected' : '' }}>Any Date</option>
                        <option value="today" {{ ($dateFilter ?? 'any') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ ($dateFilter ?? 'any') == 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ ($dateFilter ?? 'any') == 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="older" {{ ($dateFilter ?? 'any') == 'older' ? 'selected' : '' }}>Older</option>
                    </select>
                </div>
                <div class="col-md-3">
                    @if(isset($customerStats))
                        <div class="text-end">
                            <small class="text-muted">
                                Active Subs: {{ $customerStats['active_subscriptions'] ?? 0 }} |
                                Orders (30d): {{ $customerStats['recent_orders'] ?? 0 }}
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(empty($recentCustomers))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4>All Customers & Subscribers</h4>
                <p class="text-muted">No customers found.</p>
                <p class="text-muted">Try adjusting your search terms or filters.</p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Customers & Subscribers</h5>
                <small class="text-muted">{{ count($recentCustomers) }} customers shown</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Orders</th>
                                <th>Subscribed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCustomers as $customer)
                                <tr>
                                    <td>
                                        <strong>{{ $customer['name'] }}</strong>
                                    </td>
                                    <td>{{ $customer['email'] }}</td>
                                    <td>{{ $customer['phone'] ?: '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($customer['joined'])->format('M j, Y') }}</td>
                                    <td>
                                        @if($customer['orders_count'] > 0)
                                            <span class="badge bg-success">{{ $customer['orders_count'] }}</span>
                                            @if($customer['last_order'])
                                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($customer['last_order'])->format('M j') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($customer['subscribed'])
                                            <span class="badge bg-primary">Yes</span>
                                        @else
                                            <span class="badge bg-light text-dark">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="switchToUser({{ $customer['id'] }}, '{{ addslashes($customer['name']) }}')">
                                                <i class="fas fa-user-check"></i> Switch
                                            </button>
                                            <a href="mailto:{{ $customer['email'] }}" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if(isset($pagination) && $pagination['total_pages'] > 1)
                <div class="card-footer">
                    <nav aria-label="Customer pagination">
                        <ul class="pagination justify-content-center mb-0">
                            @if($pagination['has_prev'])
                                <li class="page-item">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination['prev_page']]) }}">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            @endif

                            @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++)
                                <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                                </li>
                            @endfor

                            @if($pagination['has_next'])
                                <li class="page-item">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $pagination['next_page']]) }}">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    @endif
</div>

<script>
function switchToUser(userId, userName) {
    if (!confirm('Switch to user: ' + userName + '? You will be logged in as this user.')) {
        return;
    }

    // Create a form to submit POST request that will redirect
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/customers/switch/' + userId;
    
    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);
    
    // Add to body and submit
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection

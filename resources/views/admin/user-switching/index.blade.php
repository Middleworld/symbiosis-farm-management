@extends('layouts.app')

@section('title', 'User Switching')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-user-friends"></i> User Switching
        </h1>
        <div>
            <a href="{{ route('admin.deliveries.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-truck"></i> Back to Deliveries
            </a>
        </div>
    </div>

    <!-- Search Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-search"></i> Search Users
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="mb-3">
                <div class="input-group">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search by name, email, or username..." 
                           value="{{ $searchQuery }}"
                           id="userSearchInput">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    @if($searchQuery)
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    @endif
                </div>
            </form>

            <!-- Live Search Results -->
            <div id="searchResults" class="mt-3" style="display: none;">
                <h6>Search Results:</h6>
                <div id="searchResultsList"></div>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    @if(!empty($searchResults))
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-search"></i> Search Results for "{{ $searchQuery }}"
                    <span class="badge bg-primary ms-2">{{ count($searchResults) }} found</span>
                </h5>
            </div>
            <div class="card-body">
                @if(count($searchResults) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($searchResults as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user['display_name'] ?? $user['username'] ?? 'Unknown' }}</strong>
                                            <br>
                                            <small class="text-muted">ID: {{ $user['id'] }}</small>
                                        </td>
                                        <td>
                                            <small>{{ $user['email'] ?? 'No email' }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-info" 
                                                        onclick="viewUserDetails({{ $user['id'] }})" 
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-primary" 
                                                        onclick="switchToUser({{ $user['id'] }}, '{{ $user['display_name'] ?? $user['username'] ?? 'User' }}')" 
                                                        title="Switch to User">
                                                    <i class="fas fa-sign-in-alt"></i> Switch
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No users found</h5>
                        <p class="text-muted">Try searching with different keywords.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Recent Users -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-clock"></i> Recent Users
                <span class="badge bg-info ms-2">{{ count($recentUsers) }}</span>
            </h5>
        </div>
        <div class="card-body">
            @if(count($recentUsers) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentUsers as $user)
                                <tr>
                                    <td>
                                        <strong>{{ $user['display_name'] ?? $user['username'] ?? 'Unknown' }}</strong>
                                        <br>
                                        <small class="text-muted">ID: {{ $user['id'] }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $user['email'] ?? 'No email' }}</small>
                                    </td>
                                    <td>
                                        @if(!empty($user['last_login']))
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($user['last_login'])->diffForHumans() }}
                                            </small>
                                        @else
                                            <small class="text-muted">Unknown</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info" 
                                                    onclick="viewUserDetails({{ $user['id'] }})" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-primary" 
                                                    onclick="switchToUser({{ $user['id'] }}, '{{ $user['display_name'] ?? $user['username'] ?? 'User' }}')" 
                                                    title="Switch to User">
                                                <i class="fas fa-sign-in-alt"></i> Switch
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No recent users found</h5>
                    <p class="text-muted">Recent user activity will appear here.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Debug Info -->
    @if(isset($debug) && !empty($debug))
        <div class="alert alert-info mt-3">
            <h6>Debug Info:</h6>
            @if(isset($debug['endpoint']))
                <p><strong>Endpoint:</strong> {{ $debug['endpoint'] }}</p>
            @endif
            @if(isset($debug['total_users_from_api']))
                <p><strong>Total users from API:</strong> {{ $debug['total_users_from_api'] }}</p>
            @endif
            @if(isset($debug['filtered_users_count']))
                <p><strong>Filtered users count:</strong> {{ $debug['filtered_users_count'] }}</p>
            @endif
            @if(isset($debug['error']))
                <p class="text-danger"><strong>Error:</strong> {{ $debug['error'] }}</p>
            @endif
            @if(isset($debug['exception']))
                <p class="text-danger"><strong>Exception:</strong> {{ $debug['exception'] }}</p>
            @endif
        </div>
    @endif
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user"></i> User Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="switchFromModalBtn" onclick="switchFromModal()">
                    <i class="fas fa-sign-in-alt"></i> Switch to User
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentUserId = null;
let currentUserName = '';

// Live search functionality
document.getElementById('userSearchInput').addEventListener('input', function() {
    const query = this.value.trim();
    const resultsDiv = document.getElementById('searchResults');
    const resultsList = document.getElementById('searchResultsList');
    
    if (query.length < 2) {
        resultsDiv.style.display = 'none';
        return;
    }
    
    // Show loading
    resultsList.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm" role="status"></div> Searching...</div>';
    resultsDiv.style.display = 'block';
    
    // Perform search
    fetch(`/admin/users/search?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.users.length > 0) {
                let html = '<div class="list-group">';
                data.users.forEach(user => {
                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${user.display_name || user.username || 'Unknown'}</strong>
                                <br>
                                <small class="text-muted">${user.email || 'No email'}</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-info me-1" 
                                        onclick="viewUserDetails(${user.id})" 
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" 
                                        onclick="switchToUser(${user.id}, '${user.display_name || user.username || 'User'}')" 
                                        title="Switch to User">
                                    <i class="fas fa-sign-in-alt"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                resultsList.innerHTML = html;
            } else {
                resultsList.innerHTML = '<div class="alert alert-info mb-0">No users found for this search.</div>';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            resultsList.innerHTML = '<div class="alert alert-danger mb-0">Search failed. Please try again.</div>';
        });
});

// View user details
function viewUserDetails(userId) {
    currentUserId = userId;
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    const content = document.getElementById('userDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading user details...</p>
        </div>
    `;
    
    modal.show();
    
    // Fetch user details
    fetch(`/admin/users/details/${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                const user = data.user;
                currentUserName = user.display_name || user.username || 'Unknown User';
                
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>${user.id}</td>
                                </tr>
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td>${user.username || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Display Name:</strong></td>
                                    <td>${user.display_name || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>${user.email || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>First Name:</strong></td>
                                    <td>${user.first_name || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Last Name:</strong></td>
                                    <td>${user.last_name || 'N/A'}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            
                            ${user.billing_address ? `
                                <h6>Billing Address</h6>
                                <address class="small">
                                    ${user.billing_address.first_name || ''} ${user.billing_address.last_name || ''}<br>
                                    ${user.billing_address.address_1 || ''}<br>
                                    ${user.billing_address.city || ''} ${user.billing_address.postcode || ''}<br>
                                    ${user.billing_address.phone ? `Phone: ${user.billing_address.phone}` : ''}
                                </address>
                            ` : ''}
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Failed to load user details: ${data.error || 'Unknown error'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading user details:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading user details. Please try again.
                </div>
            `;
        });
}

// Switch to user
function switchToUser(userId, userName) {
    if (confirm(`Are you sure you want to switch to user "${userName}"?\n\nThis will open a new tab with you logged in as this user.`)) {
        // Show loading state
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Switching...';
        btn.disabled = true;
        
        fetch(`/admin/users/switch/${userId}`, {
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
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            
            if (data.success && data.switch_url) {
                // Open switch URL in new tab
                window.open(data.switch_url, '_blank');
                
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    Successfully switched to user "${userName}". Check the new tab that opened.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container').prepend(alertDiv);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            } else {
                alert('Failed to switch to user: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Switch error:', error);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Error switching to user. Please try again.');
        });
    }
}

// Switch from modal
function switchFromModal() {
    if (currentUserId && currentUserName) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('userDetailsModal'));
        modal.hide();
        switchToUser(currentUserId, currentUserName);
    }
}

// Add CSRF token to meta if not present
if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = '{{ csrf_token() }}';
    document.head.appendChild(meta);
}
</script>
@endsection

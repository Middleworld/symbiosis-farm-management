<div class="col-md-6 col-lg-4 mb-3">
    <div class="card h-100 user-card">
        <div class="card-body">
            <h6 class="card-title">{{ $user['display_name'] ?? $user['username'] ?? 'N/A' }}</h6>
            <div class="card-text">
                <small class="text-muted">ID: {{ $user['id'] ?? 'N/A' }}</small><br>
                
                @if(isset($user['email']))
                    <div class="mb-1">
                        <i class="fas fa-envelope text-muted"></i>
                        <small>{{ $user['email'] }}</small>
                    </div>
                @endif
                
                @if(isset($user['username']))
                    <div class="mb-1">
                        <i class="fas fa-user text-muted"></i>
                        <small>{{ $user['username'] }}</small>
                    </div>
                @endif
                
                @if(isset($user['last_order_date']))
                    <div class="mb-1">
                        <i class="fas fa-shopping-cart text-muted"></i>
                        <small>Last order: {{ $user['last_order_date'] }}</small>
                    </div>
                @endif
                
                @if(isset($user['total_orders']))
                    <div class="mb-1">
                        <i class="fas fa-list-ol text-muted"></i>
                        <small>{{ $user['total_orders'] }} orders</small>
                    </div>
                @endif
                
                @if(isset($user['account_balance']))
                    <div class="mb-1">
                        <i class="fas fa-pound-sign text-muted"></i>
                        <small>Balance: £{{ number_format($user['account_balance'], 2) }}</small>
                    </div>
                @endif
            </div>
            
            <div class="mt-3">
                <button type="button" 
                        class="btn btn-primary btn-sm switch-user-btn w-100" 
                        data-user-id="{{ $user['id'] }}" 
                        data-user-name="{{ $user['display_name'] ?? $user['username'] ?? 'Customer' }}">
                    <i class="fas fa-user-switch"></i> Switch to This User
                </button>
            </div>
        </div>
    </div>
</div>

@extends('layouts.customer')

@section('title', 'Subscription Details')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12 mb-3">
            <a href="{{ route('customer.subscriptions.index') }}" class="btn btn-outline-secondary">
                ← Back to My Subscriptions
            </a>
        </div>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <div class="row">
        <!-- Main Subscription Details -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-{{ $subscription->isActive() ? 'success' : ($subscription->isPaused() ? 'warning' : 'secondary') }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ $subscription->plan->name ?? 'Vegbox Subscription' }}</h4>
                        @if($subscription->isPaused())
                            <span class="badge bg-light text-dark">Paused</span>
                        @elseif($subscription->canceled_at)
                            <span class="badge bg-light text-dark">Cancelled</span>
                        @else
                            <span class="badge bg-light text-dark">Active</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Price</label>
                            <div class="h5">£{{ number_format($subscription->price, 2) }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Billing Frequency</label>
                            <div>{{ ucfirst($subscription->billing_frequency) }} {{ $subscription->billing_period }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Delivery Day</label>
                            <div>{{ ucfirst($subscription->delivery_day ?? 'Not set') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Started</label>
                            <div>{{ $subscription->starts_at ? $subscription->starts_at->format('F j, Y') : 'N/A' }}</div>
                        </div>
                        
                        @if($subscription->isActive())
                            <div class="col-md-6">
                                <label class="text-muted small">Next Billing</label>
                                <div>{{ $subscription->next_billing_at ? $subscription->next_billing_at->format('F j, Y') : 'Not scheduled' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Next Delivery</label>
                                <div>{{ $subscription->next_delivery_date ? $subscription->next_delivery_date->format('F j, Y') : 'Not scheduled' }}</div>
                            </div>
                        @endif
                        
                        @if($subscription->isPaused())
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <strong>Your subscription is paused until {{ $subscription->pause_until->format('F j, Y') }}</strong>
                                    <p class="mb-0 mt-2">You won't be charged or receive deliveries during this time.</p>
                                </div>
                            </div>
                        @endif
                        
                        @if($subscription->canceled_at)
                            <div class="col-12">
                                <div class="alert alert-secondary">
                                    <strong>Your subscription has been cancelled</strong>
                                    <p class="mb-0 mt-2">
                                        @if($subscription->cancels_at)
                                            Your subscription will remain active until {{ $subscription->cancels_at->format('F j, Y') }}
                                        @else
                                            Your subscription is no longer active
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Delivery Address -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Delivery Address</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Contact support to update your delivery address.</p>
                    <!-- TODO: Add address update form when delivery_addresses table is created -->
                </div>
            </div>
            
            <!-- Payment History -->
            @if(!empty($payments))
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                <tr>
                                    <td>{{ $payment['date'] }}</td>
                                    <td>£{{ number_format($payment['amount'], 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $payment['status'] === 'completed' ? 'success' : 'danger' }}">
                                            {{ ucfirst($payment['status']) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Actions Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Manage Subscription</h5>
                </div>
                <div class="card-body">
                    @if($subscription->isActive() && !$subscription->canceled_at)
                        <!-- Change Delivery Day -->
                        <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#changeDeliveryDayModal">
                            Change Delivery Day
                        </button>
                        
                        <!-- Pause Subscription -->
                        @if(!$subscription->isPaused())
                        <button class="btn btn-outline-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#pauseSubscriptionModal">
                            Pause Subscription
                        </button>
                        @else
                        <!-- Resume Subscription -->
                        <form action="{{ route('customer.subscriptions.resume', $subscription->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-success w-100 mb-2">
                                Resume Subscription
                            </button>
                        </form>
                        @endif
                        
                        <!-- Cancel Subscription -->
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelSubscriptionModal">
                            Cancel Subscription
                        </button>
                    @elseif($subscription->canceled_at && $subscription->cancels_at && $subscription->cancels_at->isFuture())
                        <div class="alert alert-info">
                            Your subscription will end on {{ $subscription->cancels_at->format('F j, Y') }}
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Need Help -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Need Help?</h5>
                </div>
                <div class="card-body">
                    <p class="small mb-2">Contact us for assistance:</p>
                    <p class="small mb-1"><strong>Email:</strong> info@middleworldfarms.org</p>
                    <p class="small mb-0"><strong>Phone:</strong> 01234 567890</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Delivery Day Modal -->
<div class="modal fade" id="changeDeliveryDayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('customer.subscriptions.update-delivery-day', $subscription->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Change Delivery Day</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select new delivery day:</label>
                        <select name="delivery_day" class="form-select" required>
                            <option value="">Choose day...</option>
                            <option value="monday" {{ $subscription->delivery_day === 'monday' ? 'selected' : '' }}>Monday</option>
                            <option value="tuesday" {{ $subscription->delivery_day === 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="wednesday" {{ $subscription->delivery_day === 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="thursday" {{ $subscription->delivery_day === 'thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="friday" {{ $subscription->delivery_day === 'friday' ? 'selected' : '' }}>Friday</option>
                            <option value="saturday" {{ $subscription->delivery_day === 'saturday' ? 'selected' : '' }}>Saturday</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Delivery Day</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pause Subscription Modal -->
<div class="modal fade" id="pauseSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('customer.subscriptions.pause', $subscription->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Pause Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You can pause your subscription temporarily. You won't be charged or receive deliveries during this time.</p>
                    <div class="mb-3">
                        <label class="form-label">Resume on:</label>
                        <input type="date" name="pause_until" class="form-control" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Pause Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Subscription Modal -->
<div class="modal fade" id="cancelSubscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('customer.subscriptions.cancel', $subscription->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Are you sure?</strong>
                        <p class="mb-0 mt-2">Your subscription will remain active until the end of your current billing period.</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tell us why (optional):</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Help us improve..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Subscription</button>
                    <button type="submit" class="btn btn-danger">Cancel Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

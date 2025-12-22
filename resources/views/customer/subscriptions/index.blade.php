@extends('layouts.customer')

@section('title', 'My Subscriptions')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">My Subscriptions</h1>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if($subscriptions->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5">
                        <svg class="mb-3" width="64" height="64" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5z"/>
                        </svg>
                        <h4>No Active Subscriptions</h4>
                        <p class="text-muted">You don't have any subscriptions yet.</p>
                        <a href="https://middleworldfarms.org/shop" class="btn btn-primary mt-3">
                            Browse Our Vegboxes
                        </a>
                    </div>
                </div>
            @else
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    @foreach($subscriptions as $subscription)
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-{{ $subscription->isActive() ? 'success' : ($subscription->isPaused() ? 'warning' : 'secondary') }} text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">{{ $subscription->plan->name ?? 'Vegbox Subscription' }}</h5>
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
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Price:</span>
                                            <strong>£{{ number_format($subscription->price, 2) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Frequency:</span>
                                            <span>{{ ucfirst($subscription->billing_frequency) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Delivery Day:</span>
                                            <span>{{ ucfirst($subscription->delivery_day ?? 'Not set') }}</span>
                                        </div>
                                    </div>
                                    
                                    @if($subscription->isPaused())
                                        <div class="alert alert-warning py-2 mb-3">
                                            <small>
                                                <strong>Paused until:</strong><br>
                                                {{ $subscription->pause_until->format('F j, Y') }}
                                            </small>
                                        </div>
                                    @elseif($subscription->isActive())
                                        <div class="alert alert-info py-2 mb-3">
                                            <small>
                                                <strong>Next delivery:</strong><br>
                                                {{ $subscription->next_delivery_date ? $subscription->next_delivery_date->format('F j, Y') : 'Not scheduled' }}
                                            </small>
                                        </div>
                                    @elseif($subscription->canceled_at)
                                        <div class="alert alert-secondary py-2 mb-3">
                                            <small>
                                                <strong>Ends:</strong><br>
                                                {{ $subscription->cancels_at ? $subscription->cancels_at->format('F j, Y') : 'Cancelled' }}
                                            </small>
                                        </div>
                                    @endif
                                    
                                    <a href="{{ route('customer.subscriptions.show', $subscription->id) }}" class="btn btn-outline-primary w-100">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

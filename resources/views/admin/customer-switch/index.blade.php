@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <!-- Title Section -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Customer Account Switching</h1>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" id="testApiBtn">
                            <i class="fas fa-plug"></i> Test API
                        </button>
                        <a href="{{ route('admin.deliveries.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Back to Deliveries
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="row">
            <!-- Search Customers Section -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-search"></i> Search Customers
                        </h5>
                    </div>
                    <div class="card-body">
                        <input type="text"
                               class="form-control form-control-lg"
                               id="searchQuery"
                               placeholder="Search by name, email, or username..."
                               autocomplete="off">
                    </div>
                </div>
            </div>

            <!-- Recent Customers Section -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock"></i> Recent Customers
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(isset($recentUsers['success']) && $recentUsers['success'] && !empty($recentUsers['users']))
                            <div class="list-group list-group-flush">
                                @foreach($recentUsers['users'] as $user)
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <strong>{{ $user['display_name'] }}</strong><br>
                                            <small class="text-muted">{{ $user['user_email'] }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <p>No recent customers found</p>
                                @if(isset($recentUsers['message']))
                                    <small class="text-danger">{{ $recentUsers['message'] }}</small>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
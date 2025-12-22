@extends('layouts.app')

@section('title', 'FarmOS Integration - Harvest & Stock Management')

@section('page-header')
    <h1>🌱 FarmOS Integration</h1>
    <p class="lead">Automated harvest logs and stock management from your FarmOS installation</p>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Test Data Warning --}}
    @if($hasTestData)
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">⚠️ Test Data Active</h5>
                <p class="mb-2">
                    This system currently contains <strong>TEST DATA</strong> with "TEST -" prefixes. 
                    This is safe development data that can be easily removed.
                </p>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearTestData()">
                    <i class="fas fa-trash"></i> Clear All Test Data
                </button>
                <small class="text-muted d-block mt-2">
                    Real FarmOS data will not have "TEST -" prefixes and will be preserved.
                </small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <!-- Stats Cards -->
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-0">Recent Harvests</h5>
                            <h2 class="mb-0">{{ $stats['recent_harvests'] }}</h2>
                            <small>Last 7 days</small>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-seedling fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-0">Unsynced Harvests</h5>
                            <h2 class="mb-0">{{ $stats['unsynced_harvests'] }}</h2>
                            <small>Need processing</small>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-sync-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-0">Stock Items</h5>
                            <h2 class="mb-0">{{ $stats['total_stock_items'] }}</h2>
                            <small>Active products</small>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-boxes fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-0">Low Stock</h5>
                            <h2 class="mb-0">{{ $stats['low_stock_items'] }}</h2>
                            <small>Need attention</small>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary" id="syncHarvestsBtn">
                            <i class="fas fa-sync"></i> Sync New Harvests
                        </button>
                        <a href="{{ route('admin.farmos.harvests') }}" class="btn btn-outline-success">
                            <i class="fas fa-seedling"></i> View All Harvests
                        </a>
                        <a href="{{ route('admin.farmos.stock') }}" class="btn btn-outline-info">
                            <i class="fas fa-boxes"></i> Manage Stock
                        </a>
                        <a href="{{ route('admin.farmos.crop-plans') }}" class="btn btn-outline-warning">
                            <i class="fas fa-calendar-alt"></i> Crop Planning
                        </a>
                        <a href="{{ route('admin.farmos.planting-chart') }}" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line"></i> Planting Timeline
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Harvests -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-seedling"></i> Recent Harvests
                    </h5>
                </div>
                <div class="card-body">
                    @if($recentHarvests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Crop</th>
                                        <th>Quantity</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentHarvests as $harvest)
                                    <tr>
                                        <td>
                                            <strong>
                                                @if(is_array($harvest))
                                                    {{ $harvest['crop_name'] ?? $harvest['name'] ?? 'Unknown Crop' }}
                                                @else
                                                    {{ $harvest->crop_name ?? 'Unknown Crop' }}
                                                @endif
                                            </strong>
                                            @if(is_array($harvest))
                                                @if(isset($harvest['crop_type']) && $harvest['crop_type'])
                                                    <br><small class="text-muted">{{ $harvest['crop_type'] }}</small>
                                                @endif
                                            @else
                                                @if($harvest->crop_type)
                                                    <br><small class="text-muted">{{ $harvest->crop_type }}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if(is_array($harvest))
                                                {{ $harvest['formatted_quantity'] ?? $harvest['quantity'] ?? 'N/A' }}
                                            @else
                                                {{ $harvest->formatted_quantity ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(is_array($harvest))
                                                @php
                                                    $date = $harvest['harvest_date'] ?? $harvest['timestamp'] ?? $harvest['date'] ?? null;
                                                    $dateObj = null;
                                                    if ($date) {
                                                        try {
                                                            $dateObj = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
                                                        } catch (\Exception $e) {
                                                            $dateObj = null;
                                                        }
                                                    }
                                                @endphp
                                                {{ $dateObj ? $dateObj->format('M j') : 'N/A' }}
                                                @if($dateObj && $dateObj->isToday())
                                                    <span class="badge bg-success">Today</span>
                                                @endif
                                            @else
                                                {{ $harvest->harvest_date ? $harvest->harvest_date->format('M j') : 'N/A' }}
                                                @if($harvest->harvest_date && $harvest->harvest_date->isToday())
                                                    <span class="badge bg-success">Today</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if(is_array($harvest))
                                                @if(isset($harvest['synced_to_stock']) && $harvest['synced_to_stock'])
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Synced
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock"></i> Pending
                                                    </span>
                                                @endif
                                            @else
                                                @if($harvest->synced_to_stock ?? false)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Synced
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock"></i> Pending
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.farmos.harvests') }}" class="btn btn-sm btn-outline-primary">
                                View All Harvests
                            </a>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-seedling fa-3x mb-3"></i>
                            <p>No recent harvests found</p>
                            <button class="btn btn-primary" id="syncHarvestsBtn2">
                                <i class="fas fa-sync"></i> Sync from FarmOS
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle text-danger"></i> Low Stock Items
                    </h5>
                </div>
                <div class="card-body">
                    @if($lowStockItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Available</th>
                                        <th>Minimum</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockItems as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->name }}</strong>
                                            @if($item->crop_type)
                                                <br><small class="text-muted">{{ $item->crop_type }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->formatted_available_stock }}</td>
                                        <td>{{ number_format($item->minimum_stock, 2) }} {{ $item->units }}</td>
                                        <td>
                                            <span class="badge bg-{{ $item->stock_status === 'low' ? 'danger' : 'warning' }}">
                                                {{ ucfirst($item->stock_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.farmos.stock', ['low_stock_only' => 1]) }}" class="btn btn-sm btn-outline-danger">
                                View All Low Stock
                            </a>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <p>All stock levels are good!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Harvests -->
    @if($upcomingHarvests->count() > 0)
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> Upcoming Harvests
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Crop</th>
                                    <th>Variety</th>
                                    <th>Planned Start</th>
                                    <th>Planned End</th>
                                    <th>Expected Yield</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingHarvests as $plan)
                                <tr>
                                    <td><strong>{{ $plan->crop_name }}</strong></td>
                                    <td>{{ $plan->variety ?? '-' }}</td>
                                    <td>{{ $plan->planned_harvest_start ? $plan->planned_harvest_start->format('M j') : '-' }}</td>
                                    <td>{{ $plan->planned_harvest_end ? $plan->planned_harvest_end->format('M j') : '-' }}</td>
                                    <td>{{ $plan->expected_yield ? number_format($plan->expected_yield, 2) . ' ' . $plan->yield_units : '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $plan->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($plan->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.farmos.crop-plans') }}" class="btn btn-sm btn-outline-primary">
                            View All Crop Plans
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sync harvests buttons
        document.querySelectorAll('#syncHarvestsBtn, #syncHarvestsBtn2').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const button = this;
                const originalText = button.innerHTML;
                
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
                
                fetch('{{ route("admin.farmos.sync-harvests") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Success: ${data.message}`);
                        location.reload(); // Refresh to show new data
                    } else {
                        alert(`Error: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error syncing harvests. Please try again.');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        });
    });
    </script>
</div>
@endsection

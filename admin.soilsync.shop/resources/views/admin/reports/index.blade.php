@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-bar"></i> Farm Reports</h1>
        
        <div class="btn-group">
            <button class="btn btn-outline-primary" onclick="changeDateRange(7)">7 Days</button>
            <button class="btn btn-outline-primary active" onclick="changeDateRange(30)">30 Days</button>
            <button class="btn btn-outline-primary" onclick="changeDateRange(90)">90 Days</button>
            <button class="btn btn-outline-primary" onclick="changeDateRange(365)">1 Year</button>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Deliveries</h6>
                            <h2 class="mb-0">{{ $deliveryStats['total'] }}</h2>
                        </div>
                        <i class="fas fa-truck fa-3x opacity-50"></i>
                    </div>
                    <small>Avg time: {{ $deliveryStats['average_time'] }}min</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Tasks Completed</h6>
                            <h2 class="mb-0">{{ $taskStats['completed'] }}/{{ $taskStats['total'] }}</h2>
                        </div>
                        <i class="fas fa-tasks fa-3x opacity-50"></i>
                    </div>
                    <small>Completion rate: {{ $taskStats['completion_rate'] }}%</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Harvests</h6>
                            <h2 class="mb-0">{{ $harvestStats['total'] }}</h2>
                        </div>
                        <i class="fas fa-seedling fa-3x opacity-50"></i>
                    </div>
                    <small>Total weight: {{ $harvestStats['total_weight'] }}kg</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Active Customers</h6>
                            <h2 class="mb-0">{{ $customerStats['active_customers'] }}</h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                    <small>Total orders: {{ $customerStats['total_orders'] }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="row">
        <!-- Deliveries by Week Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Deliveries by Week</h5>
                    <a href="{{ route('admin.reports.export', ['type' => 'deliveries', 'range' => $dateRange]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="deliveriesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tasks by Category Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Tasks by Category</h5>
                    <a href="{{ route('admin.reports.export', ['type' => 'tasks', 'range' => $dateRange]) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="tasksChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Harvests Table -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Harvests by Variety</h5>
                    <a href="{{ route('admin.reports.export', ['type' => 'harvests', 'range' => $dateRange]) }}" class="btn btn-sm btn-info">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Crop Name</th>
                                    <th>Type</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($harvestStats['by_variety'] as $harvest)
                                <tr>
                                    <td>{{ $harvest->crop_name }}</td>
                                    <td>{{ $harvest->crop_type }}</td>
                                    <td class="text-end">{{ round($harvest->total_quantity, 2) }} <small class="text-muted">{{ $harvest->units }}</small></td>
                                    <td class="text-end">{{ $harvest->count }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No harvest data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers Table -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-star"></i> Top Customers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-end">Deliveries</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deliveryStats['top_customers'] as $customer)
                                <tr>
                                    <td>{{ $customer->name }}</td>
                                    <td class="text-end">{{ $customer->deliveries }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No customer data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Stats -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Task Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>By Priority</h6>
                            <div style="height: 200px;">
                                <canvas id="tasksPriorityChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6>By Category</h6>
                            <ul class="list-group list-group-flush">
                                @foreach($taskStats['by_category'] as $cat)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ ucfirst($cat->category ?? 'Uncategorized') }}
                                    <span class="badge bg-primary rounded-pill">{{ $cat->count }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning" role="alert">
                                <h6><i class="fas fa-exclamation-triangle"></i> Overdue Tasks</h6>
                                <h3>{{ $taskStats['overdue'] }}</h3>
                                <small>Tasks require attention</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Ensure charts don't create excessive height */
    #deliveriesChart,
    #tasksChart,
    #tasksPriorityChart {
        max-height: 100% !important;
        width: 100% !important;
    }
    
    /* Prevent container from growing unnecessarily */
    .card-body {
        overflow: hidden;
        position: relative;
    }
    
    /* Ensure container-fluid doesn't overflow */
    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    /* Prevent any rogue elements from creating space */
    .row {
        margin-left: 0;
        margin-right: 0;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Date range selector
function changeDateRange(days) {
    window.location.href = '{{ route("admin.reports") }}?range=' + days;
}

// Deliveries by Week Chart
const deliveriesCtx = document.getElementById('deliveriesChart');
if (deliveriesCtx) {
    new Chart(deliveriesCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($deliveryStats['by_week']->pluck('week_number')) !!},
            datasets: [{
                label: 'Deliveries',
                data: {!! json_encode($deliveryStats['by_week']->pluck('count')) !!},
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Tasks by Category Chart
const tasksCtx = document.getElementById('tasksChart');
if (tasksCtx) {
    new Chart(tasksCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($taskStats['by_category']->pluck('category')->map(fn($c) => ucfirst($c ?? 'Uncategorized'))) !!},
            datasets: [{
                data: {!! json_encode($taskStats['by_category']->pluck('count')) !!},
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Tasks Priority Chart
const tasksPriorityCtx = document.getElementById('tasksPriorityChart');
if (tasksPriorityCtx) {
    new Chart(tasksPriorityCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($taskStats['by_priority']->pluck('priority')->map(fn($p) => ucfirst($p ?? 'Normal'))) !!},
            datasets: [{
                label: 'Tasks',
                data: {!! json_encode($taskStats['by_priority']->pluck('count')) !!},
                backgroundColor: [
                    'rgba(220, 53, 69, 0.5)',
                    'rgba(255, 193, 7, 0.5)',
                    'rgba(40, 167, 69, 0.5)',
                ],
                borderColor: [
                    'rgba(220, 53, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(40, 167, 69, 1)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}
</script>
@endpush
@endsection
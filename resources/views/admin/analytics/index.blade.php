@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-line"></i> Farm Analytics</h1>
        
        <div class="btn-group">
            <button class="btn btn-outline-primary" onclick="changeDateRange(7)">7 Days</button>
            <button class="btn btn-outline-primary active" onclick="changeDateRange(30)">30 Days</button>
            <button class="btn btn-outline-primary" onclick="changeDateRange(90)">90 Days</button>
            <button class="btn btn-outline-primary" onclick="changeDateRange(365)">1 Year</button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-3x text-primary mb-2"></i>
                    <h6 class="text-muted">Customer Retention</h6>
                    <h2 class="text-primary">{{ $kpis['customer_retention_rate'] }}%</h2>
                    <small>Returning customers</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                    <h6 class="text-muted">Task Completion Rate</h6>
                    <h2 class="text-success">{{ $kpis['task_completion_rate'] }}%</h2>
                    <small>{{ $kpis['completed_tasks'] }}/{{ $kpis['total_tasks'] }} tasks</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-leaf fa-3x text-info mb-2"></i>
                    <h6 class="text-muted">Harvest Efficiency</h6>
                    <h2 class="text-info">{{ $kpis['harvest_efficiency'] }}kg</h2>
                    <small>per week average</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-robot fa-3x text-warning mb-2"></i>
                    <h6 class="text-muted">AI Requests</h6>
                    <h2 class="text-warning">{{ $kpis['ai_requests'] }}</h2>
                    <small>Total interactions</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Trends -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Customer Growth</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="customerGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Task Trends</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="taskTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Analytics -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-brain"></i> AI Usage Analytics</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Requests by Type</h6>
                            <div style="height: 250px;">
                                <canvas id="aiRequestsTypeChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Daily AI Activity</h6>
                            <div style="height: 250px;">
                                <canvas id="aiDailyChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Average Response Time</h6>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ min($aiStats['avg_response_time'] * 20, 100) }}%">
                                    {{ $aiStats['avg_response_time'] }}s
                                    @if($aiStats['avg_response_time'] < 1)
                                        <small>({{ round($aiStats['avg_response_time'] * 1000) }}ms)</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-medal"></i> Top AI Users</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th class="text-end">Requests</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($aiStats['top_users'] as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td class="text-end"><span class="badge bg-primary">{{ $user->request_count }}</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">
                                        <i class="fas fa-user-slash mb-2"></i><br>
                                        No authenticated user requests yet
                                    </td>
                                </tr>
                                @endforelse
                                @if($aiStats['anonymous_requests'] > 0)
                                <tr class="table-secondary">
                                    <td><i class="fas fa-robot"></i> System/Anonymous</td>
                                    <td class="text-end"><span class="badge bg-secondary">{{ $aiStats['anonymous_requests'] }}</span></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Productivity Metrics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-seedling"></i> Crop Diversity</h5>
                </div>
                <div class="card-body">
                    <h3 class="text-center mb-3">{{ $productivity['crop_diversity'] }} varieties</h3>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Top Varieties</th>
                                    <th class="text-end">Harvests</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productivity['top_varieties'] as $variety)
                                <tr>
                                    <td>{{ $variety->variety_name }}</td>
                                    <td class="text-end">{{ $variety->harvest_count }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Task Efficiency</h5>
                </div>
                <div class="card-body">
                    <h6>Average Completion Time</h6>
                    <h3 class="text-center text-success mb-3">{{ $productivity['avg_completion_time'] }} days</h3>
                    
                    <h6 class="mt-4">Task Categories</h6>
                    <div style="height: 250px;">
                        <canvas id="taskCategoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Harvest & Delivery Trends -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-area"></i> Harvest Trends</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="harvestTrendsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-truck"></i> Delivery Trends</h5>
                </div>
                <div class="card-body" style="height: 300px;">
                    <canvas id="deliveryTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Metrics -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-circle pulse"></i> Real-time Metrics 
                        <small class="float-end">Updates every 30s</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h6 class="text-muted">Active Users</h6>
                            <h2 id="activeUsers">-</h2>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Today's Tasks</h6>
                            <h2 id="todayTasks">-</h2>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Today's Harvests</h6>
                            <h2 id="todayHarvests">-</h2>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">Today's Deliveries</h6>
                            <h2 id="todayDeliveries">-</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Prevent excessive scrolling and chart overflow */
canvas {
    max-height: 100% !important;
}

.card-body {
    overflow: hidden;
}

.container-fluid {
    overflow-x: hidden;
}

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
    window.location.href = '{{ route("admin.analytics") }}?range=' + days;
}

// Customer Growth Chart
const customerGrowthCtx = document.getElementById('customerGrowthChart').getContext('2d');
new Chart(customerGrowthCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($trends['customer_growth']->pluck('date')) !!},
        datasets: [{
            label: 'New Customers',
            data: {!! json_encode($trends['customer_growth']->pluck('count')) !!},
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Task Trends Chart
const taskTrendsCtx = document.getElementById('taskTrendsChart').getContext('2d');
new Chart(taskTrendsCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($trends['task_trends']->pluck('date')) !!},
        datasets: [{
            label: 'Tasks Created',
            data: {!! json_encode($trends['task_trends']->pluck('count')) !!},
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// AI Requests by Type
const aiTypeCtx = document.getElementById('aiRequestsTypeChart').getContext('2d');
new Chart(aiTypeCtx, {
    type: 'pie',
    data: {
        labels: {!! json_encode($aiStats['by_type']->pluck('request_type')) !!},
        datasets: [{
            data: {!! json_encode($aiStats['by_type']->pluck('count')) !!},
            backgroundColor: [
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)',
                'rgba(75, 192, 192, 0.5)',
                'rgba(153, 102, 255, 0.5)',
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// AI Daily Activity
const aiDailyCtx = document.getElementById('aiDailyChart').getContext('2d');
new Chart(aiDailyCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($aiStats['by_day']->pluck('date')) !!},
        datasets: [{
            label: 'Requests',
            data: {!! json_encode($aiStats['by_day']->pluck('count')) !!},
            backgroundColor: 'rgba(255, 206, 86, 0.5)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Task Categories Chart
const taskCatCtx = document.getElementById('taskCategoriesChart').getContext('2d');
new Chart(taskCatCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($productivity['task_categories']->pluck('category')->map(fn($c) => ucfirst($c ?? 'Uncategorized'))) !!},
        datasets: [{
            data: {!! json_encode($productivity['task_categories']->pluck('count')) !!},
            backgroundColor: [
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)',
                'rgba(75, 192, 192, 0.5)',
                'rgba(153, 102, 255, 0.5)',
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Harvest Trends Chart
const harvestTrendsCtx = document.getElementById('harvestTrendsChart').getContext('2d');
new Chart(harvestTrendsCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($trends['harvest_trends']->pluck('date')) !!},
        datasets: [{
            label: 'Harvests',
            data: {!! json_encode($trends['harvest_trends']->pluck('count')) !!},
            borderColor: 'rgba(40, 167, 69, 1)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Delivery Trends Chart
const deliveryTrendsCtx = document.getElementById('deliveryTrendsChart').getContext('2d');
new Chart(deliveryTrendsCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($trends['delivery_trends']->pluck('date')) !!},
        datasets: [{
            label: 'Deliveries',
            data: {!! json_encode($trends['delivery_trends']->pluck('count')) !!},
            borderColor: 'rgba(220, 53, 69, 1)',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Real-time metrics update
function updateRealTimeMetrics() {
    fetch('{{ route("admin.analytics.realtime") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('activeUsers').textContent = data.active_users;
            document.getElementById('todayTasks').textContent = data.today_tasks;
            document.getElementById('todayHarvests').textContent = data.today_harvests;
            document.getElementById('todayDeliveries').textContent = data.today_deliveries;
        })
        .catch(error => console.error('Error fetching real-time metrics:', error));
}

// Update on load and every 30 seconds
updateRealTimeMetrics();
setInterval(updateRealTimeMetrics, 30000);
</script>
@endpush
@endsection
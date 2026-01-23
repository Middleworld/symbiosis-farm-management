@extends('layouts.app')

@section('title', 'ROS Swarm')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1">ROS Swarm</h2>
            <p class="text-muted mb-0">Simulation status and bridge configuration.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Swarm (3 bots)</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Gazebo sim running three robots: <strong>amiga_01</strong>, <strong>amiga_02</strong>, <strong>amiga_03</strong>.</p>
                    <ul class="mb-0">
                        <li>Launch: <strong>ros2 launch mwf_farm_sim sim.launch.py</strong></li>
                        <li>World: <strong>mwf_farm.world</strong></li>
                        <li>URDF: <strong>amiga_base.urdf</strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>Laravel Bridge</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Bridge posts telemetry to Laravel.</p>
                    <ul class="mb-3">
                        <li>Endpoint: <strong>/api/ros/telemetry</strong></li>
                        <li>Header: <strong>X-ROS-API-Key</strong> (optional)</li>
                        <li>Env: <strong>LARAVEL_BASE_URL</strong>, <strong>LARAVEL_ROS_ENDPOINT</strong>, <strong>LARAVEL_API_KEY</strong></li>
                    </ul>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-1"></i>Telemetry endpoint is active and receiving data!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Telemetry Dashboard Section -->
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Live Telemetry</h5>
                    <button id="refresh-telemetry" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div id="telemetry-loading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading telemetry data...</p>
                    </div>

                    <div id="telemetry-content" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">Source</h6>
                                    <span id="telemetry-source" class="h5 text-primary">gazebo</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">Last Update</h6>
                                    <span id="telemetry-timestamp" class="h5 text-success">-</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">Active Robots</h6>
                                    <span id="telemetry-robot-count" class="h5 text-info">3</span>
                                </div>
                            </div>
                        </div>

                        <div id="robot-positions" class="row">
                            <!-- Robot cards will be populated here -->
                        </div>
                    </div>

                    <div id="telemetry-error" class="alert alert-warning" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="error-message">Unable to load telemetry data</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadTelemetry();

    // Refresh button
    document.getElementById('refresh-telemetry').addEventListener('click', loadTelemetry);

    // Auto-refresh every 10 seconds
    setInterval(loadTelemetry, 10000);
});

function loadTelemetry() {
    const loading = document.getElementById('telemetry-loading');
    const content = document.getElementById('telemetry-content');
    const error = document.getElementById('telemetry-error');

    loading.style.display = 'block';
    content.style.display = 'none';
    error.style.display = 'none';

    fetch('/api/ros/telemetry')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            displayTelemetry(data);
            loading.style.display = 'none';
            content.style.display = 'block';
        })
        .catch(err => {
            document.getElementById('error-message').textContent = err.message;
            loading.style.display = 'none';
            error.style.display = 'block';
        });
}

function displayTelemetry(data) {
    document.getElementById('telemetry-source').textContent = data.source || 'Unknown';
    document.getElementById('telemetry-timestamp').textContent = new Date(data.timestamp_ms).toLocaleTimeString();
    document.getElementById('telemetry-robot-count').textContent = data.robots ? data.robots.length : 0;

    const positionsDiv = document.getElementById('robot-positions');
    positionsDiv.innerHTML = '';

    if (data.robots && data.robots.length > 0) {
        data.robots.forEach(robot => {
            const robotCard = document.createElement('div');
            robotCard.className = 'col-md-4 mb-3';
            robotCard.innerHTML = `
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">${robot.name}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <small class="text-muted">Position (m)</small>
                                <div class="row">
                                    <div class="col-4"><strong>X:</strong> ${robot.position.x.toFixed(3)}</div>
                                    <div class="col-4"><strong>Y:</strong> ${robot.position.y.toFixed(3)}</div>
                                    <div class="col-4"><strong>Z:</strong> ${robot.position.z.toFixed(3)}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <small class="text-muted">Linear Velocity (m/s)</small>
                                <div class="row">
                                    <div class="col-4"><strong>X:</strong> ${robot.linear.x.toFixed(3)}</div>
                                    <div class="col-4"><strong>Y:</strong> ${robot.linear.y.toFixed(3)}</div>
                                    <div class="col-4"><strong>Z:</strong> ${robot.linear.z.toFixed(3)}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <small class="text-muted">Angular Velocity (rad/s)</small>
                                <div class="row">
                                    <div class="col-4"><strong>X:</strong> ${robot.angular.x.toFixed(3)}</div>
                                    <div class="col-4"><strong>Y:</strong> ${robot.angular.y.toFixed(3)}</div>
                                    <div class="col-4"><strong>Z:</strong> ${robot.angular.z.toFixed(3)}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            positionsDiv.appendChild(robotCard);
        });
    } else {
        positionsDiv.innerHTML = '<div class="col-12"><div class="alert alert-info">No robot data available</div></div>';
    }
}
</script>
@endsection

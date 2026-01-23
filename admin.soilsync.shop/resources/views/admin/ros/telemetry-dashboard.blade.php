@extends('layouts.app')

@section('title', 'ROS Telemetry Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">ROS Telemetry Dashboard</h4>
                    <p class="card-subtitle">Real-time robot positions from Gazebo simulation</p>
                </div>
                <div class="card-body">
                    <div id="telemetry-status" class="alert alert-info">
                        <i class="fas fa-spinner fa-spin"></i> Loading telemetry data...
                    </div>

                    <div id="telemetry-content" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Simulation Info</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Source:</strong></td>
                                        <td id="source">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Timestamp:</strong></td>
                                        <td id="timestamp">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Robots:</strong></td>
                                        <td id="robot-count">-</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Robot Positions</h5>
                                <div id="robot-positions">
                                    <!-- Robot data will be populated here -->
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Raw Data</h5>
                                <pre id="raw-data" class="bg-light p-3 rounded"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetchTelemetry();

    // Refresh every 5 seconds
    setInterval(fetchTelemetry, 5000);
});

function fetchTelemetry() {
    fetch('/api/ros/telemetry')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch telemetry: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            displayTelemetry(data);
        })
        .catch(error => {
            document.getElementById('telemetry-status').className = 'alert alert-danger';
            document.getElementById('telemetry-status').innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + error.message;
            document.getElementById('telemetry-content').style.display = 'none';
        });
}

function displayTelemetry(data) {
    document.getElementById('telemetry-status').className = 'alert alert-success';
    document.getElementById('telemetry-status').innerHTML = '<i class="fas fa-check-circle"></i> Telemetry data loaded successfully';
    document.getElementById('telemetry-content').style.display = 'block';

    // Update simulation info
    document.getElementById('source').textContent = data.source || 'Unknown';
    document.getElementById('timestamp').textContent = new Date(data.timestamp_ms).toLocaleString();
    document.getElementById('robot-count').textContent = data.robots ? data.robots.length : 0;

    // Update robot positions
    const positionsDiv = document.getElementById('robot-positions');
    positionsDiv.innerHTML = '';

    if (data.robots && data.robots.length > 0) {
        data.robots.forEach(robot => {
            const robotCard = document.createElement('div');
            robotCard.className = 'card mb-2';
            robotCard.innerHTML = `
                <div class="card-body p-2">
                    <h6 class="card-title">${robot.name}</h6>
                    <div class="row">
                        <div class="col-6">
                            <small><strong>Position:</strong><br>
                            X: ${robot.position.x.toFixed(3)}<br>
                            Y: ${robot.position.y.toFixed(3)}<br>
                            Z: ${robot.position.z.toFixed(3)}</small>
                        </div>
                        <div class="col-6">
                            <small><strong>Velocity:</strong><br>
                            Linear: ${robot.linear.x.toFixed(3)}, ${robot.linear.y.toFixed(3)}, ${robot.linear.z.toFixed(3)}<br>
                            Angular: ${robot.angular.x.toFixed(3)}, ${robot.angular.y.toFixed(3)}, ${robot.angular.z.toFixed(3)}</small>
                        </div>
                    </div>
                </div>
            `;
            positionsDiv.appendChild(robotCard);
        });
    } else {
        positionsDiv.innerHTML = '<p class="text-muted">No robot data available</p>';
    }

    // Update raw data
    document.getElementById('raw-data').textContent = JSON.stringify(data, null, 2);
}
</script>
@endsection
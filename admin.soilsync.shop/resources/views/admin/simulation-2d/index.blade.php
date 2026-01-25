@extends('layouts.app')

@section('title', '2D Robot Simulation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">2D Robot Simulation</h3>
                    <div class="card-tools">
                        <button id="initializeBtn" class="btn btn-primary btn-sm">Initialize</button>
                        <button id="startStopBtn" class="btn btn-success btn-sm">Start</button>
                        <button id="stepBtn" class="btn btn-warning btn-sm">Step</button>
                        <button id="resetBtn" class="btn btn-danger btn-sm">Reset</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div style="position: relative;">
                                <!-- Map Background Image -->
                                <img id="mapBackground"
                                     src="https://maps.googleapis.com/maps/api/staticmap?center=52.2053,0.1218&zoom=16&size=800x600&maptype=satellite&key={{ config('services.google_maps.api_key') }}"
                                     style="position: absolute; top: 0; left: 0; z-index: 1; border: 2px solid #007bff; opacity: 0.8;"
                                     alt="Farm Map">

                                <!-- Simulation Canvas Overlay -->
                                <canvas id="simulationCanvas"
                                        width="800"
                                        height="600"
                                        style="position: absolute; top: 0; left: 0; z-index: 2; background: rgba(255, 255, 255, 0.0); border: 1px solid red;">
                                </canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="robotCount">Number of Robots:</label>
                                <input type="number" id="robotCount" class="form-control" value="3" min="1" max="10">
                            </div>
                            <div class="mb-3">
                                <label for="useFarmOS">Use FarmOS World:</label>
                                <input type="checkbox" id="useFarmOS" class="form-check-input">
                            </div>
                            <div class="mb-3">
                                <label for="showMap">Show Map Background:</label>
                                <input type="checkbox" id="showMap" class="form-check-input" checked>
                            </div>
                            <div class="mb-3">
                                <h5>Robot Controls</h5>
                                <div id="robotControls"></div>
                            </div>
                            <div class="mb-3">
                                <h5>Sensor Readings</h5>
                                <div id="sensorReadings"></div>
                            </div>
                            <div class="mb-3">
                                <h5>Communication Log</h5>
                                <div id="commLog" style="height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
class Simulation2D {
    constructor() {
        this.canvas = document.getElementById('simulationCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.isRunning = false;
        this.intervalId = null;
        this.scale = 10; // pixels per meter
        this.state = null;

        this.bindEvents();
    }

    bindEvents() {
        document.getElementById('initializeBtn').addEventListener('click', () => this.initialize());
        document.getElementById('startStopBtn').addEventListener('click', () => this.toggleSimulation());
        document.getElementById('stepBtn').addEventListener('click', () => this.step());
        document.getElementById('resetBtn').addEventListener('click', () => this.reset());
        document.getElementById('showMap').addEventListener('change', () => this.toggleMap());
    }

    async initialize() {
        console.log('Initialize function called');
        alert('Initialize button clicked! Check console for details.');

        const robotCount = document.getElementById('robotCount').value;
        const useFarmOS = document.getElementById('useFarmOS').checked;

        try {
            const response = await fetch('/admin/simulation-2d/initialize', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    robot_count: parseInt(robotCount),
                    use_farmos: useFarmOS
                })
            });

            const data = await response.json();
            this.state = data.state;
            
            // Update map center if provided
            if (data.map_center) {
                this.updateMapImage(data.map_center.lat, data.map_center.lng);
            }
            
            this.render();
            this.updateControls();

            // Test canvas drawing after render
            const canvas = document.getElementById('simulationCanvas');
            const ctx = canvas.getContext('2d');
            console.log('Canvas dimensions:', canvas.width, 'x', canvas.height);
            console.log('Canvas position:', canvas.offsetLeft, canvas.offsetTop);
            // Note: Test rectangle is now drawn in render() function
        } catch (error) {
            console.error('Initialization failed:', error);
        }
    }

    async step() {
        if (!this.state) return;

        try {
            const response = await fetch('/admin/simulation-2d/step', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();
            this.state = data.state;
            this.render();
            this.updateSensorReadings();
        } catch (error) {
            console.error('Step failed:', error);
        }
    }

    toggleSimulation() {
        if (this.isRunning) {
            this.stop();
        } else {
            this.start();
        }
    }

    start() {
        if (!this.state) return;

        this.isRunning = true;
        document.getElementById('startStopBtn').textContent = 'Stop';
        document.getElementById('startStopBtn').className = 'btn btn-danger btn-sm';

        this.intervalId = setInterval(() => this.step(), 100); // 10 FPS
    }

    stop() {
        this.isRunning = false;
        document.getElementById('startStopBtn').textContent = 'Start';
        document.getElementById('startStopBtn').className = 'btn btn-success btn-sm';

        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    reset() {
        this.stop();
        this.state = null;
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        document.getElementById('robotControls').innerHTML = '';
        document.getElementById('sensorReadings').innerHTML = '';
        document.getElementById('commLog').innerHTML = '';
    }

    updateMapImage(lat, lng) {
        const mapImg = document.getElementById('mapBackground');
        console.log('Map background element found:', !!mapImg);
        if (mapImg) {
            console.log('Map background position:', mapImg.offsetLeft, mapImg.offsetTop, 'size:', mapImg.width, 'x', mapImg.height);
        }
        const apiKey = '{{ config("services.google_maps.api_key") }}';
        console.log('Google Maps API Key configured:', apiKey ? 'Yes' : 'No', 'Length:', apiKey.length);
        const url = `https://maps.googleapis.com/maps/api/staticmap?center=${lat},${lng}&zoom=18&size=800x600&maptype=satellite&key=${apiKey}`;
        console.log('Loading map image URL:', url.substring(0, 100) + '...');
        mapImg.onload = function() {
            console.log('Map image loaded successfully, size:', mapImg.naturalWidth, 'x', mapImg.naturalHeight);
        };
        mapImg.onerror = function() {
            console.error('Failed to load map image, showing placeholder...');
            // Create a simple placeholder background
            const canvas = document.createElement('canvas');
            canvas.width = 800;
            canvas.height = 600;
            const ctx = canvas.getContext('2d');

            // Create a simple grass-like background
            ctx.fillStyle = '#90EE90'; // Light green
            ctx.fillRect(0, 0, 800, 600);

            // Add some simple field markings
            ctx.strokeStyle = '#228B22';
            ctx.lineWidth = 2;
            for (let i = 0; i < 800; i += 50) {
                ctx.beginPath();
                ctx.moveTo(i, 0);
                ctx.lineTo(i, 600);
                ctx.stroke();
            }
            for (let i = 0; i < 600; i += 50) {
                ctx.beginPath();
                ctx.moveTo(0, i);
                ctx.lineTo(800, i);
                ctx.stroke();
            }

            // Add text
            ctx.fillStyle = '#000';
            ctx.font = '20px Arial';
            ctx.fillText('Farm Field Layout (Map API not available)', 200, 300);

            mapImg.src = canvas.toDataURL();
            console.log('Placeholder map created');
        };
        mapImg.src = url;
    }

    toggleMap() {
        const showMap = document.getElementById('showMap').checked;
        const mapImg = document.getElementById('mapBackground');
        mapImg.style.display = showMap ? 'block' : 'none';
        this.render(); // Re-render to adjust for map visibility
    }

    render() {
        if (!this.state) {
            return;
        }

        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // If map is hidden, draw a simple background
        if (!document.getElementById('showMap').checked) {
            this.ctx.fillStyle = '#f0f0f0';
            this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        }

        // Draw pheromone grid
        this.renderPheromoneGrid();

        // Draw walls
        this.renderWalls();

        // Draw obstacles
        this.renderObstacles();

        // Draw entities (weeds, crops, targets)
        this.renderEntities();

        // Draw robots
        this.renderRobots();

        // Debug: Draw test rectangle
        this.ctx.fillStyle = 'red';
        this.ctx.fillRect(10, 10, 50, 50);
        console.log('Drew debug rectangle after render');
    }

    renderPheromoneGrid() {
        const grid = this.state.pheromoneGrid;
        const cellSize = this.scale;

        for (let y = 0; y < grid.length; y++) {
            for (let x = 0; x < grid[0].length; x++) {
                const pheromone = grid[y][x];
                if (pheromone > 0.01) {
                    const alpha = Math.min(pheromone * 0.5, 0.8);
                    this.ctx.fillStyle = `rgba(255, 0, 255, ${alpha})`;
                    this.ctx.fillRect(x * cellSize, y * cellSize, cellSize, cellSize);
                }
            }
        }
    }

    renderWalls() {
        this.ctx.strokeStyle = '#ff0000';
        this.ctx.lineWidth = 3;

        // Draw walls as red outlines on the map
        this.state.environment.walls.forEach(wall => {
            this.ctx.beginPath();
            this.ctx.moveTo(wall.x1 * this.scale, wall.y1 * this.scale);
            this.ctx.lineTo(wall.x2 * this.scale, wall.y2 * this.scale);
            this.ctx.stroke();
        });
    }

    renderObstacles() {
        this.ctx.fillStyle = '#666';
        this.ctx.strokeStyle = '#333';
        this.ctx.lineWidth = 1;

        this.state.environment.obstacles.forEach(obstacle => {
            this.ctx.beginPath();
            this.ctx.arc(
                obstacle.x * this.scale,
                obstacle.y * this.scale,
                obstacle.radius * this.scale,
                0, 2 * Math.PI
            );
            this.ctx.fill();
            this.ctx.stroke();
        });
    }

    renderEntities() {
        this.state.environment.entities.forEach(entity => {
            switch (entity.type) {
                case 'weed':
                    this.ctx.fillStyle = '#ff0000';
                    break;
                case 'crop':
                    this.ctx.fillStyle = '#00ff00';
                    break;
                case 'target':
                    this.ctx.fillStyle = '#0000ff';
                    break;
            }

            this.ctx.beginPath();
            this.ctx.arc(
                entity.x * this.scale,
                entity.y * this.scale,
                0.2 * this.scale,
                0, 2 * Math.PI
            );
            this.ctx.fill();
        });
    }

    renderRobots() {
        console.log('Rendering robots, count:', this.state.robots.length);
        this.state.robots.forEach((robot, index) => {
            console.log('Robot', index, 'position:', robot.x, robot.y, 'scale:', this.scale);
            // Robot body
            this.ctx.fillStyle = '#0066ff';
            this.ctx.strokeStyle = '#0044cc';
            this.ctx.lineWidth = 2;
            console.log('Set robot colors - fill:', this.ctx.fillStyle, 'stroke:', this.ctx.strokeStyle);

            this.ctx.beginPath();
            this.ctx.arc(
                robot.x * this.scale,
                robot.y * this.scale,
                (robot.radius * this.scale) * 2,  // Make robots 2x bigger
                0, 2 * Math.PI
            );
            this.ctx.fill();
            this.ctx.stroke();
            console.log('Drew robot', index, 'at', robot.x * this.scale, robot.y * this.scale);

            // Direction indicator
            const dirLength = robot.radius * 3;  // Match the 2x size increase
            this.ctx.strokeStyle = '#fff';
            this.ctx.lineWidth = 3;
            this.ctx.beginPath();
            this.ctx.moveTo(robot.x * this.scale, robot.y * this.scale);
            this.ctx.lineTo(
                (robot.x + Math.cos(robot.theta) * dirLength) * this.scale,
                (robot.y + Math.sin(robot.theta) * dirLength) * this.scale
            );
            this.ctx.stroke();

            // Robot ID
            this.ctx.fillStyle = '#fff';
            this.ctx.font = '12px Arial';
            this.ctx.textAlign = 'center';
            this.ctx.fillText(
                `R${index}`,
                robot.x * this.scale,
                robot.y * this.scale + 4
            );
        });
    }

    updateControls() {
        if (!this.state) return;

        const controlsDiv = document.getElementById('robotControls');
        controlsDiv.innerHTML = '';

        this.state.robots.forEach((robot, index) => {
            const robotDiv = document.createElement('div');
            robotDiv.className = 'mb-2 p-2 border rounded';
            robotDiv.innerHTML = `
                <h6>Robot ${index}</h6>
                <div class="btn-group btn-group-sm mb-1">
                    <button class="btn btn-outline-primary" onclick="sendRobotCommand(${index}, {type: 'cmd_vel', linear: 0.5, angular: 0})">Forward</button>
                    <button class="btn btn-outline-primary" onclick="sendRobotCommand(${index}, {type: 'cmd_vel', linear: -0.5, angular: 0})">Backward</button>
                    <button class="btn btn-outline-primary" onclick="sendRobotCommand(${index}, {type: 'cmd_vel', linear: 0, angular: 1})">Turn Left</button>
                    <button class="btn btn-outline-primary" onclick="sendRobotCommand(${index}, {type: 'cmd_vel', linear: 0, angular: -1})">Turn Right</button>
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-success" onclick="sendRobotCommand(${index}, {type: 'follow_pheromone'})">Follow Pheromone</button>
                    <button class="btn btn-outline-secondary" onclick="sendRobotCommand(${index}, {type: 'stop'})">Stop</button>
                </div>
            `;
            controlsDiv.appendChild(robotDiv);
        });
    }

    updateSensorReadings() {
        if (!this.state) return;

        const readingsDiv = document.getElementById('sensorReadings');
        readingsDiv.innerHTML = '';

        this.state.robots.forEach((robot, index) => {
            const robotDiv = document.createElement('div');
            robotDiv.className = 'mb-2';
            robotDiv.innerHTML = `<h6>Robot ${index} Sensors:</h6>`;

            Object.keys(robot.sensors).forEach(sensorName => {
                const sensor = robot.sensors[sensorName];
                robotDiv.innerHTML += `<small>${sensorName}: ${sensor.reading.toFixed(2)}m</small><br>`;
            });

            readingsDiv.appendChild(robotDiv);
        });
    }

    async sendCommand(robotIndex, command) {
        try {
            await fetch('/admin/simulation-2d/command', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    robot_index: robotIndex,
                    command: command
                })
            });
        } catch (error) {
            console.error('Command failed:', error);
        }
    }
}

// Global function for robot commands
function sendRobotCommand(robotIndex, command) {
    if (window.simulation) {
        window.simulation.sendCommand(robotIndex, command);
    }
}

// Initialize simulation when page loads
let simulation;
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing simulation');
    simulation = new Simulation2D();
    console.log('Simulation object created');
});
</script>
@endsection
@extends('layouts.admin')

@section('title', 'System Updates')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Updates</h3>
                </div>
                
                <div class="card-body">
                    <!-- Current Version -->
                    <div class="mb-4">
                        <h5>Current Version</h5>
                        <p class="text-muted">
                            <strong>System Version:</strong> {{ $current_version['system_version'] ?? 'Unknown' }}<br>
                            <strong>Release Date:</strong> {{ $current_version['release_date'] ?? 'Unknown' }}<br>
                            <strong>Release Name:</strong> {{ $current_version['release_name'] ?? 'N/A' }}
                        </p>
                    </div>
                    
                    <!-- Check for Updates Button -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary" id="checkUpdatesBtn" onclick="checkForUpdates()">
                            <i class="fas fa-sync-alt"></i> Check for Updates
                        </button>
                        <button type="button" class="btn btn-info ml-2" onclick="checkPluginVersions()">
                            <i class="fas fa-plug"></i> Check Plugin Versions
                        </button>
                    </div>
                    
                    <!-- Update Results -->
                    <div id="updateResults" class="d-none">
                        <div class="alert" id="updateAlert" role="alert"></div>
                        
                        <div id="updateDetails"></div>
                    </div>
                    
                    <!-- Plugin Versions -->
                    <div id="pluginVersions" class="d-none">
                        <h5 class="mt-4">WordPress Plugin Versions</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Plugin</th>
                                    <th>Status</th>
                                    <th>Version</th>
                                </tr>
                            </thead>
                            <tbody id="pluginList">
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Update Server Info -->
                    <div class="mt-4">
                        <h6 class="text-muted">Update Server</h6>
                        <p class="small text-muted mb-0">
                            <i class="fas fa-server"></i> {{ $update_server }}<br>
                            <i class="fas fa-info-circle"></i> Updates are checked against the gold master repository
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Component Versions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Installed Components</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Laravel Admin</h6>
                            <ul class="small">
                                <li>Version: {{ $current_version['components']['laravel_admin']['version'] ?? 'Unknown' }}</li>
                                <li>PHP: {{ PHP_VERSION }}</li>
                                <li>Laravel: {{ app()->version() }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>WordPress Plugins</h6>
                            <ul class="small">
                                @foreach($current_version['components']['wordpress_plugins'] ?? [] as $slug => $plugin)
                                    <li>{{ $slug }}: v{{ $plugin['version'] }}</li>
                                @endforeach
                            </ul>
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
function checkForUpdates() {
    const btn = document.getElementById('checkUpdatesBtn');
    const results = document.getElementById('updateResults');
    const alert = document.getElementById('updateAlert');
    const details = document.getElementById('updateDetails');
    
    // Show loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Checking...';
    results.classList.remove('d-none');
    alert.className = 'alert alert-info';
    alert.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking for updates...';
    
    fetch('{{ route("admin.system.updates.check") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Check for Updates';
        
        if (data.success) {
            if (data.has_updates) {
                alert.className = 'alert alert-warning';
                alert.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Update Available!</strong><br>
                    Current: ${data.current_version} → Latest: ${data.latest_version}
                `;
                
                details.innerHTML = `
                    <h6>What's New:</h6>
                    <ul>
                        ${(data.updates.changes || []).map(change => `<li>${change}</li>`).join('')}
                    </ul>
                    <p class="text-muted small">
                        <strong>Note:</strong> Updates must be applied manually. Contact your system administrator or 
                        use the deployment scripts in /scripts/deployment/
                    </p>
                `;
            } else {
                alert.className = 'alert alert-success';
                alert.innerHTML = `
                    <i class="fas fa-check-circle"></i> 
                    <strong>Up to Date!</strong><br>
                    You're running the latest version: ${data.current_version}
                `;
                details.innerHTML = '';
            }
        } else {
            alert.className = 'alert alert-danger';
            alert.innerHTML = `<i class="fas fa-times-circle"></i> ${data.message}`;
            details.innerHTML = '';
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Check for Updates';
        alert.className = 'alert alert-danger';
        alert.innerHTML = `<i class="fas fa-times-circle"></i> Error: ${error.message}`;
    });
}

function checkPluginVersions() {
    const container = document.getElementById('pluginVersions');
    const list = document.getElementById('pluginList');
    
    container.classList.remove('d-none');
    list.innerHTML = '<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i> Checking...</td></tr>';
    
    fetch('{{ route("admin.system.updates.plugins") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            list.innerHTML = '';
            Object.entries(data.plugins).forEach(([slug, plugin]) => {
                const statusBadge = plugin.installed 
                    ? '<span class="badge badge-success">Installed</span>'
                    : '<span class="badge badge-warning">Not Found</span>';
                    
                const version = plugin.version || 'N/A';
                
                list.innerHTML += `
                    <tr>
                        <td>${slug}</td>
                        <td>${statusBadge}</td>
                        <td>${version}</td>
                    </tr>
                `;
            });
        } else {
            list.innerHTML = `<tr><td colspan="3" class="text-danger">${data.message}</td></tr>`;
        }
    })
    .catch(error => {
        list.innerHTML = `<tr><td colspan="3" class="text-danger">Error: ${error.message}</td></tr>`;
    });
}
</script>
@endsection

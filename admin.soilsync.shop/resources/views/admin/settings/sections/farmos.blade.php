<h4 class="mb-3"><i class="fas fa-tractor"></i> farmOS Integration</h4>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-plug"></i> Connection Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>farmOS URL:</strong>
                    @if(config('services.farmos.url'))
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> {{ config('services.farmos.url') }}</span>
                    @else
                        <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Not configured</span>
                    @endif
                </div>
                
                <div class="mb-3">
                    <strong>OAuth Authentication:</strong>
                    @if(config('services.farmos.client_id') && config('services.farmos.client_secret'))
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> OAuth configured</span>
                    @else
                        <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> OAuth not configured</span>
                    @endif
                </div>
                
                <div class="mb-3">
                    <strong>Basic Auth (Fallback):</strong>
                    @if(config('services.farmos.username') && config('services.farmos.password'))
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Credentials configured</span>
                    @else
                        <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Not configured</span>
                    @endif
                </div>

                <div class="alert alert-info mt-3">
                    <small><i class="fas fa-info-circle"></i> API credentials are managed in .env file by administrator</small>
                </div>

                <button type="button" class="btn btn-primary" onclick="testFarmOSConnection()">
                    <i class="fas fa-flask"></i> Test Connection
                </button>
                <div id="farmos-test-result" class="mt-3"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-sync"></i> Sync Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-seedling"></i> Plant Varieties</span>
                        <span class="badge bg-secondary">Last synced: {{ $settings['farmos_last_variety_sync'] ?? 'Never' }}</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-map-marked"></i> Field Beds</span>
                        <span class="badge bg-secondary">Last synced: {{ $settings['farmos_last_bed_sync'] ?? 'Never' }}</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-carrot"></i> Harvests</span>
                        <span class="badge bg-secondary">Last synced: {{ $settings['farmos_last_harvest_sync'] ?? 'Never' }}</span>
                    </div>
                </div>

                <div class="alert alert-warning mt-3">
                    <small><i class="fas fa-exclamation-triangle"></i> Use artisan commands to sync data: <code>php artisan farmos:sync-varieties</code></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testFarmOSConnection() {
    const resultDiv = document.getElementById('farmos-test-result');
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testing API and database connections...</div>';
    
    fetch('/admin/farmos/test-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        let html = '<div class="card">';
        
        // API Test Result
        if (data.results.api.status === 'success') {
            html += `
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success fs-4 me-3"></i>
                        <div>
                            <strong>API Connection</strong>
                            <div class="small text-muted">
                                Version: ${data.results.api.version || 'Unknown'}<br>
                                Auth: ${data.results.api.auth_method || 'Unknown'}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-times-circle text-danger fs-4 me-3"></i>
                        <div>
                            <strong>API Connection Failed</strong>
                            <div class="small text-danger">${data.results.api.message}</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Database Test Result
        if (data.results.database.status === 'success') {
            html += `
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success fs-4 me-3"></i>
                        <div>
                            <strong>Direct Database Connection</strong>
                            <div class="small text-muted">
                                ${data.results.database.varieties} varieties, ${data.results.database.beds} beds found
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-times-circle text-danger fs-4 me-3"></i>
                        <div>
                            <strong>Direct Database Connection Failed</strong>
                            <div class="small text-danger">${data.results.database.message}</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
        resultDiv.innerHTML = html;
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> <strong>Test failed</strong><br>
                <small>${error.message}</small>
            </div>
        `;
    });
}
</script>

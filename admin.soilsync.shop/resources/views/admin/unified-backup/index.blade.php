@extends('layouts.app')

@section('title', 'Unified Backup System')

@section('styles')
<style>
.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: .25rem;
    margin-bottom: 1rem;
    background-color: #fff;
    display: flex;
    align-items: center;
    padding: 0;
}

.info-box-icon {
    border-top-left-radius: .25rem;
    border-bottom-left-radius: .25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.875rem;
    width: 70px;
    height: 70px;
}

.info-box-content {
    padding: 5px 10px;
    margin-left: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.info-box-text {
    text-transform: uppercase;
    font-weight: 700;
    font-size: .6875rem;
    color: #6c757d;
}

.info-box-number {
    font-size: 1.125rem;
    font-weight: 700;
    color: #000;
}

/* Ensure table content is visible */
.table td, .table th {
    color: #000 !important;
    background-color: #fff !important;
}

.table .badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
}

/* Make sure backup files table is visible */
#backupFilesTable {
    color: #000;
}

#backupFilesTable .btn {
    margin-right: 0.25rem;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i> Unified Backup System
                    </h3>
                    <div class="card-tools">
                        <div class="form-check form-check-inline me-2">
                            <input class="form-check-input" type="checkbox" id="cloudUploadCheck" checked>
                            <label class="form-check-label text-sm" for="cloudUploadCheck">
                                <i class="fas fa-cloud"></i> Upload to Google Drive
                            </label>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="runBackupBtn">
                            <i class="fas fa-play"></i> Run Backup
                        </button>
                        <button type="button" class="btn btn-info btn-sm" id="refreshStatusBtn">
                            <i class="fas fa-sync"></i> Refresh Status
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Progress Indicator (Hidden by default) -->
                    <div class="alert alert-info d-none" id="backupProgressAlert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-spinner fa-spin me-3"></i>
                            <div class="flex-grow-1">
                                <strong id="progressTitle">Backup in progress...</strong>
                                <div class="progress mt-2" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" 
                                         id="progressBar"
                                         style="width: 0%">
                                        <span id="progressText">Starting...</span>
                                    </div>
                                </div>
                                <small class="text-muted" id="progressStatus">Checking status...</small>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Status -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Backups</span>
                                    <span class="info-box-number" id="totalBackups">{{ $backupStatus['total_backups'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-calendar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Latest Backup</span>
                                    <span class="info-box-number" id="latestBackup">{{ $backupStatus['latest_backup'] ?? 'Never' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-hdd"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Size</span>
                                    <span class="info-box-number" id="totalSize">{{ number_format($backupStatus['total_size'] / 1024 / 1024 / 1024, 2) }} GB</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon {{ $backupStatus['is_healthy'] ? 'bg-success' : 'bg-danger' }}">
                                    <i class="fas fa-heartbeat"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Status</span>
                                    <span class="info-box-number" id="backupStatus">
                                        {{ $backupStatus['is_healthy'] ? 'Healthy' : 'Unhealthy' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Backups -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h4>Available Backups</h4>
                            <div class="accordion" id="backupFilesContainer">
                                <div class="text-center p-3">
                                    <i class="fas fa-spinner fa-spin"></i> Loading backup files...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configured Sites -->
                    <div class="row">
                        <div class="col-12">
                            <h4>Configured Sites</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Site</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Last Backup</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sites as $key => $site)
                                        <tr>
                                            <td>{{ $site['label'] }}</td>
                                            <td>
                                                <span class="badge bg-primary">{{ ucfirst($site['type']) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $site['enabled'] ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $site['enabled'] ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </td>
                                            <td>{{ $site['last_backup'] ?? 'Never' }}</td>
                                            <td>
                                                @if($site['enabled'])
                                                    <button class="btn btn-sm btn-outline-primary backup-site-btn" data-site="{{ $key }}">
                                                        <i class="fas fa-play"></i> Backup
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
$(document).ready(function() {
    console.log('Backup page loaded');
    
    var progressCheckInterval = null;
    var backupStartTime = null;
    var initialBackupCount = {{ $backupStatus['total_backups'] }};

    // Load backup files on page load
    loadBackupFiles();

    // Progress tracking function
    function startProgressTracking(siteName) {
        backupStartTime = Date.now();
        initialBackupCount = {{ $backupStatus['total_backups'] }};
        
        $('#backupProgressAlert').removeClass('d-none');
        $('#progressTitle').text('Backing up ' + siteName + '...');
        $('#progressStatus').text('Backup started - checking progress...');
        
        var progressPercent = 0;
        var elapsed = 0;
        
        // Update progress bar every 2 seconds
        progressCheckInterval = setInterval(function() {
            elapsed = Math.floor((Date.now() - backupStartTime) / 1000);
            
            // Simulate progress based on typical backup time
            // Admin: ~30s, FarmOS: ~30s, Carey One (WordPress): ~5-10 minutes
            var expectedTime = 60; // Default to 1 minute
            if (siteName.includes('Admin')) {
                expectedTime = 60; // 1 minute for Laravel
            } else if (siteName.includes('FarmOS')) {
                expectedTime = 60; // 1 minute for FarmOS
            } else if (siteName.includes('Carey') || siteName.includes('WordPress')) {
                expectedTime = 600; // 10 minutes for WordPress sites
            }
            
            progressPercent = Math.min(95, (elapsed / expectedTime) * 100);
            
            $('#progressBar').css('width', progressPercent + '%');
            $('#progressText').text(Math.round(progressPercent) + '%');
            
            if (elapsed < 60) {
                $('#progressStatus').text('Elapsed time: ' + elapsed + 's - Compressing files...');
            } else {
                var minutes = Math.floor(elapsed / 60);
                var seconds = elapsed % 60;
                $('#progressStatus').text('Elapsed time: ' + minutes + 'm ' + seconds + 's - Large backup in progress...');
            }
            
            // Check if backup completed by checking for new backup file
            $.get('{{ route("admin.unified-backup.status") }}')
                .done(function(status) {
                    if (status.total_backups > initialBackupCount) {
                        // New backup appeared!
                        clearInterval(progressCheckInterval);
                        $('#progressBar').css('width', '100%').removeClass('progress-bar-animated');
                        $('#progressText').text('100% - Complete!');
                        $('#progressStatus').html('<i class="fas fa-check-circle text-success"></i> Backup completed successfully in ' + elapsed + 's');
                        
                        // Reload page after 3 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else if (elapsed > expectedTime * 2.5) {
                        // Taking too long - might have failed
                        clearInterval(progressCheckInterval);
                        $('#progressBar').addClass('bg-warning').removeClass('progress-bar-animated');
                        $('#progressStatus').html('<i class="fas fa-exclamation-triangle text-warning"></i> Backup taking longer than expected (' + Math.floor(elapsed/60) + ' minutes). It may still be running - check logs or refresh page.');
                    }
                });
        }, 2000);
    }

    // Run full backup
    $('#runBackupBtn').click(function() {
        if (confirm('Are you sure you want to run a full backup? This will run in the background.')) {
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');

            $.post('{{ route("admin.unified-backup.run") }}', {
                _token: '{{ csrf_token() }}',
                site: 'admin',
                cloud: $('#cloudUploadCheck').is(':checked')
            })
            .done(function(response) {
                $('#runBackupBtn').prop('disabled', false).html('<i class="fas fa-play"></i> Run Backup');
                startProgressTracking('Admin (Laravel)');
            })
            .fail(function(xhr) {
                alert('Backup failed to start: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                $('#runBackupBtn').prop('disabled', false).html('<i class="fas fa-play"></i> Run Backup');
            });
        }
    });

    // Run site-specific backup
    $('.backup-site-btn').click(function() {
        var site = $(this).data('site');
        var siteLabel = $(this).closest('tr').find('td:first').text().trim();
        var btn = $(this);

        if (confirm('Are you sure you want to backup ' + siteLabel + '? This will run in the background.')) {
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');

            $.post('{{ route("admin.unified-backup.run") }}', {
                _token: '{{ csrf_token() }}',
                site: site
            })
            .done(function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-play"></i> Backup');
                startProgressTracking(siteLabel);
            })
            .fail(function(xhr) {
                alert('Backup failed to start: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                btn.prop('disabled', false).html('<i class="fas fa-play"></i> Backup');
            });
        }
    });

    // Refresh status
    $('#refreshStatusBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');

        $.ajax({
            url: '{{ route("admin.unified-backup.status") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .done(function(data) {
            $('#totalBackups').text(data.total_backups);
            $('#latestBackup').text(data.latest_backup || 'Never');
            $('#totalSize').text((data.total_size / 1024 / 1024 / 1024).toFixed(2) + ' GB');
            $('#backupStatus').text(data.is_healthy ? 'Healthy' : 'Unhealthy');
            $('#backupStatus').closest('.info-box').find('.info-box-icon')
                .removeClass('bg-success bg-danger')
                .addClass(data.is_healthy ? 'bg-success' : 'bg-danger');

            // Reload backup files too
            loadBackupFiles();
        })
        .always(function() {
            $('#refreshStatusBtn').prop('disabled', false).html('<i class="fas fa-sync"></i> Refresh Status');
        });
    });
});

function loadBackupFiles() {
    console.log('Loading backup files...');
    var url = '{{ route("admin.unified-backup.files") }}';
    console.log('Requesting URL:', url);
    $.ajax({
        url: url,
        type: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .done(function(backups) {
        console.log('Backup files loaded:', backups);
        var container = $('#backupFilesContainer');
        container.empty();

        if (backups.length === 0) {
            container.append('<div class="alert alert-info">No backup files found</div>');
            return;
        }

        // Group backups by site
        var backupsBySite = {};
        backups.forEach(function(backup) {
            var site = backup.site || 'unknown';
            if (!backupsBySite[site]) {
                backupsBySite[site] = [];
            }
            backupsBySite[site].push(backup);
        });

        // Create accordions for each site
        var siteIndex = 0;
        Object.keys(backupsBySite).forEach(function(site) {
            var siteBackups = backupsBySite[site];
            var accordionId = 'site-' + site.replace(/[^a-zA-Z0-9]/g, '-');
            var isFirst = siteIndex === 0;

            var accordionHtml = '<div class="accordion-item">' +
                '<h2 class="accordion-header" id="heading-' + accordionId + '">' +
                    '<button class="accordion-button ' + (isFirst ? '' : 'collapsed') + '" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-' + accordionId + '" aria-expanded="' + (isFirst ? 'true' : 'false') + '" aria-controls="collapse-' + accordionId + '">' +
                        '<i class="fas fa-server me-2"></i>' + site + ' (' + siteBackups.length + ' backup' + (siteBackups.length !== 1 ? 's' : '') + ')' +
                    '</button>' +
                '</h2>' +
                '<div id="collapse-' + accordionId + '" class="accordion-collapse collapse ' + (isFirst ? 'show' : '') + '" aria-labelledby="heading-' + accordionId + '" data-bs-parent="#backupFilesContainer">' +
                    '<div class="accordion-body">' +
                        '<div class="table-responsive">' +
                            '<table class="table table-striped">' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th>Filename</th>' +
                                        '<th>Size</th>' +
                                        '<th>Created</th>' +
                                        '<th>Actions</th>' +
                                    '</tr>' +
                                '</thead>' +
                                '<tbody>';

            siteBackups.forEach(function(backup) {
                var sizeMB = (backup.size / 1024 / 1024).toFixed(1);
                var createdDate = new Date(backup.created * 1000).toLocaleString();

                accordionHtml += '<tr>' +
                    '<td><i class="fas fa-file-archive text-warning me-2"></i>' + backup.filename + '</td>' +
                    '<td>' + sizeMB + ' MB</td>' +
                    '<td>' + createdDate + '</td>' +
                    '<td>' +
                        '<a href="/admin/unified-backup/download/' + backup.filename + '" class="btn btn-sm btn-outline-primary me-1" target="_blank">' +
                            '<i class="fas fa-download"></i> Download' +
                        '</a>' +
                        '<button class="btn btn-sm btn-outline-danger delete-backup-btn" data-filename="' + backup.filename + '">' +
                            '<i class="fas fa-trash"></i> Delete' +
                        '</button>' +
                    '</td>' +
                '</tr>';
            });

            accordionHtml += '</tbody></table></div></div></div></div>';
            container.append(accordionHtml);
            siteIndex++;
        });

        // Handle delete buttons (using event delegation for dynamically loaded content)
        $(document).on('click', '.delete-backup-btn', function() {
            var filename = $(this).data('filename');
            var button = $(this);
            
            if (confirm('Are you sure you want to delete backup: ' + filename + '? This action cannot be undone.')) {
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
                
                $.post('{{ route("admin.unified-backup.delete") }}', {
                    _token: '{{ csrf_token() }}',
                    filename: filename
                })
                .done(function(response) {
                    if (response.success) {
                        alert('Backup deleted successfully!');
                        // Reload the page to refresh the backup list
                        location.reload();
                    } else {
                        alert('Failed to delete backup: ' + response.message);
                        button.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
                    }
                })
                .fail(function(xhr) {
                    console.error('Delete failed:', xhr);
                    var errorMsg = 'Failed to delete backup';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    button.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
                });
            }
        });
    })
    .fail(function(xhr, status, error) {
        console.error('Failed to load backup files:', {
            status: xhr.status,
            statusText: xhr.statusText,
            responseText: xhr.responseText,
            error: error
        });
        var errorMessage = 'Failed to load backup files';
        if (xhr.status === 401) {
            errorMessage += ': Authentication required. Please refresh the page and log in again.';
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage += ': ' + xhr.responseJSON.message;
        } else if (error) {
            errorMessage += ': ' + error;
        }
        $('#backupFilesContainer').html('<div class="alert alert-danger">Failed to load backup files: ' + errorMessage + '</div>');
    });
}
</script>
@endsection
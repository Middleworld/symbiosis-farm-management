@extends('layouts.app')

@section('title', 'FarmOS - Harvest Logs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-seedling text-success"></i> Harvest Logs</h2>
                    <p class="text-muted">Manage harvest data from FarmOS and sync to local stock</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('admin.farmos.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button type="button" class="btn btn-primary" id="syncHarvestsBtn">
                        <i class="fas fa-sync-alt"></i> Sync from FarmOS
                    </button>
                    <button type="button" class="btn btn-success" id="syncSelectedToStockBtn" disabled>
                        <i class="fas fa-box"></i> Add Selected to Stock
                    </button>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="crop_type" class="form-label">Crop Type</label>
                            <select name="crop_type" id="crop_type" class="form-select">
                                <option value="">All Crops</option>
                                @foreach($cropTypes as $type)
                                    @php
                                        $value = is_array($type) ? ($type['name'] ?? ($type['label'] ?? 'Unknown')) : $type;
                                    @endphp
                                    <option value="{{ $value }}" {{ request('crop_type') == $value ? 'selected' : '' }}>
                                        {{ ucfirst($value) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" 
                                   value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" 
                                   value="{{ request('date_to', now()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="{{ route('admin.farmos.harvests') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Harvest Logs Table --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Harvest Records</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">
                                Select All
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" class="form-check-input" id="selectAllCheckbox">
                                    </th>
                                    <th>Harvest Date</th>
                                    <th>Crop</th>
                                    <th>Variety</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Location</th>
                                    <th>Quality</th>
                                    <th>Stock Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($harvestLogs as $harvest)
                                <tr data-harvest-id="{{ $harvest->id }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input harvest-checkbox" 
                                               value="{{ $harvest->id }}" 
                                               {{ $harvest->synced_to_stock ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        <strong>{{ $harvest->harvest_date->format('M j, Y') }}</strong>
                                        <br><small class="text-muted">{{ $harvest->harvest_date->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-leaf text-success me-2"></i>
                                            <div>
                                                <strong>{{ $harvest->crop_type }}</strong>
                                                @if($harvest->plant_asset_name)
                                                    <br><small class="text-muted">{{ $harvest->plant_asset_name }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $harvest->variety ?? '-' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-primary fs-6">{{ number_format($harvest->quantity, 1) }}</span>
                                    </td>
                                    <td>
                                        {{ $harvest->unit }}
                                    </td>
                                    <td>
                                        @if($harvest->location)
                                            <i class="fas fa-map-marker-alt text-info me-1"></i>
                                            {{ $harvest->location }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($harvest->quality_grade)
                                            @php
                                                $badgeClass = match(strtolower($harvest->quality_grade)) {
                                                    'excellent', 'a+', 'premium' => 'bg-success',
                                                    'good', 'a', 'standard' => 'bg-primary',
                                                    'fair', 'b' => 'bg-warning',
                                                    'poor', 'c', 'seconds' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $harvest->quality_grade }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($harvest->synced_to_stock)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> In Stock
                                            </span>
                                            @if($harvest->synced_at)
                                                <br><small class="text-muted">{{ $harvest->synced_at->format('M j') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if(!$harvest->synced_to_stock)
                                                <button type="button" class="btn btn-outline-success sync-to-stock-btn"
                                                        data-harvest-id="{{ $harvest->id }}"
                                                        title="Add to stock">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-outline-info view-details-btn"
                                                    data-harvest-id="{{ $harvest->id }}"
                                                    title="View details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($harvest->farmos_log_id)
                                                <a href="{{ $farmosBaseUrl }}/log/{{ $harvest->farmos_log_id }}" 
                                                   target="_blank" 
                                                   class="btn btn-outline-secondary"
                                                   title="View in FarmOS">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-seedling fa-2x mb-3"></i>
                                            <p>No harvest logs found. Try syncing from FarmOS or adjusting your filters.</p>
                                            <button type="button" class="btn btn-primary" id="syncHarvestsEmptyBtn">
                                                <i class="fas fa-sync-alt"></i> Sync from FarmOS
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($harvestLogs->hasPages())
                <div class="card-footer">
                    {{ $harvestLogs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Harvest Details Modal --}}
<div class="modal fade" id="harvestDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Harvest Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="harvestDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all functionality
    $('#selectAllCheckbox, #selectAll').change(function() {
        $('.harvest-checkbox:not(:disabled)').prop('checked', this.checked);
        updateBulkActionButtons();
    });

    $('.harvest-checkbox').change(function() {
        updateBulkActionButtons();
    });

    function updateBulkActionButtons() {
        const selectedCount = $('.harvest-checkbox:checked').length;
        $('#syncSelectedToStockBtn').prop('disabled', selectedCount === 0);
    }

    // Sync harvests from FarmOS
    $('#syncHarvestsBtn, #syncHarvestsEmptyBtn').click(function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Syncing...').prop('disabled', true);
        
        $.post('{{ route("admin.farmos.sync-harvests") }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message || 'Sync failed');
            }
        })
        .fail(function(xhr) {
            toastr.error('Error syncing harvests: ' + (xhr.responseJSON?.message || 'Unknown error'));
        })
        .always(function() {
            btn.html(originalText).prop('disabled', false);
        });
    });

    // Sync selected harvests to stock
    $('#syncSelectedToStockBtn').click(function() {
        const selectedIds = $('.harvest-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            toastr.warning('Please select harvests to add to stock');
            return;
        }

        const btn = $(this);
        const originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Adding to Stock...').prop('disabled', true);
        
        $.post('{{ route("admin.farmos.sync-to-stock") }}', {
            _token: '{{ csrf_token() }}',
            harvest_ids: selectedIds
        })
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to add to stock');
            }
        })
        .fail(function(xhr) {
            toastr.error('Error adding to stock: ' + (xhr.responseJSON?.message || 'Unknown error'));
        })
        .always(function() {
            btn.html(originalText).prop('disabled', false);
        });
    });

    // Individual sync to stock
    $('.sync-to-stock-btn').click(function() {
        const harvestId = $(this).data('harvest-id');
        const btn = $(this);
        const originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.post('{{ route("admin.farmos.sync-to-stock") }}', {
            _token: '{{ csrf_token() }}',
            harvest_ids: [harvestId]
        })
        .done(function(response) {
            if (response.success) {
                toastr.success('Added to stock successfully');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to add to stock');
            }
        })
        .fail(function(xhr) {
            toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
        })
        .always(function() {
            btn.html(originalText).prop('disabled', false);
        });
    });

    // View harvest details
    $('.view-details-btn').click(function() {
        const harvestId = $(this).data('harvest-id');
        
        $('#harvestDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#harvestDetailsModal').modal('show');
        
        $.get('{{ route("admin.farmos.harvests") }}/' + harvestId)
        .done(function(response) {
            $('#harvestDetailsContent').html(response);
        })
        .fail(function() {
            $('#harvestDetailsContent').html('<div class="alert alert-danger">Error loading harvest details</div>');
        });
    });
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'FarmOS - Stock Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-boxes text-info"></i> Stock Management</h2>
                    <p class="text-muted">Manage inventory levels and track stock movements</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('admin.farmos.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="fas fa-plus"></i> Add Stock Item
                    </button>
                    <button type="button" class="btn btn-primary" id="bulkUpdateBtn" disabled>
                        <i class="fas fa-edit"></i> Bulk Update
                    </button>
                </div>
            </div>

            {{-- Stock Summary Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Items</h6>
                                    <h3 class="mb-0">{{ $stockStats['total_items'] }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-box fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">In Stock</h6>
                                    <h3 class="mb-0">{{ $stockStats['items_in_stock'] }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Low Stock</h6>
                                    <h3 class="mb-0">{{ $stockStats['low_stock_items'] }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Out of Stock</h6>
                                    <h3 class="mb-0">{{ $stockStats['out_of_stock_items'] }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                                <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="location" class="form-label">Location</label>
                            <select name="location" id="location" class="form-select">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    @php
                                        $locationValue = is_array($location) ? ($location['name'] ?? $location['label'] ?? '') : $location;
                                    @endphp
                                    <option value="{{ $locationValue }}" {{ request('location') == $locationValue ? 'selected' : '' }}>
                                        {{ $locationValue }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Search by name, variety..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="{{ route('admin.farmos.stock') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Stock Items Table --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Stock Items</h5>
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
                                    <th>Item</th>
                                    <th>Current Stock</th>
                                    <th>Min/Max Levels</th>
                                    <th>Unit</th>
                                    <th>Location</th>
                                    <th>Quality</th>
                                    <th>Last Updated</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockItems as $item)
                                <tr data-stock-id="{{ $item->id }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input stock-checkbox" 
                                               value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $iconClass = match(strtolower($item->crop_type)) {
                                                    'tomato', 'tomatoes' => 'fas fa-apple-alt text-danger',
                                                    'lettuce', 'greens' => 'fas fa-leaf text-success',
                                                    'herbs' => 'fas fa-seedling text-success',
                                                    'carrot', 'carrots' => 'fas fa-carrot text-warning',
                                                    default => 'fas fa-leaf text-success'
                                                };
                                            @endphp
                                            <i class="{{ $iconClass }} me-2"></i>
                                            <div>
                                                <strong>{{ $item->crop_type }}</strong>
                                                @if($item->variety)
                                                    <br><small class="text-muted">{{ $item->variety }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary fs-6 me-2">{{ number_format($item->current_quantity, 1) }}</span>
                                            @if($item->current_quantity <= ($item->min_quantity ?? 0))
                                                <i class="fas fa-exclamation-triangle text-danger" title="Below minimum level"></i>
                                            @elseif($item->current_quantity <= (($item->min_quantity ?? 0) * 1.5))
                                                <i class="fas fa-exclamation-circle text-warning" title="Low stock"></i>
                                            @else
                                                <i class="fas fa-check-circle text-success" title="Good stock level"></i>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            Min: {{ $item->min_quantity ?? 'Not set' }}
                                            <br>Max: {{ $item->max_quantity ?? 'Not set' }}
                                        </small>
                                    </td>
                                    <td>{{ $item->unit }}</td>
                                    <td>
                                        @if($item->location)
                                            <i class="fas fa-map-marker-alt text-info me-1"></i>
                                            {{ $item->location }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->quality_grade)
                                            @php
                                                $badgeClass = match(strtolower($item->quality_grade)) {
                                                    'excellent', 'a+', 'premium' => 'bg-success',
                                                    'good', 'a', 'standard' => 'bg-primary',
                                                    'fair', 'b' => 'bg-warning',
                                                    'poor', 'c', 'seconds' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $item->quality_grade }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $item->updated_at->format('M j, Y') }}
                                        <br><small class="text-muted">{{ $item->updated_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($item->current_quantity <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif($item->current_quantity <= ($item->min_quantity ?? 0))
                                            <span class="badge bg-warning">Low Stock</span>
                                        @else
                                            <span class="badge bg-success">In Stock</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary edit-stock-btn"
                                                    data-stock-id="{{ $item->id }}"
                                                    title="Edit stock">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success adjust-stock-btn"
                                                    data-stock-id="{{ $item->id }}"
                                                    title="Adjust quantity">
                                                <i class="fas fa-plus-minus"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info view-history-btn"
                                                    data-stock-id="{{ $item->id }}"
                                                    title="View history">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            @if($item->harvest_log_id)
                                                <a href="{{ route('admin.farmos.harvests', ['harvest' => $item->harvest_log_id]) }}" 
                                                   class="btn btn-outline-secondary"
                                                   title="View source harvest">
                                                    <i class="fas fa-seedling"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-boxes fa-2x mb-3"></i>
                                            <p>No stock items found. Try adjusting your filters or add new stock items.</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
                                                <i class="fas fa-plus"></i> Add Stock Item
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($stockItems->hasPages())
                <div class="card-footer">
                    {{ $stockItems->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Add Stock Modal --}}
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Stock Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addStockForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="crop_type" class="form-label">Crop Type *</label>
                            <input type="text" class="form-control" id="crop_type" name="crop_type" required>
                        </div>
                        <div class="col-md-6">
                            <label for="variety" class="form-label">Variety</label>
                            <input type="text" class="form-control" id="variety" name="variety">
                        </div>
                        <div class="col-md-6">
                            <label for="current_quantity" class="form-label">Quantity *</label>
                            <input type="number" step="0.1" class="form-control" id="current_quantity" name="current_quantity" required>
                        </div>
                        <div class="col-md-6">
                            <label for="unit" class="form-label">Unit *</label>
                            <select class="form-select" id="unit" name="unit" required>
                                <option value="">Select unit</option>
                                <option value="lbs">lbs</option>
                                <option value="kg">kg</option>
                                <option value="oz">oz</option>
                                <option value="g">g</option>
                                <option value="bunches">bunches</option>
                                <option value="pieces">pieces</option>
                                <option value="boxes">boxes</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="min_quantity" class="form-label">Minimum Quantity</label>
                            <input type="number" step="0.1" class="form-control" id="min_quantity" name="min_quantity">
                        </div>
                        <div class="col-md-6">
                            <label for="max_quantity" class="form-label">Maximum Quantity</label>
                            <input type="number" step="0.1" class="form-control" id="max_quantity" name="max_quantity">
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                        <div class="col-md-6">
                            <label for="quality_grade" class="form-label">Quality Grade</label>
                            <select class="form-select" id="quality_grade" name="quality_grade">
                                <option value="">Select quality</option>
                                <option value="Excellent">Excellent</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Stock Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Stock Modal --}}
<div class="modal fade" id="editStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Stock Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editStockForm">
                <div class="modal-body">
                    <div id="editStockContent">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Adjust Stock Modal --}}
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock Quantity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustStockForm">
                <div class="modal-body">
                    <div id="adjustStockContent">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Adjust Quantity</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all functionality
    $('#selectAllCheckbox, #selectAll').change(function() {
        $('.stock-checkbox').prop('checked', this.checked);
        updateBulkActionButtons();
    });

    $('.stock-checkbox').change(function() {
        updateBulkActionButtons();
    });

    function updateBulkActionButtons() {
        const selectedCount = $('.stock-checkbox:checked').length;
        $('#bulkUpdateBtn').prop('disabled', selectedCount === 0);
    }

    // Add stock form
    $('#addStockForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Adding...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.farmos.stock") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .done(function(response) {
            if (response.success) {
                toastr.success('Stock item added successfully');
                $('#addStockModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to add stock item');
            }
        })
        .fail(function(xhr) {
            toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
        })
        .always(function() {
            submitBtn.html(originalText).prop('disabled', false);
        });
    });

    // Edit stock
    $('.edit-stock-btn').click(function() {
        const stockId = $(this).data('stock-id');
        
        $('#editStockContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#editStockModal').modal('show');
        
        $.get('{{ route("admin.farmos.stock") }}/' + stockId + '/edit')
        .done(function(response) {
            $('#editStockContent').html(response);
        })
        .fail(function() {
            $('#editStockContent').html('<div class="alert alert-danger">Error loading stock details</div>');
        });
    });

    // Adjust stock
    $('.adjust-stock-btn').click(function() {
        const stockId = $(this).data('stock-id');
        
        $('#adjustStockContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#adjustStockModal').modal('show');
        
        $.get('{{ route("admin.farmos.stock") }}/' + stockId + '/adjust')
        .done(function(response) {
            $('#adjustStockContent').html(response);
        })
        .fail(function() {
            $('#adjustStockContent').html('<div class="alert alert-danger">Error loading adjustment form</div>');
        });
    });
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'Box Configuration Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>
                    @if($configuration->is_seasonal)
                        Seasonal Configuration: {{ $configuration->seasonal_name }}
                    @else
                        Weekly Configuration
                    @endif
                </h1>
                <div>
                    <a href="{{ route('admin.box-configurations.edit', $configuration->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('admin.box-configurations.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <p class="text-muted">
                @if($configuration->is_seasonal)
                    Seasonal configuration for {{ $configuration->seasonal_name }} ({{ $configuration->start_date->format('M j') }} - {{ $configuration->end_date->format('M j, Y') }})
                @else
                    Weekly configuration for week starting {{ $configuration->week_starting->format('M j, Y') }}
                @endif
            </p>
        </div>
    </div>

    <!-- Configuration Details -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-box"></i> {{ is_array($configuration->plan->name) ? $configuration->plan->name['en'] : $configuration->plan->name }}
                        <span class="badge bg-primary ms-2">{{ ucfirst($configuration->plan->box_size) }}</span>
                        @if($configuration->is_active)
                            <span class="badge bg-success ms-2">Active</span>
                        @else
                            <span class="badge bg-secondary ms-2">Inactive</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            @if($configuration->is_active)
                                <span class="text-success">Active</span>
                            @else
                                <span class="text-muted">Inactive</span>
                            @endif
                        </div>
                    </div>

                    @if($configuration->admin_notes)
                    <div class="mb-3">
                        <strong>Admin Notes:</strong>
                        <p class="text-muted">{{ $configuration->admin_notes }}</p>
                    </div>
                    @endif

                    <!-- Items Table -->
                    <h6 class="mt-4 mb-3">Box Contents</h6>
                    @if($configuration->items->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total Value</th>
                                        <th>Variety</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($configuration->items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->product ? $item->product->name : $item->item_name }}</strong>
                                            @if($item->product && $item->product->description)
                                                <br><small class="text-muted">{{ Str::limit($item->product->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>£{{ number_format($item->price_at_time, 2) }}</td>
                                        <td>£{{ number_format($item->quantity * $item->price_at_time, 2) }}</td>
                                        <td>
                                            @if($item->plant_variety_id && $item->plantVariety)
                                                {{ $item->plantVariety->name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th colspan="3">Total Value</th>
                                        <th>£{{ number_format($configuration->items->sum(function($item) { return $item->quantity * $item->price_at_time; }), 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No items configured for this box.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Allocation Summary -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie"></i> Allocation Summary
                    </h6>
                </div>
                <div class="card-body">
                    @if($allocationSummary)
                        <div class="mb-3">
                            <strong>Total Items:</strong> {{ $allocationSummary['total_items'] }}
                        </div>
                        <div class="mb-3">
                            <strong>Total Value:</strong> £{{ number_format($allocationSummary['total_value'], 2) }}
                        </div>
                        <div class="mb-3">
                            <strong>Categories:</strong>
                            <ul class="list-unstyled">
                                @foreach($allocationSummary['categories'] as $category => $count)
                                <li>{{ $category }}: {{ $count }} items</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-muted">No allocation data available.</p>
                    @endif
                </div>
            </div>

            @if($configuration->is_seasonal)
            <!-- Generate Weekly Boxes -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-magic"></i> Generate Weekly Boxes
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Create individual weekly configurations from this seasonal template.</p>
                    <form action="{{ route('admin.box-configurations.generate-weekly-boxes', $configuration->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('This will create weekly configurations for each week in the seasonal period. Continue?')">
                            <i class="fas fa-magic"></i> Generate Weekly Boxes
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-cogs"></i> Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('admin.box-configurations.duplicate', $configuration->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-copy"></i> Duplicate
                            </button>
                        </form>

                        @if($configuration->items->count() > 0)
                        <button type="button" class="btn btn-outline-info btn-sm w-100" onclick="importHarvests({{ $configuration->id }})">
                            <i class="fas fa-download"></i> Import Harvests
                        </button>
                        @endif

                        <form action="{{ route('admin.box-configurations.destroy', $configuration->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this configuration?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function importHarvests(configId) {
    if (confirm('This will import harvest data from farmOS for this configuration. Continue?')) {
        fetch(`{{ url('/admin/box-configurations') }}/${configId}/import-harvests`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Harvest data imported successfully!');
                location.reload();
            } else {
                alert('Error importing harvest data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error importing harvest data: ' + error.message);
        });
    }
}
</script>
@endsection
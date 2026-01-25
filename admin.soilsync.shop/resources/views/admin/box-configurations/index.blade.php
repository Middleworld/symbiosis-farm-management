@extends('layouts.app')

@section('title', 'Box Configurations')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Weekly Box Configurations</h1>
                <a href="{{ route('admin.box-configurations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Week
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Overview</h5>
                    <p>Set up weekly box configurations with available items and token values. Customers can then customize their boxes by dragging items from the available list.</p>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value">{{ $configurations->flatten()->count() }}</div>
                                <div class="stat-label">Total Configurations</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-value">{{ $plans->count() }}</div>
                                <div class="stat-label">Active Plans</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Weekly Box Configurations</h5>
                </div>
                <div class="card-body">
                    @if($configurations->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                            <h4>No Box Configurations Yet</h4>
                            <p class="text-muted">Create your first weekly box configuration to get started.</p>
                            <a href="{{ route('admin.box-configurations.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Configuration
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Week</th>
                                        <th>Configurations</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($configurations as $weekKey => $weekConfigs)
                                        <tr class="week-row" data-week="{{ $weekKey }}" style="cursor: pointer;">
                                            <td>
                                                <strong>Week {{ $weekKey }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $weekConfigs->first()->week_starting->format('M j, Y') }} - {{ $weekConfigs->first()->week_starting->copy()->addDays(6)->format('M j, Y') }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $weekConfigs->count() }} configuration{{ $weekConfigs->count() > 1 ? 's' : '' }}</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary toggle-week me-1" data-week="{{ $weekKey }}">
                                                    <i class="fas fa-chevron-down"></i> Expand
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary duplicate-week" data-week="{{ $weekKey }}" title="Duplicate to next week">
                                                    <i class="fas fa-copy"></i> Duplicate
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="week-details" data-week="{{ $weekKey }}" style="display: none;">
                                            <td colspan="3">
                                                <div class="week-configurations">
                                                    @foreach($weekConfigs as $config)
                                                        <div class="configuration-item" data-config-id="{{ $config->id }}">
                                                            <div class="configuration-header" onclick="toggleConfiguration('{{ $config->id }}')">
                                                                <i class="fas fa-chevron-right toggle-icon"></i>
                                                                <strong>{{ $config->plan->name }}</strong>
                                                                <span class="badge bg-info ms-2">{{ $config->items->count() }} items</span>
                                                                <span class="badge bg-warning ms-1">{{ $config->default_tokens }} tokens</span>
                                                                <div class="configuration-actions">
                                                                    <a href="{{ route('admin.box-configurations.show', $config) }}" class="btn btn-sm btn-info me-1">
                                                                        <i class="fas fa-eye"></i> View
                                                                    </a>
                                                                    <a href="{{ route('admin.box-configurations.edit', $config) }}" class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-edit"></i> Edit
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <div class="configuration-content" id="config-{{ $config->id }}" style="display: none;">
                                                                <div class="items-list">
                                                                    @if($config->items->count() > 0)
                                                                        <div class="row">
                                                                            @foreach($config->items as $item)
                                                                                <div class="col-md-6 col-lg-4 mb-2">
                                                                                    <div class="item-card">
                                                                                        <strong>{{ $item->plantVariety->name ?? $item->item_name ?? 'Unknown Item' }}</strong>
                                                                                        @if($item->quantity)
                                                                                            <br><small class="text-muted">{{ $item->quantity }} units</small>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <p class="text-muted mb-0">No items configured</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle week details
    document.querySelectorAll('.toggle-week').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const week = this.getAttribute('data-week');
            toggleWeek(week);
        });
    });

    // Toggle on row click
    document.querySelectorAll('.week-row').forEach(row => {
        row.addEventListener('click', function() {
            const week = this.getAttribute('data-week');
            toggleWeek(week);
        });
    });

    // Duplicate week
    document.querySelectorAll('.duplicate-week').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const week = this.getAttribute('data-week');
            duplicateWeek(week, this);
        });
    });
});

function toggleWeek(week) {
    const detailsRow = document.querySelector(`.week-details[data-week="${week}"]`);
    const button = document.querySelector(`.toggle-week[data-week="${week}"]`);
    const icon = button.querySelector('i');

    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = '';
        icon.className = 'fas fa-chevron-up';
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Collapse';
    } else {
        detailsRow.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Expand';
    }
}

function toggleConfiguration(configId) {
    const content = document.getElementById(`config-${configId}`);
    const icon = document.querySelector(`[data-config-id="${configId}"] .toggle-icon`);

    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.className = 'fas fa-chevron-down toggle-icon';
    } else {
        content.style.display = 'none';
        icon.className = 'fas fa-chevron-right toggle-icon';
    }
}

function duplicateWeek(week, button) {
    if (!confirm('Are you sure you want to duplicate this week\'s configurations to the next week?')) {
        return;
    }

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Duplicating...';

    fetch(`/admin/box-configurations/duplicate-week/${encodeURIComponent(week)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show the new week
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-copy"></i> Duplicate';
        }
    })
    .catch(error => {
        alert('Error duplicating week: ' + error.message);
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-copy"></i> Duplicate';
    });
}
</script>

<style>
.week-row:hover {
    background-color: #f8f9fa !important;
}

.week-details {
    background-color: #f8f9fa;
}

.week-configurations {
    padding: 15px;
}

.configuration-item {
    margin-bottom: 10px;
    border-left: 3px solid #dee2e6;
    padding-left: 15px;
}

.configuration-header {
    display: flex;
    align-items: center;
    padding: 10px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.configuration-header:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.toggle-icon {
    margin-right: 10px;
    transition: transform 0.2s ease;
}

.configuration-actions {
    margin-left: auto;
}

.configuration-content {
    margin-top: 10px;
    padding: 15px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-left: 25px;
}

.items-list {
    max-height: 300px;
    overflow-y: auto;
}

.item-card {
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.9em;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>
@endsection

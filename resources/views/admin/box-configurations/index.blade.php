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
                                <div class="stat-value">{{ $configurations->total() }}</div>
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
                    <h5 class="mb-0">Upcoming Weeks</h5>
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
                                        <th>Week Starting</th>
                                        <th>Plan</th>
                                        <th>Default Tokens</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($configurations as $config)
                                        <tr>
                                            <td>
                                                <strong>{{ $config->week_starting->format('d M Y') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $config->week_display }}</small>
                                            </td>
                                            <td>
                                                @if($config->plan)
                                                    {{ $config->plan->name }}
                                                    <br>
                                                    <small class="text-muted">{{ $config->plan->box_size ?? 'N/A' }}</small>
                                                @else
                                                    <span class="text-muted">All Plans</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $config->default_tokens }} tokens</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $config->items->count() }} items</span>
                                            </td>
                                            <td>
                                                @if($config->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.box-configurations.show', $config) }}" class="btn btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.box-configurations.edit', $config) }}" class="btn btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.box-configurations.destroy', $config) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this configuration?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $configurations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 15px;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #4CAF50;
}

.stat-label {
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>
@endsection

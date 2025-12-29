@extends('layouts.admin')

@section('title', 'Update Tracking')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Update Tracking</h1>
                    <p class="text-muted mb-0">Track all code changes and generate customer update scripts</p>
                </div>
                <a href="{{ route('admin.updates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Log New Update
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="customer_id" class="form-label">Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select">
                                <option value="">All Customers</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer }}" {{ $customerId == $customer ? 'selected' : '' }}>
                                    {{ $customer }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="environment" class="form-label">Environment</label>
                            <select name="environment" id="environment" class="form-select">
                                @foreach($environments as $env)
                                <option value="{{ $env }}" {{ $environment == $env ? 'selected' : '' }}>
                                    {{ ucfirst($env) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <a href="{{ route('admin.updates.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Updates List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Update History</h5>
                </div>
                <div class="card-body">
                    @if($updates->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Title</th>
                                    <th>Customer</th>
                                    <th>Environment</th>
                                    <th>Applied At</th>
                                    <th>Files Changed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($updates as $update)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">{{ $update->version }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $update->title }}</strong>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($update->description, 50) }}</small>
                                    </td>
                                    <td>
                                        @if($update->customer_id)
                                        <span class="badge bg-info">{{ $update->customer_id }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst($update->environment) }}</span>
                                    </td>
                                    <td>{{ $update->applied_at->format('d M Y H:i') }}</td>
                                    <td>{{ count($update->files_changed) }}</td>
                                    <td>
                                        <a href="{{ route('admin.updates.show', $update) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No updates found</h5>
                        <p class="text-muted">Start by logging your first update</p>
                        <a href="{{ route('admin.updates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Log First Update
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Script Section -->
    @if($customers->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Generate Update Script</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.updates.script') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="script_customer_id" class="form-label">Customer</label>
                            <select name="customer_id" id="script_customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer }}">{{ $customer }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="target_version" class="form-label">Target Version</label>
                            <input type="text" name="target_version" id="target_version" class="form-control" 
                                   placeholder="e.g., 1.2.0" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-download me-2"></i>Generate Script
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection</content>
<parameter name="filePath">/opt/sites/admin.middleworldfarms.org/resources/views/admin/updates/index.blade.php
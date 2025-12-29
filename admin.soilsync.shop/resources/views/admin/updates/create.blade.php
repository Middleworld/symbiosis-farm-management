@extends('layouts.admin')

@section('title', 'Log New Update')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Log New Update</h1>
                    <p class="text-muted mb-0">Record code changes for update tracking</p>
                </div>
                <a href="{{ route('admin.updates.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Updates
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Update Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.updates.store') }}" method="POST">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="version" class="form-label">Version <span class="text-danger">*</span></label>
                                <input type="text" name="version" id="version" class="form-control" 
                                       value="{{ old('version') }}" placeholder="e.g., 1.2.3" required>
                                @error('version')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="environment" class="form-label">Environment <span class="text-danger">*</span></label>
                                <select name="environment" id="environment" class="form-select" required>
                                    <option value="production" {{ old('environment', 'production') == 'production' ? 'selected' : '' }}>Production</option>
                                    <option value="demo" {{ old('environment') == 'demo' ? 'selected' : '' }}>Demo</option>
                                    <option value="staging" {{ old('environment') == 'staging' ? 'selected' : '' }}>Staging</option>
                                </select>
                                @error('environment')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control" 
                                       value="{{ old('title') }}" placeholder="Brief description of the update" required>
                                @error('title')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" id="description" class="form-control" rows="3" 
                                          placeholder="Detailed description of what changed" required>{{ old('description') }}</textarea>
                                @error('description')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label">Customer ID (Optional)</label>
                                <input type="text" name="customer_id" id="customer_id" class="form-control" 
                                       value="{{ old('customer_id') }}" placeholder="Leave empty for global updates">
                                @error('customer_id')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="files_changed" class="form-label">Files Changed (One per line)</label>
                                <textarea name="files_changed" id="files_changed" class="form-control" rows="5" 
                                          placeholder="app/Http/Controllers/ExampleController.php&#10;resources/views/example.blade.php&#10;database/migrations/2023_01_01_000000_example.php">{{ old('files_changed') }}</textarea>
                                <div class="form-text">List all files that were modified in this update</div>
                                @error('files_changed')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Log Update
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Help</h5>
                </div>
                <div class="card-body">
                    <h6>Version Numbering</h6>
                    <p class="small text-muted">Use semantic versioning (MAJOR.MINOR.PATCH):</p>
                    <ul class="small">
                        <li><strong>MAJOR</strong>: Breaking changes</li>
                        <li><strong>MINOR</strong>: New features</li>
                        <li><strong>PATCH</strong>: Bug fixes</li>
                    </ul>
                    
                    <h6 class="mt-3">Customer ID</h6>
                    <p class="small text-muted">Leave empty for updates that apply to all customers. Use customer-specific IDs for tenant-specific changes.</p>
                    
                    <h6 class="mt-3">Files Changed</h6>
                    <p class="small text-muted">List all files that were modified. This helps generate accurate update scripts.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection</content>
<parameter name="filePath">/opt/sites/admin.middleworldfarms.org/resources/views/admin/updates/create.blade.php
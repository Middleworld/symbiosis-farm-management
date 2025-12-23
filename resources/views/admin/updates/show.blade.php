@extends('layouts.admin')

@section('title', 'Update Details - ' . $update->title)

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">{{ $update->title }}</h1>
                    <p class="text-muted mb-0">Version {{ $update->version }} • Applied {{ $update->applied_at->format('d M Y \a\t H:i') }}</p>
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
                    <h5 class="mb-0">Update Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <strong>Version:</strong>
                            <span class="badge bg-primary ms-2">{{ $update->version }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Environment:</strong>
                            <span class="badge bg-secondary ms-2">{{ ucfirst($update->environment) }}</span>
                        </div>
                        @if($update->customer_id)
                        <div class="col-md-6">
                            <strong>Customer:</strong>
                            <span class="badge bg-info ms-2">{{ $update->customer_id }}</span>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <strong>Applied By:</strong>
                            <span class="text-muted ms-2">{{ $update->applied_by ?? 'System' }}</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Description</h6>
                        <p class="mb-0">{{ $update->description }}</p>
                    </div>
                    
                    @if($update->files_changed && count($update->files_changed) > 0)
                    <div class="mb-4">
                        <h6>Files Changed ({{ count($update->files_changed) }})</h6>
                        <div class="list-group">
                            @foreach($update->files_changed as $file)
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-2">
                                <code class="mb-0">{{ $file }}</code>
                                <small class="text-muted">{{ $update->changes[array_search($file, array_column($update->changes, 'file'))]['type'] ?? 'unknown' }}</small>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if($update->changes && count($update->changes) > 0)
                    <div>
                        <h6>Detailed Changes</h6>
                        <ul class="list-group">
                            @foreach($update->changes as $change)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $change['description'] }}</span>
                                <span class="badge bg-light text-dark">{{ $change['type'] }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($update->customer_id)
                    <a href="{{ route('admin.updates.script', ['customer_id' => $update->customer_id, 'target_version' => $update->version]) }}" 
                       class="btn btn-success w-100 mb-2">
                        <i class="fas fa-download me-2"></i>Generate Update Script
                    </a>
                    @endif
                    
                    <button class="btn btn-outline-primary w-100" onclick="copyUpdateInfo()">
                        <i class="fas fa-copy me-2"></i>Copy Update Info
                    </button>
                </div>
            </div>
            
            @if($update->customer_id)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Customer Update History</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.updates.index', ['customer_id' => $update->customer_id]) }}" class="btn btn-outline-secondary btn-sm">
                        View All Updates for {{ $update->customer_id }}
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function copyUpdateInfo() {
    const updateInfo = `Update: ${document.querySelector('h1').textContent.trim()}
Version: {{ $update->version }}
Environment: {{ ucfirst($update->environment) }}
@if($update->customer_id)
Customer: {{ $update->customer_id }}
@endif
Applied: {{ $update->applied_at->format('d M Y H:i') }}

Description:
{{ $update->description }}

@if($update->files_changed)
Files Changed:
{{ implode("\n", $update->files_changed) }}
@endif`;

    navigator.clipboard.writeText(updateInfo).then(() => {
        // Show success message
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}
</script>
@endsection</content>
<parameter name="filePath">/opt/sites/admin.middleworldfarms.org/resources/views/admin/updates/show.blade.php
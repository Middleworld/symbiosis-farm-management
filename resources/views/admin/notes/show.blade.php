@extends('layouts.app')

@section('title', $note->title)
@section('page-title', 'Note Details')

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="{{ route('admin.notes.index', ['type' => $note->type]) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Notes
                </a>
            </div>
            <div>
                <a href="{{ route('admin.notes.edit', $note) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <form action="{{ route('admin.notes.destroy', $note) }}" 
                      method="POST" 
                      class="d-inline"
                      onsubmit="return confirm('Are you sure you want to delete this note?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    @if($note->is_pinned)
                        <i class="fas fa-thumbtack text-warning"></i>
                    @endif
                    <h4 class="mb-0">{{ $note->title }}</h4>
                </div>
            </div>
            <div class="card-body">
                <div class="note-content">
                    {!! nl2br(e($note->content)) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Note Meta Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Note Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Type</small>
                    <div>
                        <span class="badge bg-primary">{{ ucfirst($note->type) }}</span>
                    </div>
                </div>

                @if($note->category)
                <div class="mb-3">
                    <small class="text-muted">Category</small>
                    <div>
                        <span class="badge bg-secondary">{{ $note->category }}</span>
                    </div>
                </div>
                @endif

                <div class="mb-3">
                    <small class="text-muted">Status</small>
                    <div>
                        @if($note->is_public)
                            <span class="badge bg-success"><i class="fas fa-users"></i> Public</span>
                        @else
                            <span class="badge bg-warning"><i class="fas fa-lock"></i> Private</span>
                        @endif
                        
                        @if($note->is_pinned)
                            <span class="badge bg-info"><i class="fas fa-thumbtack"></i> Pinned</span>
                        @endif
                    </div>
                </div>

                @if($note->tags && count($note->tags) > 0)
                <div class="mb-3">
                    <small class="text-muted">Tags</small>
                    <div>
                        @foreach($note->tags as $tag)
                            <span class="badge bg-light text-dark me-1 mb-1">
                                <i class="fas fa-tag"></i> {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <hr>

                <div class="mb-3">
                    <small class="text-muted">Created By</small>
                    <div>{{ $note->createdBy->name ?? 'Unknown' }}</div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Created At</small>
                    <div>{{ $note->created_at->format('M d, Y H:i') }}</div>
                </div>

                @if($note->updated_at && $note->updated_at != $note->created_at)
                <div class="mb-3">
                    <small class="text-muted">Last Updated</small>
                    <div>{{ $note->updated_at->format('M d, Y H:i') }}</div>
                </div>
                @endif

                @if($note->task)
                <hr>
                <div class="mb-3">
                    <small class="text-muted">Linked Task</small>
                    <div>
                        <a href="{{ route('admin.tasks.show', $note->task) }}" class="btn btn-sm btn-outline-primary w-100">
                            <i class="fas fa-tasks"></i> {{ $note->task->title }}
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h6>
            </div>
            <div class="card-body">
                <button class="btn btn-outline-secondary btn-sm w-100 mb-2" onclick="togglePin()">
                    <i class="fas fa-thumbtack"></i> {{ $note->is_pinned ? 'Unpin' : 'Pin' }} Note
                </button>
                <button class="btn btn-outline-primary btn-sm w-100" onclick="copyToClipboard()">
                    <i class="fas fa-copy"></i> Copy Content
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePin() {
    fetch(`/admin/notes/{{ $note->id }}/toggle-pin`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to toggle pin status');
    });
}

function copyToClipboard() {
    const content = document.querySelector('.note-content').innerText;
    navigator.clipboard.writeText(content).then(() => {
        alert('Content copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}
</script>
@endsection

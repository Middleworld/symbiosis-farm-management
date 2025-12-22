@extends('layouts.app')

@section('title', 'Notes Management')
@section('page-title', 'Notes')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link {{ $type === 'dev' ? 'active' : '' }}" href="{{ route('admin.notes.index', ['type' => 'dev']) }}">
                            <i class="fas fa-code"></i> Dev Notes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $type === 'farm' ? 'active' : '' }}" href="{{ route('admin.notes.index', ['type' => 'farm']) }}">
                            <i class="fas fa-tractor"></i> Farm Notes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $type === 'general' ? 'active' : '' }}" href="{{ route('admin.notes.index', ['type' => 'general']) }}">
                            <i class="fas fa-sticky-note"></i> General
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                <a href="{{ route('admin.notes.create', ['type' => $type]) }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Note
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="btn-group" role="group">
            <a href="{{ route('admin.notes.index', ['type' => $type, 'filter' => 'all']) }}" 
               class="btn btn-sm btn-outline-secondary {{ $filter === 'all' ? 'active' : '' }}">
                All Notes
            </a>
            <a href="{{ route('admin.notes.index', ['type' => $type, 'filter' => 'my-notes']) }}" 
               class="btn btn-sm btn-outline-secondary {{ $filter === 'my-notes' ? 'active' : '' }}">
                My Notes
            </a>
            <a href="{{ route('admin.notes.index', ['type' => $type, 'filter' => 'public']) }}" 
               class="btn btn-sm btn-outline-secondary {{ $filter === 'public' ? 'active' : '' }}">
                Public
            </a>
            <a href="{{ route('admin.notes.index', ['type' => $type, 'filter' => 'pinned']) }}" 
               class="btn btn-sm btn-outline-secondary {{ $filter === 'pinned' ? 'active' : '' }}">
                <i class="fas fa-thumbtack"></i> Pinned
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Notes List -->
<div class="row">
    <div class="col-md-12">
        @if($notes->count() > 0)
            <div class="list-group">
                @foreach($notes as $note)
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @if($note->is_pinned)
                                    <i class="fas fa-thumbtack text-warning"></i>
                                @endif
                                <h5 class="mb-0">
                                    <a href="{{ route('admin.notes.show', $note) }}" class="text-decoration-none">
                                        {{ $note->title }}
                                    </a>
                                </h5>
                                @if($note->category)
                                    <span class="badge bg-secondary">{{ $note->category }}</span>
                                @endif
                                @if($note->is_public)
                                    <span class="badge bg-success"><i class="fas fa-users"></i> Public</span>
                                @endif
                            </div>
                            
                            <p class="mb-2 text-muted">
                                {{ Str::limit(strip_tags($note->content), 200) }}
                            </p>
                            
                            <div class="d-flex gap-3 small text-muted">
                                <span>
                                    <i class="fas fa-user"></i> 
                                    {{ $note->createdBy->name ?? 'Unknown' }}
                                </span>
                                <span>
                                    <i class="fas fa-calendar"></i> 
                                    {{ $note->created_at->format('M d, Y H:i') }}
                                </span>
                                @if($note->task)
                                    <span>
                                        <i class="fas fa-tasks"></i> 
                                        <a href="{{ route('admin.tasks.show', $note->task) }}">
                                            Task: {{ $note->task->title }}
                                        </a>
                                    </span>
                                @endif
                            </div>

                            @if($note->tags && count($note->tags) > 0)
                                <div class="mt-2">
                                    @foreach($note->tags as $tag)
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-tag"></i> {{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        
                        <div class="btn-group" role="group">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-secondary" 
                                    onclick="togglePin({{ $note->id }})"
                                    title="Toggle Pin">
                                <i class="fas fa-thumbtack"></i>
                            </button>
                            <a href="{{ route('admin.notes.edit', $note) }}" 
                               class="btn btn-sm btn-outline-primary"
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.notes.destroy', $note) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Are you sure you want to delete this note?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $notes->links() }}
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                No notes found. <a href="{{ route('admin.notes.create', ['type' => $type]) }}">Create your first note!</a>
            </div>
        @endif
    </div>
</div>

<script>
function togglePin(noteId) {
    fetch(`/admin/notes/${noteId}/toggle-pin`, {
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
</script>
@endsection

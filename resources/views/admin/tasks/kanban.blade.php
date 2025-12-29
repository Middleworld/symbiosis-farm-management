@extends('layouts.app')

@section('title', 'Task Kanban Board')
@section('page-title', 'Tasks - Kanban View')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link {{ $type === 'dev' ? 'active' : '' }}" href="{{ route('admin.tasks.kanban', ['type' => 'dev']) }}">
                            <i class="fas fa-code"></i> Dev Tasks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $type === 'farm' ? 'active' : '' }}" href="{{ route('admin.tasks.kanban', ['type' => 'farm']) }}">
                            <i class="fas fa-tractor"></i> Admin Tasks
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                <a href="{{ route('admin.tasks.index', ['type' => $type]) }}" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> List View
                </a>
                <a href="{{ route('admin.tasks.create', ['type' => $type]) }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Task
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- To Do Column -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-circle"></i> To Do
                    <span class="badge bg-light text-dark float-end">{{ $tasks['todo']->count() ?? 0 }}</span>
                </h6>
            </div>
            <div class="card-body kanban-column" data-status="todo" style="min-height: 500px; max-height: 70vh; overflow-y: auto;">
                @foreach($tasks['todo'] ?? [] as $task)
                    @include('admin.tasks.partials.kanban-card', ['task' => $task])
                @endforeach
            </div>
        </div>
    </div>

    <!-- In Progress Column -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-spinner"></i> In Progress
                    <span class="badge bg-light text-dark float-end">{{ $tasks['in_progress']->count() ?? 0 }}</span>
                </h6>
            </div>
            <div class="card-body kanban-column" data-status="in_progress" style="min-height: 500px; max-height: 70vh; overflow-y: auto;">
                @foreach($tasks['in_progress'] ?? [] as $task)
                    @include('admin.tasks.partials.kanban-card', ['task' => $task])
                @endforeach
            </div>
        </div>
    </div>

    <!-- Review Column -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h6 class="mb-0">
                    <i class="fas fa-eye"></i> Review
                    <span class="badge bg-light text-dark float-end">{{ $tasks['review']->count() ?? 0 }}</span>
                </h6>
            </div>
            <div class="card-body kanban-column" data-status="review" style="min-height: 500px; max-height: 70vh; overflow-y: auto;">
                @foreach($tasks['review'] ?? [] as $task)
                    @include('admin.tasks.partials.kanban-card', ['task' => $task])
                @endforeach
            </div>
        </div>
    </div>

    <!-- Completed Column -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="fas fa-check-circle"></i> Completed
                    <span class="badge bg-light text-dark float-end">{{ $tasks['completed']->count() ?? 0 }}</span>
                </h6>
            </div>
            <div class="card-body kanban-column" data-status="completed" style="min-height: 500px; max-height: 70vh; overflow-y: auto;">
                @foreach($tasks['completed'] ?? [] as $task)
                    @include('admin.tasks.partials.kanban-card', ['task' => $task])
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple drag and drop for kanban
    const cards = document.querySelectorAll('.kanban-card');
    const columns = document.querySelectorAll('.kanban-column');
    
    cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });
    
    columns.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('drop', handleDrop);
        column.addEventListener('dragenter', handleDragEnter);
        column.addEventListener('dragleave', handleDragLeave);
    });
    
    let draggedElement = null;
    
    function handleDragStart(e) {
        draggedElement = this;
        this.style.opacity = '0.5';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }
    
    function handleDragEnd(e) {
        this.style.opacity = '1';
        columns.forEach(col => col.classList.remove('drag-over'));
    }
    
    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }
    
    function handleDragEnter(e) {
        this.classList.add('drag-over');
    }
    
    function handleDragLeave(e) {
        this.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        if (draggedElement !== this) {
            const newStatus = this.dataset.status;
            const taskId = draggedElement.dataset.taskId;
            
            // Move the card
            this.appendChild(draggedElement);
            
            // Update status via AJAX
            fetch(`/admin/tasks/${taskId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update badge counts
                    updateColumnCounts();
                    // Show success message
                    showToast('Task status updated!', 'success');
                } else {
                    // Revert on error
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                location.reload();
            });
        }
        
        this.classList.remove('drag-over');
        return false;
    }
    
    function updateColumnCounts() {
        columns.forEach(column => {
            const count = column.querySelectorAll('.kanban-card').length;
            const badge = column.closest('.card').querySelector('.badge');
            if (badge) {
                badge.textContent = count;
            }
        });
    }
    
    function showToast(message, type = 'info') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
});
</script>

<style>
.drag-over {
    background-color: #f0f0f0;
    border: 2px dashed #007bff;
}

.kanban-column {
    transition: background-color 0.2s;
}
</style>
@endpush
@endsection

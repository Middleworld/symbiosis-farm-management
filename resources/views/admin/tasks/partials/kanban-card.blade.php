<div class="card kanban-card mb-2" draggable="true" data-task-id="{{ $task->id }}">
    <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-start mb-1">
            <a href="{{ route('admin.tasks.show', $task) }}" class="text-decoration-none text-dark flex-grow-1">
                <strong class="small">#{{ $task->id }} {{ Str::limit($task->title, 40) }}</strong>
            </a>
            <span class="badge bg-{{ $task->priority_badge }} ms-1">{{ substr($task->priority, 0, 1) }}</span>
        </div>
        
        @if($task->description)
        <p class="text-muted small mb-2">{{ Str::limit($task->description, 60) }}</p>
        @endif
        
        <div class="d-flex justify-content-between align-items-center">
            <div>
                @if($task->getAssignedTo())
                <small class="text-muted">
                    <i class="fas fa-user"></i> {{ Str::limit($task->getAssignedTo()->name, 15) }}
                </small>
                @endif
            </div>
            <div>
                @if($task->due_date)
                <small class="{{ $task->isOverdue() ? 'text-danger' : 'text-muted' }}">
                    <i class="fas fa-calendar"></i> {{ $task->due_date->format('M d') }}
                </small>
                @endif
            </div>
        </div>
        
        @if($task->type === 'dev' && $task->dev_category)
        <div class="mt-1">
            <span class="badge bg-light text-dark small">{{ $task->category_label }}</span>
        </div>
        @endif
    </div>
</div>

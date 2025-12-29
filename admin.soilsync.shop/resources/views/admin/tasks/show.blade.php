@extends('layouts.app')

@section('title', 'Task #' . $task->id)
@section('page-title', 'Task #' . $task->id . ' - ' . $task->title)

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <a href="{{ route('admin.tasks.index', ['type' => $task->type]) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Tasks
        </a>
        <a href="{{ route('admin.tasks.edit', $task) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Task
        </a>
        @if($task->status !== 'completed')
        <form action="{{ route('admin.tasks.update-status', $task) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="completed">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> Mark Completed
            </button>
        </form>
        @endif
        <form action="{{ route('admin.tasks.destroy', $task) }}" method="POST" class="d-inline" 
              onsubmit="return confirm('Are you sure you want to delete this task?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Delete
            </button>
        </form>
    </div>
</div>

<div class="row">
    <!-- Main Task Details -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">{{ $task->title }}</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Status:</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="badge bg-{{ $task->status_badge }}">
                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Priority:</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="badge bg-{{ $task->priority_badge }}">
                            {{ ucfirst($task->priority) }}
                        </span>
                    </div>
                </div>
                
                @if($task->type === 'dev' && $task->dev_category)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Category:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $task->category_label }}
                    </div>
                </div>
                @endif
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Assigned To:</strong>
                    </div>
                    <div class="col-md-9">
                        @if($task->getAssignedTo())
                            <i class="fas fa-user"></i> {{ $task->getAssignedTo()->name }}
                        @else
                            <span class="text-muted">Unassigned</span>
                        @endif
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Created By:</strong>
                    </div>
                    <div class="col-md-9">
                        <i class="fas fa-user"></i> {{ $task->getCreatedBy()->name }}
                        <small class="text-muted">on {{ $task->created_at->format('M d, Y \a\t h:i A') }}</small>
                    </div>
                </div>
                
                @if($task->due_date)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Due Date:</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="{{ $task->isOverdue() ? 'text-danger fw-bold' : '' }}">
                            <i class="fas fa-calendar"></i> {{ $task->due_date->format('F d, Y') }}
                            @if($task->isOverdue())
                                <span class="badge bg-danger ms-2">OVERDUE</span>
                            @endif
                        </span>
                    </div>
                </div>
                @endif
                
                @if($task->estimated_hours)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Estimated Hours:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $task->estimated_hours }} hours
                        @if($task->actual_hours)
                            <small class="text-muted">(Actual: {{ $task->actual_hours }} hours)</small>
                        @endif
                    </div>
                </div>
                @endif
                
                @if($task->completed_at)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Completed:</strong>
                    </div>
                    <div class="col-md-9">
                        <i class="fas fa-check-circle text-success"></i> {{ $task->completed_at->format('M d, Y \a\t h:i A') }}
                    </div>
                </div>
                @endif
                
                @if($task->description)
                <hr>
                <div class="mb-3">
                    <strong>Description:</strong>
                    <div class="mt-2">{!! $task->description !!}</div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Comments Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-comments"></i> Comments ({{ $task->comments->count() }})
                </h5>
            </div>
            <div class="card-body">
                @if($task->comments->count() > 0)
                    @foreach($task->comments as $comment)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $comment->user->name }}</strong>
                            <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="mt-2">{!! nl2br(e($comment->comment)) !!}</div>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">No comments yet.</p>
                @endif
                
                <hr>
                
                <form action="{{ route('admin.tasks.add-comment', $task) }}" method="POST" id="comment-form">
                    @csrf
                    <div class="mb-3">
                        <label for="comment" class="form-label"><strong>Add Comment</strong></label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" required placeholder="Write your comment here..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Post Comment
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Attachments -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-paperclip"></i> Attachments ({{ $task->attachments->count() }})
                </h5>
            </div>
            <div class="card-body">
                @if($task->attachments->count() > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($task->attachments as $attachment)
                        <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                            <div class="flex-grow-1">
                                <a href="{{ $attachment->url }}" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-file"></i> {{ Str::limit($attachment->filename, 30) }}
                                </a>
                                <br>
                                <small class="text-muted">{{ $attachment->filesize_human }} - {{ $attachment->uploader->name }}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-attachment" 
                                    data-attachment-id="{{ $attachment->id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">No attachments yet.</p>
                @endif
                
                <hr>
                
                <form action="{{ route('admin.tasks.upload-attachment', $task) }}" method="POST" 
                      enctype="multipart/form-data" id="attachment-form">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label"><strong>Upload File</strong></label>
                        <input type="file" class="form-control" id="file" name="file" required>
                        <small class="text-muted">Max 10MB</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Activity Timeline -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history"></i> Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <small class="text-muted">
                            <i class="fas fa-plus-circle text-success"></i> 
                            Created {{ $task->created_at->diffForHumans() }}
                        </small>
                    </div>
                    @if($task->updated_at != $task->created_at)
                    <div class="timeline-item mt-2">
                        <small class="text-muted">
                            <i class="fas fa-edit text-primary"></i> 
                            Updated {{ $task->updated_at->diffForHumans() }}
                        </small>
                    </div>
                    @endif
                    @if($task->completed_at)
                    <div class="timeline-item mt-2">
                        <small class="text-muted">
                            <i class="fas fa-check-circle text-success"></i> 
                            Completed {{ $task->completed_at->diffForHumans() }}
                        </small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete attachment
    document.querySelectorAll('.delete-attachment').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to delete this attachment?')) return;
            
            const attachmentId = this.dataset.attachmentId;
            const taskId = {{ $task->id }};
            
            fetch(`/admin/tasks/${taskId}/attachments/${attachmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
</script>
@endpush
@endsection

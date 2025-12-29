@extends('layouts.app')

@section('title', 'Task Management')
@section('page-title', 'Tasks')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link {{ $type === 'dev' ? 'active' : '' }}" href="{{ route('admin.tasks.index', ['type' => 'dev']) }}">
                            <i class="fas fa-code"></i> Dev Tasks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $type === 'farm' ? 'active' : '' }}" href="{{ route('admin.tasks.index', ['type' => 'farm']) }}">
                            <i class="fas fa-tractor"></i> Admin Tasks
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                <a href="{{ route('admin.tasks.kanban', ['type' => $type]) }}" class="btn btn-outline-primary">
                    <i class="fas fa-columns"></i> Kanban View
                </a>
                <a href="{{ route('admin.tasks.create', ['type' => $type]) }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Task
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-0">{{ $stats['total'] }}</h3>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-secondary">
            <div class="card-body">
                <h3 class="mb-0 text-secondary">{{ $stats['todo'] }}</h3>
                <small class="text-muted">To Do</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h3 class="mb-0 text-primary">{{ $stats['in_progress'] }}</h3>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h3 class="mb-0 text-warning">{{ $stats['review'] }}</h3>
                <small class="text-muted">Review</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-success">
            <div class="card-body">
                <h3 class="mb-0 text-success">{{ $stats['completed'] }}</h3>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h3 class="mb-0 text-danger">{{ $stats['overdue'] }}</h3>
                <small class="text-muted">Overdue</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.tasks.index', ['type' => $type, 'filter' => 'all']) }}" 
                       class="btn btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                        All Tasks
                    </a>
                    <a href="{{ route('admin.tasks.index', ['type' => $type, 'filter' => 'my-tasks']) }}" 
                       class="btn btn-sm {{ $filter === 'my-tasks' ? 'btn-primary' : 'btn-outline-primary' }}">
                        My Tasks ({{ $stats['my_tasks'] }})
                    </a>
                    <a href="{{ route('admin.tasks.index', ['type' => $type, 'filter' => 'created-by-me']) }}" 
                       class="btn btn-sm {{ $filter === 'created-by-me' ? 'btn-primary' : 'btn-outline-primary' }}">
                        Created by Me
                    </a>
                    <a href="{{ route('admin.tasks.index', ['type' => $type, 'filter' => 'unassigned']) }}" 
                       class="btn btn-sm {{ $filter === 'unassigned' ? 'btn-primary' : 'btn-outline-primary' }}">
                        Unassigned
                    </a>
                    <a href="{{ route('admin.tasks.index', ['type' => $type, 'filter' => 'overdue']) }}" 
                       class="btn btn-sm {{ $filter === 'overdue' ? 'btn-danger' : 'btn-outline-danger' }}">
                        Overdue
                    </a>
                    <a href="{{ route('admin.tasks.index', ['type' => $type, 'filter' => 'high-priority']) }}" 
                       class="btn btn-sm {{ $filter === 'high-priority' ? 'btn-warning' : 'btn-outline-warning' }}">
                        High Priority
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tasks Table -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tasks"></i> 
                    {{ ucfirst(str_replace('-', ' ', $filter)) }} 
                    ({{ $tasks->total() }})
                </h5>
            </div>
            <div class="card-body p-0">
                @if($tasks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="35%">Task</th>
                                <th width="10%">Status</th>
                                <th width="10%">Priority</th>
                                <th width="15%">Assigned To</th>
                                <th width="10%">Due Date</th>
                                <th width="10%">Category</th>
                                <th width="5%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                            <tr class="{{ $task->isOverdue() ? 'table-danger' : '' }}">
                                <td>
                                    <a href="{{ route('admin.tasks.show', $task) }}" class="text-decoration-none">
                                        #{{ $task->id }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('admin.tasks.show', $task) }}" class="text-decoration-none text-dark">
                                        <strong>{{ $task->title }}</strong>
                                    </a>
                                    @if($task->description)
                                    <br><small class="text-muted">{{ Str::limit(strip_tags($task->description), 100) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $task->status_badge }}">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $task->priority_badge }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </td>
                                <td>
                                    @if($task->getAssignedTo())
                                        <i class="fas fa-user"></i> {{ $task->getAssignedTo()->name }}
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    @if($task->due_date)
                                        <span class="{{ $task->isOverdue() ? 'text-danger fw-bold' : '' }}">
                                            <i class="fas fa-calendar"></i> 
                                            {{ $task->due_date->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">No due date</span>
                                    @endif
                                </td>
                                <td>
                                    @if($task->type === 'dev' && $task->dev_category)
                                        <small>{{ $task->category_label }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.tasks.edit', $task) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.tasks.show', $task) }}" class="btn btn-outline-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No tasks found. <a href="{{ route('admin.tasks.create', ['type' => $type]) }}">Create one now</a>.</p>
                </div>
                @endif
            </div>
            @if($tasks->hasPages())
            <div class="card-footer">
                {{ $tasks->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

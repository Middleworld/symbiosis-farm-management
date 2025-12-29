@extends('layouts.app')

@section('title', 'Create Task')
@section('page-title', 'Create New Task')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus"></i> Create New {{ ucfirst($type) }} Task
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.tasks.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Task Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title') }}" required autofocus>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="5">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="todo" {{ old('status') === 'todo' ? 'selected' : '' }}>To Do</option>
                                    <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="review" {{ old('status') === 'review' ? 'selected' : '' }}>Review</option>
                                    <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority *</label>
                                <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                    <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    @if($type === 'dev')
                    <div class="mb-3">
                        <label for="dev_category" class="form-label">Category</label>
                        <select class="form-select @error('dev_category') is-invalid @enderror" id="dev_category" name="dev_category">
                            <option value="">Select category...</option>
                            <option value="bug" {{ old('dev_category') === 'bug' ? 'selected' : '' }}>🐛 Bug</option>
                            <option value="feature" {{ old('dev_category') === 'feature' ? 'selected' : '' }}>✨ Feature</option>
                            <option value="enhancement" {{ old('dev_category') === 'enhancement' ? 'selected' : '' }}>⚡ Enhancement</option>
                            <option value="maintenance" {{ old('dev_category') === 'maintenance' ? 'selected' : '' }}>🔧 Maintenance</option>
                            <option value="documentation" {{ old('dev_category') === 'documentation' ? 'selected' : '' }}>📚 Documentation</option>
                        </select>
                        @error('dev_category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="assigned_to" class="form-label">Assign To</label>
                                <select class="form-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                                    <option value="">Unassigned</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                       id="due_date" name="due_date" value="{{ old('due_date') }}">
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estimated_hours" class="form-label">Estimated Hours</label>
                        <input type="number" step="0.5" class="form-control @error('estimated_hours') is-invalid @enderror" 
                               id="estimated_hours" name="estimated_hours" value="{{ old('estimated_hours') }}" placeholder="e.g., 2.5">
                        @error('estimated_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.tasks.index', ['type' => $type]) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

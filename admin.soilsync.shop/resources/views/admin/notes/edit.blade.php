@extends('layouts.app')

@section('title', 'Edit Note')
@section('page-title', 'Edit Note')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit"></i> Edit Note
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.notes.update', $note) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title', $note->title) }}" 
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('content') is-invalid @enderror" 
                                  id="content" 
                                  name="content" 
                                  rows="10" 
                                  required>{{ old('content', $note->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" 
                                       class="form-control @error('category') is-invalid @enderror" 
                                       id="category" 
                                       name="category" 
                                       value="{{ old('category', $note->category) }}"
                                       placeholder="e.g., Bug Fix, Feature, Planning">
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tags" class="form-label">Tags (comma separated)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="tags-input" 
                                       placeholder="e.g., important, review, urgent">
                                <small class="text-muted">Press Enter or comma to add tags</small>
                                <div id="tags-display" class="mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_public" 
                                       name="is_public" 
                                       {{ old('is_public', $note->is_public) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_public">
                                    <i class="fas fa-users"></i> Public Note
                                    <small class="text-muted d-block">Visible to all users</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_pinned" 
                                       name="is_pinned" 
                                       {{ old('is_pinned', $note->is_pinned) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_pinned">
                                    <i class="fas fa-thumbtack"></i> Pin Note
                                    <small class="text-muted d-block">Show at top of list</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.notes.show', $note) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Note
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Simple tag management with existing tags
let tags = @json($note->tags ?? []);
const tagsInput = document.getElementById('tags-input');
const tagsDisplay = document.getElementById('tags-display');

// Initialize display with existing tags
updateTagsDisplay();

tagsInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTag(this.value.trim());
        this.value = '';
    }
});

tagsInput.addEventListener('blur', function() {
    if (this.value.trim()) {
        addTag(this.value.trim());
        this.value = '';
    }
});

function addTag(tag) {
    if (tag && !tags.includes(tag)) {
        tags.push(tag);
        updateTagsDisplay();
    }
}

function removeTag(tag) {
    tags = tags.filter(t => t !== tag);
    updateTagsDisplay();
}

function updateTagsDisplay() {
    tagsDisplay.innerHTML = tags.map(tag => `
        <span class="badge bg-primary me-1">
            ${tag}
            <i class="fas fa-times ms-1" style="cursor: pointer;" onclick="removeTag('${tag}')"></i>
        </span>
    `).join('');
    
    // Update hidden input
    let hiddenInputs = document.querySelectorAll('input[name="tags[]"]');
    hiddenInputs.forEach(input => input.remove());
    
    tags.forEach(tag => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'tags[]';
        input.value = tag;
        document.querySelector('form').appendChild(input);
    });
}
</script>
@endsection

@extends('layouts.app')

@section('title', 'Create Product Attribute')

@section('styles')
<style>
/* Scoped text visibility - does not affect sidebar */
.main-content p,
.main-content h1, .main-content h2, .main-content h3, .main-content h4, .main-content h5, .main-content h6,
.main-content label {
    color: #000 !important;
}

/* Ensure form elements have proper contrast */
.form-control {
    color: #000 !important;
    background-color: #fff !important;
    border-color: #ced4da !important;
}

.form-control:focus {
    color: #000 !important;
    background-color: #fff !important;
    border-color: #80bdff !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
}

.form-control::placeholder {
    color: #6c757d !important;
}

/* Ensure labels are visible */
label {
    color: #000 !important;
}

/* Ensure buttons have proper contrast */
.btn-primary {
    background-color: #007bff !important;
    border-color: #007bff !important;
    color: #fff !important;
}

.btn-secondary {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    color: #fff !important;
}

/* Ensure alerts are visible */
.alert {
    color: #000 !important;
}

.alert-info {
    background-color: #d1ecf1 !important;
    border-color: #bee5eb !important;
}

/* Custom checkboxes and radios */
.custom-control-label {
    color: #000 !important;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Product Attribute</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.product-attributes.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slug">Slug</label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                           id="slug" name="slug" value="{{ old('slug') }}" placeholder="auto-generated">
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">URL-friendly identifier (auto-generated from name if empty)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('type') is-invalid @enderror"
                                            id="type" name="type" required>
                                        <option value="select" {{ old('type') == 'select' ? 'selected' : '' }}>Select</option>
                                        <option value="text" {{ old('type') == 'text' ? 'selected' : '' }}>Text</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order">Sort Order</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Display order (lower numbers appear first)</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="options_group">
                            <label for="options">Attribute Options</label>
                            <div id="options_container">
                                @if(old('options'))
                                    @foreach(old('options') as $option)
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="options[]" value="{{ $option }}" placeholder="e.g., Small, Medium, Large">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-danger" type="button" onclick="removeOption(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addOption()">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                            <small class="form-text text-muted">
                                Add options for select-type attributes. Leave empty for text attributes.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="is_visible" name="is_visible" value="1"
                                               {{ old('is_visible', true) ? 'checked' : '' }}>
                                        <label for="is_visible" class="custom-control-label">
                                            Visible on Product Page
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Show this attribute on product pages.
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="is_variation" name="is_variation" value="1"
                                               {{ old('is_variation') ? 'checked' : '' }}>
                                        <label for="is_variation" class="custom-control-label">
                                            Used for Variations
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Use this attribute to create product variations.
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="is_taxonomy" name="is_taxonomy" value="1"
                                               {{ old('is_taxonomy') ? 'checked' : '' }}>
                                        <label for="is_taxonomy" class="custom-control-label">
                                            Is Taxonomy
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Create as a taxonomy attribute.
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="is_active" name="is_active" value="1"
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label for="is_active" class="custom-control-label">
                                            Active
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Only active attributes can be used.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Attribute
                        </button>
                        <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-4">
            <!-- AI Helper Widget will be injected here -->
            <div id="ai-helper-container"></div>
        </div>
    </div>
</div>

<script>
function toggleOptionsVisibility() {
    const type = document.getElementById('type').value;
    const optionsGroup = document.getElementById('options_group');

    if (type === 'select') {
        optionsGroup.style.display = 'block';
    } else {
        optionsGroup.style.display = 'none';
    }
}

function addOption() {
    const container = document.getElementById('options_container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" name="options[]" placeholder="e.g., Small, Medium, Large">
        <div class="input-group-append">
            <button class="btn btn-outline-danger" type="button" onclick="removeOption(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
}

function removeOption(button) {
    button.closest('.input-group').remove();
}

// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim('-');
    document.getElementById('slug').value = slug;
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('type').addEventListener('change', toggleOptionsVisibility);
    toggleOptionsVisibility(); // Set initial state
});
</script>

<script src="{{ asset('js/ai-helper-widget.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AI Helper Widget for product attributes
    if (typeof AIHelperWidget !== 'undefined') {
        window.aiHelper = new AIHelperWidget({
            apiUrl: '/admin/help/ai-helper',
            pageContext: 'product-attributes',
            currentSection: 'create',
            position: 'inline',
            container: '#ai-helper-container'
        });
    }
});
</script>

@endsection
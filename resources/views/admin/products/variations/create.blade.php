@extends('layouts.admin')

@section('title', 'Add Product Variation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add Variation to: {{ $product->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Product
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.products.variations.store', $product) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Variation Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required
                                   placeholder="e.g., Small, Medium, Large">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="sku">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                   id="sku" name="sku" value="{{ old('sku') }}" required
                                   placeholder="e.g., SM-vegbox-small">
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="regular_price">Regular Price</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" class="form-control @error('regular_price') is-invalid @enderror"
                                               id="regular_price" name="regular_price" value="{{ old('regular_price') }}" 
                                               step="0.01" min="0">
                                    </div>
                                    @error('regular_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sale_price">Sale Price</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" class="form-control @error('sale_price') is-invalid @enderror"
                                               id="sale_price" name="sale_price" value="{{ old('sale_price') }}" 
                                               step="0.01" min="0">
                                    </div>
                                    @error('sale_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price">Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" class="form-control @error('price') is-invalid @enderror"
                                               id="price" name="price" value="{{ old('price') }}" 
                                               step="0.01" min="0" required>
                                    </div>
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h4 class="card-title">Attributes</h4>
                            </div>
                            <div class="card-body">
                                <div id="attributes-container">
                                    <div class="attribute-row mb-3">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" name="attributes[0][name]" 
                                                       placeholder="Attribute name (e.g., Size)" value="{{ old('attributes.0.name') }}">
                                            </div>
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" name="attributes[0][value]" 
                                                       placeholder="Value (e.g., Small)" value="{{ old('attributes.0.value') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger btn-block" onclick="removeAttribute(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addAttribute()">
                                    <i class="fas fa-plus"></i> Add Attribute
                                </button>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h4 class="card-title">Stock Management</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="manage_stock" name="manage_stock" value="1" 
                                           {{ old('manage_stock') ? 'checked' : '' }} onchange="toggleStockQuantity()">
                                    <label class="form-check-label" for="manage_stock">Manage stock for this variation</label>
                                </div>

                                <div class="form-group" id="stock_quantity_group" style="display: none;">
                                    <label for="stock_quantity">Stock Quantity</label>
                                    <input type="number" class="form-control @error('stock_quantity') is-invalid @enderror"
                                           id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0">
                                    @error('stock_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Variation
                        </button>
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let attributeCount = 1;

function addAttribute() {
    const container = document.getElementById('attributes-container');
    const row = document.createElement('div');
    row.className = 'attribute-row mb-3';
    row.innerHTML = `
        <div class="row">
            <div class="col-md-5">
                <input type="text" class="form-control" name="attributes[${attributeCount}][name]" 
                       placeholder="Attribute name (e.g., Color)">
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control" name="attributes[${attributeCount}][value]" 
                       placeholder="Value (e.g., Red)">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-block" onclick="removeAttribute(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
    attributeCount++;
}

function removeAttribute(button) {
    button.closest('.attribute-row').remove();
}

function toggleStockQuantity() {
    const checkbox = document.getElementById('manage_stock');
    const group = document.getElementById('stock_quantity_group');
    group.style.display = checkbox.checked ? 'block' : 'none';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleStockQuantity();
});
</script>
@endsection

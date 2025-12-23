@extends('layouts.app')

@section('title', 'Edit Shipping Class')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Shipping Class</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.shipping-classes.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.shipping-classes.update', $shippingClass) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $shippingClass->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slug">Slug</label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                           id="slug" name="slug" value="{{ old('slug', $shippingClass->slug) }}" placeholder="auto-generated">
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">URL-friendly identifier (auto-generated from name if empty)</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description', $shippingClass->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cost">Cost ({{ env('CURRENCY_SYMBOL') }}) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control @error('cost') is-invalid @enderror"
                                           id="cost" name="cost" value="{{ old('cost', $shippingClass->cost) }}" required>
                                    @error('cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order">Sort Order</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $shippingClass->sort_order) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Display order (lower numbers appear first)</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="is_free" name="is_free" value="1"
                                               {{ old('is_free', $shippingClass->is_free) ? 'checked' : '' }}>
                                        <label for="is_free" class="custom-control-label">
                                            Free Shipping
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Check if this shipping class should be free.
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="is_farm_collection" name="is_farm_collection" value="1"
                                               {{ old('is_farm_collection', $shippingClass->is_farm_collection) ? 'checked' : '' }}>
                                        <label for="is_farm_collection" class="custom-control-label">
                                            Farm Collection
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Check if customers can collect from farm.
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="is_active" name="is_active" value="1"
                                               {{ old('is_active', $shippingClass->is_active) ? 'checked' : '' }}>
                                        <label for="is_active" class="custom-control-label">
                                            Active
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Only active shipping classes can be assigned to products.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="delivery_zones_group" style="{{ $shippingClass->is_farm_collection ? 'display: none;' : '' }}">
                            <label for="delivery_zones">Delivery Zones</label>
                            <div id="delivery_zones_container">
                                @php
                                    $zones = old('delivery_zones', $shippingClass->delivery_zones ?? []);
                                @endphp
                                @if($zones && count($zones) > 0)
                                    @foreach($zones as $zone)
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="delivery_zones[]" value="{{ $zone }}" placeholder="e.g., London, Greater London">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-danger" type="button" onclick="removeZone(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addDeliveryZone()">
                                <i class="fas fa-plus"></i> Add Delivery Zone
                            </button>
                            <small class="form-text text-muted">
                                Leave empty to allow delivery to all zones. Add specific zones to restrict delivery areas.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $shippingClass->is_active) ? 'checked' : '' }}>
                                <label for="is_active" class="custom-control-label">
                                    Active
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Only active shipping classes can be assigned to products.
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Shipping Class
                        </button>
                        <a href="{{ route('admin.shipping-classes.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Help</h3>
                </div>
                <div class="card-body">
                    <h5>Farm Collection vs Paid Delivery</h5>
                    <p><strong>Farm Collection:</strong> Customers can pick up items directly from the farm. No delivery cost applies.</p>
                    <p><strong>Paid Delivery:</strong> Items are delivered to the customer's address with an associated cost.</p>

                    <h5>Delivery Zones</h5>
                    <p>Specify geographic areas where delivery is available. Leave empty to allow delivery anywhere.</p>

                    <h5>Shipping Classes</h5>
                    <p>Shipping classes group products with similar delivery requirements and costs.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFarmCollection() {
    const isFarmCollection = document.getElementById('is_farm_collection').checked;
    const costField = document.getElementById('cost');
    const isFreeField = document.getElementById('is_free');

    if (isFarmCollection) {
        costField.value = '0.00';
        costField.disabled = true;
        isFreeField.checked = true;
        isFreeField.disabled = true;
    } else {
        costField.disabled = false;
        isFreeField.disabled = false;
    }
}

function toggleFreeShipping() {
    const isFree = document.getElementById('is_free').checked;
    const costField = document.getElementById('cost');

    if (isFree) {
        costField.value = '0.00';
        costField.disabled = true;
    } else {
        costField.disabled = false;
    }
}

function addDeliveryZone() {
    const container = document.getElementById('delivery_zones_container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" name="delivery_zones[]" placeholder="e.g., London, Greater London">
        <div class="input-group-append">
            <button class="btn btn-outline-danger" type="button" onclick="removeZone(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
}

function removeZone(button) {
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
    document.getElementById('is_farm_collection').addEventListener('change', toggleFarmCollection);
    document.getElementById('is_free').addEventListener('change', toggleFreeShipping);
    toggleFarmCollection(); // Set initial state
    toggleFreeShipping(); // Set initial state
});
</script>
@endsection
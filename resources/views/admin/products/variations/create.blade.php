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

                        <div class="form-group">
                            <label for="price">Base Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">£</span>
                                </div>
                                <input type="number" class="form-control @error('price') is-invalid @enderror"
                                       id="price" name="price" value="{{ old('price') }}" 
                                       step="0.01" min="0" required onchange="updateSolidarityPricing()">
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> This is the recommended/standard price. Set solidarity pricing range below.
                            </small>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Solidarity Pricing -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h4 class="card-title">💚 Solidarity Pricing</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="solidarity_pricing_enabled" 
                                           name="metadata[solidarity_pricing_enabled]" value="1" 
                                           {{ old('metadata.solidarity_pricing_enabled', true) ? 'checked' : '' }}
                                           onchange="toggleSolidarityFields()">
                                    <label class="form-check-label" for="solidarity_pricing_enabled">
                                        <strong>Enable Pay-What-You-Can Pricing</strong>
                                    </label>
                                </div>
                                
                                <div id="solidarity-pricing-fields">
                                    @php
                                        $minPercent = \App\Models\Setting::where('key', 'solidarity_min_percent')->value('value') ?? 70;
                                        $maxPercent = \App\Models\Setting::where('key', 'solidarity_max_percent')->value('value') ?? 167;
                                    @endphp
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="solidarity_min_price">Minimum Price (£)</label>
                                                <input type="number" class="form-control" id="solidarity_min_price" 
                                                       name="metadata[solidarity_min_price]" 
                                                       value="{{ old('metadata.solidarity_min_price') }}" 
                                                       step="0.01" min="0" placeholder="Auto: {{ $minPercent }}%">
                                                <small class="text-muted">Solidarity zone ({{ $minPercent }}%)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="solidarity_recommended_price">Recommended (£)</label>
                                                <input type="number" class="form-control" id="solidarity_recommended_price" 
                                                       name="metadata[solidarity_recommended_price]" 
                                                       value="{{ old('metadata.solidarity_recommended_price') }}" 
                                                       step="0.01" min="0" placeholder="Same as base price">
                                                <small class="text-muted">Standard price</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="solidarity_max_price">Maximum Price (£)</label>
                                                <input type="number" class="form-control" id="solidarity_max_price" 
                                                       name="metadata[solidarity_max_price]" 
                                                       value="{{ old('metadata.solidarity_max_price') }}" 
                                                       step="0.01" min="0" placeholder="Auto: {{ $maxPercent }}%">
                                                <small class="text-muted">Supporter zone ({{ $maxPercent }}%)</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info" style="font-size: 12px;">
                                        <strong>💡 How it works:</strong><br>
                                        • <strong>Solidarity ({{ $minPercent }}-93%)</strong>: For those who need support<br>
                                        • <strong>Standard (recommended)</strong>: True cost/break-even price<br>
                                        • <strong>Supporter (120-{{ $maxPercent }}%)</strong>: Extra contribution<br>
                                        <em>Leave min/max blank to use system defaults based on base price.</em>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h4 class="card-title">Attributes</h4>
                                <small class="text-muted">Select values for each variation attribute</small>
                            </div>
                            <div class="card-body">
                                <div id="global-attributes-container">
                                    <!-- Global attributes will be loaded here -->
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="sr-only">Loading attributes...</span>
                                        </div>
                                        <span class="ml-2">Loading global attributes...</span>
                                    </div>
                                </div>
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
// Load global attributes on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleStockQuantity();
    loadGlobalAttributes();
});

async function loadGlobalAttributes() {
    try {
        const response = await fetch('{{ route("admin.product-attributes.api.list") }}', {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        console.log('Global attributes loaded:', data);
        
        if (data.success && data.attributes.length > 0) {
            renderGlobalAttributes(data.attributes);
        } else {
            document.getElementById('global-attributes-container').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No global variation attributes defined.
                    <a href="{{ route('admin.product-attributes.index') }}" target="_blank" class="alert-link">
                        Create global attributes
                    </a> for Frequency and Payment Schedule.
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading attributes:', error);
        document.getElementById('global-attributes-container').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Failed to load attributes: ${error.message}
            </div>
        `;
    }
}

function renderGlobalAttributes(attributes) {
    let html = '';
    
    attributes.forEach(attr => {
        const options = Array.isArray(attr.options) ? attr.options : [];
        const oldValue = '{{ old("attributes.' + attr.slug + '") }}';
        
        html += `
            <div class="form-group">
                <label for="attr_${attr.slug}">
                    ${attr.label} 
                    <span class="badge badge-info">Global</span>
                    <span class="text-danger">*</span>
                </label>
                <select class="form-control attribute-select" id="attr_${attr.slug}" name="attributes[${attr.slug}]" 
                        data-attr-label="${attr.label}" required onchange="updateVariationName()">
                    <option value="">Select ${attr.label}</option>
        `;
        
        options.forEach(option => {
            const value = typeof option === 'object' ? option.slug : option;
            const label = typeof option === 'object' ? option.name : option;
            html += `<option value="${value}">${label}</option>`;
        });
        
        html += `
                </select>
                <small class="form-text text-muted">Options: ${options.map(o => typeof o === 'object' ? o.name : o).join(', ')}</small>
            </div>
        `;
    });
    
    document.getElementById('global-attributes-container').innerHTML = html;
}

function updateVariationName() {
    const selects = document.querySelectorAll('.attribute-select');
    const selectedValues = [];
    
    selects.forEach(select => {
        if (select.value) {
            // Get the display text from the selected option
            const selectedOption = select.options[select.selectedIndex];
            selectedValues.push(selectedOption.text);
        }
    });
    
    if (selectedValues.length > 0) {
        // Generate name like "Weekly" or "Fortnightly - Monthly"
        const generatedName = selectedValues.join(' - ');
        document.getElementById('name').value = generatedName;
        
        // Generate SKU without random suffix
        const productName = '{{ $product->name }}';
        const skuPrefix = productName.split(' ').map(word => word.charAt(0).toUpperCase()).join('');
        const skuSuffix = selectedValues.join('-').toLowerCase().replace(/[^a-z0-9-]/g, '');
        document.getElementById('sku').value = `${skuPrefix}-${skuSuffix}`;
    }
}

function toggleStockQuantity() {
    const checkbox = document.getElementById('manage_stock');
    const group = document.getElementById('stock_quantity_group');
    group.style.display = checkbox.checked ? 'block' : 'none';
}

function toggleSolidarityFields() {
    const checkbox = document.getElementById('solidarity_pricing_enabled');
    const fields = document.getElementById('solidarity-pricing-fields');
    fields.style.display = checkbox.checked ? 'block' : 'none';
}

function updateSolidarityPricing() {
    const basePrice = parseFloat(document.getElementById('price').value) || 0;
    const minPercent = {{ \App\Models\Setting::where('key', 'solidarity_min_percent')->value('value') ?? 70 }};
    const maxPercent = {{ \App\Models\Setting::where('key', 'solidarity_max_percent')->value('value') ?? 167 }};
    
    if (basePrice > 0) {
        const minPrice = (basePrice * minPercent / 100).toFixed(2);
        const maxPrice = (basePrice * maxPercent / 100).toFixed(2);
        
        // Update placeholders
        document.getElementById('solidarity_min_price').placeholder = `Auto: £${minPrice}`;
        document.getElementById('solidarity_recommended_price').placeholder = `£${basePrice.toFixed(2)}`;
        document.getElementById('solidarity_max_price').placeholder = `Auto: £${maxPrice}`;
        
        // Auto-fill recommended price if empty
        if (!document.getElementById('solidarity_recommended_price').value) {
            document.getElementById('solidarity_recommended_price').value = basePrice.toFixed(2);
        }
    }
}
</script>
@endsection

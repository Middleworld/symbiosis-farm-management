@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Product: {{ $product->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Basic Information</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="name">Product Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                   id="name" name="name" value="{{ old('name', $product->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="sku">SKU <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                                   id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required>
                                            @error('sku')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Unique identifier for the product</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="product_type">Product Type</label>
                                            <select class="form-control @error('product_type') is-invalid @enderror" 
                                                    id="product_type" name="product_type">
                                                <option value="simple" {{ old('product_type', $product->product_type ?? 'simple') == 'simple' ? 'selected' : '' }}>Simple Product</option>
                                                <option value="variable" {{ old('product_type', $product->product_type ?? 'simple') == 'variable' ? 'selected' : '' }}>Variable Product (with variations)</option>
                                            </select>
                                            @error('product_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Variable products have multiple variations (e.g., sizes, colors)</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="category">Category</label>
                                                    <input type="text" class="form-control @error('category') is-invalid @enderror"
                                                           id="category" name="category" value="{{ old('category', $product->category) }}"
                                                           list="categories">
                                                    @error('category')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <datalist id="categories">
                                                        @foreach($categories as $category)
                                                            <option value="{{ $category }}">
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="subcategory">Subcategory</label>
                                                    <input type="text" class="form-control @error('subcategory') is-invalid @enderror"
                                                           id="subcategory" name="subcategory" value="{{ old('subcategory', $product->subcategory) }}">
                                                    @error('subcategory')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing & Inventory -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Pricing & Inventory</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="price">Price <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">{{ env('CURRENCY_SYMBOL', '$') }}</span>
                                                </div>
                                                <input type="number" class="form-control @error('price') is-invalid @enderror"
                                                       id="price" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" required>
                                            </div>
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="cost_price">Cost Price</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">{{ env('CURRENCY_SYMBOL', '$') }}</span>
                                                </div>
                                                <input type="number" class="form-control @error('cost_price') is-invalid @enderror"
                                                       id="cost_price" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0">
                                            </div>
                                            @error('cost_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="stock_quantity">Stock Quantity</label>
                                            <input type="number" class="form-control @error('stock_quantity') is-invalid @enderror"
                                                   id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0">
                                            @error('stock_quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label for="min_stock_level">Min Stock</label>
                                                    <input type="number" class="form-control @error('min_stock_level') is-invalid @enderror"
                                                           id="min_stock_level" name="min_stock_level" value="{{ old('min_stock_level', $product->min_stock_level) }}" min="0">
                                                    @error('min_stock_level')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label for="max_stock_level">Max Stock</label>
                                                    <input type="number" class="form-control @error('max_stock_level') is-invalid @enderror"
                                                           id="max_stock_level" name="max_stock_level" value="{{ old('max_stock_level', $product->max_stock_level) }}" min="0">
                                                    @error('max_stock_level')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tax & Shipping -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h4 class="card-title">Tax & Shipping</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_taxable" name="is_taxable" value="1" {{ old('is_taxable', $product->is_taxable) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_taxable">Taxable</label>
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="tax_rate">Tax Rate (%)</label>
                                            <input type="number" class="form-control @error('tax_rate') is-invalid @enderror"
                                                   id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate) }}" step="0.01" min="0" max="100">
                                            @error('tax_rate')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label for="weight">Weight</label>
                                                    <input type="number" class="form-control @error('weight') is-invalid @enderror"
                                                           id="weight" name="weight" value="{{ old('weight', $product->weight) }}" step="0.01" min="0">
                                                    @error('weight')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label for="unit">Unit</label>
                                                    <select class="form-control @error('unit') is-invalid @enderror" id="unit" name="unit">
                                                        <option value="">Select Unit</option>
                                                        <option value="kg" {{ old('unit', $product->unit) == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                                                        <option value="g" {{ old('unit', $product->unit) == 'g' ? 'selected' : '' }}>Gram (g)</option>
                                                        <option value="lb" {{ old('unit', $product->unit) == 'lb' ? 'selected' : '' }}>Pound (lb)</option>
                                                        <option value="oz" {{ old('unit', $product->unit) == 'oz' ? 'selected' : '' }}>Ounce (oz)</option>
                                                        <option value="l" {{ old('unit', $product->unit) == 'l' ? 'selected' : '' }}>Liter (l)</option>
                                                        <option value="ml" {{ old('unit', $product->unit) == 'ml' ? 'selected' : '' }}>Milliliter (ml)</option>
                                                        <option value="each" {{ old('unit', $product->unit) == 'each' ? 'selected' : '' }}>Each</option>
                                                    </select>
                                                    @error('unit')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="shipping_class_id">Shipping Class</label>
                                            <select class="form-control @error('shipping_class_id') is-invalid @enderror" id="shipping_class_id" name="shipping_class_id">
                                                <option value="">Select Shipping Class</option>
                                                @foreach(\App\Models\ShippingClass::active()->orderBy('sort_order')->orderBy('name')->get() as $shippingClass)
                                                    <option value="{{ $shippingClass->id }}" {{ old('shipping_class_id', $product->shipping_class_id) == $shippingClass->id ? 'selected' : '' }}>
                                                        {{ $shippingClass->name }}
                                                        @if($shippingClass->is_farm_collection)
                                                            (Farm Collection)
                                                        @elseif($shippingClass->is_free)
                                                            (Free Shipping)
                                                        @else
                                                            ({{ env('CURRENCY_SYMBOL') }}{{ number_format($shippingClass->cost, 2) }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('shipping_class_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Determines delivery options and costs for this product.
                                                <a href="{{ route('admin.shipping-classes.index') }}" target="_blank">Manage shipping classes</a>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Solidarity Pricing (WooCommerce Sync) -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h4 class="card-title">💚 Solidarity Pricing</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" class="form-check-input" id="solidarity_pricing_enabled" 
                                                   name="metadata[solidarity_pricing_enabled]" value="1" 
                                                   {{ old('metadata.solidarity_pricing_enabled', $product->metadata['solidarity_pricing_enabled'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="solidarity_pricing_enabled">
                                                <strong>Enable Pay-What-You-Can Pricing</strong>
                                            </label>
                                        </div>
                                        
                                        <div id="solidarity-pricing-fields" style="{{ old('metadata.solidarity_pricing_enabled', $product->metadata['solidarity_pricing_enabled'] ?? false) ? '' : 'display:none;' }}">
                                            @php
                                                $minPercent = \App\Models\Setting::where('key', 'solidarity_min_percent')->value('value') ?? 70;
                                                $maxPercent = \App\Models\Setting::where('key', 'solidarity_max_percent')->value('value') ?? 167;
                                                $calculatedMin = ($product->price ?? 0) * ($minPercent / 100);
                                                $calculatedMax = ($product->price ?? 0) * ($maxPercent / 100);
                                            @endphp
                                            
                                            <div class="alert alert-warning" style="font-size: 13px;">
                                                <strong>ℹ️ Note:</strong> The <strong>Recommended Price</strong> should match your product's regular price (£{{ number_format($product->price ?? 0, 2) }}). 
                                                Min/Max will auto-calculate using system defaults ({{ $minPercent }}% / {{ $maxPercent }}%) if left blank.
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="solidarity_min_price">Minimum Price (£)</label>
                                                <input type="number" class="form-control" id="solidarity_min_price" 
                                                       name="metadata[solidarity_min_price]" 
                                                       value="{{ old('metadata.solidarity_min_price', $product->metadata['solidarity_min_price'] ?? '') }}" 
                                                       step="0.01" min="0" placeholder="Auto: £{{ number_format($calculatedMin, 2) }}">
                                                <small class="text-muted">Solidarity zone floor ({{ $minPercent }}% of recommended)</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="solidarity_recommended_price">
                                                    Recommended Price (£) <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control" id="solidarity_recommended_price" 
                                                       name="metadata[solidarity_recommended_price]" 
                                                       value="{{ old('metadata.solidarity_recommended_price', $product->metadata['solidarity_recommended_price'] ?? $product->price ?? '') }}" 
                                                       step="0.01" min="0" placeholder="True cost price (same as regular price)">
                                                <small class="text-muted">Standard/true cost price - usually same as product price above</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="solidarity_max_price">Maximum Price (£)</label>
                                                <input type="number" class="form-control" id="solidarity_max_price" 
                                                       name="metadata[solidarity_max_price]" 
                                                       value="{{ old('metadata.solidarity_max_price', $product->metadata['solidarity_max_price'] ?? '') }}" 
                                                       step="0.01" min="0" placeholder="Auto: £{{ number_format($calculatedMax, 2) }}">
                                                <small class="text-muted">Supporter zone ceiling ({{ $maxPercent }}% of recommended)</small>
                                            </div>
                                            
                                            <div class="alert alert-info mt-2" style="font-size: 12px;">
                                                <strong>💡 How it works:</strong><br>
                                                • <strong>Solidarity ({{ $minPercent }}-93%)</strong>: For those who need support<br>
                                                • <strong>Standard (recommended)</strong>: True cost/break-even price<br>
                                                • <strong>Supporter (120-{{ $maxPercent }}%)</strong>: Extra contribution to support others<br>
                                                <em>Change defaults in <a href="{{ route('admin.settings') }}" target="_blank">Settings → WooCommerce</a></em>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h4 class="card-title">Status</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Upload -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h4 class="card-title">Product Image</h4>
                                    </div>
                                    <div class="card-body">
                                        @if($product->image_url)
                                            <div class="mb-3">
                                                <label>Current Image:</label><br>
                                                @if(str_starts_with($product->image_url, 'http'))
                                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-width: 200px;">
                                                @else
                                                    <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-width: 200px;">
                                                @endif
                                            </div>
                                        @endif

                                        <div class="form-group">
                                            <label for="image">Upload New Image (leave empty to keep current)</label>
                                            <input type="file" class="form-control-file @error('image') is-invalid @enderror"
                                                   id="image" name="image" accept="image/*">
                                            @error('image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Accepted formats: JPEG, PNG, JPG, GIF. Max size: 2MB</small>
                                        </div>
                                        <div id="image-preview" class="mt-2" style="display: none;">
                                            <img id="preview-img" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Variations (for variable products) -->
                        @if($product->product_type === 'variable')
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Product Variations</h4>
                                        <div class="card-tools">
                                            <a href="{{ route('admin.products.variations.create', $product) }}" class="btn btn-success btn-sm">
                                                <i class="fas fa-plus"></i> Add Variation
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($product->variations && $product->variations->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>SKU</th>
                                                        <th>Attributes</th>
                                                        <th>Price</th>
                                                        <th>Stock</th>
                                                        <th>Status</th>
                                                        <th>WooCommerce ID</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($product->variations as $variation)
                                                    <tr>
                                                        <td>{{ $variation->name }}</td>
                                                        <td><code>{{ $variation->sku }}</code></td>
                                                        <td>
                                                            @if($variation->attributes)
                                                                @foreach($variation->attributes as $key => $value)
                                                                    <span class="badge badge-info">{{ $key }}: {{ $value }}</span>
                                                                @endforeach
                                                            @endif
                                                        </td>
                                                        <td>{{ $variation->formatted_price }}</td>
                                                        <td>
                                                            @if($variation->manage_stock)
                                                                {{ $variation->stock_quantity ?? 0 }}
                                                            @else
                                                                <span class="text-muted">Not managed</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($variation->is_active)
                                                                <span class="badge badge-success">Active</span>
                                                            @else
                                                                <span class="badge badge-secondary">Inactive</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($variation->woo_variation_id)
                                                                <span class="badge badge-info">{{ $variation->woo_variation_id }}</span>
                                                            @else
                                                                <span class="text-muted small">Not synced</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('admin.products.variations.edit', [$product, $variation]) }}" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="{{ route('admin.products.variations.destroy', [$product, $variation]) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this variation?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @else
                                        <p class="text-muted text-center py-3">
                                            No variations added yet. <a href="{{ route('admin.products.variations.create', $product) }}">Add your first variation</a>
                                        </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Get solidarity pricing percentages from settings
const minPercent = {{ $minPercent ?? 70 }} / 100;
const maxPercent = {{ $maxPercent ?? 167 }} / 100;

// Image preview
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

// Solidarity pricing toggle
document.getElementById('solidarity_pricing_enabled').addEventListener('change', function() {
    const fieldsContainer = document.getElementById('solidarity-pricing-fields');
    fieldsContainer.style.display = this.checked ? 'block' : 'none';
    
    // Auto-fill recommended price from product price if enabling for first time
    if (this.checked) {
        const recommendedPriceField = document.getElementById('solidarity_recommended_price');
        const productPrice = document.getElementById('price').value;
        
        // Only auto-fill if recommended price is empty
        if (!recommendedPriceField.value && productPrice) {
            recommendedPriceField.value = productPrice;
            updatePlaceholders(productPrice);
        }
    }
});

// Update solidarity pricing placeholders when product price changes
document.getElementById('price').addEventListener('input', function() {
    const productPrice = parseFloat(this.value) || 0;
    updatePlaceholders(productPrice);
    
    // Auto-update recommended price if solidarity pricing is enabled
    const solidarityEnabled = document.getElementById('solidarity_pricing_enabled').checked;
    const recommendedPriceField = document.getElementById('solidarity_recommended_price');
    
    if (solidarityEnabled && recommendedPriceField) {
        // Only update if it's currently empty or matches old product price
        if (!recommendedPriceField.value || recommendedPriceField.value == recommendedPriceField.dataset.oldPrice) {
            recommendedPriceField.value = productPrice.toFixed(2);
            recommendedPriceField.dataset.oldPrice = productPrice.toFixed(2);
        }
    }
});

function updatePlaceholders(price) {
    const minField = document.getElementById('solidarity_min_price');
    const maxField = document.getElementById('solidarity_max_price');
    
    if (minField) {
        minField.placeholder = 'Auto: £' + (price * minPercent).toFixed(2);
    }
    if (maxField) {
        maxField.placeholder = 'Auto: £' + (price * maxPercent).toFixed(2);
    }
}
</script>
@endsection
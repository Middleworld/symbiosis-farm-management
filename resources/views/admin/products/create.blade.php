@extends('layouts.admin')

@section('title', 'Add New Product')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Product</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
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
                                                   id="name" name="name" value="{{ old('name') }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="sku">SKU <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                                   id="sku" name="sku" value="{{ old('sku') }}" required>
                                            @error('sku')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Unique identifier for the product</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="product_type">Product Type</label>
                                            <select class="form-control @error('product_type') is-invalid @enderror" 
                                                    id="product_type" name="product_type">
                                                <option value="simple" {{ old('product_type', 'simple') == 'simple' ? 'selected' : '' }}>Simple Product</option>
                                                <option value="variable" {{ old('product_type') == 'variable' ? 'selected' : '' }}>Variable Product (with variations)</option>
                                            </select>
                                            @error('product_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Variable products have multiple variations (e.g., sizes, colors)</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="4">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="category">Category</label>
                                                    <input type="text" class="form-control @error('category') is-invalid @enderror"
                                                           id="category" name="category" value="{{ old('category') }}"
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
                                                           id="subcategory" name="subcategory" value="{{ old('subcategory') }}">
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
                                                    <span class="input-group-text">{{ config('pos_payments.currency_symbol', env('CURRENCY_SYMBOL', '£')) }}</span>
                                                </div>
                                                <input type="number" class="form-control @error('price') is-invalid @enderror"
                                                       id="price" name="price" value="{{ old('price') }}" step="0.01" min="0" required>
                                            </div>
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="cost_price">Cost Price</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">{{ config('pos_payments.currency_symbol', env('CURRENCY_SYMBOL', '£')) }}</span>
                                                </div>
                                                <input type="number" class="form-control @error('cost_price') is-invalid @enderror"
                                                       id="cost_price" name="cost_price" value="{{ old('cost_price') }}" step="0.01" min="0">
                                            </div>
                                            @error('cost_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="stock_quantity">Stock Quantity</label>
                                            <input type="number" class="form-control @error('stock_quantity') is-invalid @enderror"
                                                   id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity') }}" min="0">
                                            @error('stock_quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label for="min_stock_level">Min Stock</label>
                                                    <input type="number" class="form-control @error('min_stock_level') is-invalid @enderror"
                                                           id="min_stock_level" name="min_stock_level" value="{{ old('min_stock_level') }}" min="0">
                                                    @error('min_stock_level')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label for="max_stock_level">Max Stock</label>
                                                    <input type="number" class="form-control @error('max_stock_level') is-invalid @enderror"
                                                           id="max_stock_level" name="max_stock_level" value="{{ old('max_stock_level') }}" min="0">
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
                                            <input type="checkbox" class="form-check-input" id="is_taxable" name="is_taxable" value="1" {{ old('is_taxable') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_taxable">Taxable</label>
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="tax_rate">Tax Rate (%)</label>
                                            <input type="number" class="form-control @error('tax_rate') is-invalid @enderror"
                                                   id="tax_rate" name="tax_rate" value="{{ old('tax_rate') }}" step="0.01" min="0" max="100">
                                            @error('tax_rate')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label for="weight">Weight</label>
                                                    <input type="number" class="form-control @error('weight') is-invalid @enderror"
                                                           id="weight" name="weight" value="{{ old('weight') }}" step="0.01" min="0">
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
                                                        <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                                                        <option value="g" {{ old('unit') == 'g' ? 'selected' : '' }}>Gram (g)</option>
                                                        <option value="lb" {{ old('unit') == 'lb' ? 'selected' : '' }}>Pound (lb)</option>
                                                        <option value="oz" {{ old('unit') == 'oz' ? 'selected' : '' }}>Ounce (oz)</option>
                                                        <option value="l" {{ old('unit') == 'l' ? 'selected' : '' }}>Liter (l)</option>
                                                        <option value="ml" {{ old('unit') == 'ml' ? 'selected' : '' }}>Milliliter (ml)</option>
                                                        <option value="each" {{ old('unit') == 'each' ? 'selected' : '' }}>Each</option>
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
                                                    <option value="{{ $shippingClass->id }}" {{ old('shipping_class_id') == $shippingClass->id ? 'selected' : '' }}>
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

                                <!-- Status -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h4 class="card-title">Status</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
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
                                        <div class="form-group">
                                            <label for="image">Upload Image</label>
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
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Product
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
</script>
@endsection
@extends('layouts.admin')

@section('title', 'Product Details')

@section('styles')
<style>
.card-body .mb-0 {
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
}
.description-content {
    line-height: 1.6;
    font-size: 0.95rem;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Details: {{ $product->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Product Image -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Product Image</h4>
                                </div>
                                <div class="card-body text-center">
                                    @if($product->image_url)
                                        @if(str_starts_with($product->image_url, 'http'))
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded">
                                        @else
                                            <img src="{{ route('product.image', ['path' => $product->image_url]) }}" alt="{{ $product->name }}" class="img-fluid rounded">
                                        @endif
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                                            <div class="text-center">
                                                <i class="fas fa-image fa-4x text-muted mb-3"></i>
                                                <p class="text-muted">No image available</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Product Information -->
                        <div class="col-md-8">
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">Basic Information</h4>
                                        </div>
                                        <div class="card-body">
                                            <dl class="row">
                                                <dt class="col-sm-4">Name:</dt>
                                                <dd class="col-sm-8">{{ $product->name }}</dd>

                                                <dt class="col-sm-4">SKU:</dt>
                                                <dd class="col-sm-8">
                                                    <code>{{ $product->sku }}</code>
                                                </dd>

                                                <dt class="col-sm-4">Category:</dt>
                                                <dd class="col-sm-8">{{ $product->category ?: 'Not specified' }}</dd>

                                                <dt class="col-sm-4">Subcategory:</dt>
                                                <dd class="col-sm-8">{{ $product->subcategory ?: 'Not specified' }}</dd>

                                                <dt class="col-sm-4">Status:</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-secondary' }}">
                                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </dd>

                                                <dt class="col-sm-4">Created:</dt>
                                                <dd class="col-sm-8">{{ $product->created_at->format('M d, Y H:i') }}</dd>

                                                <dt class="col-sm-4">Updated:</dt>
                                                <dd class="col-sm-8">{{ $product->updated_at->format('M d, Y H:i') }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pricing & Inventory -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">Pricing & Inventory</h4>
                                        </div>
                                        <div class="card-body">
                                            <dl class="row">
                                                <dt class="col-sm-5">Price:</dt>
                                                <dd class="col-sm-7">
                                                    <strong class="text-success">{{ env('CURRENCY_SYMBOL', '$') }}{{ number_format($product->price, 2) }}</strong>
                                                </dd>

                                                <dt class="col-sm-5">Cost Price:</dt>
                                                <dd class="col-sm-7">
                                                    @if($product->cost_price)
                                                        {{ env('CURRENCY_SYMBOL', '$') }}{{ number_format($product->cost_price, 2) }}
                                                        <small class="text-muted">
                                                            ({{ number_format((($product->price - $product->cost_price) / $product->cost_price) * 100, 1) }}% margin)
                                                        </small>
                                                    @else
                                                        Not specified
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">Stock:</dt>
                                                <dd class="col-sm-7">
                                                    @if($product->stock_quantity !== null)
                                                        {{ $product->stock_quantity }}
                                                        @if($product->min_stock_level && $product->stock_quantity <= $product->min_stock_level)
                                                            <span class="badge badge-warning ml-1">Low Stock</span>
                                                        @endif
                                                    @else
                                                        Unlimited
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">Min Stock:</dt>
                                                <dd class="col-sm-7">{{ $product->min_stock_level ?: 'Not set' }}</dd>

                                                <dt class="col-sm-5">Max Stock:</dt>
                                                <dd class="col-sm-7">{{ $product->max_stock_level ?: 'Not set' }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tax & Shipping -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">Tax & Shipping</h4>
                                        </div>
                                        <div class="card-body">
                                            <dl class="row">
                                                <dt class="col-sm-5">Taxable:</dt>
                                                <dd class="col-sm-7">
                                                    <span class="badge {{ $product->is_taxable ? 'badge-success' : 'badge-secondary' }}">
                                                        {{ $product->is_taxable ? 'Yes' : 'No' }}
                                                    </span>
                                                </dd>

                                                <dt class="col-sm-5">Tax Rate:</dt>
                                                <dd class="col-sm-7">
                                                    @if($product->tax_rate)
                                                        {{ number_format($product->tax_rate, 2) }}%
                                                    @else
                                                        Not specified
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">Weight:</dt>
                                                <dd class="col-sm-7">
                                                    @if($product->weight)
                                                        {{ $product->weight }} {{ $product->unit ?: 'units' }}
                                                    @else
                                                        Not specified
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">Unit:</dt>
                                                <dd class="col-sm-7">{{ $product->unit ?: 'Not specified' }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <!-- WooCommerce Integration -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">WooCommerce Integration</h4>
                                        </div>
                                        <div class="card-body">
                                            <dl class="row">
                                                <dt class="col-sm-5">WooCommerce ID:</dt>
                                                <dd class="col-sm-7">
                                                    @if($product->woo_product_id)
                                                        <code>{{ $product->woo_product_id }}</code>
                                                        <small class="text-muted d-block">Linked to WooCommerce</small>
                                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="syncWithWooCommerce({{ $product->id }})">
                                                            <i class="fab fa-wordpress"></i> Sync Now
                                                        </button>
                                                        <a href="{{ route('admin.products.iframe-edit', $product) }}" class="btn btn-sm btn-outline-secondary mt-2 ml-1" target="_blank">
                                                            <i class="fas fa-external-link-alt"></i> WooCommerce Admin
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2 ml-1" onclick="unlinkFromWooCommerce({{ $product->id }})">
                                                            <i class="fas fa-unlink"></i> Unlink
                                                        </button>
                                                    @else
                                                        <span class="text-muted">Not linked</span>
                                                        <small class="text-muted d-block">Will be linked in Phase 2</small>
                                                        <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="syncWithWooCommerce({{ $product->id }})">
                                                            <i class="fas fa-link"></i> Link to WooCommerce
                                                        </button>
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">Sync Status:</dt>
                                                <dd class="col-sm-7">
                                                    <span class="badge badge-info">Phase 2 - API Integration Ready</span>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Short Description -->
                            @if(isset($product->metadata['short_description']) && $product->metadata['short_description'])
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h4 class="card-title mb-0">Short Description</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-0 text-muted description-content">{!! $product->metadata['short_description'] !!}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Description -->
                            @if($product->description)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="card-title">Full Description</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-0 description-content">{!! $product->description !!}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- SEO Information -->
                            @if(isset($product->metadata['seo_title']) || isset($product->metadata['seo_description']) || isset($product->metadata['seo_keywords']))
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-info text-white">
                                                <h4 class="card-title mb-0">
                                                    <i class="fas fa-search"></i> SEO Information
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                @if(isset($product->metadata['seo_title']) && $product->metadata['seo_title'])
                                                    <div class="mb-3">
                                                        <strong class="d-block mb-1">SEO Title:</strong>
                                                        <div class="text-muted">{{ $product->metadata['seo_title'] }}</div>
                                                        <small class="text-info">{{ strlen($product->metadata['seo_title']) }} characters</small>
                                                    </div>
                                                @endif

                                                @if(isset($product->metadata['seo_description']) && $product->metadata['seo_description'])
                                                    <div class="mb-3">
                                                        <strong class="d-block mb-1">Meta Description:</strong>
                                                        <div class="text-muted">{{ $product->metadata['seo_description'] }}</div>
                                                        <small class="text-info">{{ strlen($product->metadata['seo_description']) }} characters</small>
                                                    </div>
                                                @endif

                                                @if(isset($product->metadata['seo_keywords']) && $product->metadata['seo_keywords'])
                                                    <div class="mb-0">
                                                        <strong class="d-block mb-1">Focus Keywords:</strong>
                                                        <div class="text-muted">{{ $product->metadata['seo_keywords'] }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Product
                            </a>
                            <button type="button" class="btn btn-sm ml-2 {{ $product->is_active ? 'btn-secondary' : 'btn-success' }}"
                                    onclick="toggleStatus({{ $product->id }})">
                                <i class="fas fa-{{ $product->is_active ? 'ban' : 'check' }}"></i>
                                {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-danger" onclick="deleteProduct({{ $product->id }}, '{{ $product->name }}')">
                                <i class="fas fa-trash"></i> Delete Product
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleStatus(productId) {
    if (confirm('Are you sure you want to change this product\'s status?')) {
        $.ajax({
            url: '{{ route("admin.products.toggle-active", ":id") }}'.replace(':id', productId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while updating the product status.');
            }
        });
    }
}

function deleteProduct(productId, productName) {
    if (confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.products.destroy", ":id") }}'.replace(':id', productId);

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = '{{ csrf_token() }}';
        form.appendChild(token);

        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        form.appendChild(method);

        document.body.appendChild(form);
        form.submit();
    }
}

function syncWithWooCommerce(productId) {
    if (confirm('Sync this product with WooCommerce?')) {
        $.ajax({
            url: '{{ route("admin.products.sync-woocommerce", ":id") }}'.replace(':id', productId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('An error occurred while syncing with WooCommerce: ' + xhr.responseJSON?.message || 'Unknown error');
            }
        });
    }
}

function unlinkFromWooCommerce(productId) {
    if (confirm('Unlink this product from WooCommerce? The product will remain in WooCommerce but will no longer be synced.')) {
        $.ajax({
            url: '{{ route("admin.products.unlink-woocommerce", ":id") }}'.replace(':id', productId),
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('An error occurred while unlinking from WooCommerce: ' + xhr.responseJSON?.message || 'Unknown error');
            }
        });
    }
}
</script>
@endsection
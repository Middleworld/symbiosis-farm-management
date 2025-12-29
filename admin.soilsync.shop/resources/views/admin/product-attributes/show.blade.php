@extends('layouts.app')

@section('title', 'Product Attribute Details')

@section('styles')
<style>
/* Scoped text visibility - does not affect sidebar */
.main-content p,
.main-content h1, .main-content h2, .main-content h3, .main-content h4, .main-content h5, .main-content h6,
.main-content label {
    color: #000 !important;
}

/* Ensure table has proper contrast */
.table {
    background-color: #fff !important;
    color: #000 !important;
}

.table td, .table th {
    background-color: #fff !important;
    color: #000 !important;
    border-color: #dee2e6 !important;
}

/* Ensure badges have white text on colored backgrounds */
.badge-success {
    background-color: #28a745 !important;
    color: #fff !important;
}

.badge-info {
    background-color: #17a2b8 !important;
    color: #fff !important;
}

.badge-secondary {
    background-color: #6c757d !important;
    color: #fff !important;
}

.badge-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.badge-danger {
    background-color: #dc3545 !important;
    color: #fff !important;
}

/* Ensure links are visible */
a {
    color: #007bff !important;
}

a:hover {
    color: #0056b3 !important;
}

/* Ensure muted text is still readable */
.text-muted {
    color: #6c757d !important;
}

/* Ensure alerts are visible */
.alert {
    color: #000 !important;
}

.alert-info {
    background-color: #d1ecf1 !important;
    border-color: #bee5eb !important;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Attribute Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('admin.product-attributes.edit', $attribute) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form action="{{ route('admin.product-attributes.destroy', $attribute) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Are you sure you want to delete this attribute?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Name:</th>
                                    <td>{{ $attribute->name }}</td>
                                </tr>
                                <tr>
                                    <th>Slug:</th>
                                    <td><code>{{ $attribute->slug }}</code></td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td>
                                        <span class="badge badge-info">{{ ucfirst($attribute->type) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Sort Order:</th>
                                    <td>{{ $attribute->sort_order }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Status:</th>
                                    <td>
                                        @if($attribute->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Visibility:</th>
                                    <td>
                                        @if($attribute->is_visible)
                                            <span class="badge badge-success">Visible on Product Page</span>
                                        @else
                                            <span class="badge badge-secondary">Hidden</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Used for Variations:</th>
                                    <td>
                                        @if($attribute->is_variation)
                                            <span class="badge badge-primary">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Is Taxonomy:</th>
                                    <td>
                                        @if($attribute->is_taxonomy)
                                            <span class="badge badge-info">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($attribute->options && count($attribute->options) > 0)
                        <div class="row">
                            <div class="col-12">
                                <h5>Attribute Options</h5>
                                <div class="d-flex flex-wrap">
                                    @foreach($attribute->options as $option)
                                        <span class="badge badge-outline-primary mr-2 mb-2">{{ $option }}</span>
                                    @endforeach
                                </div>
                                <small class="text-muted">{{ count($attribute->options) }} option(s)</small>
                            </div>
                        </div>
                    @endif

                    @if($attribute->woo_id)
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-link"></i>
                                    <strong>WooCommerce Integration:</strong>
                                    This attribute is synced with WooCommerce (ID: {{ $attribute->woo_id }}).
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Related Variations -->
            @if($attribute->isUsedInVariations())
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Related Product Variations</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Variation</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attribute->getRelatedVariations() as $variation)
                                        <tr>
                                            <td>
                                                @if($variation->product_id)
                                                    <a href="{{ route('admin.products.show', $variation->product_id) }}">
                                                        Product #{{ $variation->product_id }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">Product not found</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($variation->attributes)
                                                    @foreach($variation->attributes as $attrName => $attrValue)
                                                        <span class="badge badge-light">{{ $attrName }}: {{ $attrValue }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">No attributes</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($variation->regular_price)
                                                    £{{ number_format($variation->regular_price, 2) }}
                                                    @if($variation->sale_price)
                                                        <br><small class="text-muted">Sale: £{{ number_format($variation->sale_price, 2) }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
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
                                                <span class="text-muted">N/A</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-md-4">
            <!-- AI Helper Widget will be injected here -->
            <div id="ai-helper-container"></div>
        </div>
    </div>
</div>

<script src="{{ asset('js/ai-helper-widget.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AI Helper Widget for product attributes
    if (typeof AIHelperWidget !== 'undefined') {
        window.aiHelper = new AIHelperWidget({
            apiUrl: '/admin/help/ai-helper',
            pageContext: 'product-attributes',
            currentSection: 'show',
            position: 'inline',
            container: '#ai-helper-container'
        });
    }
});
</script>

@endsection
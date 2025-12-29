@extends('layouts.admin')

@section('title', 'Edit Product in WooCommerce')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fab fa-wordpress"></i> Edit Product: {{ $product->name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Product
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-list"></i> All Products
                        </a>
                        <a href="{{ $editUrl }}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Open in New Tab
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-external-link-alt"></i>
                        <strong>WooCommerce Product Editor</strong>
                        <p class="mb-3">Advanced WooCommerce editing features are available directly in the WooCommerce admin interface.</p>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Product Information</h5>
                                <ul class="list-unstyled">
                                    <li><strong>ID:</strong> {{ $product->id }}</li>
                                    <li><strong>SKU:</strong> {{ $product->sku }}</li>
                                    <li><strong>WooCommerce ID:</strong> {{ $product->woo_product_id }}</li>
                                    <li><strong>Status:</strong> <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Edit Options</h5>
                                <p>Click the button below to open the WooCommerce product editor in a new tab:</p>
                                <a href="{{ $editUrl }}" class="btn btn-primary btn-lg" target="_blank">
                                    <i class="fas fa-edit"></i> Edit in WooCommerce
                                </a>
                                <p class="text-muted mt-2 small">
                                    <i class="fas fa-info-circle"></i> Changes made in WooCommerce will be reflected in this admin interface after synchronization.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
@endsection
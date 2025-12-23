@extends('layouts.admin')

@section('title', 'WooCommerce Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fab fa-wordpress"></i> WooCommerce Products Dashboard
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                        <a href="{{ $dashboardUrl }}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Open in New Tab
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-external-link-alt"></i>
                        <strong>WooCommerce Products Dashboard</strong>
                        <p class="mb-3">Access advanced WooCommerce features for managing products, orders, and store settings.</p>

                        <div class="row">
                            <div class="col-md-8">
                                <h5>Available Features</h5>
                                <ul>
                                    <li><strong>Product Management:</strong> Create, edit, and manage all product types</li>
                                    <li><strong>Variable Products:</strong> Handle products with multiple variations</li>
                                    <li><strong>Advanced Settings:</strong> Configure shipping, taxes, and inventory</li>
                                    <li><strong>Bulk Operations:</strong> Edit multiple products at once</li>
                                    <li><strong>Order Management:</strong> Process and fulfill customer orders</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h5>Access Dashboard</h5>
                                <p>Click the button below to open the WooCommerce admin dashboard:</p>
                                <a href="{{ $dashboardUrl }}" class="btn btn-primary btn-lg" target="_blank">
                                    <i class="fas fa-tachometer-alt"></i> Open WooCommerce Dashboard
                                </a>
                                <p class="text-muted mt-2 small">
                                    <i class="fas fa-info-circle"></i> Opens in a new tab for full functionality.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
@endsection
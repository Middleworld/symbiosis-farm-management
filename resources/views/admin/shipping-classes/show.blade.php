@extends('layouts.app')

@section('title', 'Shipping Class Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $shippingClass->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.shipping-classes.edit', $shippingClass) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.shipping-classes.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Name:</dt>
                        <dd class="col-sm-9">{{ $shippingClass->name }}</dd>

                        <dt class="col-sm-3">Slug:</dt>
                        <dd class="col-sm-9">{{ $shippingClass->slug }}</dd>

                        <dt class="col-sm-3">Type:</dt>
                        <dd class="col-sm-9">
                            @if($shippingClass->is_farm_collection)
                                <span class="badge badge-success">Farm Collection</span>
                            @else
                                <span class="badge badge-info">Delivery</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Cost:</dt>
                        <dd class="col-sm-9">
                            @if($shippingClass->is_free || $shippingClass->is_farm_collection)
                                <span class="text-muted">Free</span>
                            @else
                                {{ env('CURRENCY_SYMBOL') }}{{ number_format($shippingClass->cost, 2) }}
                            @endif
                        </dd>

                        <dt class="col-sm-3">Free Shipping:</dt>
                        <dd class="col-sm-9">
                            @if($shippingClass->is_free)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Sort Order:</dt>
                        <dd class="col-sm-9">{{ $shippingClass->sort_order }}</dd>

                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            @if($shippingClass->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </dd>

                        @if($shippingClass->description)
                        <dt class="col-sm-3">Description:</dt>
                        <dd class="col-sm-9">{{ $shippingClass->description }}</dd>
                        @endif

                        @if($shippingClass->is_farm_collection && $shippingClass->collection_instructions)
                        <dt class="col-sm-3">Collection Instructions:</dt>
                        <dd class="col-sm-9">{{ $shippingClass->collection_instructions }}</dd>
                        @endif

                        @if(!$shippingClass->is_farm_collection && $shippingClass->delivery_zones && count($shippingClass->delivery_zones) > 0)
                        <dt class="col-sm-3">Delivery Zones:</dt>
                        <dd class="col-sm-9">
                            <ul class="list-unstyled">
                                @foreach($shippingClass->delivery_zones as $zone)
                                    <li><i class="fas fa-map-marker-alt text-muted"></i> {{ $zone }}</li>
                                @endforeach
                            </ul>
                        </dd>
                        @endif

                        <dt class="col-sm-3">Created:</dt>
                        <dd class="col-sm-9">{{ $shippingClass->created_at->format('M j, Y g:i A') }}</dd>

                        <dt class="col-sm-3">Last Updated:</dt>
                        <dd class="col-sm-9">{{ $shippingClass->updated_at->format('M j, Y g:i A') }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Products using this shipping class -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Products ({{ $shippingClass->products()->count() }})</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    @if($shippingClass->products()->count() > 0)
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shippingClass->products as $product)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.products.show', $product) }}">{{ $product->name }}</a>
                                </td>
                                <td>{{ $product->sku ?? 'N/A' }}</td>
                                <td>{{ env('CURRENCY_SYMBOL') }}{{ number_format($product->price, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $product->stock_status_color }}">{{ $product->stock_quantity }}</span>
                                </td>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="p-4 text-center text-muted">
                        No products are currently assigned to this shipping class.
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.shipping-classes.edit', $shippingClass) }}" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-edit"></i> Edit Shipping Class
                    </a>

                    @if($shippingClass->products()->count() === 0)
                    <form action="{{ route('admin.shipping-classes.destroy', $shippingClass) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this shipping class? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="fas fa-trash"></i> Delete Shipping Class
                        </button>
                    </form>
                    @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Cannot Delete:</strong> This shipping class is assigned to {{ $shippingClass->products()->count() }} product(s).
                        Remove all product assignments before deleting.
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h4 mb-0">{{ $shippingClass->products()->count() }}</div>
                            <small class="text-muted">Products</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0">{{ $shippingClass->products()->where('is_active', true)->count() }}</div>
                            <small class="text-muted">Active Products</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
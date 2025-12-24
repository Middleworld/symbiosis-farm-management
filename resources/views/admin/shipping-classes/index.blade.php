@extends('layouts.app')

@section('title', 'Shipping Classes')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Shipping Classes</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.shipping-classes.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Shipping Class
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap" style="color: #000;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Cost</th>
                                <th>Free</th>
                                <th>Sort Order</th>
                                <th>Status</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shippingClasses as $shippingClass)
                            <tr>
                                <td>
                                    <strong>{{ $shippingClass->name }}</strong>
                                    @if($shippingClass->description)
                                        <br><small class="text-muted">{{ Str::limit($shippingClass->description, 50) }}</small>
                                    @endif
                                    <br><small class="text-muted">{{ $shippingClass->slug }}</small>
                                </td>
                                <td>
                                    @if($shippingClass->is_farm_collection)
                                        <span class="badge bg-success text-white">Farm Collection</span>
                                    @else
                                        <span class="badge bg-info text-white">Delivery</span>
                                    @endif
                                </td>
                                <td>
                                    @if($shippingClass->is_free || $shippingClass->is_farm_collection)
                                        <span class="text-muted">Free</span>
                                    @else
                                        {{ env('CURRENCY_SYMBOL') }}{{ number_format($shippingClass->cost, 2) }}
                                    @endif
                                </td>
                                <td>
                                    @if($shippingClass->is_free)
                                        <span class="badge bg-success text-white">Yes</span>
                                    @else
                                        <span class="badge bg-secondary text-white">No</span>
                                    @endif
                                </td>
                                <td>{{ $shippingClass->sort_order }}</td>
                                <td>
                                    @if($shippingClass->is_active)
                                        <span class="badge bg-success text-white">Active</span>
                                    @else
                                        <span class="badge bg-secondary text-white">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-dark text-white">{{ $shippingClass->products()->count() }} products</span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.shipping-classes.show', $shippingClass) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.shipping-classes.edit', $shippingClass) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($shippingClass->products()->count() === 0)
                                        <form action="{{ route('admin.shipping-classes.destroy', $shippingClass) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this shipping class?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    No shipping classes found. <a href="{{ route('admin.shipping-classes.create') }}">Create one now</a>.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Product Attributes')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Attributes</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.product-attributes.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Attribute
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Visibility</th>
                                <th>Used for Variations</th>
                                <th>Taxonomy</th>
                                <th>Options</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attributes as $attribute)
                            <tr>
                                <td>
                                    <strong>{{ $attribute->name }}</strong>
                                    @if($attribute->description)
                                        <br><small class="text-muted">{{ Str::limit($attribute->description, 50) }}</small>
                                    @endif
                                    <br><small class="text-muted">{{ $attribute->slug }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ ucfirst($attribute->type) }}</span>
                                </td>
                                <td>
                                    @if($attribute->is_visible)
                                        <span class="badge badge-success">Visible</span>
                                    @else
                                        <span class="badge badge-secondary">Hidden</span>
                                    @endif
                                </td>
                                <td>
                                    @if($attribute->is_variation)
                                        <span class="badge badge-warning">Yes</span>
                                    @else
                                        <span class="badge badge-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($attribute->is_taxonomy)
                                        <span class="badge badge-info">Taxonomy</span>
                                    @else
                                        <span class="badge badge-secondary">Custom</span>
                                    @endif
                                </td>
                                <td>
                                    @if($attribute->options && count($attribute->options) > 0)
                                        <small>{{ count($attribute->options) }} options</small>
                                        <br><small class="text-muted">{{ implode(', ', array_slice($attribute->options, 0, 3)) }}{{ count($attribute->options) > 3 ? '...' : '' }}</small>
                                    @else
                                        <span class="text-muted">No options</span>
                                    @endif
                                </td>
                                <td>
                                    @if($attribute->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.product-attributes.show', $attribute) }}" class="btn btn-info btn-sm" style="color: #fff !important; background-color: #17a2b8 !important; border-color: #17a2b8 !important;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.product-attributes.edit', $attribute) }}" class="btn btn-warning btn-sm" style="color: #212529 !important; background-color: #ffc107 !important; border-color: #ffc107 !important;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if(!$attribute->isUsedInVariations())
                                        <form action="{{ route('admin.product-attributes.destroy', $attribute) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this attribute?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" style="color: #fff !important; background-color: #dc3545 !important; border-color: #dc3545 !important;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    No product attributes found. <a href="{{ route('admin.product-attributes.create') }}">Create one now</a>.
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
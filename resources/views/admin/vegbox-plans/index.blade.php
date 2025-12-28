@extends('layouts.app')

@section('title', 'Vegbox Plans')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Vegbox Plans</h1>
                <a href="{{ route('admin.vegbox-plans.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Plan
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($plans->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                    <h4>No Plans Yet</h4>
                    <p class="text-muted">Create your first vegbox plan to get started with box customization.</p>
                    <a href="{{ route('admin.vegbox-plans.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Plan
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Box Size</th>
                                <th>Frequency</th>
                                <th>Default Tokens</th>
                                <th>Price</th>
                                <th>Billing</th>
                                <th>Status</th>
                                <th>Subscribers</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $plan)
                                <tr>
                                    <td>
                                        <strong>{{ $plan->name }}</strong>
                                        @if($plan->description)
                                            <br><small class="text-muted">{{ Str::limit($plan->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($plan->box_size ?? 'N/A') }}</span>
                                    </td>
                                    <td>{{ ucfirst($plan->delivery_frequency ?? 'N/A') }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $plan->default_tokens ?? 10 }} tokens</span>
                                    </td>
                                    <td>£{{ number_format($plan->price, 2) }}</td>
                                    <td>
                                        Every {{ $plan->invoice_period }} {{ Str::plural($plan->invoice_interval, $plan->invoice_period) }}
                                    </td>
                                    <td>
                                        @if($plan->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            // CSA subscriptions don't link to plans directly yet
                                            // They use box_size and woo_product_id instead
                                            $activeCount = 0;
                                        @endphp
                                        {{ $activeCount }}
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.vegbox-plans.edit', $plan) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.vegbox-plans.destroy', $plan) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

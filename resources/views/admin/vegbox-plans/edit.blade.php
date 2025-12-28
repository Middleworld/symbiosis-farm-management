@extends('layouts.app')

@section('title', 'Edit Vegbox Plan')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Edit Vegbox Plan: {{ $vegboxPlan->name }}</h1>
                <a href="{{ route('admin.vegbox-plans.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Plans
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.vegbox-plans.update', $vegboxPlan) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Plan Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $vegboxPlan->name) }}"
                                   placeholder="e.g., Small Weekly Box"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Brief description of what's included in this box">{{ old('description', $vegboxPlan->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="box_size" class="form-label">Box Size <span class="text-danger">*</span></label>
                                <select class="form-select @error('box_size') is-invalid @enderror" 
                                        id="box_size" 
                                        name="box_size" 
                                        required>
                                    <option value="">Select size...</option>
                                    <option value="small" {{ old('box_size', $vegboxPlan->box_size) == 'small' ? 'selected' : '' }}>Small</option>
                                    <option value="medium" {{ old('box_size', $vegboxPlan->box_size) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="large" {{ old('box_size', $vegboxPlan->box_size) == 'large' ? 'selected' : '' }}>Large</option>
                                </select>
                                @error('box_size')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="delivery_frequency" class="form-label">Delivery Frequency <span class="text-danger">*</span></label>
                                <select class="form-select @error('delivery_frequency') is-invalid @enderror" 
                                        id="delivery_frequency" 
                                        name="delivery_frequency" 
                                        required>
                                    <option value="">Select frequency...</option>
                                    <option value="weekly" {{ old('delivery_frequency', $vegboxPlan->delivery_frequency) == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="fortnightly" {{ old('delivery_frequency', $vegboxPlan->delivery_frequency) == 'fortnightly' ? 'selected' : '' }}>Fortnightly</option>
                                </select>
                                @error('delivery_frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="default_tokens" class="form-label">Default Tokens <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('default_tokens') is-invalid @enderror" 
                                   id="default_tokens" 
                                   name="default_tokens" 
                                   value="{{ old('default_tokens', $vegboxPlan->default_tokens ?? 10) }}"
                                   min="1"
                                   max="50"
                                   required>
                            @error('default_tokens')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Number of tokens customers get to customize their box each week</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pricing & Billing</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (£) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('price') is-invalid @enderror" 
                                   id="price" 
                                   name="price" 
                                   value="{{ old('price', $vegboxPlan->price) }}"
                                   step="0.01"
                                   min="0"
                                   required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="invoice_period" class="form-label">Bill Every <span class="text-danger">*</span></label>
                                <input type="number" 
                                       class="form-control @error('invoice_period') is-invalid @enderror" 
                                       id="invoice_period" 
                                       name="invoice_period" 
                                       value="{{ old('invoice_period', $vegboxPlan->invoice_period) }}"
                                       min="1"
                                       required>
                                @error('invoice_period')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="invoice_interval" class="form-label">Period <span class="text-danger">*</span></label>
                                <select class="form-select @error('invoice_interval') is-invalid @enderror" 
                                        id="invoice_interval" 
                                        name="invoice_interval" 
                                        required>
                                    <option value="week" {{ old('invoice_interval', $vegboxPlan->invoice_interval) == 'week' ? 'selected' : '' }}>Week(s)</option>
                                    <option value="month" {{ old('invoice_interval', $vegboxPlan->invoice_interval) == 'month' ? 'selected' : '' }}>Month(s)</option>
                                    <option value="year" {{ old('invoice_interval', $vegboxPlan->invoice_interval) == 'year' ? 'selected' : '' }}>Year(s)</option>
                                </select>
                                @error('invoice_interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" 
                                   class="form-control @error('sort_order') is-invalid @enderror" 
                                   id="sort_order" 
                                   name="sort_order" 
                                   value="{{ old('sort_order', $vegboxPlan->sort_order) }}"
                                   min="0">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Lower numbers appear first</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', $vegboxPlan->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active (visible to customers)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    // CSA subscriptions don't link to plans directly yet
                    $activeSubCount = 0;
                @endphp
                @if($activeSubCount > 0)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Active Subscriptions</strong><br>
                        This plan has {{ $activeSubCount }} active subscription(s). 
                        Changes to pricing may affect billing.
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.vegbox-plans.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Update Plan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.gap-2 {
    gap: 0.5rem;
}
</style>
@endsection

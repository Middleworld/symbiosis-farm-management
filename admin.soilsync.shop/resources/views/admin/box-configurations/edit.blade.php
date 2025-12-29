@extends('layouts.app')

@section('title', 'Edit Box Configuration')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Edit Weekly Box Configuration</h1>
                <a href="{{ route('admin.box-configurations.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
            <p class="text-muted">Configure what products go into each box size for the week. Prices are from product catalog.</p>
        </div>
    </div>

    <!-- Week Selection (applies to all plans) -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="global_week_starting" class="form-label">Week Starting (Monday) <span class="text-danger">*</span></label>
                    <input type="date" 
                           class="form-control" 
                           id="global_week_starting" 
                           value="{{ $configuration->week_starting->format('Y-m-d') }}"
                           onchange="updateAllWeekFields(this.value)">
                    <small class="text-muted">This week applies to all box configurations below</small>
                </div>
                <div class="col-md-8">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> Configure products for each box size. Use the tabs below to switch between plans.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Info (Read-only for editing) -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-box"></i> Editing: {{ is_array($configuration->plan->name) ? $configuration->plan->name['en'] : $configuration->plan->name }}
                <span class="badge bg-light text-dark ms-2">{{ ucfirst($configuration->plan->box_size) }}</span>
            </h5>
        </div>
    </div>

    <!-- Single Plan Form (no tabs needed for edit) -->
    <div class="box-config-container">
        @php
            $plan = $configuration->plan;
        @endphp
                
                <form action="{{ route('admin.box-configurations.update', $configuration) }}" method="POST" class="box-config-form" id="form-plan-{{ $plan->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <input type="hidden" name="week_starting" class="week-starting-field" value="{{ $configuration->week_starting->format('Y-m-d') }}">
                    
                    <div class="row">
                        <!-- Product Selection -->
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-shopping-basket"></i> Available Products
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <input type="text" 
                                               class="form-control" 
                                               placeholder="Search products..." 
                                               onkeyup="filterProducts(this, {{ $plan->id }})">
                                    </div>
                                    
                                    <div id="products-list-{{ $plan->id }}" style="max-height: 600px; overflow-y: auto;">
                                        @foreach($products as $category => $categoryProducts)
                                            <h6 class="text-muted mt-3 mb-2">
                                                <i class="fas fa-tag"></i> {{ $category ?: 'Uncategorized' }}
                                            </h6>
                                            <div class="list-group mb-3">
                                                @foreach($categoryProducts as $product)
                                                    <div class="list-group-item list-group-item-action product-item" data-product-id="{{ $product->id }}">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="flex-grow-1">
                                                                <strong>{{ $product->name }}</strong>
                                                                <br>
                                                                <small class="text-muted">SKU: {{ $product->sku }}</small>
                                                            </div>
                                                            <div class="text-end me-3">
                                                                <span class="badge bg-primary">£{{ number_format($product->price, 2) }}</span>
                                                                @if($product->stock_quantity)
                                                                    <br><small class="text-success">Stock: {{ $product->stock_quantity }}</small>
                                                                @endif
                                                            </div>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-success"
                                                                    onclick="addProductToBox({{ $plan->id }}, {{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, '{{ $product->unit ?? 'item' }}')">
                                                                <i class="fas fa-plus"></i> Add
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Box Contents -->
                        <div class="col-md-4">
                            <div class="card mb-4 sticky-top" style="top: 20px;">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-box-open"></i> {{ $plan->name }} Contents
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <strong>Total Value:</strong> <span id="total-value-{{ $plan->id }}" class="float-end">£0.00</span>
                                    </div>
                                    
                                    <div id="box-contents-{{ $plan->id }}" class="mb-3" style="max-height: 400px; overflow-y: auto;">
                                        <p class="text-muted text-center py-4">
                                            <i class="fas fa-inbox"></i><br>
                                            No products added yet
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="admin_notes_{{ $plan->id }}" class="form-label">Admin Notes</label>
                                        <textarea class="form-control" 
                                                  id="admin_notes_{{ $plan->id }}" 
                                                  name="admin_notes" 
                                                  rows="3"
                                                  placeholder="Internal notes about this configuration..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100" onclick="return validateForm({{ $plan->id }})">
                                        <i class="fas fa-save"></i> Save {{ $plan->name }} Configuration
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let boxItems = {};

// Initialize for the configuration's plan
boxItems[{{ $configuration->plan_id }}] = [];

// Pre-populate with existing items for this configuration's plan
@if($configuration->items->count() > 0)
    boxItems[{{ $configuration->plan_id }}] = [
        @foreach($configuration->items as $item)
            {
                productId: {{ $item->product_id }},
                name: '{{ addslashes($item->item_name) }}',
                price: {{ $item->price_at_time }},
                quantity: {{ $item->quantity }},
                unit: '{{ $item->unit }}'
            }{{ $loop->last ? '' : ',' }}
        @endforeach
    ];
    
    // Render the pre-populated items when page loads
    document.addEventListener('DOMContentLoaded', function() {
        renderBoxContents({{ $configuration->plan_id }});
    });
@endif

// Update all week fields when global week changes
function updateAllWeekFields(weekValue) {
    document.querySelectorAll('.week-starting-field').forEach(field => {
        field.value = weekValue;
    });
}

// Add product to box
function addProductToBox(planId, productId, productName, price, unit) {
    console.log('Adding product:', {planId, productId, productName, price, unit});
    
    // Check if already added
    if (boxItems[planId].find(item => item.productId === productId)) {
        alert('This product is already in the box');
        return;
    }
    
    boxItems[planId].push({
        productId: productId,
        name: productName,
        price: parseFloat(price),
        quantity: 1,
        unit: unit
    });
    
    console.log('Box items for plan', planId, ':', boxItems[planId]);
    renderBoxContents(planId);
}

// Remove product from box
function removeProductFromBox(planId, productId) {
    boxItems[planId] = boxItems[planId].filter(item => item.productId !== productId);
    renderBoxContents(planId);
}

// Update quantity
function updateQuantity(planId, productId, quantity) {
    const item = boxItems[planId].find(item => item.productId === productId);
    if (item) {
        item.quantity = parseInt(quantity) || 1;
        renderBoxContents(planId);
    }
}

// Render box contents
function renderBoxContents(planId) {
    const container = document.getElementById(`box-contents-${planId}`);
    const totalValueSpan = document.getElementById(`total-value-${planId}`);
    
    if (boxItems[planId].length === 0) {
        container.innerHTML = `
            <p class="text-muted text-center py-4">
                <i class="fas fa-inbox"></i><br>
                No products added yet
            </p>
        `;
        totalValueSpan.textContent = '£0.00';
        return;
    }
    
    let totalValue = 0;
    let html = '<div class="list-group">';
    
    boxItems[planId].forEach(item => {
        const itemTotal = item.price * item.quantity;
        totalValue += itemTotal;
        
        html += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <strong>${item.name}</strong>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeProductFromBox(${planId}, ${item.productId})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="input-group input-group-sm" style="max-width: 120px;">
                        <input type="number" 
                               class="form-control form-control-sm" 
                               value="${item.quantity}" 
                               min="1"
                               onchange="updateQuantity(${planId}, ${item.productId}, this.value)">
                        <span class="input-group-text">${item.unit}</span>
                    </div>
                    <span class="badge bg-success">£${itemTotal.toFixed(2)}</span>
                </div>
                <input type="hidden" name="items[${item.productId}][product_id]" value="${item.productId}">
                <input type="hidden" name="items[${item.productId}][quantity]" value="${item.quantity}">
                <input type="hidden" name="items[${item.productId}][price]" value="${item.price}">
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
    totalValueSpan.textContent = `£${totalValue.toFixed(2)}`;
}

// Filter products
function filterProducts(input, planId) {
    const filter = input.value.toLowerCase();
    const container = document.getElementById(`products-list-${planId}`);
    const items = container.querySelectorAll('.product-item');
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Validate form before submission
function validateForm(planId) {
    console.log('Validating form for plan', planId);
    console.log('Items in box:', boxItems[planId]);
    
    if (!boxItems[planId] || boxItems[planId].length === 0) {
        alert('Please add at least one product to the box before saving.');
        return false;
    }
    
    // Make sure hidden inputs are rendered before submission
    renderBoxContents(planId);
    
    console.log('Form valid, submitting with', boxItems[planId].length, 'items');
    return true;
}
</script>
@endsection

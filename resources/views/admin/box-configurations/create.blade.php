@extends('layouts.app')

@section('title', 'Create Box Configuration')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Create Weekly Box Configuration</h1>
                <a href="{{ route('admin.box-configurations.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.box-configurations.store') }}" method="POST" id="box-config-form">
        @csrf
        
        <div class="row">
            <!-- Configuration Settings -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Week Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="week_starting" class="form-label">Week Starting (Monday) <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control @error('week_starting') is-invalid @enderror" 
                                   id="week_starting" 
                                   name="week_starting" 
                                   value="{{ old('week_starting', $weekStart->format('Y-m-d')) }}"
                                   required>
                            @error('week_starting')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Select the Monday of the week</small>
                        </div>

                        <div class="mb-3">
                            <label for="plan_id" class="form-label">Vegbox Plan <span class="text-danger">*</span></label>
                            <select class="form-select @error('plan_id') is-invalid @enderror" 
                                    id="plan_id" 
                                    name="plan_id" 
                                    required>
                                <option value="">Select a plan...</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} - {{ $plan->box_size ?? 'Standard' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="default_tokens" class="form-label">Default Tokens <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('default_tokens') is-invalid @enderror" 
                                   id="default_tokens" 
                                   name="default_tokens" 
                                   value="{{ old('default_tokens', 10) }}"
                                   min="1"
                                   max="50"
                                   required>
                            @error('default_tokens')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Total tokens customers get for this box</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active (visible to customers)
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" 
                                      id="admin_notes" 
                                      name="admin_notes" 
                                      rows="3">{{ old('admin_notes') }}</textarea>
                            <small class="text-muted">Internal notes (not visible to customers)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Products -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add Products to Box</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="search" 
                                   class="form-control" 
                                   id="product-search" 
                                   placeholder="Search products...">
                        </div>

                        <div id="products-list" class="products-grid">
                            @foreach($products as $product)
                                <div class="product-card" data-product-name="{{ strtolower($product->name) }}">
                                    <div class="product-info">
                                        <strong>{{ $product->name }}</strong>
                                        @if($product->category)
                                            <br><small class="text-muted">{{ $product->category }}</small>
                                        @endif
                                    </div>
                                    <button type="button" 
                                            class="btn btn-sm btn-primary add-product-btn" 
                                            data-product-id="{{ $product->id }}"
                                            data-product-name="{{ $product->name }}">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Selected Items -->
                <div class="card mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Selected Items (<span id="selected-count">0</span>)</h5>
                    </div>
                    <div class="card-body">
                        <div id="selected-items-list" class="selected-items-container">
                            <p class="text-muted text-center py-4">No items added yet. Click "Add" on products above.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.box-configurations.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save"></i> Create Configuration
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 10px;
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.product-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
}

.product-card:hover {
    background: #e9ecef;
}

.selected-items-container {
    min-height: 200px;
}

.selected-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 100px;
    gap: 10px;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 10px;
    border: 2px solid #4CAF50;
}

.selected-item strong {
    font-size: 16px;
}

.form-control-sm {
    padding: 0.25rem 0.5rem;
}

.gap-2 {
    gap: 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedItems = [];
    
    // Product search
    document.getElementById('product-search').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const productName = card.dataset.productName;
            card.style.display = productName.includes(searchTerm) ? 'flex' : 'none';
        });
    });
    
    // Add product
    document.querySelectorAll('.add-product-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            
            if (selectedItems.find(item => item.id === productId)) {
                alert('Product already added!');
                return;
            }
            
            selectedItems.push({
                id: productId,
                name: productName,
                tokens: 2,
                quantity: null,
                featured: false
            });
            
            renderSelectedItems();
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-check"></i> Added';
        });
    });
    
    function renderSelectedItems() {
        const container = document.getElementById('selected-items-list');
        
        if (selectedItems.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">No items added yet. Click "Add" on products above.</p>';
            document.getElementById('selected-count').textContent = '0';
            return;
        }
        
        container.innerHTML = selectedItems.map((item, index) => `
            <div class="selected-item">
                <div>
                    <strong>${item.name}</strong>
                    <input type="hidden" name="items[${index}][product_id]" value="${item.id}">
                </div>
                <div>
                    <label class="form-label-sm">Token Value:</label>
                    <input type="number" 
                           class="form-control form-control-sm" 
                           name="items[${index}][token_value]" 
                           value="${item.tokens}"
                           min="1" 
                           max="10"
                           required>
                </div>
                <div>
                    <label class="form-label-sm">Quantity Available:</label>
                    <input type="number" 
                           class="form-control form-control-sm" 
                           name="items[${index}][quantity_available]" 
                           value="${item.quantity || ''}"
                           min="1"
                           placeholder="Unlimited">
                </div>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="items[${index}][is_featured]" 
                               value="1"
                               ${item.featured ? 'checked' : ''}>
                        <label class="form-check-label">Featured</label>
                    </div>
                </div>
                <button type="button" 
                        class="btn btn-sm btn-danger remove-item-btn" 
                        data-index="${index}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `).join('');
        
        document.getElementById('selected-count').textContent = selectedItems.length;
        
        // Bind remove buttons
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                const productId = selectedItems[index].id;
                
                selectedItems.splice(index, 1);
                renderSelectedItems();
                
                // Re-enable add button
                document.querySelectorAll('.add-product-btn').forEach(addBtn => {
                    if (addBtn.dataset.productId === productId) {
                        addBtn.disabled = false;
                        addBtn.innerHTML = '<i class="fas fa-plus"></i> Add';
                    }
                });
            });
        });
    }
});
</script>
@endsection

@extends('layouts.app')

@section('title', 'POS Inventory Management')

@section('content')
<div class="container-fluid">
    <!-- Header with Select All Checkbox -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="form-check me-4">
                            <input class="form-check-input" type="checkbox" id="selectAllCheckbox" 
                                   style="width: 1.5rem; height: 1.5rem; cursor: pointer;"
                                   onchange="toggleSelectAll(this.checked)">
                            <label class="form-check-label text-white ms-2" for="selectAllCheckbox" style="cursor: pointer; font-size: 1.1rem;">
                                Select All
                            </label>
                        </div>
                        <div class="flex-grow-1">
                            <h1 class="mb-2 text-white" style="font-size: 2rem;">
                                <i class="fas fa-store-alt me-2"></i>POS Inventory Management
                            </h1>
                            <p class="text-white mb-0 opacity-90" style="font-size: 1rem;">
                                <i class="fas fa-info-circle me-1"></i>
                                Select which products are available for sale at the market stall today
                            </p>
                        </div>
                        <div class="text-end text-white">
                            <div class="d-flex flex-column align-items-end">
                                <small class="opacity-75">Total Products</small>
                                <h2 class="mb-0" id="totalProductsCount">0</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Search products...">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="categoryFilter">
                <option value="">All Categories</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="availabilityFilter">
                <option value="all">All Products</option>
                <option value="available">Available Only</option>
                <option value="unavailable">Unavailable Only</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" onclick="loadProducts()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <span id="selectionCount">0 products selected</span>
                        </div>
                        <div class="col-md-8 text-end">
                            <button class="btn btn-success btn-sm" onclick="bulkAction('enable')" id="bulkEnableBtn" disabled>
                                <i class="fas fa-check-circle"></i> Make Available
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="bulkAction('disable')" id="bulkDisableBtn" disabled>
                                <i class="fas fa-times-circle"></i> Make Unavailable
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="clearSelection()">
                                <i class="fas fa-times"></i> Clear Selection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="row" id="productsGrid">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading products...</p>
        </div>
    </div>
</div>

<style>
.product-card {
    transition: all 0.2s;
    cursor: pointer;
    border: 2px solid transparent;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.product-card.selected {
    border-color: #0d6efd;
    background-color: #e7f1ff;
}

.product-card.pos-available {
    border-left: 4px solid #198754;
}

.product-card.pos-unavailable {
    border-left: 4px solid #6c757d;
    opacity: 0.7;
}

.availability-toggle {
    font-size: 1.5rem;
    width: 3rem !important;
    height: 1.5rem !important;
    cursor: pointer;
}

.category-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

<script>
// Fix for Laravel Debugbar jQuery.noConflict
(function($) {
    'use strict';
    
    // Debug: Check if jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
    } else {
        console.log('jQuery version:', jQuery.fn.jquery);
    }

    let selectedProducts = new Set();
    let allProducts = [];

    $(document).ready(function() {
        console.log('Document ready - loading products...');
        loadProducts();
        loadCategories();

        // Search input with debounce
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadProducts(), 300);
        });

        // Filter changes
        $('#categoryFilter, #availabilityFilter').on('change', function() {
            loadProducts();
        });
    });

    function loadCategories() {
        console.log('Loading categories...');
        $.get('{{ route('pos.inventory.categories') }}', function(categories) {
            console.log('Categories loaded:', categories);
            const categoryFilter = $('#categoryFilter');
            categoryFilter.find('option:not(:first)').remove();
            
            categories.forEach(cat => {
                categoryFilter.append(
                    `<option value="${cat.category}">${cat.category} (${cat.available}/${cat.total})</option>`
                );
            });
        }).fail(function(xhr, status, error) {
            console.error('Failed to load categories:', error, xhr.responseText);
        });
    }

    function loadProducts() {
        console.log('Loading products...');
        const search = $('#searchInput').val();
        const category = $('#categoryFilter').val();
        const availability = $('#availabilityFilter').val();

        $.get('{{ route('pos.inventory.products') }}', {
            search: search,
            category: category,
            availability: availability
        }, function(products) {
            console.log('Products loaded:', products.length, 'products');
            allProducts = products;
            renderProducts(products);
        }).fail(function(xhr, status, error) {
            console.error('Failed to load products:', error, xhr.responseText);
            $('#productsGrid').html(`
                <div class="col-12 text-center py-5">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #dc3545;"></i>
                    <p class="mt-3 text-danger">Error loading products</p>
                    <p class="text-muted">${error}</p>
                </div>
            `);
        });
    }

    function renderProducts(products) {
        const grid = $('#productsGrid');
        
        // Update total count
        $('#totalProductsCount').text(products.length);
        
        if (products.length === 0) {
            grid.html(`
                <div class="col-12 text-center py-5">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="mt-3 text-muted">No products found</p>
                </div>
            `);
            return;
        }

        let html = '';
        products.forEach(product => {
            const isSelected = selectedProducts.has(product.id);
            const availableClass = product.pos_available ? 'pos-available' : 'pos-unavailable';
            const selectedClass = isSelected ? 'selected' : '';
            
            html += `
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="card product-card ${availableClass} ${selectedClass}" 
                         data-product-id="${product.id}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">${product.name}</h6>
                                    <span class="badge bg-secondary category-badge">${product.category || 'Uncategorized'}</span>
                                </div>
                                <div class="form-check form-switch ms-2">
                                    <input class="form-check-input availability-toggle" 
                                           type="checkbox" 
                                           ${product.pos_available ? 'checked' : ''}
                                           data-product-id="${product.id}"
                                           title="Toggle POS availability">
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-primary">£${parseFloat(product.price).toFixed(2)}</strong>
                                    ${product.unit ? `
                                        <span class="badge ${product.unit.toLowerCase() === 'kg' ? 'bg-warning' : 'bg-info'}" 
                                              title="${product.unit.toLowerCase() === 'kg' ? 'Requires weighing' : 'Fixed price'}">
                                            ${product.unit.toLowerCase() === 'kg' ? '<i class="fas fa-balance-scale"></i> per KG' : 
                                              product.unit.toLowerCase() === 'each' ? '<i class="fas fa-box"></i> each' : 
                                              '/ ' + product.unit}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="badge ${product.pos_available ? 'bg-success' : 'bg-secondary'}">
                                    ${product.pos_available ? '✓ Available in POS' : '✗ Not in POS'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        grid.html(html);
        
        // Attach event handlers using jQuery (not inline onclick)
        $('.product-card').on('click', function() {
            const productId = parseInt($(this).data('product-id'));
            selectProduct(productId);
        });
        
        $('.availability-toggle').on('change', function(e) {
            e.stopPropagation();
            const productId = parseInt($(this).data('product-id'));
            toggleAvailability(productId);
        });
    }

    function toggleAvailability(productId) {
        $.post(`/pos/inventory/toggle/${productId}`, {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                loadProducts();
                loadCategories();
            }
        }).fail(function() {
            showToast('Failed to update product availability', 'error');
        });
    }

    function selectProduct(productId) {
        if (selectedProducts.has(productId)) {
            selectedProducts.delete(productId);
        } else {
            selectedProducts.add(productId);
        }
        
        updateSelection();
    }

    function updateSelection() {
        // Update visual selection
        $('.product-card').each(function() {
            const productId = parseInt($(this).data('product-id'));
            if (selectedProducts.has(productId)) {
                $(this).addClass('selected');
            } else {
                $(this).removeClass('selected');
            }
        });

        // Update counter and button states
        const count = selectedProducts.size;
        $('#selectionCount').text(`${count} product${count !== 1 ? 's' : ''} selected`);
        $('#bulkEnableBtn, #bulkDisableBtn').prop('disabled', count === 0);
    }

    function clearSelection() {
        selectedProducts.clear();
        $('#selectAllCheckbox').prop('checked', false);
        updateSelection();
    }

    function toggleSelectAll(checked) {
        if (checked) {
            // Select all visible products
            allProducts.forEach(product => {
                selectedProducts.add(product.id);
            });
        } else {
            // Deselect all
            selectedProducts.clear();
        }
        updateSelection();
    }

    function bulkAction(action) {
        if (selectedProducts.size === 0) {
            showToast('No products selected', 'warning');
            return;
        }

        const productIds = Array.from(selectedProducts);
        const actionText = action === 'enable' ? 'make available' : 'make unavailable';
        
        if (!confirm(`Are you sure you want to ${actionText} ${productIds.length} product(s)?`)) {
            return;
        }

        $.post('{{ route('pos.inventory.bulk-update') }}', {
            _token: '{{ csrf_token() }}',
            product_ids: productIds,
            action: action
        }, function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                clearSelection();
                loadProducts();
                loadCategories();
            }
        }).fail(function() {
            showToast('Failed to update products', 'error');
        });
    }

    function showToast(message, type = 'info') {
        const bgColor = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        }[type] || '#17a2b8';

        const toast = $(`
            <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
                <div class="toast show" role="alert">
                    <div class="toast-header" style="background-color: ${bgColor}; color: white;">
                        <strong class="me-auto">
                            ${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'} 
                            ${type.charAt(0).toUpperCase() + type.slice(1)}
                        </strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            </div>
        `);

        $('body').append(toast);
        
        setTimeout(() => {
            toast.find('.toast').removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Make functions globally accessible
    window.loadProducts = loadProducts;
    window.toggleSelectAll = toggleSelectAll;
    window.clearSelection = clearSelection;
    window.bulkAction = bulkAction;

})(jQuery); // End of jQuery noConflict wrapper
</script>
@endsection

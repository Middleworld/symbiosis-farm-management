@extends('layouts.app')

@section('title', 'Product Variations - ' . $product->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-layer-group"></i>
                        Product Variations: {{ $product->name }}
                        <small class="text-muted">WooCommerce Product ID: {{ $product->woo_product_id }}</small>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="showCreateVariationModal()">
                            <i class="fas fa-plus"></i> Add Variation
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="loadVariations()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Product Attributes Section -->
                    <div class="mb-4">
                        <h5>Product Attributes</h5>
                        <div id="attributes-section">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Variations Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="variations-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all-variations"></th>
                                    <th>ID</th>
                                    <th>SKU</th>
                                    <th>Attributes</th>
                                    <th>Price</th>
                                    <th>Sale Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="variations-tbody">
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="sr-only">Loading variations...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="mt-3" id="bulk-actions" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <select class="form-control" id="bulk-variation-action">
                                    <option value="">Select Action</option>
                                    <option value="publish">Publish</option>
                                    <option value="draft">Draft</option>
                                    <option value="trash">Trash</option>
                                    <option value="bulk_field_update">Update Field</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-primary" onclick="executeBulkVariationAction()">
                                    <i class="fas fa-bolt"></i> Execute Bulk Action
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearVariationSelection()">
                                    Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Variation Modal -->
<div class="modal fade" id="variation-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="variation-modal-title">Create Variation</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="variation-form">
                    <input type="hidden" id="variation-id" name="variation_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="variation-sku">SKU</label>
                                <input type="text" class="form-control" id="variation-sku" name="sku">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="variation-status">Status</label>
                                <select class="form-control" id="variation-status" name="status">
                                    <option value="publish">Published</option>
                                    <option value="draft">Draft</option>
                                    <option value="trash">Trash</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="variation-regular-price">Regular Price</label>
                                <input type="number" step="0.01" class="form-control" id="variation-regular-price" name="regular_price">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="variation-sale-price">Sale Price</label>
                                <input type="number" step="0.01" class="form-control" id="variation-sale-price" name="sale_price">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="variation-stock-quantity">Stock Quantity</label>
                                <input type="number" class="form-control" id="variation-stock-quantity" name="stock_quantity">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="variation-stock-status">Stock Status</label>
                                <select class="form-control" id="variation-stock-status" name="stock_status">
                                    <option value="instock">In Stock</option>
                                    <option value="outofstock">Out of Stock</option>
                                    <option value="onbackorder">On Backorder</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="variation-weight">Weight (kg)</label>
                                <input type="number" step="0.01" class="form-control" id="variation-weight" name="weight">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="variation-length">Length (cm)</label>
                                <input type="number" step="0.01" class="form-control" id="variation-length" name="length">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="variation-width">Width (cm)</label>
                                <input type="number" step="0.01" class="form-control" id="variation-width" name="width">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="variation-height">Height (cm)</label>
                        <input type="number" step="0.01" class="form-control" id="variation-height" name="height">
                    </div>

                    <!-- Variation Attributes -->
                    <div id="variation-attributes">
                        <h6>Variation Attributes</h6>
                        <div id="attributes-container">
                            <!-- Attributes will be loaded dynamically -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveVariation()">
                    <i class="fas fa-save"></i> Save Variation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Field Update Modal -->
<div class="modal fade" id="bulk-field-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Update Variations</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="bulk-field">Field to Update</label>
                    <select class="form-control" id="bulk-field">
                        <option value="regular_price">Regular Price</option>
                        <option value="sale_price">Sale Price</option>
                        <option value="stock_quantity">Stock Quantity</option>
                        <option value="stock_status">Stock Status</option>
                        <option value="weight">Weight</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bulk-value">New Value</label>
                    <input type="text" class="form-control" id="bulk-value" placeholder="Enter new value">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkFieldUpdate()">
                    <i class="fas fa-save"></i> Update All Selected
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@vite(['resources/js/mwf-api.js'])
<script>
// Global variables
let currentProductId = {{ $product->woo_product_id }};
let currencySymbol = '{{ config('pos_payments.currency_symbol', '£') }}';
let productAttributes = [];
let variations = [];

// Initialize page
$(document).ready(function() {
    loadProductAttributes();
    loadVariations();

    // Select all variations checkbox
    $('#select-all-variations').on('change', function() {
        $('.variation-checkbox').prop('checked', $(this).is(':checked'));
        toggleBulkActions();
    });

    // Individual variation checkboxes
    $(document).on('change', '.variation-checkbox', function() {
        toggleBulkActions();
    });
});

// Load product attributes
async function loadProductAttributes() {
    try {
        showLoading('attributes-section', 'Loading attributes...');
        
        console.log('=== DEBUG loadProductAttributes ===');
        console.log('Fetching from:', '{{ route("admin.product-attributes.api.list") }}');
        
        // Load both product-specific and global attributes
        const [productResponse, globalResponse] = await Promise.all([
            window.mwfApi.getProductAttributes(currentProductId),
            fetch('{{ route("admin.product-attributes.api.list") }}').then(r => r.json())
        ]);
        
        console.log('Product response:', productResponse);
        console.log('Global response:', globalResponse);

        if (productResponse.success && globalResponse.success) {
            // Merge product and global attributes, prioritizing product-specific ones
            const productAttrNames = productResponse.attributes.map(a => a.name);
            const globalAttrs = globalResponse.attributes.filter(a => !productAttrNames.includes(a.name));
            
            console.log('Product attribute names:', productAttrNames);
            console.log('Global attributes:', globalAttrs);
            
            productAttributes = [...productResponse.attributes, ...globalAttrs];
            console.log('Merged productAttributes:', productAttributes);
            renderAttributes();
        } else {
            throw new Error(productResponse.error || globalResponse.error || 'Failed to load attributes');
        }
    } catch (error) {
        console.error('Error loading attributes:', error);
        showError('attributes-section', error.message);
    }
}

// Render product attributes
function renderAttributes() {
    let html = '<div class="row">';

    productAttributes.forEach(attr => {
        html += `
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>${attr.label}</h6>
                        <p class="text-muted small">${attr.type === 'taxonomy' ? 'Global Attribute' : 'Custom Attribute'}</p>
                        <div class="mb-2">
                            <strong>Options:</strong><br>
                            ${attr.options.map(opt => `<span class="badge badge-secondary mr-1">${typeof opt === 'object' ? opt.name : opt}</span>`).join(' ')}
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" ${attr.variation ? 'checked' : ''} disabled>
                            <label class="form-check-label small">Used for variations</label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    $('#attributes-section').html(html);
}

// Load variations
async function loadVariations() {
    try {
        showLoading('variations-tbody', 'Loading variations...', true);
        const response = await window.mwfApi.getProductVariations(currentProductId);

        if (response.success) {
            variations = response.variations;
            renderVariations();
        } else {
            throw new Error(response.error || 'Failed to load variations');
        }
    } catch (error) {
        showError('variations-tbody', error.message, true);
    }
}

// Render variations table
function renderVariations() {
    if (variations.length === 0) {
        $('#variations-tbody').html(`
            <tr>
                <td colspan="9" class="text-center text-muted">
                    <i class="fas fa-info-circle"></i> No variations found for this product.
                    <br><button type="button" class="btn btn-sm btn-success mt-2" onclick="showCreateVariationModal()">
                        <i class="fas fa-plus"></i> Create First Variation
                    </button>
                </td>
            </tr>
        `);
        return;
    }

    let html = '';
    variations.forEach(variation => {
        const attributesText = Object.entries(variation.attributes || {})
            .map(([key, value]) => `${key}: ${value}`)
            .join(', ');

        html += `
            <tr>
                <td><input type="checkbox" class="variation-checkbox" value="${variation.id}"></td>
                <td>${variation.id}</td>
                <td>${variation.sku || '-'}</td>
                <td><small>${attributesText || 'No attributes'}</small></td>
                <td>${currencySymbol}${variation.price || variation.regular_price || '-'}</td>
                <td>${variation.sale_price ? currencySymbol + variation.sale_price : '-'}</td>
                <td>${variation.stock_quantity !== null ? variation.stock_quantity : '-'}</td>
                <td>
                    <span class="badge badge-${getStatusBadgeClass(variation.stock_status || 'instock')}">
                        ${variation.stock_status || 'instock'}
                    </span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-warning" onclick="editVariation(${variation.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteVariation(${variation.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    $('#variations-tbody').html(html);
}

// Show create variation modal
function showCreateVariationModal() {
    $('#variation-modal-title').text('Create Variation');
    $('#variation-id').val('');
    $('#variation-form')[0].reset();

    // Load attribute options for the form
    renderVariationAttributes();

    $('#variation-modal').modal('show');
}

// Edit variation
async function editVariation(variationId) {
    const variation = variations.find(v => v.id === variationId);
    if (!variation) return;

    $('#variation-modal-title').text('Edit Variation');
    $('#variation-id').val(variation.id);
    $('#variation-sku').val(variation.sku || '');
    $('#variation-regular-price').val(variation.regular_price || '');
    $('#variation-sale-price').val(variation.sale_price || '');
    $('#variation-stock-quantity').val(variation.stock_quantity || '');
    $('#variation-stock-status').val(variation.stock_status || 'instock');
    $('#variation-weight').val(variation.weight || '');
    $('#variation-length').val(variation.length || '');
    $('#variation-width').val(variation.width || '');
    $('#variation-height').val(variation.height || '');

    // Load attribute options for the form
    renderVariationAttributes(variation.attributes);

    $('#variation-modal').modal('show');
}

// Render variation attributes in form
function renderVariationAttributes(selectedAttributes = {}) {
    let html = '';
    
    console.log('=== DEBUG renderVariationAttributes ===');
    console.log('Total productAttributes:', productAttributes.length);
    console.log('productAttributes:', productAttributes);

    productAttributes.forEach(attr => {
        console.log(`Checking attribute: ${attr.label}, variation: ${attr.variation}`);
        if (!attr.variation) return; // Only show attributes used for variations

        const isGlobal = attr.type === 'taxonomy';
        const badge = isGlobal ? '<span class="badge badge-info ml-2">Global</span>' : '<span class="badge badge-secondary ml-2">Custom</span>';
        
        html += `<div class="form-group">`;
        html += `<label>${attr.label}${badge}</label>`;
        html += `<select class="form-control" name="attributes[${attr.name}]" required>`;
        html += `<option value="">Select ${attr.label}</option>`;

        attr.options.forEach(option => {
            const value = typeof option === 'object' ? option.slug : option;
            const label = typeof option === 'object' ? option.name : option;
            const selected = selectedAttributes[attr.name] === value ? 'selected' : '';
            html += `<option value="${value}" ${selected}>${label}</option>`;
        });

        html += `</select></div>`;
    });
    
    console.log('Generated HTML:', html === '' ? 'EMPTY' : 'Has content');

    if (html === '') {
        html = '<div class="alert alert-info">No variation attributes defined for this product. <a href="{{ route("admin.product-attributes.index") }}" target="_blank">Manage global attributes</a> or add custom attributes to the product.</div>';
    }

    $('#attributes-container').html(html);
}

// Save variation
async function saveVariation() {
    const formData = new FormData(document.getElementById('variation-form'));
    const variationData = {};

    // Convert form data to object
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('attributes[')) {
            if (!variationData.attributes) variationData.attributes = {};
            const attrKey = key.match(/attributes\[(.*?)\]/)[1];
            variationData.attributes[attrKey] = value;
        } else if (value !== '') {
            variationData[key] = key.includes('price') || key.includes('weight') || key.includes('length') || key.includes('width') || key.includes('height')
                ? parseFloat(value)
                : key === 'stock_quantity' ? parseInt(value) : value;
        }
    }

    try {
        let response;
        if (variationData.variation_id) {
            // Update existing variation
            response = await window.mwfApi.updateVariation(variationData.variation_id, variationData);
        } else {
            // Create new variation
            response = await window.mwfApi.createVariation(currentProductId, variationData);
        }

        if (response.success) {
            $('#variation-modal').modal('hide');
            loadVariations(); // Refresh the list
            showToast('Variation saved successfully!', 'success');
        } else {
            throw new Error(response.error || 'Failed to save variation');
        }
    } catch (error) {
        showToast(error.message, 'error');
    }
}

// Delete variation
async function deleteVariation(variationId) {
    if (!confirm('Are you sure you want to delete this variation? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await window.mwfApi.deleteVariation(variationId);

        if (response.success) {
            loadVariations(); // Refresh the list
            showToast('Variation deleted successfully!', 'success');
        } else {
            throw new Error(response.error || 'Failed to delete variation');
        }
    } catch (error) {
        showToast(error.message, 'error');
    }
}

// Bulk operations
function toggleBulkActions() {
    const checkedBoxes = $('.variation-checkbox:checked');
    $('#bulk-actions').toggle(checkedBoxes.length > 0);
}

function clearVariationSelection() {
    $('.variation-checkbox').prop('checked', false);
    $('#select-all-variations').prop('checked', false);
    $('#bulk-actions').hide();
}

async function executeBulkVariationAction() {
    const action = $('#bulk-variation-action').val();
    const selectedVariations = $('.variation-checkbox:checked').map(function() {
        return parseInt($(this).val());
    }).get();

    if (!action || selectedVariations.length === 0) {
        showToast('Please select an action and variations', 'warning');
        return;
    }

    if (action === 'bulk_field_update') {
        $('#bulk-field-modal').modal('show');
        return;
    }

    if (!confirm(`Are you sure you want to ${action} ${selectedVariations.length} selected variations?`)) {
        return;
    }

    try {
        const response = await window.mwfApi.bulkUpdateVariations({
            variation_ids: selectedVariations,
            updates: { status: action }
        });

        if (response.success) {
            loadVariations();
            clearVariationSelection();
            showToast(`Bulk action completed! ${response.success_count} updated, ${response.error_count} failed.`, 'success');
        } else {
            throw new Error(response.error || 'Bulk action failed');
        }
    } catch (error) {
        showToast(error.message, 'error');
    }
}

async function executeBulkFieldUpdate() {
    const field = $('#bulk-field').val();
    const value = $('#bulk-value').val();
    const selectedVariations = $('.variation-checkbox:checked').map(function() {
        return parseInt($(this).val());
    }).get();

    if (!field || !value) {
        showToast('Please select a field and enter a value', 'warning');
        return;
    }

    try {
        const updates = {};
        updates[field] = field === 'stock_quantity' ? parseInt(value) : value;

        const response = await window.mwfApi.bulkUpdateVariations({
            variation_ids: selectedVariations,
            updates: updates
        });

        if (response.success) {
            $('#bulk-field-modal').modal('hide');
            loadVariations();
            clearVariationSelection();
            showToast(`Bulk update completed! ${response.success_count} updated, ${response.error_count} failed.`, 'success');
        } else {
            throw new Error(response.error || 'Bulk update failed');
        }
    } catch (error) {
        showToast(error.message, 'error');
    }
}

// Utility functions
function getStatusBadgeClass(status) {
    switch (status) {
        case 'instock': return 'success';
        case 'outofstock': return 'danger';
        case 'onbackorder': return 'warning';
        default: return 'secondary';
    }
}

function showLoading(elementId, message, isTableRow = false) {
    const content = isTableRow
        ? `<tr><td colspan="9" class="text-center"><div class="spinner-border" role="status"><span class="sr-only">${message}</span></div></td></tr>`
        : `<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">${message}</span></div><p>${message}</p></div>`;

    $(`#${elementId}`).html(content);
}

function showError(elementId, message, isTableRow = false) {
    const content = isTableRow
        ? `<tr><td colspan="9" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> ${message}</td></tr>`
        : `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ${message}</div>`;

    $(`#${elementId}`).html(content);
}

function showToast(message, type = 'info') {
    // Simple toast implementation - you can replace with a proper toast library
    const toastClass = `alert alert-${type === 'error' ? 'danger' : type}`;
    const toast = $(`<div class="${toastClass} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">${message}<button type="button" class="close" data-dismiss="alert">&times;</button></div>`);

    $('body').append(toast);
    setTimeout(() => toast.alert('close'), 5000);
}
</script>
@endsection
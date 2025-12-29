@extends('layouts.admin')

@section('title', 'Edit Product via MWF Integration')

@section('styles')
<style>
.loading-overlay                                                            <div class="input-group">
                                                    <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                                    <input type="number" class="form-control" id="sale-price" step="0.01" min="0">
                                                </div>                                 <div class="input-group">
                                                    <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                                    <input type="number" class="form-control" id="regular-price" step="0.01" min="0">
                                                </div>  position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.form-group {
    margin-bottom: 1rem;
}

.image-preview {
    max-width: 100px;
    max-height: 100px;
    object-fit: cover;
    border-radius: 4px;
}

.image-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 4px;
    padding: 2rem;
    text-align: center;
    transition: border-color 0.3s;
}

.image-upload-area:hover {
    border-color: #007bff;
}

.image-upload-area.dragover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.variation-row {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #f8f9fa;
}

.attribute-list {
    max-height: 200px;
    overflow-y: auto;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Edit Product: <span id="product-name">Loading...</span>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="saveProduct()" id="save-btn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
                <div class="card-body position-relative">
                    <!-- Loading overlay -->
                    <div class="loading-overlay d-none" id="loading-overlay">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2" id="loading-text">Loading product data...</div>
                        </div>
                    </div>

                    <!-- Error alert -->
                    <div class="alert alert-danger d-none" id="error-alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="error-message"></span>
                    </div>

                    <!-- Success alert -->
                    <div class="alert alert-success d-none" id="success-alert">
                        <i class="fas fa-check-circle"></i>
                        <span id="success-message">Product updated successfully!</span>
                    </div>

                    <form id="product-form">
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Basic Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="product-name-input">Product Name *</label>
                                            <input type="text" class="form-control" id="product-name-input" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="product-sku">SKU</label>
                                            <input type="text" class="form-control" id="product-sku">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="regular-price">Regular Price</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                                        <input type="number" class="form-control" id="regular-price" step="0.01" min="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sale-price">Sale Price</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                                        <input type="number" class="form-control" id="sale-price" step="0.01" min="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Name Your Price Settings -->
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="nyp-enabled">
                                                <label class="form-check-label" for="nyp-enabled">
                                                    Enable Name Your Price (YITH)
                                                </label>
                                            </div>
                                        </div>

                                        <div id="nyp-settings" style="display: none;">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="nyp-min-price">Minimum Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                                            <input type="number" class="form-control" id="nyp-min-price" step="0.01" min="0">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="nyp-max-price">Maximum Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                                            <input type="number" class="form-control" id="nyp-max-price" step="0.01" min="0">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="nyp-suggested-price">Suggested Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                                            <input type="number" class="form-control" id="nyp-suggested-price" step="0.01" min="0">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="nyp-hide-regular-price">
                                                    <label class="form-check-label" for="nyp-hide-regular-price">
                                                        Hide regular price when Name Your Price is active
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="product-description">Description</label>
                                            <textarea class="form-control" id="product-description" rows="4"></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label for="product-short-description">Short Description</label>
                                            <textarea class="form-control" id="product-short-description" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Product Images -->
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Product Images</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="image-gallery" class="mb-3">
                                            <!-- Images will be loaded here -->
                                        </div>
                                        <div class="image-upload-area" id="image-upload-area">
                                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                            <p class="mb-1">Drag & drop images here or click to browse</p>
                                            <small class="text-muted">Supported formats: JPG, PNG, GIF</small>
                                            <input type="file" id="image-upload" multiple accept="image/*" style="display: none;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Inventory -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Inventory</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" class="form-check-input" id="manage-stock">
                                            <label class="form-check-label" for="manage-stock">
                                                Manage stock?
                                            </label>
                                        </div>

                                        <div class="form-group">
                                            <label for="stock-quantity">Stock Quantity</label>
                                            <input type="number" class="form-control" id="stock-quantity" min="0">
                                        </div>

                                        <div class="form-group">
                                            <label for="stock-status">Stock Status</label>
                                            <select class="form-control" id="stock-status">
                                                <option value="instock">In Stock</option>
                                                <option value="outofstock">Out of Stock</option>
                                                <option value="onbackorder">On Backorder</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Status</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="product-status">Product Status</label>
                                            <select class="form-control" id="product-status">
                                                <option value="publish">Published</option>
                                                <option value="draft">Draft</option>
                                                <option value="pending">Pending Review</option>
                                                <option value="private">Private</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="tax-status">Tax Status</label>
                                            <select class="form-control" id="tax-status">
                                                <option value="taxable">Taxable</option>
                                                <option value="none">None (Zero Tax)</option>
                                                <option value="shipping">Shipping Only</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Categories and Tags -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Categories</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="categories-list">
                                            <!-- Categories will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Tags</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="tags-list">
                                            <!-- Tags will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attributes (for variable products) -->
                        <div class="card mt-4" id="attributes-card" style="display: none;">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Product Attributes</h5>
                            </div>
                            <div class="card-body">
                                <div id="attributes-list" class="attribute-list">
                                    <!-- Attributes will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Variations (for variable products) -->
                        <div class="card mt-4" id="variations-card" style="display: none;">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Product Variations</h5>
                            </div>
                            <div class="card-body">
                                <div id="variations-list">
                                    <!-- Variations will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Product data
let productData = null;
let productId = {{ $product->woo_product_id ?? 'null' }};

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Wait for mwfApi to be available
    waitForMwfApi().then(() => {
        if (productId) {
            loadProduct();
        } else {
            showError('No WooCommerce product ID found for this product.');
        }
    }).catch((error) => {
        showError('Failed to load MWF API: ' + error.message);
    });

    // Name Your Price toggle
    document.getElementById('nyp-enabled').addEventListener('change', function() {
        const nypSettings = document.getElementById('nyp-settings');
        if (this.checked) {
            nypSettings.style.display = 'block';
        } else {
            nypSettings.style.display = 'none';
        }
    });
});

/**
 * Wait for mwfApi to be available on window
 */
function waitForMwfApi() {
    return new Promise((resolve, reject) => {
        // Check immediately
        if (window.mwfApi) {
            resolve();
            return;
        }

        // Listen for the custom event
        const handleApiReady = (event) => {
            window.removeEventListener('mwfApiReady', handleApiReady);
            resolve();
        };
        window.addEventListener('mwfApiReady', handleApiReady);

        // Fallback: also check periodically
        const checkInterval = setInterval(() => {
            if (window.mwfApi) {
                clearInterval(checkInterval);
                window.removeEventListener('mwfApiReady', handleApiReady);
                resolve();
            }
        }, 50);

        // Timeout after 10 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
            window.removeEventListener('mwfApiReady', handleApiReady);
            reject(new Error('MWF API client failed to load within timeout'));
        }, 10000);
    });
}

/**
 * Load product data from MWF API
 */
async function loadProduct() {
    showLoading('Loading product data...');

    try {
        const response = await window.mwfApi.getProduct(productId);

        if (response.success && response.product) {
            productData = response.product;
            populateForm(productData);
            hideLoading();

            // Load variations if it's a variable product
            if (productData.type === 'variable') {
                loadVariations();
            }
        } else {
            throw new Error('Failed to load product data');
        }
    } catch (error) {
        hideLoading();
        showError('Failed to load product: ' + error.message);
    }
}

/**
 * Load product variations
 */
async function loadVariations() {
    try {
        const response = await window.mwfApi.getProductVariations(productId);

        if (response.success) {
            displayVariations(response.variations || []);
        }
    } catch (error) {
        console.error('Failed to load variations:', error);
    }
}

/**
 * Populate form with product data
 */
function populateForm(product) {
    // Basic info
    document.getElementById('product-name').textContent = product.name;
    document.getElementById('product-name-input').value = product.name || '';
    document.getElementById('product-sku').value = product.sku || '';
    document.getElementById('regular-price').value = product.regular_price || '';
    document.getElementById('sale-price').value = product.sale_price || '';
    // Clean HTML from description for better editing experience
    const cleanDescription = product.description ? 
        product.description
            .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '') // Remove scripts
            .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '') // Remove styles
            .replace(/<[^>]*>/g, '') // Remove HTML tags
            .replace(/&nbsp;/g, ' ') // Replace non-breaking spaces
            .replace(/&[a-zA-Z0-9#]+;/g, ' ') // Replace other HTML entities
            .replace(/\s+/g, ' ') // Normalize whitespace
            .trim() : '';
    
    const cleanShortDescription = product.short_description ? 
        product.short_description
            .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
            .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
            .replace(/<[^>]*>/g, '')
            .replace(/&nbsp;/g, ' ')
            .replace(/&[a-zA-Z0-9#]+;/g, ' ')
            .replace(/\s+/g, ' ')
            .trim() : '';
    
    document.getElementById('product-description').value = cleanDescription;
    document.getElementById('product-short-description').value = cleanShortDescription;

    // Inventory
    document.getElementById('manage-stock').checked = product.manage_stock || false;
    document.getElementById('stock-quantity').value = product.stock_quantity || '';
    document.getElementById('stock-status').value = product.stock_status || 'instock';

    // Status
    document.getElementById('product-status').value = product.status || 'publish';
    document.getElementById('tax-status').value = product.tax_status || 'taxable';

    // Name Your Price settings
    const nypEnabled = product.meta_data && product.meta_data._yith_name_your_price_enabled === 'yes';
    document.getElementById('nyp-enabled').checked = nypEnabled;
    document.getElementById('nyp-settings').style.display = nypEnabled ? 'block' : 'none';

    if (nypEnabled) {
        document.getElementById('nyp-min-price').value = product.meta_data._yith_name_your_price_min_price || '';
        document.getElementById('nyp-max-price').value = product.meta_data._yith_name_your_price_max_price || '';
        document.getElementById('nyp-suggested-price').value = product.meta_data._yith_name_your_price_suggested_price || '';
        document.getElementById('nyp-hide-regular-price').checked = product.meta_data._yith_name_your_price_hide_regular_price === 'yes';
    }

    // Images
    displayImages(product.images || []);

    // Categories and tags
    displayCategories(product.categories || []);
    displayTags(product.tags || []);

    // Attributes (always show the card for editing)
    document.getElementById('attributes-card').style.display = 'block';
    productData.attributes = product.attributes || [];
    displayAttributes(productData.attributes);

    // Show variations card for variable products
    if (product.type === 'variable') {
        document.getElementById('variations-card').style.display = 'block';
    }
}

/**
 * Display product images
 */
function displayImages(images) {
    const gallery = document.getElementById('image-gallery');
    gallery.innerHTML = '';

    if (images.length === 0) {
        gallery.innerHTML = '<p class="text-muted">No images uploaded</p>';
        return;
    }

    images.forEach(image => {
        const img = document.createElement('img');
        img.src = image.url;
        img.alt = image.alt || '';
        img.className = 'image-preview me-2 mb-2';
        gallery.appendChild(img);
    });
}

/**
 * Display categories
 */
function displayCategories(categories) {
    const container = document.getElementById('categories-list');
    container.innerHTML = '';

    if (!categories || categories.length === 0) {
        container.innerHTML = '<small class="text-muted">No categories assigned</small>';
        return;
    }

    const categoryNames = categories.map(cat => typeof cat === 'object' ? cat.name : cat).join(', ');
    container.innerHTML = `<small class="text-muted">Categories: ${categoryNames}</small>`;
}

/**
 * Display tags
 */
function displayTags(tags) {
    const container = document.getElementById('tags-list');
    container.innerHTML = '';

    if (!tags || tags.length === 0) {
        container.innerHTML = '<small class="text-muted">No tags assigned</small>';
        return;
    }

    const tagNames = tags.map(tag => typeof tag === 'object' ? tag.name : tag).join(', ');
    container.innerHTML = `<small class="text-muted">Tags: ${tagNames}</small>`;
}

/**
 * Display attributes (editable)
 */
function displayAttributes(attributes) {
    const container = document.getElementById('attributes-list');
    container.innerHTML = '';

    if (!attributes || attributes.length === 0) {
        container.innerHTML = '<p class="text-muted">No attributes defined</p>';
    } else {
        attributes.forEach((attr, index) => {
            const div = document.createElement('div');
            div.className = 'attribute-item border rounded p-3 mb-3';
            div.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Attribute Name</label>
                        <input type="text" class="form-control" value="${attr.name || ''}" data-attr-field="name" data-attr-index="${index}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Options (comma-separated)</label>
                        <input type="text" class="form-control" value="${attr.options ? attr.options.join(', ') : ''}" data-attr-field="options" data-attr-index="${index}" placeholder="e.g. Single Person, Couples Vegbox, Small Family, Large Family" required>
                        <div class="form-text">Separate multiple options with commas</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Settings</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" ${attr.visible ? 'checked' : ''} data-attr-field="visible" data-attr-index="${index}" id="visible-${index}">
                            <label class="form-check-label" for="visible-${index}">
                                Visible on product page
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" ${attr.variation ? 'checked' : ''} data-attr-field="variation" data-attr-index="${index}" id="variation-${index}">
                            <label class="form-check-label" for="variation-${index}">
                                Used for variations
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeAttribute(${index})">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    }

    // Add "Add Attribute" button
    const addButton = document.createElement('div');
    addButton.className = 'mt-3';
    addButton.innerHTML = `
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addAttribute()">
            <i class="fas fa-plus"></i> Add Attribute
        </button>
    `;
    container.appendChild(addButton);
}

/**
 * Display variations
 */
function displayVariations(variations) {
    const container = document.getElementById('variations-list');
    container.innerHTML = '';

    if (variations.length === 0) {
        container.innerHTML = '<p class="text-muted">No variations found</p>';
        return;
    }

    variations.forEach(variation => {
        const div = document.createElement('div');
        div.className = 'variation-row';
        div.innerHTML = `
            <div class="row">
                <div class="col-md-3">
                    <strong>SKU:</strong> ${variation.sku || 'N/A'}
                </div>
                <div class="col-md-3">
                    <strong>Price:</strong> {{ config('pos_payments.currency_symbol', '£') }}${variation.price || 'N/A'}
                </div>
                <div class="col-md-3">
                    <strong>Stock:</strong> ${variation.stock_quantity || 0}
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong> ${variation.stock_status || 'N/A'}
                </div>
            </div>
            ${variation.attributes ? `<div class="mt-2"><strong>Attributes:</strong> ${Object.values(variation.attributes).join(', ')}</div>` : ''}
        `;
        container.appendChild(div);
    });
}

/**
 * Save product changes
 */
async function saveProduct() {
    if (!productData) {
        showError('No product data loaded');
        return;
    }

    showLoading('Saving product...');
    disableForm(true);

    try {
        const attributes = getAttributeData();
        if (attributes === null) {
            // Validation errors occurred, error message already shown
            hideLoading();
            disableForm(false);
            return;
        }

        const updateData = {
            name: document.getElementById('product-name-input').value,
            sku: document.getElementById('product-sku').value,
            regular_price: document.getElementById('regular-price').value,
            sale_price: document.getElementById('sale-price').value,
            description: document.getElementById('product-description').value,
            short_description: document.getElementById('product-short-description').value,
            manage_stock: document.getElementById('manage-stock').checked,
            stock_quantity: document.getElementById('stock-quantity').value,
            stock_status: document.getElementById('stock-status').value,
            status: document.getElementById('product-status').value,
            tax_status: document.getElementById('tax-status').value,
            attributes: attributes,
            // YITH Name Your Price settings
            nyp_enabled: document.getElementById('nyp-enabled').checked,
            nyp_min_price: document.getElementById('nyp-min-price').value,
            nyp_max_price: document.getElementById('nyp-max-price').value,
            nyp_suggested_price: document.getElementById('nyp-suggested-price').value,
            nyp_hide_regular_price: document.getElementById('nyp-hide-regular-price').checked,
        };

        const response = await window.mwfApi.updateProduct(productId, updateData);

        if (response.success) {
            showSuccess();
            // Reload product data to reflect changes
            setTimeout(() => loadProduct(), 1500);
        } else {
            throw new Error(response.message || 'Failed to update product');
        }
    } catch (error) {
        showError('Failed to save product: ' + error.message);
    } finally {
        hideLoading();
        disableForm(false);
    }
}

/**
 * Add a new attribute
 */
function addAttribute() {
    showLoading('Adding attribute...');

    setTimeout(() => {
        if (!productData.attributes) {
            productData.attributes = [];
        }

        productData.attributes.push({
            name: '',
            options: [],
            visible: true,
            variation: false
        });

        displayAttributes(productData.attributes);
        hideLoading();
    }, 200);
}

/**
 * Remove an attribute
 */
function removeAttribute(index) {
    if (confirm('Are you sure you want to remove this attribute?')) {
        showLoading('Removing attribute...');

        setTimeout(() => {
            productData.attributes.splice(index, 1);
            displayAttributes(productData.attributes);
            hideLoading();
        }, 200);
    }
}

/**
 * Get current attribute data from form
 */
function getAttributeData() {
    const attributes = [];
    const attributeItems = document.querySelectorAll('.attribute-item');
    const errors = [];

    attributeItems.forEach((item, index) => {
        const nameInput = item.querySelector('[data-attr-field="name"]');
        const optionsInput = item.querySelector('[data-attr-field="options"]');
        const visibleInput = item.querySelector('[data-attr-field="visible"]');
        const variationInput = item.querySelector('[data-attr-field="variation"]');

        const name = nameInput.value.trim();
        const optionsText = optionsInput.value.trim();

        // Validate attribute name
        if (!name) {
            errors.push(`Attribute ${index + 1}: Name cannot be empty`);
            return;
        }

        // Check for duplicate names
        if (attributes.some(attr => attr.name.toLowerCase() === name.toLowerCase())) {
            errors.push(`Attribute ${index + 1}: "${name}" already exists`);
            return;
        }

        // Validate options
        if (!optionsText) {
            errors.push(`Attribute ${index + 1} "${name}": Options cannot be empty`);
            return;
        }

        const options = optionsText.split(',').map(opt => opt.trim()).filter(opt => opt);

        if (options.length === 0) {
            errors.push(`Attribute ${index + 1} "${name}": At least one option is required`);
            return;
        }

        // Check for duplicate options
        const uniqueOptions = [...new Set(options.map(opt => opt.toLowerCase()))];
        if (uniqueOptions.length !== options.length) {
            errors.push(`Attribute ${index + 1} "${name}": Duplicate options are not allowed`);
            return;
        }

        attributes.push({
            name: name,
            options: options,
            visible: visibleInput.checked,
            variation: variationInput.checked
        });
    });

    if (errors.length > 0) {
        showError('Please fix the following errors:\n' + errors.join('\n'));
        return null;
    }

    return attributes;
}

/**
 * Show loading overlay
 */
function showLoading(text = 'Loading...') {
    document.getElementById('loading-text').textContent = text;
    document.getElementById('loading-overlay').classList.remove('d-none');
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    document.getElementById('loading-overlay').classList.add('d-none');
}

/**
 * Show error message
 */
function showError(message) {
    document.getElementById('error-message').textContent = message;
    document.getElementById('error-alert').classList.remove('d-none');
    document.getElementById('success-alert').classList.add('d-none');
}

/**
 * Show success message
 */
function showSuccess() {
    document.getElementById('success-alert').classList.remove('d-none');
    document.getElementById('error-alert').classList.add('d-none');
}

/**
 * Disable/enable form
 */
function disableForm(disabled) {
    const form = document.getElementById('product-form');
    const inputs = form.querySelectorAll('input, textarea, select');
    const saveBtn = document.getElementById('save-btn');

    inputs.forEach(input => input.disabled = disabled);
    saveBtn.disabled = disabled;

    if (disabled) {
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    } else {
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    }
}

// Image upload functionality (placeholder for now)
document.getElementById('image-upload-area').addEventListener('click', function() {
    document.getElementById('image-upload').click();
});

document.getElementById('image-upload').addEventListener('change', function(e) {
    // Handle file upload here
    console.log('Files selected:', e.target.files);
    // TODO: Implement image upload functionality
});
</script>
@endsection
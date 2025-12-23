@extends('layouts.admin')

@section('styles')
<style>
.table {
    min-width: 1200px;
    margin-bottom: 0;
}
.table th, .table td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}
.table th:nth-child(3), .table td:nth-child(3) { /* Image column */
    width: 80px;
    max-width: 80px;
}
.table th:nth-child(4), .table td:nth-child(4) { /* Name column */
    max-width: 250px;
}
.table th:nth-child(5), .table td:nth-child(5) { /* SKU column */
    width: 100px;
    max-width: 100px;
}
.table th:nth-child(8), .table td:nth-child(8) { /* WooCommerce column */
    width: 120px;
    max-width: 120px;
}
.table th:nth-child(9), .table td:nth-child(9) { /* Status column */
    width: 80px;
    max-width: 80px;
}
.table th:nth-child(10), .table td:nth-child(10) { /* Actions column */
    width: 140px;
    max-width: 140px;
}
/* Ensure badges and buttons fit properly */
.badge {
    font-size: 0.75rem;
}
.btn-group .btn {
    padding: 0.25rem 0.5rem;
}
</style>
@endsection

@section('title', 'Product Management')

@section('title', 'Product Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Products</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Product
                        </a>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a href="{{ route('admin.products.export.csv') }}" class="dropdown-item">
                                    <i class="fas fa-file-csv"></i> Export to CSV
                                </a>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="testWooCommerceConnection()">
                            <i class="fas fa-plug"></i> Test WC
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" id="mwf-dropdown" onclick="toggleMWFDropdown()">
                                <i class="fas fa-magic"></i> MWF Bulk Ops
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" id="mwf-dropdown-menu" style="display: none;">
                                <h6 class="dropdown-header">Product Actions</h6>
                                <a href="#" class="dropdown-item" onclick="bulkAction('publish')">
                                    <i class="fas fa-eye"></i> Publish Selected
                                </a>
                                <a href="#" class="dropdown-item" onclick="bulkAction('draft')">
                                    <i class="fas fa-eye-slash"></i> Draft Selected
                                </a>
                                <a href="#" class="dropdown-item" onclick="bulkAction('trash')">
                                    <i class="fas fa-trash"></i> Trash Selected
                                </a>
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header">Bulk Updates</h6>
                                <a href="#" class="dropdown-item" onclick="showBulkUpdateModal()">
                                    <i class="fas fa-edit"></i> Update Prices
                                </a>
                                <a href="#" class="dropdown-item" onclick="showBulkUpdateModal()">
                                    <i class="fas fa-boxes"></i> Update Stock
                                </a>
                                <a href="#" class="dropdown-item" onclick="showBulkUpdateModal()">
                                    <i class="fas fa-tags"></i> Update Categories
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-header border-0">
                    <form method="GET" action="{{ route('admin.products.index') }}" class="form-inline">
                        <div class="form-group mr-3">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search products..." value="{{ request('search') }}">
                        </div>
                        <div class="form-group mr-3">
                            <select name="category" class="form-control form-control-sm">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <select name="status" class="form-control form-control-sm">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm mr-2">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </form>
                </div>

                <div class="card-body p-0" style="overflow-x: auto;">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th width="60">
                                    <a href="{{ route('admin.products.index', ['sort' => 'id', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}">
                                        ID {{ request('sort') == 'id' ? (request('direction') == 'asc' ? '↑' : '↓') : '' }}
                                    </a>
                                </th>
                                <th width="80">Image</th>
                                <th>
                                    <a href="{{ route('admin.products.index', ['sort' => 'name', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}">
                                        Name {{ request('sort') == 'name' ? (request('direction') == 'asc' ? '↑' : '↓') : '' }}
                                    </a>
                                </th>
                                <th width="100">SKU</th>
                                <th width="80">
                                    <a href="{{ route('admin.products.index', ['sort' => 'price', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}">
                                        Price {{ request('sort') == 'price' ? (request('direction') == 'asc' ? '↑' : '↓') : '' }}
                                    </a>
                                </th>
                                <th width="120">Category</th>
                                <th width="70">Stock</th>
                                <th width="120">WooCommerce</th>
                                <th width="80">Status</th>
                                <th width="140">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input product-checkbox" value="{{ $product->id }}">
                                    </td>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        @if($product->image_url)
                                            @if(str_starts_with($product->image_url, 'http'))
                                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            @endif
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $product->name }}</strong>
                                        @if($product->description)
                                            <br><small class="text-muted">{{ Str::limit(html_entity_decode(strip_tags($product->description)), 50) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $product->sku }}</td>
                                    <td>{{ config('pos_payments.currency_symbol', '£') }}{{ number_format($product->price, 2) }}</td>
                                    <td>{{ $product->category ?: '-' }}</td>
                                    <td>
                                        @if($product->stock_quantity !== null)
                                            {{ $product->stock_quantity }}
                                            @if($product->min_stock_level && $product->stock_quantity <= $product->min_stock_level)
                                                <span class="badge badge-warning">Low</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->woo_product_id)
                                            <span class="badge badge-success" title="Linked to WooCommerce product #{{ $product->woo_product_id }}">
                                                <i class="fab fa-wordpress"></i> Linked
                                            </span>
                                        @else
                                            <span class="badge badge-secondary" title="Not linked to WooCommerce">
                                                <i class="fas fa-unlink"></i> Not Linked
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-info btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm {{ $product->is_active ? 'btn-secondary' : 'btn-success' }}"
                                                    onclick="toggleStatus({{ $product->id }})"
                                                    title="{{ $product->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="fas fa-{{ $product->is_active ? 'ban' : 'check' }}"></i>
                                            </button>
                                            @if($product->woo_product_id)
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                        onclick="syncWithWooCommerce({{ $product->id }})"
                                                        title="Sync with WooCommerce">
                                                    <i class="fab fa-wordpress"></i>
                                                </button>
                                                <a href="{{ route('admin.products.api-edit', $product) }}" 
                                                   class="btn btn-outline-info btn-sm" 
                                                   title="Edit via MWF API">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('admin.products.iframe-edit', $product) }}" 
                                                   class="btn btn-outline-secondary btn-sm" 
                                                   target="_blank"
                                                   title="Edit in WooCommerce Admin">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <a href="{{ route('admin.products.variations', $product) }}" 
                                                   class="btn btn-outline-purple btn-sm" 
                                                   title="Manage Variations">
                                                    <i class="fas fa-layer-group"></i>
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-outline-success btn-sm"
                                                        onclick="syncWithWooCommerce({{ $product->id }})"
                                                        title="Link to WooCommerce">
                                                    <i class="fas fa-link"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteProduct({{ $product->id }}, '{{ $product->name }}')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-box-open fa-3x mb-3"></i>
                                            <p>No products found.</p>
                                            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Your First Product
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($products->hasPages())
                    <div class="card-footer">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Import Products from CSV</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.products.import.csv') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="csv_file">Select CSV File</label>
                        <input type="file" class="form-control-file" id="csv_file" name="csv_file" accept=".csv,.txt" required>
                        <small class="form-text text-muted">
                            CSV should have columns: ID, Name, SKU, Description, Price, Cost Price, Category, Stock Quantity, Active, Taxable
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Select all checkbox functionality
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
});

function toggleStatus(productId) {
    if (confirm('Are you sure you want to change this product\'s status?')) {
        $.ajax({
            url: '{{ route("admin.products.toggle-active", ":id") }}'.replace(':id', productId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while updating the product status.');
            }
        });
    }
}

function deleteProduct(productId, productName) {
    if (confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.products.destroy", ":id") }}'.replace(':id', productId);

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = '{{ csrf_token() }}';
        form.appendChild(token);

        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        form.appendChild(method);

        document.body.appendChild(form);
        form.submit();
    }
}

function syncWithWooCommerce(productId) {
    console.log('Syncing product:', productId);
    if (confirm('Sync this product with WooCommerce?')) {
        $.ajax({
            url: '{{ route("admin.products.sync-woocommerce", ":id") }}'.replace(':id', productId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Sync response:', response);
                if (response.success) {
                    alert('Success: ' + response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Sync error:', xhr, status, error);
                alert('An error occurred while syncing with WooCommerce: ' + (xhr.responseJSON?.message || error || 'Unknown error'));
            }
        });
    }
}

function testWooCommerceConnection() {
    console.log('Testing WooCommerce connection...');
    $.ajax({
        url: '{{ route("admin.products.test-woocommerce-connection") }}',
        type: 'GET',
        success: function(response) {
            console.log('Connection test response:', response);
            if (response.success) {
                alert('WooCommerce connection successful!');
            } else {
                alert('WooCommerce connection failed: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Connection test error:', xhr, status, error);
            alert('Connection test failed: ' + (xhr.responseJSON?.message || error || 'Unknown error'));
        }
    });
}

function syncAllWithWooCommerce() {
    if (confirm('Sync all active products with WooCommerce? This may take some time.')) {
        $.ajax({
            url: '{{ route("admin.products.sync-all-woocommerce") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Bulk sync completed: ' + response.message);
                    location.reload();
                } else {
                    alert('Bulk sync failed: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('An error occurred during bulk sync: ' + xhr.responseJSON?.message || 'Unknown error');
            }
        });
    }
}

function bulkSyncSelected() {
    const selectedProducts = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => parseInt(cb.value));
    
    if (selectedProducts.length === 0) {
        alert('Please select products to sync.');
        return;
    }
    
    if (confirm('Sync ' + selectedProducts.length + ' selected products with WooCommerce?')) {
        $.ajax({
            url: '{{ route("admin.products.bulk-sync-woocommerce") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                product_ids: selectedProducts
            },
            success: function(response) {
                if (response.success) {
                    alert('Bulk sync completed: ' + response.message);
                    location.reload();
                } else {
                    alert('Bulk sync failed: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('An error occurred during bulk sync: ' + xhr.responseJSON?.message || 'Unknown error');
            }
        });
    }
}

function fetchAllFromWooCommerce() {
    if (confirm('Fetch all products from WooCommerce? This will create or update products in your admin system.')) {
        $.ajax({
            url: '{{ route("admin.products.fetch-all-woocommerce") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Fetch completed: ' + response.message);
                    location.reload();
                } else {
                    alert('Fetch failed: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('An error occurred during fetch: ' + xhr.responseJSON?.message || 'Unknown error');
            }
        });
    }
}

function bulkFetchSelected() {
    const selectedProducts = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => parseInt(cb.value));
    
    if (selectedProducts.length === 0) {
        alert('Please select products to fetch. Note: This will fetch the corresponding WooCommerce products by ID.');
        return;
    }
    
    if (confirm('Fetch ' + selectedProducts.length + ' selected products from WooCommerce? This will update the selected products with data from WooCommerce.')) {
        $.ajax({
            url: '{{ route("admin.products.bulk-fetch-woocommerce") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                product_ids: selectedProducts
            },
            success: function(response) {
                if (response.success) {
                    alert('Bulk fetch completed: ' + response.message);
                    location.reload();
                } else {
                    alert('Bulk fetch failed: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('An error occurred during bulk fetch: ' + xhr.responseJSON?.message || 'Unknown error');
            }
        });
    }
}

// Initialize Bootstrap dropdowns
$(document).ready(function() {
    $('.dropdown-toggle').dropdown();
});

function toggleWooCommerceDropdown() {
    const menu = document.getElementById('woocommerce-dropdown-menu');
    const isVisible = menu.style.display !== 'none';
    
    // Hide all dropdowns first
    document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
        dropdown.style.display = 'none';
    });
    
    // Toggle this dropdown
    menu.style.display = isVisible ? 'none' : 'block';
}

function toggleMWFDropdown() {
    const menu = document.getElementById('mwf-dropdown-menu');
    const isVisible = menu.style.display !== 'none';
    
    // Hide all dropdowns first
    document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
        dropdown.style.display = 'none';
    });
    
    // Toggle this dropdown
    menu.style.display = isVisible ? 'none' : 'block';
}

// MWF Bulk Operations
async function bulkAction(action) {
    const selectedProducts = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => parseInt(cb.value));
    
    if (selectedProducts.length === 0) {
        alert('Please select products to perform this action on.');
        return;
    }
    
    showBulkProgress('Getting WooCommerce IDs...', 10);
    
    // Get WooCommerce product IDs for selected products
    const wooProductIds = [];
    const promises = selectedProducts.map(productId => getWooProductId(productId));
    
    try {
        const results = await Promise.all(promises);
        results.forEach(wooId => {
            if (wooId) wooProductIds.push(wooId);
        });
        
        if (wooProductIds.length === 0) {
            hideBulkProgress();
            alert('No selected products are linked to WooCommerce. Please link them first.');
            return;
        }
        
        if (!confirm(`Are you sure you want to ${action} ${wooProductIds.length} selected products?`)) {
            hideBulkProgress();
            return;
        }
        
        await executeBulkAction(action, wooProductIds);
    } catch (error) {
        hideBulkProgress();
        alert('Failed to get WooCommerce IDs: ' + error.message);
    }
}

async function executeBulkAction(action, wooProductIds) {
    try {
        showBulkProgress('Executing bulk action...', 0);
        
        const response = await window.mwfApi.executeAction(action, wooProductIds);
        
        if (response.success) {
            showBulkProgress(`Bulk ${action} completed successfully! ${response.success_count} succeeded, ${response.error_count} failed.`, 100);
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            throw new Error(response.message || 'Bulk action failed');
        }
    } catch (error) {
        hideBulkProgress();
        alert('Bulk action failed: ' + error.message);
    }
}

async function getWooProductId(productId) {
    try {
        // Make a simple API call to get product info
        const response = await fetch(`/admin/products/${productId}/woo-id`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            return data.woo_product_id;
        }
    } catch (error) {
        console.error('Failed to get WooCommerce ID for product', productId, error);
    }
    return null;
}

function showBulkUpdateModal() {
    // For now, show a simple prompt. In a real implementation, this would open a modal
    const field = prompt('What field do you want to update? (regular_price, sale_price, stock_quantity)');
    if (!field) return;
    
    const value = prompt(`Enter new value for ${field}:`);
    if (value === null) return;
    
    bulkUpdate(field, value);
}

async function bulkUpdate(field, value) {
    const selectedProducts = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => parseInt(cb.value));
    
    if (selectedProducts.length === 0) {
        alert('Please select products to update.');
        return;
    }
    
    showBulkProgress('Getting WooCommerce IDs...', 10);
    
    // Get WooCommerce product IDs for selected products
    const wooProductIds = [];
    const promises = selectedProducts.map(productId => getWooProductId(productId));
    
    try {
        const results = await Promise.all(promises);
        results.forEach(wooId => {
            if (wooId) wooProductIds.push(wooId);
        });
        
        if (wooProductIds.length === 0) {
            hideBulkProgress();
            alert('No selected products are linked to WooCommerce. Please link them first.');
            return;
        }
        
        if (!confirm(`Update ${field} to "${value}" for ${wooProductIds.length} products?`)) {
            hideBulkProgress();
            return;
        }
        
        showBulkProgress('Updating products...', 50);
        
        const response = await fetch('/admin/products/mwf-integration/bulk-update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                action: 'bulk_field_update', // Special action for field updates
                product_ids: wooProductIds,
                field: field,
                value: value
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showBulkProgress(`Bulk update completed! ${result.success_count || wooProductIds.length} updated.`, 100);
            setTimeout(() => {
                hideBulkProgress();
                location.reload(); // Refresh to show updated values
            }, 1500);
        } else {
            hideBulkProgress();
            alert('Bulk update failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        hideBulkProgress();
        alert('Failed to perform bulk update: ' + error.message);
    }
}

function showBulkProgress(message, progress) {
    // Remove existing progress bar if any
    const existing = document.getElementById('bulk-progress-container');
    if (existing) existing.remove();
    
    // Create progress container
    const container = document.createElement('div');
    container.id = 'bulk-progress-container';
    container.className = 'alert alert-info position-fixed';
    container.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    container.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div>
                <div id="bulk-progress-message">${message}</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: ${progress}%"></div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(container);
}

function hideBulkProgress() {
    const container = document.getElementById('bulk-progress-container');
    if (container) {
        container.remove();
    }
}

// Close dropdowns when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('.btn-group').length) {
        document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }
});
</script>
@endsection
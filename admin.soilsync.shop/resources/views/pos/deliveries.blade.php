@extends('layouts.app')

@section('title', 'Weekly Schedule')

@section('page-header')
    <div class="d-flex justify-content-center align-items-center w-100">
        <h2 class="mb-0">Weekly Schedule</h2>
    </div>
@endsection

@section('styles')
<style>
    /* Mobile-optimized styles */
    .sidebar {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
    
    .container-fluid {
        padding: 0.5rem !important;
    }
    
    .delivery-card {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .delivery-card.completed {
        opacity: 0.6;
        background: #f8f9fa;
        border-color: #28a745;
    }
    
    .customer-name {
        font-size: 1.25rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .badge-type {
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
    
    .btn-complete {
        width: 100%;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: bold;
    }
    
    .loading {
        text-align: center;
        padding: 3rem;
        font-size: 1.25rem;
        color: #6c757d;
    }
    
    .count-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 1.5rem;
    }
    
    .filter-tabs {
        margin-bottom: 1rem;
        position: sticky;
        top: 0;
        background: white;
        z-index: 100;
        padding: 0.5rem 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .filter-tabs .btn {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    /* Mobile dropdown styling */
    #mobile-filter-select {
        font-size: 1.1rem;
        padding: 0.75rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Back and Refresh Buttons -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('pos.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to POS
                </a>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
            
            <!-- Date Display -->
            <div class="text-center mb-3">
                <h5 class="mb-0" id="delivery-date"></h5>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <!-- Desktop Filter Buttons -->
                <div class="d-none d-md-flex flex-wrap gap-2">
                    <button class="btn btn-primary" id="filter-all" onclick="filterDeliveries('all')">
                        All (<span id="count-all">0</span>)
                    </button>
                    <button class="btn btn-outline-primary" id="filter-delivery" onclick="filterDeliveries('delivery')">
                        Deliveries (<span id="count-delivery">0</span>)
                    </button>
                    <button class="btn btn-outline-success" id="filter-collection" onclick="filterDeliveries('collection')">
                        Collections (<span id="count-collection">0</span>)
                    </button>
                    <button class="btn btn-outline-secondary" id="filter-completed" onclick="filterDeliveries('completed')">
                        Completed (<span id="count-completed">0</span>)
                    </button>
                </div>
                
                <!-- Mobile Filter Dropdown -->
                <div class="d-md-none">
                    <select class="form-select" id="mobile-filter-select" onchange="filterDeliveries(this.value)">
                        <option value="all">All</option>
                        <option value="delivery">Deliveries</option>
                        <option value="collection">Collections</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
            
            <!-- Loading State -->
            <div id="loading" class="loading">
                <i class="fas fa-spinner fa-spin fa-3x"></i>
                <p>Loading schedule...</p>
            </div>
            
            <!-- Deliveries List -->
            <div id="deliveries-list" style="display: none;"></div>
            
            <!-- Empty State -->
            <div id="empty-state" style="display: none; text-align: center; padding: 3rem;">
                <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                <h3>All Done!</h3>
                <p class="text-muted">No pending items for this week</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let allDeliveries = [];
let currentFilter = 'all';

// Set today's date
document.getElementById('delivery-date').textContent = new Date().toLocaleDateString('en-GB', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
});

// Load deliveries on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDeliveries();
});

async function loadDeliveries() {
    try {
        const response = await fetch('/pos/deliveries/data', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to load deliveries: ' + response.statusText);
        }
        
        const data = await response.json();
        console.log('Received data:', data);
        
        // Combine deliveries and collections
        allDeliveries = [];
        
        // The API returns data.deliveries and data.collections which are objects with date keys
        // Each date key contains an object with 'deliveries' and 'collections' arrays
        if (data.deliveries) {
            Object.entries(data.deliveries).forEach(([date, dayData]) => {
                // Process deliveries for this day
                if (dayData.deliveries && Array.isArray(dayData.deliveries)) {
                    dayData.deliveries.forEach(delivery => {
                        allDeliveries.push({
                            id: delivery.id || delivery.order_number,
                            name: delivery.name || delivery.customer_name || 'Unknown Customer',
                            location: delivery.address || delivery.shipping_address || '',
                            date: date,
                            type: 'delivery',
                            completed: delivery.completed || delivery.completion_status === 'completed' || false
                        });
                    });
                }
                
                // Process collections for this day (they're in the same structure)
                if (dayData.collections && Array.isArray(dayData.collections)) {
                    dayData.collections.forEach(collection => {
                        allDeliveries.push({
                            id: collection.id || collection.order_number,
                            name: collection.name || collection.customer_name || 'Unknown Customer',
                            location: collection.address || collection.shipping_address || '',
                            date: date,
                            type: 'collection',
                            completed: collection.completed || collection.completion_status === 'completed' || false
                        });
                    });
                }
            });
        }
        
        document.getElementById('loading').style.display = 'none';
        updateCounts();
        renderDeliveries();
        
    } catch (error) {
        console.error('Error loading schedule:', error);
        document.getElementById('loading').innerHTML = `
            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
            <p>Error loading schedule: ${error.message}</p>
            <button class="btn btn-primary" onclick="loadDeliveries()">Retry</button>
        `;
    }
}

function updateCounts() {
    const deliveryCount = allDeliveries.filter(d => d.type === 'delivery' && !d.completed).length;
    const collectionCount = allDeliveries.filter(d => d.type === 'collection' && !d.completed).length;
    const completedCount = allDeliveries.filter(d => d.completed).length;
    const allCount = allDeliveries.filter(d => !d.completed).length;
    
    console.log('Updating counts:', { deliveryCount, collectionCount, completedCount, allCount, totalDeliveries: allDeliveries.length });
    
    // Update desktop button counts
    const countAll = document.getElementById('count-all');
    const countDelivery = document.getElementById('count-delivery');
    const countCollection = document.getElementById('count-collection');
    const countCompleted = document.getElementById('count-completed');
    
    if (countAll) countAll.textContent = allCount;
    if (countDelivery) countDelivery.textContent = deliveryCount;
    if (countCollection) countCollection.textContent = collectionCount;
    if (countCompleted) countCompleted.textContent = completedCount;
    
    // Update mobile dropdown options with counts
    const mobileSelect = document.getElementById('mobile-filter-select');
    if (mobileSelect) {
        mobileSelect.options[0].text = `All (${allCount})`;
        mobileSelect.options[1].text = `Deliveries (${deliveryCount})`;
        mobileSelect.options[2].text = `Collections (${collectionCount})`;
        mobileSelect.options[3].text = `Completed (${completedCount})`;
    }
}

function filterDeliveries(filter) {
    currentFilter = filter;
    
    // Update desktop button states
    document.querySelectorAll('.filter-tabs .btn').forEach(btn => {
        btn.classList.remove('btn-primary');
        const btnFilter = btn.id.replace('filter-', '');
        
        if (btnFilter === filter) {
            btn.classList.add('btn-primary');
        } else if (filter === 'collection' && btnFilter === 'collection') {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-outline-success');
        } else if (btnFilter === 'collection') {
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-outline-success');
        } else if (btnFilter === 'completed') {
            btn.classList.add('btn-outline-secondary');
        } else {
            btn.classList.add('btn-outline-primary');
        }
    });
    
    // Update mobile dropdown selection
    const mobileSelect = document.getElementById('mobile-filter-select');
    if (mobileSelect) {
        mobileSelect.value = filter;
    }
    
    renderDeliveries();
}

function renderDeliveries() {
    let filtered = allDeliveries;
    
    // Apply filter
    if (currentFilter === 'delivery') {
        filtered = allDeliveries.filter(d => d.type === 'delivery' && !d.completed);
    } else if (currentFilter === 'collection') {
        filtered = allDeliveries.filter(d => d.type === 'collection' && !d.completed);
    } else if (currentFilter === 'completed') {
        filtered = allDeliveries.filter(d => d.completed);
    } else {
        filtered = allDeliveries.filter(d => !d.completed);
    }
    
    const listContainer = document.getElementById('deliveries-list');
    const emptyState = document.getElementById('empty-state');
    
    if (filtered.length === 0) {
        listContainer.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    listContainer.style.display = 'block';
    
    listContainer.innerHTML = filtered.map(item => `
        <div class="delivery-card ${item.completed ? 'completed' : ''}" id="delivery-${item.id}" data-date="${item.date}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="customer-name">${item.name || item.customer_name || 'Unknown'}</div>
                <span class="badge ${item.type === 'delivery' ? 'bg-primary' : 'bg-success'} badge-type">
                    <i class="fas fa-${item.type === 'delivery' ? 'truck' : 'hand-holding-box'}"></i>
                    ${item.type === 'delivery' ? 'Delivery' : 'Collection'}
                </span>
            </div>
            
            ${item.date ? `
                <div class="mb-2 text-muted small">
                    <i class="fas fa-calendar"></i> ${new Date(item.date).toLocaleDateString('en-GB', { weekday: 'short', month: 'short', day: 'numeric' })}
                </div>
            ` : ''}
            
            ${item.location ? `
                <div class="mb-2 text-muted">
                    <i class="fas fa-map-marker-alt"></i> ${item.location}
                </div>
            ` : ''}
            
            ${item.completed ? `
                <button class="btn btn-secondary btn-complete" disabled>
                    <i class="fas fa-check-circle"></i> Completed
                </button>
            ` : `
                <button class="btn btn-success btn-complete" onclick="completeDelivery('${item.id}', '${item.type}', '${item.date}')">
                    <i class="fas fa-check"></i> Mark Complete
                </button>
            `}
        </div>
    `).join('');
}

async function completeDelivery(id, type, date) {
    const card = document.getElementById(`delivery-${id}`);
    const button = card.querySelector('.btn-complete');
    const originalHtml = button.innerHTML;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Completing...';
    
    try {
        // Use the date passed from the button, or get from card data attribute
        const deliveryDate = date || card.getAttribute('data-date');
        
        const response = await fetch('/pos/deliveries/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                delivery_id: id,
                type: type,
                delivery_date: deliveryDate
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to complete');
        }
        
        // Mark as completed in local data
        const delivery = allDeliveries.find(d => d.id === id);
        if (delivery) {
            delivery.completed = true;
        }
        
        // Update UI
        card.classList.add('completed');
        button.innerHTML = '<i class="fas fa-check-circle"></i> Completed';
        button.classList.remove('btn-success');
        button.classList.add('btn-secondary');
        
        // Update counts
        updateCounts();
        
        // Show success toast
        showToast('✓ Completed successfully', 'success');
        
        // If filtering by active items, remove from view after delay
        if (currentFilter !== 'completed') {
            setTimeout(() => {
                card.style.transition = 'all 0.3s';
                card.style.opacity = '0';
                card.style.transform = 'translateX(100%)';
                setTimeout(() => renderDeliveries(), 300);
            }, 1000);
        }
        
    } catch (error) {
        console.error('Error completing delivery:', error);
        button.disabled = false;
        button.innerHTML = originalHtml;
        showToast('× Failed to complete', 'error');
    }
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        z-index: 9999;
        font-size: 1.1rem;
        animation: slideIn 0.3s ease-out;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
@endsection

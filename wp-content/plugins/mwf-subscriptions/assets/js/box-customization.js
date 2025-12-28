/**
 * Box Customization JavaScript
 * Handles drag-and-drop functionality and API communication
 */

(function($) {
    'use strict';
    
    let availableItemsSortable = null;
    let myBoxSortable = null;
    let currentData = null;
    
    const BoxCustomization = {
        
        init: function() {
            this.loadAvailableItems();
            this.initDragAndDrop();
            this.bindEvents();
        },
        
        bindEvents: function() {
            $('#save-box-selection').on('click', () => this.saveBoxSelection());
            $('#reset-to-default').on('click', () => this.resetToDefault());
            $('#week-select').on('change', () => this.loadAvailableItems());
            $('#items-search').on('input', (e) => this.filterItems(e.target.value));
        },
        
        initDragAndDrop: function() {
            const self = this;
            
            // Available items - can only drag FROM here
            availableItemsSortable = new Sortable(document.getElementById('available-items'), {
                group: {
                    name: 'box-items',
                    pull: 'clone',
                    put: false
                },
                animation: 150,
                sort: false,
                onEnd: function() {
                    self.updateTokenDisplay();
                }
            });
            
            // My Box - can drop and reorder
            myBoxSortable = new Sortable(document.getElementById('my-box'), {
                group: {
                    name: 'box-items',
                    pull: false,
                    put: true
                },
                animation: 150,
                onAdd: function(evt) {
                    const item = $(evt.item);
                    self.convertToMyBoxItem(item);
                    self.updateTokenDisplay();
                    self.enableSaveButton();
                },
                onUpdate: function() {
                    self.enableSaveButton();
                },
                onRemove: function() {
                    self.updateTokenDisplay();
                    self.enableSaveButton();
                }
            });
        },
        
        loadAvailableItems: function() {
            const subscriptionId = $('#subscription-id').val();
            const week = $('#week-select').val() || '';
            
            if (!subscriptionId) {
                return;
            }
            
            $('#available-items').html('<div class="loading-message"><span class="spinner"></span><p>Loading...</p></div>');
            $('#my-box').html('<div class="loading-message"><span class="spinner"></span><p>Loading...</p></div>');
            
            $.ajax({
                url: mwfBoxCustomization.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwf_get_available_items',
                    nonce: mwfBoxCustomization.nonce,
                    subscription_id: subscriptionId,
                    week: week
                },
                success: (response) => {
                    if (response.success) {
                        currentData = response.data;
                        this.renderAvailableItems(response.data.items);
                        this.renderMyBox(response.data.current_selections);
                        this.updateTokenDisplay();
                        $('#selection-id').val(response.data.selection.id);
                    } else {
                        this.showError(response.data.message || 'Failed to load items');
                    }
                },
                error: () => {
                    this.showError('Network error loading items');
                }
            });
        },
        
        renderAvailableItems: function(items) {
            const container = $('#available-items');
            container.empty();
            
            if (!items || items.length === 0) {
                container.html('<div class="empty-box-message"><p>No items available this week</p></div>');
                return;
            }
            
            items.forEach(item => {
                const itemHtml = this.createItemElement(item, 'available');
                container.append(itemHtml);
            });
        },
        
        renderMyBox: function(selections) {
            const container = $('#my-box');
            container.empty();
            
            if (!selections || selections.length === 0) {
                container.html('<div class="empty-box-message"><p>Drag items here to customize your box</p></div>');
                return;
            }
            
            // Map selections to items
            selections.forEach(selection => {
                const item = currentData.items.find(i => i.id === selection.configuration_item_id);
                if (item) {
                    const itemHtml = this.createItemElement(item, 'mybox', selection.quantity);
                    container.append(itemHtml);
                }
            });
        },
        
        createItemElement: function(item, type, quantity = 1) {
            const featuredClass = item.is_featured ? ' featured' : '';
            const lowStockClass = item.remaining_quantity !== null && item.remaining_quantity < 10 ? ' low-stock' : '';
            
            const quantityControl = type === 'mybox' 
                ? `<div class="box-item-quantity">
                       <button type="button" class="qty-decrease">−</button>
                       <input type="number" class="qty-input" value="${quantity}" min="1" max="10">
                       <button type="button" class="qty-increase">+</button>
                       <button type="button" class="item-remove">✕</button>
                   </div>`
                : '';
            
            const availability = item.remaining_quantity !== null
                ? `<span class="item-availability">${item.remaining_quantity} left</span>`
                : '';
            
            return $(`
                <div class="box-item${featuredClass}${lowStockClass}" data-item-id="${item.id}" data-token-value="${item.token_value}">
                    <div class="box-item-header">
                        <span class="box-item-name">${item.name}</span>
                        <span class="box-item-tokens">${item.token_value} token${item.token_value > 1 ? 's' : ''}</span>
                    </div>
                    ${item.description ? `<div class="box-item-description">${item.description}</div>` : ''}
                    <div class="box-item-meta">
                        <span class="item-unit">${item.unit}</span>
                        ${availability}
                    </div>
                    ${quantityControl}
                </div>
            `);
        },
        
        convertToMyBoxItem: function($item) {
            const itemId = $item.data('item-id');
            const tokenValue = $item.data('token-value');
            const item = currentData.items.find(i => i.id === itemId);
            
            // Replace with my box version
            const newItem = this.createItemElement(item, 'mybox', 1);
            $item.replaceWith(newItem);
            
            // Bind quantity controls
            this.bindQuantityControls(newItem);
        },
        
        bindQuantityControls: function($item) {
            const self = this;
            
            $item.find('.qty-increase').on('click', function() {
                const input = $(this).siblings('.qty-input');
                const newVal = parseInt(input.val()) + 1;
                if (newVal <= 10) {
                    input.val(newVal);
                    self.updateTokenDisplay();
                    self.enableSaveButton();
                }
            });
            
            $item.find('.qty-decrease').on('click', function() {
                const input = $(this).siblings('.qty-input');
                const newVal = parseInt(input.val()) - 1;
                if (newVal >= 1) {
                    input.val(newVal);
                    self.updateTokenDisplay();
                    self.enableSaveButton();
                }
            });
            
            $item.find('.qty-input').on('change', function() {
                let val = parseInt($(this).val());
                if (isNaN(val) || val < 1) val = 1;
                if (val > 10) val = 10;
                $(this).val(val);
                self.updateTokenDisplay();
                self.enableSaveButton();
            });
            
            $item.find('.item-remove').on('click', function() {
                $(this).closest('.box-item').remove();
                self.updateTokenDisplay();
                self.enableSaveButton();
                
                // Show empty message if no items
                if ($('#my-box .box-item').length === 0) {
                    $('#my-box').html('<div class="empty-box-message"><p>Drag items here to customize your box</p></div>');
                }
            });
        },
        
        updateTokenDisplay: function() {
            if (!currentData) return;
            
            let tokensUsed = 0;
            $('#my-box .box-item').each(function() {
                const tokenValue = parseInt($(this).data('token-value'));
                const quantity = parseInt($(this).find('.qty-input').val() || 1);
                tokensUsed += tokenValue * quantity;
            });
            
            const tokensAllocated = currentData.selection.tokens_allocated;
            const tokensRemaining = tokensAllocated - tokensUsed;
            
            $('#tokens-allocated').text(tokensAllocated);
            $('#tokens-used').text(tokensUsed);
            $('#tokens-remaining').text(tokensRemaining);
            
            const $remainingDisplay = $('#tokens-remaining').parent();
            if (tokensRemaining < 0) {
                $remainingDisplay.addClass('over-budget');
                $('#save-box-selection').prop('disabled', true);
            } else {
                $remainingDisplay.removeClass('over-budget');
            }
        },
        
        saveBoxSelection: function() {
            const subscriptionId = $('#subscription-id').val();
            const selectionId = $('#selection-id').val();
            
            if (!subscriptionId || !selectionId) {
                this.showError('Invalid selection');
                return;
            }
            
            // Collect items
            const items = [];
            $('#my-box .box-item').each(function() {
                items.push({
                    configuration_item_id: parseInt($(this).data('item-id')),
                    quantity: parseInt($(this).find('.qty-input').val() || 1)
                });
            });
            
            const $button = $('#save-box-selection');
            $button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: mwfBoxCustomization.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwf_update_box_selection',
                    nonce: mwfBoxCustomization.nonce,
                    subscription_id: subscriptionId,
                    selection_id: selectionId,
                    items: JSON.stringify(items)
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(mwfBoxCustomization.strings.save_success);
                        $button.prop('disabled', false).text('Save My Box Selection');
                    } else {
                        this.showError(response.data.message || mwfBoxCustomization.strings.save_error);
                        $button.prop('disabled', false).text('Save My Box Selection');
                    }
                },
                error: () => {
                    this.showError('Network error saving box');
                    $button.prop('disabled', false).text('Save My Box Selection');
                }
            });
        },
        
        resetToDefault: function() {
            if (!confirm(mwfBoxCustomization.strings.confirm_reset)) {
                return;
            }
            
            const subscriptionId = $('#subscription-id').val();
            const selectionId = $('#selection-id').val();
            
            $.ajax({
                url: mwfBoxCustomization.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwf_reset_box_to_default',
                    nonce: mwfBoxCustomization.nonce,
                    subscription_id: subscriptionId,
                    selection_id: selectionId
                },
                success: (response) => {
                    if (response.success) {
                        this.loadAvailableItems();
                        this.showSuccess('Box reset to default');
                    } else {
                        this.showError(response.data.message || 'Failed to reset box');
                    }
                }
            });
        },
        
        filterItems: function(searchTerm) {
            searchTerm = searchTerm.toLowerCase();
            $('#available-items .box-item').each(function() {
                const name = $(this).find('.box-item-name').text().toLowerCase();
                const desc = $(this).find('.box-item-description').text().toLowerCase();
                
                if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        enableSaveButton: function() {
            $('#save-box-selection').prop('disabled', false);
        },
        
        showSuccess: function(message) {
            $('.save-status').removeClass('error').addClass('success').text(message);
            setTimeout(() => $('.save-status').text(''), 3000);
        },
        
        showError: function(message) {
            $('.save-status').removeClass('success').addClass('error').text(message);
            setTimeout(() => $('.save-status').text(''), 5000);
        }
    };
    
    // Initialize on page load
    $(document).ready(function() {
        if ($('.mwf-box-customization').length) {
            BoxCustomization.init();
        }
    });
    
})(jQuery);

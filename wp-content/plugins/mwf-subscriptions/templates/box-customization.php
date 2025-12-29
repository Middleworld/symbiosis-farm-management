<?php
/**
 * Box Customization Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$subscription_id = get_query_var('subscription_id', 0);
?>

<div class="mwf-box-customization">
    <h2><?php _e('Customize Your Vegbox', 'mwf-subscriptions'); ?></h2>
    
    <div class="mwf-box-intro">
        <p><?php _e('Drag items from "Available This Week" into "My Box" to customize your delivery. Each item has a token value - make sure you stay within your token budget!', 'mwf-subscriptions'); ?></p>
    </div>
    
    <!-- Token Balance Display -->
    <div class="mwf-token-balance">
        <div class="token-display">
            <span class="token-label"><?php _e('Tokens Available:', 'mwf-subscriptions'); ?></span>
            <span class="token-value" id="tokens-allocated">0</span>
        </div>
        <div class="token-display">
            <span class="token-label"><?php _e('Tokens Used:', 'mwf-subscriptions'); ?></span>
            <span class="token-value" id="tokens-used">0</span>
        </div>
        <div class="token-display highlighted">
            <span class="token-label"><?php _e('Tokens Remaining:', 'mwf-subscriptions'); ?></span>
            <span class="token-value" id="tokens-remaining">0</span>
        </div>
    </div>
    
    <!-- Week Selector -->
    <div class="mwf-week-selector">
        <label for="week-select"><?php _e('Select Week:', 'mwf-subscriptions'); ?></label>
        <select id="week-select" name="week">
            <!-- Populated by JavaScript -->
        </select>
    </div>
    
    <div class="mwf-box-customization-grid">
        <!-- Available Items Column -->
        <div class="mwf-available-items-column">
            <h3><?php _e('Available This Week', 'mwf-subscriptions'); ?></h3>
            <div class="items-filter">
                <input type="search" id="items-search" placeholder="<?php _e('Search items...', 'mwf-subscriptions'); ?>">
            </div>
            <div id="available-items" class="mwf-items-list">
                <div class="loading-message">
                    <span class="spinner"></span>
                    <p><?php _e('Loading available items...', 'mwf-subscriptions'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- My Box Column -->
        <div class="mwf-my-box-column">
            <h3><?php _e('My Box', 'mwf-subscriptions'); ?></h3>
            <div class="box-actions">
                <button type="button" id="reset-to-default" class="button button-secondary">
                    <?php _e('Reset to Default', 'mwf-subscriptions'); ?>
                </button>
            </div>
            <div id="my-box" class="mwf-items-list my-box-droppable">
                <div class="empty-box-message">
                    <p><?php _e('Drag items here to customize your box', 'mwf-subscriptions'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Save Button -->
    <div class="mwf-box-actions">
        <button type="button" id="save-box-selection" class="button button-primary" disabled>
            <?php _e('Save My Box Selection', 'mwf-subscriptions'); ?>
        </button>
        <span class="save-status"></span>
    </div>
    
    <!-- Hidden fields -->
    <input type="hidden" id="subscription-id" value="<?php echo esc_attr($subscription_id); ?>">
    <input type="hidden" id="selection-id" value="">
</div>

<style>
.mwf-box-customization {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.mwf-box-intro {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.mwf-token-balance {
    display: flex;
    justify-content: space-around;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.token-display {
    text-align: center;
}

.token-display.highlighted {
    background: #e8f5e9;
    padding: 10px 20px;
    border-radius: 8px;
}

.token-display.over-budget {
    background: #ffebee !important;
}

.token-label {
    display: block;
    font-size: 14px;
    color: #666;
    margin-bottom: 5px;
}

.token-value {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #2e7d32;
}

.token-display.over-budget .token-value {
    color: #c62828;
}

.mwf-week-selector {
    margin-bottom: 20px;
}

.mwf-week-selector select {
    padding: 10px;
    font-size: 16px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.mwf-box-customization-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .mwf-box-customization-grid {
        grid-template-columns: 1fr;
    }
}

.mwf-available-items-column,
.mwf-my-box-column {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mwf-available-items-column h3,
.mwf-my-box-column h3 {
    margin-top: 0;
    border-bottom: 2px solid #4CAF50;
    padding-bottom: 10px;
}

.items-filter {
    margin-bottom: 15px;
}

.items-filter input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.mwf-items-list {
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
}

.box-item {
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: move;
    transition: all 0.2s;
}

.box-item:hover {
    border-color: #4CAF50;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
}

.box-item.dragging {
    opacity: 0.5;
}

.box-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.box-item-name {
    font-weight: bold;
    font-size: 16px;
}

.box-item-tokens {
    background: #4CAF50;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: bold;
}

.box-item-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.box-item-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #999;
}

.box-item-quantity {
    display: flex;
    align-items: center;
    gap: 10px;
}

.box-item-quantity button {
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
}

.box-item-quantity input {
    width: 50px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 4px;
}

.box-item.featured {
    border-color: #FF9800;
    background: #FFF3E0;
}

.box-item.low-stock {
    border-color: #F44336;
}

.empty-box-message {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.loading-message {
    text-align: center;
    padding: 60px 20px;
}

.spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #4CAF50;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.mwf-box-actions {
    text-align: center;
    padding: 20px;
}

.mwf-box-actions button {
    padding: 15px 30px;
    font-size: 16px;
}

.save-status {
    display: inline-block;
    margin-left: 15px;
    padding: 10px;
}

.save-status.success {
    color: #4CAF50;
}

.save-status.error {
    color: #F44336;
}

.box-actions {
    margin-bottom: 15px;
}
</style>

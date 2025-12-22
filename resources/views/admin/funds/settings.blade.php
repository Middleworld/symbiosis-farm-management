@extends('layouts.admin')

@section('title', 'Funds Settings')

@section('styles')
<style>
.settings-section {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}
.settings-section-header {
    background-color: #f8f9fa;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.375rem 0.375rem 0 0;
}
.settings-section-body {
    padding: 1.5rem;
}
.setting-group {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
}
.setting-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.setting-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
}
.setting-description {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}
.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
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
                        <i class="fas fa-cog"></i> WooCommerce Funds Settings
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.funds.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Funds
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="funds-settings-form">
                        @csrf

                        <!-- General Settings -->
                        <div class="settings-section">
                            <div class="settings-section-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cogs"></i> General Settings
                                </h5>
                            </div>
                            <div class="settings-section-body">
                                <div class="setting-group">
                                    <label class="setting-label">Enable Funds System</label>
                                    <div class="setting-description">
                                        Enable or disable the WooCommerce Funds system for customer prepayments.
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="funds_enabled" name="funds_enabled">
                                        <label class="form-check-label" for="funds_enabled">
                                            Enable Funds
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <label class="setting-label">Minimum Deposit Amount</label>
                                    <div class="setting-description">
                                        Set the minimum amount customers can deposit into their funds account.
                                    </div>
                                    <div class="input-group" style="max-width: 200px;">
                                        <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                        <input type="number" class="form-control" id="min_deposit" name="min_deposit" min="0" step="0.01" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <label class="setting-label">Maximum Deposit Amount</label>
                                    <div class="setting-description">
                                        Set the maximum amount customers can deposit into their funds account (0 = no limit).
                                    </div>
                                    <div class="input-group" style="max-width: 200px;">
                                        <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                        <input type="number" class="form-control" id="max_deposit" name="max_deposit" min="0" step="0.01" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="settings-section">
                            <div class="settings-section-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-credit-card"></i> Payment Methods
                                </h5>
                            </div>
                            <div class="settings-section-body">
                                <div class="setting-group">
                                    <label class="setting-label">Allowed Payment Methods</label>
                                    <div class="setting-description">
                                        Select which payment methods customers can use to add funds to their account.
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="payment_stripe" name="payment_methods[]" value="stripe">
                                                <label class="form-check-label" for="payment_stripe">
                                                    Stripe
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="payment_paypal" name="payment_methods[]" value="paypal">
                                                <label class="form-check-label" for="payment_paypal">
                                                    PayPal
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="payment_bank" name="payment_methods[]" value="bank_transfer">
                                                <label class="form-check-label" for="payment_bank">
                                                    Bank Transfer
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="payment_manual" name="payment_methods[]" value="manual">
                                                <label class="form-check-label" for="payment_manual">
                                                    Manual Payment
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="payment_gift" name="payment_methods[]" value="gift_card">
                                                <label class="form-check-label" for="payment_gift">
                                                    Gift Cards
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usage Settings -->
                        <div class="settings-section">
                            <div class="settings-section-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-shopping-cart"></i> Usage Settings
                                </h5>
                            </div>
                            <div class="settings-section-body">
                                <div class="setting-group">
                                    <label class="setting-label">Allow Partial Payments</label>
                                    <div class="setting-description">
                                        Allow customers to use funds for partial payment of orders, with the remainder paid by other methods.
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="allow_partial" name="allow_partial">
                                        <label class="form-check-label" for="allow_partial">
                                            Enable Partial Payments
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <label class="setting-label">Auto-apply Funds</label>
                                    <div class="setting-description">
                                        Automatically apply available funds to customer orders during checkout.
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="auto_apply" name="auto_apply">
                                        <label class="form-check-label" for="auto_apply">
                                            Auto-apply Funds
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <label class="setting-label">Funds Expiration</label>
                                    <div class="setting-description">
                                        Set how long funds remain valid before expiring (0 = never expire).
                                    </div>
                                    <div class="input-group" style="max-width: 200px;">
                                        <input type="number" class="form-control" id="expiration_days" name="expiration_days" min="0" placeholder="0">
                                        <span class="input-group-text">days</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Email Settings -->
                        <div class="settings-section">
                            <div class="settings-section-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-envelope"></i> Email Notifications
                                </h5>
                            </div>
                            <div class="settings-section-body">
                                <div class="setting-group">
                                    <label class="setting-label">Deposit Confirmation</label>
                                    <div class="setting-description">
                                        Send email confirmation when customers add funds to their account.
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_deposit" name="email_deposit">
                                        <label class="form-check-label" for="email_deposit">
                                            Send Deposit Emails
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <label class="setting-label">Low Balance Alert</label>
                                    <div class="setting-description">
                                        Send email alerts when customer funds drop below a threshold.
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_low_balance" name="email_low_balance">
                                        <label class="form-check-label" for="email_low_balance">
                                            Send Low Balance Alerts
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-group">
                                    <label class="setting-label">Low Balance Threshold</label>
                                    <div class="setting-description">
                                        Minimum balance threshold for low balance alerts.
                                    </div>
                                    <div class="input-group" style="max-width: 200px;">
                                        <span class="input-group-text">{{ config('pos_payments.currency_symbol', '£') }}</span>
                                        <input type="number" class="form-control" id="low_balance_threshold" name="low_balance_threshold" min="0" step="0.01" placeholder="10.00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" id="save-settings-btn">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
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
document.addEventListener('DOMContentLoaded', function() {
    loadFundsSettings();

    // Handle form submission
    document.getElementById('funds-settings-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveFundsSettings();
    });
});

async function loadFundsSettings() {
    try {
        const response = await fetch('{{ route("admin.funds.settings") }}');
        const data = await response.json();

        if (data.settings) {
            // Populate form fields
            document.getElementById('funds_enabled').checked = data.settings.funds_enabled || false;
            document.getElementById('min_deposit').value = data.settings.min_deposit || '';
            document.getElementById('max_deposit').value = data.settings.max_deposit || '';
            document.getElementById('allow_partial').checked = data.settings.allow_partial || false;
            document.getElementById('auto_apply').checked = data.settings.auto_apply || false;
            document.getElementById('expiration_days').value = data.settings.expiration_days || '';
            document.getElementById('email_deposit').checked = data.settings.email_deposit || false;
            document.getElementById('email_low_balance').checked = data.settings.email_low_balance || false;
            document.getElementById('low_balance_threshold').value = data.settings.low_balance_threshold || '';

            // Handle payment methods
            if (data.settings.payment_methods) {
                const paymentMethods = Array.isArray(data.settings.payment_methods)
                    ? data.settings.payment_methods
                    : data.settings.payment_methods.split(',');

                document.querySelectorAll('input[name="payment_methods[]"]').forEach(checkbox => {
                    checkbox.checked = paymentMethods.includes(checkbox.value);
                });
            }
        }

    } catch (error) {
        console.error('Failed to load funds settings:', error);
        showAlert('Failed to load settings. Please try again.', 'danger');
    }
}

async function saveFundsSettings() {
    const saveBtn = document.getElementById('save-settings-btn');
    const originalText = saveBtn.innerHTML;

    try {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        // Collect payment methods
        const paymentMethods = Array.from(document.querySelectorAll('input[name="payment_methods[]"]:checked'))
            .map(checkbox => checkbox.value);

        const formData = {
            funds_enabled: document.getElementById('funds_enabled').checked,
            min_deposit: parseFloat(document.getElementById('min_deposit').value) || 0,
            max_deposit: parseFloat(document.getElementById('max_deposit').value) || 0,
            payment_methods: paymentMethods,
            allow_partial: document.getElementById('allow_partial').checked,
            auto_apply: document.getElementById('auto_apply').checked,
            expiration_days: parseInt(document.getElementById('expiration_days').value) || 0,
            email_deposit: document.getElementById('email_deposit').checked,
            email_low_balance: document.getElementById('email_low_balance').checked,
            low_balance_threshold: parseFloat(document.getElementById('low_balance_threshold').value) || 0,
        };

        const response = await fetch('{{ route("admin.funds.settings") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Settings saved successfully!', 'success');
        } else {
            throw new Error(result.message || 'Failed to save settings');
        }

    } catch (error) {
        console.error('Failed to save funds settings:', error);
        showAlert('Failed to save settings. Please try again.', 'danger');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endsection
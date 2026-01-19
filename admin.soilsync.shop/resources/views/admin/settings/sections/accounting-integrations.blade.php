@extends('admin.settings.partials.section-layout')

@section('section-content')
<form id="accounting-settings-form">
    @csrf
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Accounting Integration:</strong> Connect your farm management system with professional accounting software.
            Choose between QuickBooks Online, Xero, or continue using our simple CSV import system.
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <label class="form-label fw-bold">Accounting Provider</label>
        <select name="accounting_provider" class="form-select" id="accounting_provider">
            <option value="csv" {{ $settings['accounting_provider'] ?? 'csv' == 'csv' ? 'selected' : '' }}>
                CSV Import (Simple)
            </option>
            <option value="quickbooks" {{ $settings['accounting_provider'] ?? '' == 'quickbooks' ? 'selected' : '' }}>
                QuickBooks Online
            </option>
            <option value="xero" {{ $settings['accounting_provider'] ?? '' == 'xero' ? 'selected' : '' }}>
                Xero
            </option>
            <option value="sage" {{ $settings['accounting_provider'] ?? '' == 'sage' ? 'selected' : '' }}>
                Sage
            </option>
            <option value="myob" {{ $settings['accounting_provider'] ?? '' == 'myob' ? 'selected' : '' }}>
                MYOB
            </option>
        </select>
        <div class="form-text">
            Select your preferred accounting integration method.
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">Auto-Sync Frequency</label>
        <select name="accounting_sync_frequency" class="form-select">
            <option value="manual" {{ $settings['accounting_sync_frequency'] ?? 'manual' == 'manual' ? 'selected' : '' }}>
                Manual Only
            </option>
            <option value="daily" {{ $settings['accounting_sync_frequency'] ?? '' == 'daily' ? 'selected' : '' }}>
                Daily
            </option>
            <option value="weekly" {{ $settings['accounting_sync_frequency'] ?? '' == 'weekly' ? 'selected' : '' }}>
                Weekly
            </option>
            <option value="monthly" {{ $settings['accounting_sync_frequency'] ?? '' == 'monthly' ? 'selected' : '' }}>
                Monthly
            </option>
        </select>
        <div class="form-text">
            How often to automatically sync transactions (if supported by your provider).
        </div>
    </div>
</div>

<!-- QuickBooks Settings -->
<div id="quickbooks_settings" class="accounting-provider-settings" style="display: none;">
    <div class="card border-primary mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>QuickBooks Online Configuration</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="quickbooks_client_id" class="form-control"
                           value="{{ $settings['quickbooks_client_id'] ?? '' }}"
                           placeholder="Enter your QuickBooks Client ID">
                    <div class="form-text">
                        From your QuickBooks Developer Console
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Client Secret</label>
                    <input type="password" name="quickbooks_client_secret" class="form-control"
                           value="{{ $settings['quickbooks_client_secret'] ?? '' }}"
                           placeholder="Enter your QuickBooks Client Secret">
                    <div class="form-text">
                        Keep this secure - never share it
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Company ID</label>
                    <input type="text" name="quickbooks_company_id" class="form-control"
                           value="{{ $settings['quickbooks_company_id'] ?? '' }}"
                           placeholder="Enter your QuickBooks Company ID">
                    <div class="form-text">
                        Found in your QuickBooks company settings
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Environment</label>
                    <select name="quickbooks_environment" class="form-select">
                        <option value="sandbox" {{ $settings['quickbooks_environment'] ?? 'sandbox' == 'sandbox' ? 'selected' : '' }}>
                            Sandbox (Testing)
                        </option>
                        <option value="production" {{ $settings['quickbooks_environment'] ?? '' == 'production' ? 'selected' : '' }}>
                            Production (Live)
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-outline-primary" id="test_quickbooks_connection">
                    <i class="fas fa-plug"></i> Test QuickBooks Connection
                </button>
                <button type="button" class="btn btn-outline-success" id="sync_quickbooks_data">
                    <i class="fas fa-sync"></i> Sync Data Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Xero Settings -->
<div id="xero_settings" class="accounting-provider-settings" style="display: none;">
    <div class="card border-success mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Xero Configuration</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="xero_client_id" class="form-control"
                           value="{{ $settings['xero_client_id'] ?? '' }}"
                           placeholder="Enter your Xero Client ID">
                    <div class="form-text">
                        From your Xero Developer Portal
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Client Secret</label>
                    <input type="password" name="xero_client_secret" class="form-control"
                           value="{{ $settings['xero_client_secret'] ?? '' }}"
                           placeholder="Enter your Xero Client Secret">
                    <div class="form-text">
                        Keep this secure - never share it
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Tenant ID</label>
                    <input type="text" name="xero_tenant_id" class="form-control"
                           value="{{ $settings['xero_tenant_id'] ?? '' }}"
                           placeholder="Enter your Xero Tenant ID">
                    <div class="form-text">
                        Your Xero organisation identifier
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Environment</label>
                    <select name="xero_environment" class="form-select">
                        <option value="demo" {{ $settings['xero_environment'] ?? 'demo' == 'demo' ? 'selected' : '' }}>
                            Demo (Testing)
                        </option>
                        <option value="production" {{ $settings['xero_environment'] ?? '' == 'production' ? 'selected' : '' }}>
                            Production (Live)
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-outline-success" id="test_xero_connection">
                    <i class="fas fa-plug"></i> Test Xero Connection
                </button>
                <button type="button" class="btn btn-outline-success" id="sync_xero_data">
                    <i class="fas fa-sync"></i> Sync Data Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sage Settings -->
<div id="sage_settings" class="accounting-provider-settings" style="display: none;">
    <div class="card border-warning mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Sage Configuration</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="sage_client_id" class="form-control"
                           value="{{ $settings['sage_client_id'] ?? '' }}"
                           placeholder="Enter your Sage Client ID">
                    <div class="form-text">
                        From your Sage Developer Portal
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Client Secret</label>
                    <input type="password" name="sage_client_secret" class="form-control"
                           value="{{ $settings['sage_client_secret'] ?? '' }}"
                           placeholder="Enter your Sage Client Secret">
                    <div class="form-text">
                        Keep this secure - never share it
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Subscription Key</label>
                    <input type="password" name="sage_subscription_key" class="form-control"
                           value="{{ $settings['sage_subscription_key'] ?? '' }}"
                           placeholder="Enter your Sage Subscription Key">
                    <div class="form-text">
                        Sage API subscription key
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Business ID</label>
                    <input type="text" name="sage_business_id" class="form-control"
                           value="{{ $settings['sage_business_id'] ?? '' }}"
                           placeholder="Enter your Sage Business ID">
                    <div class="form-text">
                        Your Sage business identifier
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Environment</label>
                    <select name="sage_environment" class="form-select">
                        <option value="sandbox" {{ $settings['sage_environment'] ?? 'sandbox' == 'sandbox' ? 'selected' : '' }}>
                            Sandbox (Testing)
                        </option>
                        <option value="production" {{ $settings['sage_environment'] ?? '' == 'production' ? 'selected' : '' }}>
                            Production (Live)
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-outline-warning" id="test_sage_connection">
                    <i class="fas fa-plug"></i> Test Sage Connection
                </button>
                <button type="button" class="btn btn-outline-warning" id="sync_sage_data">
                    <i class="fas fa-sync"></i> Sync Data Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MYOB Settings -->
<div id="myob_settings" class="accounting-provider-settings" style="display: none;">
    <div class="card border-info mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>MYOB Configuration</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="myob_client_id" class="form-control"
                           value="{{ $settings['myob_client_id'] ?? '' }}"
                           placeholder="Enter your MYOB Client ID">
                    <div class="form-text">
                        From your MYOB Developer Portal
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Client Secret</label>
                    <input type="password" name="myob_client_secret" class="form-control"
                           value="{{ $settings['myob_client_secret'] ?? '' }}"
                           placeholder="Enter your MYOB Client Secret">
                    <div class="form-text">
                        Keep this secure - never share it
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Company File ID</label>
                    <input type="text" name="myob_company_file_id" class="form-control"
                           value="{{ $settings['myob_company_file_id'] ?? '' }}"
                           placeholder="Enter your MYOB Company File ID">
                    <div class="form-text">
                        Your MYOB company file identifier
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Environment</label>
                    <select name="myob_environment" class="form-select">
                        <option value="sandbox" {{ $settings['myob_environment'] ?? 'sandbox' == 'sandbox' ? 'selected' : '' }}>
                            Sandbox (Testing)
                        </option>
                        <option value="production" {{ $settings['myob_environment'] ?? '' == 'production' ? 'selected' : '' }}>
                            Production (Live)
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-outline-info" id="test_myob_connection">
                    <i class="fas fa-plug"></i> Test MYOB Connection
                </button>
                <button type="button" class="btn btn-outline-info" id="sync_myob_data">
                    <i class="fas fa-sync"></i> Sync Data Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSV Settings -->
<div id="csv_settings" class="accounting-provider-settings">
    <div class="card border-secondary mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-file-csv me-2"></i>CSV Import Configuration</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Current Setup:</strong> You're using our simple CSV import system.
                This allows you to manually upload bank transaction files for categorization and reporting.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Default CSV Format</label>
                    <select name="csv_format" class="form-select">
                        <option value="standard" {{ $settings['csv_format'] ?? 'standard' == 'standard' ? 'selected' : '' }}>
                            Standard Bank Format
                        </option>
                        <option value="custom" {{ $settings['csv_format'] ?? '' == 'custom' ? 'selected' : '' }}>
                            Custom Format
                        </option>
                    </select>
                    <div class="form-text">
                        Choose the CSV format your bank exports
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Auto-Categorization</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="csv_auto_categorize"
                               value="1" {{ ($settings['csv_auto_categorize'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">
                            Enable automatic transaction categorization
                        </label>
                    </div>
                    <div class="form-text">
                        Automatically suggest categories for imported transactions
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Integration Status -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Integration Status</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3 border rounded">
                            <i class="fas fa-database fa-2x text-primary mb-2"></i>
                            <h6>Transactions</h6>
                            <span class="badge bg-primary">{{ $stats['total_transactions'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">
                            <i class="fas fa-tags fa-2x text-success mb-2"></i>
                            <h6>Categorized</h6>
                            <span class="badge bg-success">{{ $stats['categorized_transactions'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">
                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                            <h6>Last Sync</h6>
                            <span class="badge bg-warning">{{ $stats['last_sync'] ?? 'Never' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="accounting-save-result" style="display: none;"></div>
</form>
@endsection

@section('section-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('accounting_provider');
    const settingsDivs = document.querySelectorAll('.accounting-provider-settings');

    function showProviderSettings() {
        const selectedProvider = providerSelect.value;

        // Hide all settings
        settingsDivs.forEach(div => div.style.display = 'none');

        // Show selected provider settings
        const selectedDiv = document.getElementById(selectedProvider + '_settings');
        if (selectedDiv) {
            selectedDiv.style.display = 'block';
        }
    }

    // Initial load
    showProviderSettings();

    // Change event
    providerSelect.addEventListener('change', showProviderSettings);

    // QuickBooks connection test
    document.getElementById('test_quickbooks_connection')?.addEventListener('click', function() {
        // TODO: Implement QuickBooks connection test
        alert('QuickBooks connection test - Coming soon!');
    });

    // Xero connection test
    document.getElementById('test_xero_connection')?.addEventListener('click', function() {
        // TODO: Implement Xero connection test
        alert('Xero connection test - Coming soon!');
    });

    // Sage connection test
    document.getElementById('test_sage_connection')?.addEventListener('click', function() {
        // TODO: Implement Sage connection test
        alert('Sage connection test - Coming soon!');
    });

    // MYOB connection test
    document.getElementById('test_myob_connection')?.addEventListener('click', function() {
        // TODO: Implement MYOB connection test
        alert('MYOB connection test - Coming soon!');
    });

    // Sync buttons
    document.getElementById('sync_quickbooks_data')?.addEventListener('click', function() {
        // TODO: Implement QuickBooks sync
        alert('QuickBooks data sync - Coming soon!');
    });

    document.getElementById('sync_xero_data')?.addEventListener('click', function() {
        // TODO: Implement Xero sync
        alert('Xero data sync - Coming soon!');
    });

    document.getElementById('sync_sage_data')?.addEventListener('click', function() {
        // TODO: Implement Sage sync
        alert('Sage data sync - Coming soon!');
    });

    document.getElementById('sync_myob_data')?.addEventListener('click', function() {
        // TODO: Implement MYOB sync
        alert('MYOB data sync - Coming soon!');
    });

    // Form submission
    document.getElementById('accounting-settings-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings(this, 'accounting-save-result', '/admin/settings/update-accounting');
    });
});

// Global save function (should be available from parent template)
function saveSettings(form, resultDivId, endpoint) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    const resultDiv = document.getElementById(resultDivId);

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data._token
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (resultDiv) {
            resultDiv.style.display = 'block';
            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-success small"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger small"><i class="fas fa-exclamation-triangle"></i> ' + data.message + '</div>';
            }
        }
    })
    .catch(error => {
        if (resultDiv) {
            resultDiv.innerHTML = '<div class="alert alert-danger small">Failed to save settings</div>';
            resultDiv.style.display = 'block';
        }
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}
</script>
@endsection
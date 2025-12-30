<h4 class="mb-4"><i class="fas fa-credit-card"></i> Payment Integration</h4>

<div class="row">
    <!-- Stripe -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fab fa-stripe text-primary"></i> Stripe Payment Gateway
                </h5>
                
                <p class="text-muted small mb-4">
                    Accept credit cards, Apple Pay, Google Pay, and more. Sign up at <a href="https://stripe.com/" target="_blank">stripe.com</a>
                </p>
                
                <form id="stripe-settings-form">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stripe_key" class="form-label">Publishable Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control font-monospace small" id="stripe_key" name="stripe_key" 
                                       value="{{ env('STRIPE_KEY', '') }}" placeholder="pk_live_...">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('stripe_key')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Public key for frontend (starts with pk_)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="stripe_secret" class="form-label">Secret Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control font-monospace small" id="stripe_secret" name="stripe_secret" 
                                       value="{{ env('STRIPE_SECRET', '') }}" placeholder="sk_live_...">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('stripe_secret')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Private key for backend (starts with sk_)</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stripe_webhook_secret" class="form-label">Webhook Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control font-monospace small" id="stripe_webhook_secret" name="stripe_webhook_secret" 
                                       value="{{ env('STRIPE_WEBHOOK_SECRET', '') }}" placeholder="whsec_...">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('stripe_webhook_secret')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">For webhook verification (starts with whsec_)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="stripe_currency" class="form-label">Default Currency</label>
                            <select class="form-select" id="stripe_currency" name="stripe_currency">
                                <option value="gbp" {{ (env('STRIPE_CURRENCY', 'gbp') === 'gbp') ? 'selected' : '' }}>GBP (£ British Pound)</option>
                                <option value="usd" {{ (env('STRIPE_CURRENCY') === 'usd') ? 'selected' : '' }}>USD ($ US Dollar)</option>
                                <option value="eur" {{ (env('STRIPE_CURRENCY') === 'eur') ? 'selected' : '' }}>EUR (€ Euro)</option>
                                <option value="cad" {{ (env('STRIPE_CURRENCY') === 'cad') ? 'selected' : '' }}>CAD ($ Canadian Dollar)</option>
                                <option value="aud" {{ (env('STRIPE_CURRENCY') === 'aud') ? 'selected' : '' }}>AUD ($ Australian Dollar)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stripe_webhook_url" class="form-label">Webhook Endpoint URL</label>
                        <input type="url" class="form-control font-monospace small" id="stripe_webhook_url" 
                               readonly value="{{ url('/webhooks/stripe') }}">
                        <small class="text-muted">Copy this URL to Stripe Dashboard → Developers → Webhooks</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <strong>Stripe Setup:</strong><br>
                            1. Log into <a href="https://dashboard.stripe.com/" target="_blank">Stripe Dashboard</a><br>
                            2. Go to: <strong>Developers → API keys</strong><br>
                            3. Copy Publishable and Secret keys above<br>
                            4. Go to: <strong>Developers → Webhooks</strong><br>
                            5. Add endpoint with URL above<br>
                            6. Select events: <code>payment_intent.succeeded</code>, <code>payment_intent.payment_failed</code>, <code>charge.refunded</code>
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Stripe Settings
                    </button>
                    
                    <div id="stripe-save-result" class="mt-3" style="display:none;"></div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- MWF Shop Integration -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-shopping-cart text-success"></i> MWF Shop Integration
                </h5>
                
                <p class="text-muted small mb-4">
                    Custom payment API for Middleworld Farms shop integration
                </p>
                
                <form id="mwf-settings-form">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mwf_api_key" class="form-label">MWF API Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control font-monospace small" id="mwf_api_key" name="mwf_api_key" 
                                       value="{{ env('MWF_API_KEY', '') }}" placeholder="your_mwf_api_key">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mwf_api_key')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="mwf_api_base_url" class="form-label">MWF API Base URL</label>
                            <input type="url" class="form-control" id="mwf_api_base_url" name="mwf_api_base_url" 
                                   value="{{ env('MWF_API_BASE_URL', '') }}" placeholder="https://api.middleworldfarms.org">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="mwf_logging_enabled" 
                                   name="mwf_logging_enabled" {{ ($settings['mwf_logging_enabled'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="mwf_logging_enabled">
                                <strong>Enable transaction and error logging</strong>
                            </label>
                        </div>
                        <small class="text-muted">Log all MWF API transactions for debugging</small>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save MWF Settings
                    </button>
                    
                    <div id="mwf-save-result" class="mt-3" style="display:none;"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = event.target.closest('button').querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Stripe settings form
document.getElementById('stripe-settings-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings(this, 'stripe-save-result', '/admin/settings/update-payments');
});

// MWF settings form  
document.getElementById('mwf-settings-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings(this, 'mwf-save-result', '/admin/settings/update-payments');
});

function saveSettings(form, resultDivId, url) {
    const resultDiv = document.getElementById(resultDivId);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success small"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger small"><i class="fas fa-exclamation-triangle"></i> ' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger small">Failed to save settings</div>';
        resultDiv.style.display = 'block';
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}
</script>

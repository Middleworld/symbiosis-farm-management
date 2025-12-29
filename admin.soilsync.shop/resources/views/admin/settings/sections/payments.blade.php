<h4 class="mb-3"><i class="fas fa-credit-card"></i> Payment Integration</h4>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="alert alert-info">
            <strong><i class="fas fa-key"></i> MWF Shop Integration:</strong>
            @if(config('services.mwf.api_key'))
                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Connected</span>
            @else
                <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Not configured</span>
            @endif
            <br><small class="text-muted">API keys configured in .env by administrator</small>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="mwf_logging_enabled" 
                   name="mwf_logging_enabled" value="1" 
                   {{ isset($settings['mwf_logging_enabled']) && $settings['mwf_logging_enabled'] ? 'checked' : '' }}>
            <label class="form-check-label" for="mwf_logging_enabled">
                Enable transaction and error logging
            </label>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="alert alert-info">
            <strong><i class="fab fa-stripe"></i> Stripe Payment:</strong>
            @if(config('services.stripe.key'))
                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Connected</span>
            @else
                <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Not configured</span>
            @endif
            <br><small class="text-muted">API keys configured in .env by administrator</small>
        </div>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Save Payment Settings
    </button>
</div>

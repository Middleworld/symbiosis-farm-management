<div class="row g-4">
    <!-- Email Settings -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-envelope text-primary"></i> Email Configuration
                </h5>
                
                <form id="email-settings-form">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="mail_from_address" class="form-label">From Email Address</label>
                        <input type="email" class="form-control" id="mail_from_address" name="mail_from_address" 
                               value="{{ env('MAIL_FROM_ADDRESS', '') }}" placeholder="farm@example.com">
                    </div>
                    
                    <div class="mb-3">
                        <label for="mail_from_name" class="form-label">From Name</label>
                        <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" 
                               value="{{ env('MAIL_FROM_NAME', config('app.name')) }}" placeholder="Your Farm Name">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="email_notifications" 
                                   name="email_notifications" {{ ($settings['email_notifications'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_notifications">
                                <strong>Enable Email Notifications</strong>
                            </label>
                        </div>
                        <small class="text-muted">Send automated emails to customers</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Email Settings
                    </button>
                    
                    <div id="email-save-result" class="mt-3" style="display:none;"></div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- SMS / Phone Providers -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-sms text-success"></i> SMS / Virtual Phone Configuration
                </h5>
                
                <form id="sms-settings-form">
                    @csrf
                    
                    <!-- Provider Selection -->
                    <div class="mb-3">
                        <label for="sms_provider" class="form-label">Phone/SMS Provider</label>
                        <select class="form-select" id="sms_provider" name="sms_provider" onchange="showProviderFields(this.value)">
                            <option value="">-- Select Provider --</option>
                            <optgroup label="SMS & Voice (Full Featured)">
                                <option value="twilio" {{ (env('SMS_PROVIDER') === 'twilio') ? 'selected' : '' }}>Twilio (Popular, Voice + SMS)</option>
                                <option value="vonage" {{ (env('SMS_PROVIDER') === 'vonage') ? 'selected' : '' }}>Vonage / Nexmo (UK, Voice + SMS)</option>
                                <option value="plivo" {{ (env('SMS_PROVIDER') === 'plivo') ? 'selected' : '' }}>Plivo (Cost-effective, Voice + SMS)</option>
                            </optgroup>
                            <optgroup label="VoIP / CRM Phone Systems">
                                <option value="3cx" {{ (env('SMS_PROVIDER') === '3cx') ? 'selected' : '' }}>3CX (Business VoIP PBX)</option>
                                <option value="ringcentral" {{ (env('SMS_PROVIDER') === 'ringcentral') ? 'selected' : '' }}>RingCentral (Enterprise VoIP)</option>
                                <option value="8x8" {{ (env('SMS_PROVIDER') === '8x8') ? 'selected' : '' }}>8x8 (Cloud Contact Center)</option>
                                <option value="aircall" {{ (env('SMS_PROVIDER') === 'aircall') ? 'selected' : '' }}>Aircall (Sales-focused Phone)</option>
                            </optgroup>
                            <optgroup label="SMS Only">
                                <option value="messagebird" {{ (env('SMS_PROVIDER') === 'messagebird') ? 'selected' : '' }}>MessageBird (EU)</option>
                                <option value="clicksend" {{ (env('SMS_PROVIDER') === 'clicksend') ? 'selected' : '' }}>ClickSend (International)</option>
                            </optgroup>
                        </select>
                        <small class="text-muted">Choose your phone/SMS service provider for CRM integration</small>
                    </div>
                    
                    <!-- Twilio Fields -->
                    <div id="twilio-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            Sign up at <a href="https://www.twilio.com/" target="_blank">twilio.com</a>
                        </p>
                        
                        <div class="mb-3">
                            <label for="twilio_account_sid" class="form-label">Account SID</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="twilio_account_sid" name="twilio_account_sid" 
                                       value="{{ env('TWILIO_ACCOUNT_SID', '') }}" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('twilio_account_sid')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="twilio_auth_token" class="form-label">Auth Token</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="twilio_auth_token" name="twilio_auth_token" 
                                       value="{{ env('TWILIO_AUTH_TOKEN', '') }}" placeholder="your_auth_token">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('twilio_auth_token')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="twilio_from_number" class="form-label">From Phone Number</label>
                            <input type="tel" class="form-control" id="twilio_from_number" name="twilio_from_number" 
                                   value="{{ env('TWILIO_FROM_NUMBER', '') }}" placeholder="+447911123456">
                        </div>
                    </div>
                    
                    <!-- Vonage/Nexmo Fields -->
                    <div id="vonage-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            Sign up at <a href="https://www.vonage.com/" target="_blank">vonage.com</a> (formerly Nexmo)
                        </p>
                        
                        <div class="mb-3">
                            <label for="vonage_api_key" class="form-label">API Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="vonage_api_key" name="vonage_api_key" 
                                       value="{{ env('VONAGE_API_KEY', '') }}" placeholder="your_api_key">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('vonage_api_key')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="vonage_api_secret" class="form-label">API Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="vonage_api_secret" name="vonage_api_secret" 
                                       value="{{ env('VONAGE_API_SECRET', '') }}" placeholder="your_api_secret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('vonage_api_secret')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="vonage_from_number" class="form-label">From Phone Number</label>
                            <input type="tel" class="form-control" id="vonage_from_number" name="vonage_from_number" 
                                   value="{{ env('VONAGE_FROM_NUMBER', '') }}" placeholder="+447911123456">
                        </div>
                    </div>
                    
                    <!-- MessageBird Fields -->
                    <div id="messagebird-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            Sign up at <a href="https://www.messagebird.com/" target="_blank">messagebird.com</a>
                        </p>
                        
                        <div class="mb-3">
                            <label for="messagebird_access_key" class="form-label">Access Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="messagebird_access_key" name="messagebird_access_key" 
                                       value="{{ env('MESSAGEBIRD_ACCESS_KEY', '') }}" placeholder="your_access_key">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('messagebird_access_key')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="messagebird_from_number" class="form-label">From Phone Number or Sender ID</label>
                            <input type="text" class="form-control" id="messagebird_from_number" name="messagebird_from_number" 
                                   value="{{ env('MESSAGEBIRD_FROM_NUMBER', '') }}" placeholder="+447911123456 or YourFarm">
                        </div>
                    </div>
                    
                    <!-- ClickSend Fields -->
                    <div id="clicksend-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            Sign up at <a href="https://www.clicksend.com/" target="_blank">clicksend.com</a>
                        </p>
                        
                        <div class="mb-3">
                            <label for="clicksend_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="clicksend_username" name="clicksend_username" 
                                   value="{{ env('CLICKSEND_USERNAME', '') }}" placeholder="your_username">
                        </div>
                        
                        <div class="mb-3">
                            <label for="clicksend_api_key" class="form-label">API Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="clicksend_api_key" name="clicksend_api_key" 
                                       value="{{ env('CLICKSEND_API_KEY', '') }}" placeholder="your_api_key">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('clicksend_api_key')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="clicksend_from_number" class="form-label">From Phone Number</label>
                            <input type="tel" class="form-control" id="clicksend_from_number" name="clicksend_from_number" 
                                   value="{{ env('CLICKSEND_FROM_NUMBER', '') }}" placeholder="+447911123456">
                        </div>
                    </div>
                    
                    <!-- Plivo Fields -->
                    <div id="plivo-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            Sign up at <a href="https://www.plivo.com/" target="_blank">plivo.com</a>
                        </p>
                        
                        <div class="mb-3">
                            <label for="plivo_auth_id" class="form-label">Auth ID</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="plivo_auth_id" name="plivo_auth_id" 
                                       value="{{ env('PLIVO_AUTH_ID', '') }}" placeholder="your_auth_id">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('plivo_auth_id')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="plivo_auth_token" class="form-label">Auth Token</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="plivo_auth_token" name="plivo_auth_token" 
                                       value="{{ env('PLIVO_AUTH_TOKEN', '') }}" placeholder="your_auth_token">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('plivo_auth_token')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="plivo_from_number" class="form-label">From Phone Number</label>
                            <input type="tel" class="form-control" id="plivo_from_number" name="plivo_from_number" 
                                   value="{{ env('PLIVO_FROM_NUMBER', '') }}" placeholder="+447911123456">
                        </div>
                    </div>
                    
                    <!-- 3CX Fields -->
                    <div id="3cx-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            <strong>3CX Business VoIP PBX</strong> - Configure your 3CX server details<br>
                            Learn more: <a href="https://www.3cx.com/" target="_blank">3cx.com</a>
                        </p>
                        
                        <div class="mb-3">
                            <label for="threecx_server_url" class="form-label">3CX Server URL</label>
                            <input type="url" class="form-control" id="threecx_server_url" name="threecx_server_url" 
                                   value="{{ env('THREECX_API_URL', '') }}" placeholder="https://yourserver.3cx.uk:5001">
                        </div>
                        
                        <div class="mb-3">
                            <label for="threecx_api_token" class="form-label">API Token</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="threecx_api_token" name="threecx_api_token" 
                                       value="{{ env('THREECX_API_TOKEN', '') }}" placeholder="your_api_token">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('threecx_api_token')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="threecx_extension" class="form-label">Extension Number</label>
                            <input type="text" class="form-control" id="threecx_extension" name="threecx_extension" 
                                   value="{{ env('THREECX_EXTENSION', '') }}" placeholder="1036">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3"><i class="fas fa-users"></i> CRM Integration (Customer Popup on Calls)</h6>
                        
                        <div class="mb-3">
                            <label for="threecx_crm_url" class="form-label">CRM Popup URL</label>
                            <input type="url" class="form-control font-monospace small" id="threecx_crm_url" name="threecx_crm_url" 
                                   value="{{ $settings['threecx_crm_url'] ?? url('/admin/crm/contact?phone=%CallerNumber%&name=%CallerDisplayName%') }}" 
                                   placeholder="{{ url('/admin/crm/contact?phone=%CallerNumber%&name=%CallerDisplayName%') }}">
                            <small class="text-muted">
                                Variables: <code>%CallerNumber%</code>, <code>%CallerDisplayName%</code>
                            </small>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <strong>3CX Configuration:</strong><br>
                                1. Log into 3CX Management Console<br>
                                2. Go to: <strong>Settings → Integration → CRM Integration</strong><br>
                                3. Paste the URL above into <strong>"Open Contact URL"</strong> field<br>
                                4. Set <strong>"Notify when"</strong> to <strong>"Ringing"</strong><br>
                                5. Click Save
                            </small>
                        </div>
                    </div>
                    
                    <!-- RingCentral Fields -->
                    <div id="ringcentral-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            <strong>RingCentral Enterprise VoIP</strong><br>
                            Sign up at <a href="https://www.ringcentral.com/" target="_blank">ringcentral.com</a>
                        </p>
                        
                        <div class="mb-3">
                            <label for="ringcentral_client_id" class="form-label">Client ID</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="ringcentral_client_id" name="ringcentral_client_id" 
                                       value="{{ env('RINGCENTRAL_CLIENT_ID', '') }}" placeholder="your_client_id">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('ringcentral_client_id')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ringcentral_client_secret" class="form-label">Client Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="ringcentral_client_secret" name="ringcentral_client_secret" 
                                       value="{{ env('RINGCENTRAL_CLIENT_SECRET', '') }}" placeholder="your_client_secret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('ringcentral_client_secret')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ringcentral_phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="ringcentral_phone_number" name="ringcentral_phone_number" 
                                   value="{{ env('RINGCENTRAL_PHONE_NUMBER', '') }}" placeholder="+447911123456">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3"><i class="fas fa-users"></i> CRM Integration (Customer Popup on Calls)</h6>
                        
                        <div class="mb-3">
                            <label for="ringcentral_webhook_url" class="form-label">CRM Webhook URL</label>
                            <input type="url" class="form-control font-monospace small" id="ringcentral_webhook_url" name="ringcentral_webhook_url" 
                                   readonly value="{{ url('/admin/crm/contact') }}">
                            <small class="text-muted">Copy this URL for RingCentral webhook configuration</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <strong>RingCentral Configuration:</strong><br>
                                1. Log into RingCentral Admin Portal<br>
                                2. Go to: <strong>Settings → Integrations → Webhooks</strong><br>
                                3. Create webhook for <strong>"Telephony Session Events"</strong><br>
                                4. Paste the URL above<br>
                                5. Webhook will send <code>phone</code> parameter automatically
                            </small>
                        </div>
                    </div>
                    
                    <!-- 8x8 Fields -->
                    <div id="8x8-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            <strong>8x8 Cloud Contact Center</strong><br>
                            Sign up at <a href="https://www.8x8.com/" target="_blank">8x8.com</a>
                        </p>
                        
                        <div class="mb-3">
                            <label for="eightx8_api_key" class="form-label">API Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="eightx8_api_key" name="eightx8_api_key" 
                                       value="{{ env('EIGHTX8_API_KEY', '') }}" placeholder="your_api_key">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('eightx8_api_key')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="eightx8_api_secret" class="form-label">API Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="eightx8_api_secret" name="eightx8_api_secret" 
                                       value="{{ env('EIGHTX8_API_SECRET', '') }}" placeholder="your_api_secret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('eightx8_api_secret')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="eightx8_phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="eightx8_phone_number" name="eightx8_phone_number" 
                                   value="{{ env('EIGHTX8_PHONE_NUMBER', '') }}" placeholder="+447911123456">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3"><i class="fas fa-users"></i> CRM Integration (Customer Popup on Calls)</h6>
                        
                        <div class="mb-3">
                            <label for="eightx8_webhook_url" class="form-label">CRM Webhook URL</label>
                            <input type="url" class="form-control font-monospace small" id="eightx8_webhook_url" name="eightx8_webhook_url" 
                                   readonly value="{{ url('/admin/crm/contact') }}">
                            <small class="text-muted">Copy this URL for 8x8 screen pop configuration</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <strong>8x8 Configuration:</strong><br>
                                1. Log into 8x8 Configuration Manager<br>
                                2. Go to: <strong>Contact Center → Screen Pops</strong><br>
                                3. Add new screen pop with URL above<br>
                                4. Use <code>@{{dnis@}}</code> for caller number in URL query string<br>
                                5. Example: <code>{{ url('/admin/crm/contact?phone=') }}@{{dnis@}}</code>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Aircall Fields -->
                    <div id="aircall-fields" class="provider-fields" style="display:none;">
                        <p class="text-muted small mb-3">
                            <strong>Aircall - Sales & Support Phone System</strong><br>
                            Sign up at <a href="https://aircall.io/" target="_blank">aircall.io</a>
                        </p>
                        
                        <div class="mb-3">
                            <label for="aircall_api_id" class="form-label">API ID</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="aircall_api_id" name="aircall_api_id" 
                                       value="{{ env('AIRCALL_API_ID', '') }}" placeholder="your_api_id">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('aircall_api_id')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="aircall_api_token" class="form-label">API Token</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="aircall_api_token" name="aircall_api_token" 
                                       value="{{ env('AIRCALL_API_TOKEN', '') }}" placeholder="your_api_token">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('aircall_api_token')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="aircall_phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="aircall_phone_number" name="aircall_phone_number" 
                                   value="{{ env('AIRCALL_PHONE_NUMBER', '') }}" placeholder="+447911123456">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3"><i class="fas fa-users"></i> CRM Integration (Customer Popup on Calls)</h6>
                        
                        <div class="mb-3">
                            <label for="aircall_webhook_url" class="form-label">CRM Webhook URL</label>
                            <input type="url" class="form-control font-monospace small" id="aircall_webhook_url" name="aircall_webhook_url" 
                                   readonly value="{{ url('/admin/crm/contact') }}">
                            <small class="text-muted">Copy this URL for Aircall CRM integration</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <strong>Aircall Configuration:</strong><br>
                                1. Log into Aircall Dashboard<br>
                                2. Go to: <strong>Integrations → Custom CRM</strong><br>
                                3. Enable "Custom CRM Integration"<br>
                                4. Add webhook URL above<br>
                                5. Aircall sends <code>from</code> parameter with caller number automatically
                            </small>
                        </div>
                    </div>
                    
                    <small class="text-muted d-block mb-3">Include country code (e.g., +44 for UK)</small>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="sms_notifications" 
                                   name="sms_notifications" {{ ($settings['sms_notifications'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="sms_notifications">
                                <strong>Enable SMS Notifications</strong>
                            </label>
                        </div>
                        <small class="text-muted">Send automated SMS to customers</small>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save SMS Settings
                    </button>
                    
                    <div id="sms-save-result" class="mt-3" style="display:none;"></div>
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

// Email settings form
document.getElementById('email-settings-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings(this, 'email-save-result', '/admin/settings/update-communications');
});

// SMS settings form  
document.getElementById('sms-settings-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings(this, 'sms-save-result', '/admin/settings/update-communications');
});

// Show/hide provider-specific fields based on selection
document.getElementById('sms_provider')?.addEventListener('change', function() {
    // Hide all provider fields
    document.querySelectorAll('.provider-fields').forEach(el => el.style.display = 'none');
    
    const provider = this.value;
    if (provider) {
        // Show selected provider's fields
        const providerFields = document.getElementById(provider + '-fields');
        if (providerFields) {
            providerFields.style.display = 'block';
        }
    }
});

// Trigger on page load to show current provider's fields
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('sms_provider');
    if (providerSelect && providerSelect.value) {
        providerSelect.dispatchEvent(new Event('change'));
    }
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

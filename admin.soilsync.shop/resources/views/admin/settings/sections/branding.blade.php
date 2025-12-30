<div class="row g-3">
    <div class="col-12">
        <form id="branding-form" enctype="multipart/form-data">
            @csrf
        
        <style>
        /* Live Preview Styles */
        .preview-header {
            background: var(--brand-primary);
            border-radius: 6px;
            min-height: 60px;
            border: 2px solid #dee2e6;
            color: var(--brand-primary-text) !important;
        }
        
        .preview-sidebar {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            border-radius: 6px;
            min-height: 200px;
            border: 2px solid #dee2e6;
            overflow: hidden;
            color: var(--brand-primary-text) !important;
        }
        
        .preview-sidebar-header {
            background: rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .preview-sidebar-menu {
            padding: 10px 0;
        }
        
        .preview-menu-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            transition: background-color 0.2s;
            color: var(--brand-primary-text) !important;
        }
        
        .preview-menu-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .preview-btn-primary {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
            color: var(--brand-primary-text) !important;
        }
        
        .preview-btn-primary:hover {
            background-color: var(--brand-secondary) !important;
            border-color: var(--brand-secondary) !important;
            color: var(--brand-secondary-text) !important;
        }
        
        .preview-btn-secondary {
            background-color: var(--brand-secondary) !important;
            border-color: var(--brand-secondary) !important;
            color: var(--brand-secondary-text) !important;
        }
        
        .preview-btn-secondary:hover {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
            color: var(--brand-primary-text) !important;
        }
        </style>
        
        <div class="row g-3">
        <!-- Company Information -->
        <div class="col-md-6 mb-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-building"></i> Company Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_company_name" class="form-label"><strong>Company Name</strong></label>
                        <input type="text" class="form-control" id="brand_company_name" name="brand_company_name" 
                               value="{{ $branding->company_name ?? 'Middleworld Farms' }}" placeholder="Middleworld Farms" required>
                    </div>
                    <div class="mb-3">
                        <label for="brand_tagline" class="form-label"><strong>Tagline / Slogan</strong></label>
                        <input type="text" class="form-control" id="brand_tagline" name="brand_tagline" 
                               value="{{ $branding->tagline ?? '' }}" placeholder="Sustainable farming with modern technology">
                    </div>
                    <div class="mb-3">
                        <label for="brand_logo_alt_text" class="form-label"><strong>Logo Alt Text</strong></label>
                        <input type="text" class="form-control" id="brand_logo_alt_text" name="brand_logo_alt_text" 
                               value="{{ $branding->logo_alt_text ?? 'Middleworld Farms Logo' }}">
                        <div class="form-text"><i class="fas fa-universal-access"></i> Used for accessibility and SEO</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Color Scheme -->
        <div class="col-md-6 mb-4">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-palette"></i> Color Scheme</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_primary_color" class="form-label"><strong>Primary Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_primary_color" name="brand_primary_color" 
                                   value="{{ $branding->primary_color ?? '#2d5016' }}">
                            <input type="text" class="form-control" value="{{ $branding->primary_color ?? '#2d5016' }}" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="brand_secondary_color" class="form-label"><strong>Secondary Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_secondary_color" name="brand_secondary_color" 
                                   value="{{ $branding->secondary_color ?? '#5a7c3e' }}">
                            <input type="text" class="form-control" value="{{ $branding->secondary_color ?? '#5a7c3e' }}" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="brand_accent_color" class="form-label"><strong>Accent Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_accent_color" name="brand_accent_color" 
                                   value="{{ $branding->accent_color ?? '#f5c518' }}">
                            <input type="text" class="form-control" value="{{ $branding->accent_color ?? '#f5c518' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Live Preview -->
        <div class="col-md-12 mb-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-eye"></i> Live Preview</h5>
                    <small class="text-white-50">See how your colors will look in real-time</small>
                </div>
                <div class="card-body">
                    <!-- Preview Header -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Header Preview</strong></label>
                        <div class="preview-header">
                            <div class="d-flex justify-content-between align-items-center p-3">
                                <div class="preview-logo">
                                    @if($branding && $branding->logo_path)
                                        <img src="{{ secure_url($branding->logo_path) }}" 
                                             alt="{{ $branding->logo_alt_text ?? $branding->company_name }}" 
                                             style="height: 32px; width: auto; max-width: 120px; object-fit: contain;">
                                    @else
                                        <i class="fas fa-image" style="font-size: 24px;"></i>
                                    @endif
                                    <span class="ms-2 fw-bold">{{ $branding ? $branding->company_name : 'Your Company' }}</span>
                                </div>
                                <div class="preview-user-menu">
                                    <span>Admin User</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Sidebar -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Sidebar Preview</strong></label>
                        <div class="preview-sidebar">
                            <div class="preview-sidebar-header p-3">
                                <div class="preview-sidebar-logo mb-2">
                                    @if($branding && $branding->logo_path)
                                        <img src="{{ secure_url($branding->logo_path) }}" 
                                             alt="{{ $branding->logo_alt_text ?? $branding->company_name }}" 
                                             style="height: 24px; width: auto; max-width: 100px; object-fit: contain;">
                                    @else
                                        <i class="fas fa-image" style="font-size: 20px;"></i>
                                    @endif
                                    <div class="fw-bold small mt-1">{{ $branding ? $branding->company_name : 'Your Company' }}</div>
                                </div>
                            </div>
                            <div class="preview-sidebar-menu">
                                <div class="preview-menu-item p-2">
                                    <i class="fas fa-tachometer-alt me-2"></i>
                                    <span>Dashboard</span>
                                </div>
                                <div class="preview-menu-item p-2">
                                    <i class="fas fa-cog me-2"></i>
                                    <span>Settings</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Buttons -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Button Preview</strong></label>
                        <div class="d-flex gap-2">
                            <button class="btn preview-btn-primary">Primary Button</button>
                            <button class="btn preview-btn-secondary">Secondary Button</button>
                        </div>
                    </div>

                    <!-- Reset Preview Button -->
                    <div class="text-end">
                        <button type="button" class="btn btn-outline-secondary" onclick="resetPreview()">
                            <i class="fas fa-undo"></i> Reset Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="col-md-6 mb-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-address-card"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_contact_email" class="form-label"><strong>Contact Email</strong></label>
                        <input type="email" class="form-control" id="brand_contact_email" name="brand_contact_email" 
                               value="{{ $branding->contact_email ?? '' }}" placeholder="info@middleworldfarms.org">
                    </div>
                    <div class="mb-3">
                        <label for="brand_contact_phone" class="form-label"><strong>Contact Phone</strong></label>
                        <input type="tel" class="form-control" id="brand_contact_phone" name="brand_contact_phone" 
                               value="{{ $branding->contact_phone ?? '' }}" placeholder="+44 1234 567890">
                    </div>
                    <div class="mb-3">
                        <label for="brand_address" class="form-label"><strong>Physical Address</strong></label>
                        <textarea class="form-control" id="brand_address" name="brand_address" rows="3">{{ $branding->address ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Links -->
        <div class="col-md-6 mb-4">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fab fa-facebook"></i> Social Media Links</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_social_facebook" class="form-label"><i class="fab fa-facebook"></i> Facebook</label>
                        <input type="url" class="form-control" id="brand_social_facebook" name="brand_social_facebook" 
                               value="{{ $branding->social_links['facebook'] ?? '' }}" placeholder="https://facebook.com/yourpage">
                    </div>
                    <div class="mb-3">
                        <label for="brand_social_instagram" class="form-label"><i class="fab fa-instagram"></i> Instagram</label>
                        <input type="url" class="form-control" id="brand_social_instagram" name="brand_social_instagram" 
                               value="{{ $branding->social_links['instagram'] ?? '' }}" placeholder="https://instagram.com/yourpage">
                    </div>
                    <div class="mb-3">
                        <label for="brand_social_twitter" class="form-label"><i class="fab fa-twitter"></i> Twitter / X</label>
                        <input type="url" class="form-control" id="brand_social_twitter" name="brand_social_twitter" 
                               value="{{ $branding->social_links['twitter'] ?? '' }}" placeholder="https://twitter.com/yourpage">
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo Uploads -->
        <div class="col-md-12 mb-4">
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-image"></i> Logo Uploads</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="brand_logo_main" class="form-label"><strong>Main Logo</strong></label>
                            @if($branding && $branding->logo_path)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $branding->logo_path) }}" 
                                         alt="{{ $branding->logo_alt_text }}" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            @endif
                            <input type="file" class="form-control" id="brand_logo_main" name="brand_logo_main" 
                                   accept="image/png,image/jpeg,image/svg+xml">
                            <div class="form-text">Recommended: 400x100px, transparent PNG</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="brand_logo_small" class="form-label"><strong>Small Icon / Favicon</strong></label>
                            @if($branding && $branding->logo_small_path)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $branding->logo_small_path) }}" 
                                         alt="Small Icon" class="img-thumbnail" style="max-height: 64px;">
                                </div>
                            @endif
                            <input type="file" class="form-control" id="brand_logo_small" name="brand_logo_small" 
                                   accept="image/png,image/jpeg,image/svg+xml,image/x-icon">
                            <div class="form-text">Recommended: 64x64px square</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="brand_logo_white" class="form-label"><strong>White Logo (Dark BG)</strong></label>
                            @if($branding && $branding->logo_white_path)
                                <div class="mb-2 bg-dark p-2">
                                    <img src="{{ asset('storage/' . $branding->logo_white_path) }}" 
                                         alt="White Logo" class="img-thumbnail" style="max-height: 100px; background: transparent;">
                                </div>
                            @endif
                            <input type="file" class="form-control" id="brand_logo_white" name="brand_logo_white" 
                                   accept="image/png,image/svg+xml">
                            <div class="form-text">Recommended: 400x100px, white on transparent</div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Branding Settings
                    </button>
                    
                    <div id="branding-save-result" class="mt-3" style="display:none;"></div>
                </div>
            </div>
        </div>
        </div> <!-- End row -->
        </form>
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

// Sync color picker with text input and update live preview
document.addEventListener('DOMContentLoaded', function() {
    // Sync color pickers with text inputs
    const colorPickers = document.querySelectorAll('input[type="color"]');
    colorPickers.forEach(picker => {
        const textInput = picker.nextElementSibling;
        if (textInput && textInput.tagName === 'INPUT') {
            picker.addEventListener('input', function() {
                textInput.value = this.value;
                updateLivePreview();
            });
            textInput.addEventListener('input', function() {
                if (isValidHexColor(this.value)) {
                    picker.value = this.value;
                    updateLivePreview();
                }
            });
            textInput.addEventListener('blur', function() {
                if (!isValidHexColor(this.value)) {
                    this.value = picker.value;
                }
            });
        }
    });
});

// Validate hex color
function isValidHexColor(color) {
    return /^#[0-9A-F]{6}$/i.test(color);
}

// Calculate contrasting color (black or white) based on background luminance
function getContrastingColor(hexColor) {
    // Remove # if present
    hexColor = hexColor.replace('#', '');
    
    // Convert to RGB
    const r = parseInt(hexColor.substr(0, 2), 16);
    const g = parseInt(hexColor.substr(2, 2), 16);
    const b = parseInt(hexColor.substr(4, 2), 16);
    
    // Calculate luminance using the formula: (0.299*R + 0.587*G + 0.114*B)
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    
    // Return white for dark backgrounds, black for light backgrounds
    return luminance > 0.5 ? '#000000' : '#ffffff';
}

// Update CSS custom properties for live preview
function updateLivePreview() {
    const root = document.documentElement;

    // Get current values from inputs
    const primaryColor = document.getElementById('brand_primary_color')?.value || '{{ $branding ? $branding->primary_color : '#1a4d3a' }}';
    const secondaryColor = document.getElementById('brand_secondary_color')?.value || '{{ $branding ? $branding->secondary_color : '#2d6a4f' }}';
    const accentColor = document.getElementById('brand_accent_color')?.value || '{{ $branding ? $branding->accent_color : '#52b788' }}';

    // Update CSS custom properties for backgrounds
    root.style.setProperty('--brand-primary', primaryColor);
    root.style.setProperty('--brand-secondary', secondaryColor);
    root.style.setProperty('--brand-accent', accentColor);

    // Update CSS custom properties for contrasting text colors
    root.style.setProperty('--brand-primary-text', getContrastingColor(primaryColor));
    root.style.setProperty('--brand-secondary-text', getContrastingColor(secondaryColor));
    root.style.setProperty('--brand-accent-text', getContrastingColor(accentColor));
}

// Branding settings form
document.getElementById('branding-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    saveBrandingSettings(this, 'branding-save-result', '/admin/settings/update-branding');
});

function saveBrandingSettings(form, resultDivId, url) {
    const resultDiv = document.getElementById(resultDivId);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    const formData = new FormData(form);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success small"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            // Reload page after 2 seconds to show updated branding
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger small"><i class="fas fa-exclamation-triangle"></i> ' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger small">Failed to save branding settings</div>';
        resultDiv.style.display = 'block';
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

// Reset preview to saved values
function resetPreview() {
    // Reset color inputs to saved values
    document.getElementById('brand_primary_color').value = '{{ $branding ? $branding->primary_color : '#1a4d3a' }}';
    document.getElementById('brand_secondary_color').value = '{{ $branding ? $branding->secondary_color : '#2d6a4f' }}';
    document.getElementById('brand_accent_color').value = '{{ $branding ? $branding->accent_color : '#52b788' }}';

    // Reset text inputs (they are readonly and sync automatically)
    const primaryTextInput = document.getElementById('brand_primary_color').nextElementSibling;
    const secondaryTextInput = document.getElementById('brand_secondary_color').nextElementSibling;
    const accentTextInput = document.getElementById('brand_accent_color').nextElementSibling;

    if (primaryTextInput) primaryTextInput.value = '{{ $branding ? $branding->primary_color : '#1a4d3a' }}';
    if (secondaryTextInput) secondaryTextInput.value = '{{ $branding ? $branding->secondary_color : '#2d6a4f' }}';
    if (accentTextInput) accentTextInput.value = '{{ $branding ? $branding->accent_color : '#52b788' }}';

    // Update preview
    updateLivePreview();
}

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    updateLivePreview();
});
</script>

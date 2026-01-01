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
            color: var(--brand-sidebar-text) !important;
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

        <!-- Branding Presets -->
        <div class="col-md-12 mb-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-palette"></i> Branding Presets & Themes</h5>
                    <small class="text-white-50">Choose from pre-designed color schemes</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_theme_preset" class="form-label"><strong>Theme Preset</strong></label>
                        <select class="form-select" id="brand_theme_preset" name="brand_theme_preset">
                            <option value="custom" {{ ($branding->theme_preset ?? 'default') == 'custom' ? 'selected' : '' }}>Custom (Manual Colors)</option>
                            @foreach(config('branding.presets') as $key => $preset)
                                <option value="{{ $key }}" {{ ($branding->theme_preset ?? 'default') == $key ? 'selected' : '' }}>
                                    {{ $preset['name'] }} - {{ $preset['description'] }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Selecting a preset will automatically update all color fields below</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="brand_font_preset" class="form-label"><strong>Font Preset</strong></label>
                        <select class="form-select" id="brand_font_preset" name="brand_font_preset">
                            <option value="custom" {{ ($branding->fonts['preset'] ?? 'modern') == 'custom' ? 'selected' : '' }}>Custom Fonts</option>
                            @foreach(config('branding.fonts') as $key => $font)
                                <option value="{{ $key }}" {{ ($branding->fonts['preset'] ?? 'modern') == $key ? 'selected' : '' }}>
                                    {{ $font['name'] }}
                                </option>
                            @endforeach
                        </select>
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
                            <input type="text" class="form-control" value="{{ $branding->primary_color ?? '#2d5016' }}" placeholder="#2d5016" maxlength="7">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="brand_secondary_color" class="form-label"><strong>Secondary Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_secondary_color" name="brand_secondary_color" 
                                   value="{{ $branding->secondary_color ?? '#5a7c3e' }}">
                            <input type="text" class="form-control" value="{{ $branding->secondary_color ?? '#5a7c3e' }}" placeholder="#5a7c3e" maxlength="7">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="brand_accent_color" class="form-label"><strong>Accent Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_accent_color" name="brand_accent_color" 
                                   value="{{ $branding->accent_color ?? '#f5c518' }}">
                            <input type="text" class="form-control" value="{{ $branding->accent_color ?? '#f5c518' }}" placeholder="#f5c518" maxlength="7">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="brand_text_color" class="form-label"><strong>Text Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_text_color" name="brand_text_color" 
                                   value="{{ $branding->text_color ?? '#1a1a1a' }}">
                            <input type="text" class="form-control" value="{{ $branding->text_color ?? '#1a1a1a' }}" placeholder="#1a1a1a" maxlength="7">
                        </div>
                        <small class="form-text text-muted">Color for text elements on colored backgrounds</small>
                    </div>
                    <div class="mb-3">
                        <label for="brand_sidebar_text_color" class="form-label"><strong>Sidebar Text Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_sidebar_text_color" name="brand_sidebar_text_color" 
                                   value="{{ $branding->sidebar_text_color ?? '#ffffff' }}">
                            <input type="text" class="form-control" value="{{ $branding->sidebar_text_color ?? '#ffffff' }}" placeholder="#ffffff" maxlength="7">
                        </div>
                        <small class="form-text text-muted">Color for text in the sidebar navigation</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Colors -->
        <div class="col-md-6 mb-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-cogs"></i> Advanced Colors</h5>
                    <small class="text-white-50">Additional color controls for borders and status indicators</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_background_color" class="form-label"><strong>Background Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_background_color" name="brand_background_color" 
                                   value="{{ $branding->background_color ?? '#ffffff' }}">
                            <input type="text" class="form-control" value="{{ $branding->background_color ?? '#ffffff' }}" placeholder="#ffffff" maxlength="7">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="brand_border_color" class="form-label"><strong>Border Color</strong></label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="brand_border_color" name="brand_border_color" 
                                   value="{{ $branding->border_color ?? '#dee2e6' }}">
                            <input type="text" class="form-control" value="{{ $branding->border_color ?? '#dee2e6' }}" placeholder="#dee2e6" maxlength="7">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="brand_success_color" class="form-label"><strong>Success</strong></label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="brand_success_color" name="brand_success_color" 
                                       value="{{ $branding->success_color ?? '#28a745' }}">
                                <input type="text" class="form-control" value="{{ $branding->success_color ?? '#28a745' }}" placeholder="#28a745" maxlength="7">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="brand_warning_color" class="form-label"><strong>Warning</strong></label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="brand_warning_color" name="brand_warning_color" 
                                       value="{{ $branding->warning_color ?? '#ffc107' }}">
                                <input type="text" class="form-control" value="{{ $branding->warning_color ?? '#ffc107' }}" placeholder="#ffc107" maxlength="7">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="brand_danger_color" class="form-label"><strong>Danger</strong></label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="brand_danger_color" name="brand_danger_color" 
                                       value="{{ $branding->danger_color ?? '#dc3545' }}">
                                <input type="text" class="form-control" value="{{ $branding->danger_color ?? '#dc3545' }}" placeholder="#dc3545" maxlength="7">
                            </div>
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

        <!-- Typography -->
        <div class="col-md-6 mb-4">
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-font"></i> Typography</h5>
                    <small class="text-white-50">Customize fonts for your brand</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_font_heading" class="form-label"><strong>Heading Font</strong></label>
                        <input type="text" class="form-control" id="brand_font_heading" name="brand_font_heading" 
                               value="{{ $branding->fonts['heading'] ?? 'Inter, system-ui, sans-serif' }}" 
                               placeholder="Inter, system-ui, sans-serif">
                        <div class="form-text">Font stack for headings (h1, h2, h3, etc.)</div>
                    </div>
                    <div class="mb-3">
                        <label for="brand_font_body" class="form-label"><strong>Body Font</strong></label>
                        <input type="text" class="form-control" id="brand_font_body" name="brand_font_body" 
                               value="{{ $branding->fonts['body'] ?? '-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif' }}" 
                               placeholder="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif">
                        <div class="form-text">Font stack for body text and paragraphs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom CSS -->
        <div class="col-md-6 mb-4">
            <div class="card border-dark">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-code"></i> Custom CSS</h5>
                    <small class="text-white-50">Advanced customization with CSS</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="brand_custom_css" class="form-label"><strong>Custom CSS</strong></label>
                        <textarea class="form-control" id="brand_custom_css" name="brand_custom_css" rows="8" 
                                  placeholder="/* Add your custom CSS here */
/* Example: .custom-header { background: linear-gradient(45deg, var(--brand-primary), var(--brand-secondary)); } */">{{ $branding->custom_css ?? '' }}</textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> Use CSS custom properties like <code>var(--brand-primary)</code> to reference your brand colors.
                            <a href="#" onclick="showCssExamples()" class="ms-2">Show examples</a>
                        </div>
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
                    
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-outline-secondary" onclick="exportBranding()">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="importBranding()">
                            <i class="fas fa-upload"></i> Import
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="resetToDefaults()">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </button>
                    </div>
                    
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

    // Handle theme preset changes
    document.getElementById('brand_theme_preset')?.addEventListener('change', function() {
        loadThemePreset(this.value);
    });

    // Handle font preset changes
    document.getElementById('brand_font_preset')?.addEventListener('change', function() {
        loadFontPreset(this.value);
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
    const textColor = document.getElementById('brand_text_color')?.value || '{{ $branding ? $branding->text_color : '#1a1a1a' }}';
    const sidebarTextColor = document.getElementById('brand_sidebar_text_color')?.value || '{{ $branding ? $branding->sidebar_text_color : '#ffffff' }}';
    const backgroundColor = document.getElementById('brand_background_color')?.value || '{{ $branding ? $branding->background_color : '#ffffff' }}';
    const borderColor = document.getElementById('brand_border_color')?.value || '{{ $branding ? $branding->border_color : '#dee2e6' }}';
    const successColor = document.getElementById('brand_success_color')?.value || '{{ $branding ? $branding->success_color : '#28a745' }}';
    const warningColor = document.getElementById('brand_warning_color')?.value || '{{ $branding ? $branding->warning_color : '#ffc107' }}';
    const dangerColor = document.getElementById('brand_danger_color')?.value || '{{ $branding ? $branding->danger_color : '#dc3545' }}';

    // Update CSS custom properties for backgrounds
    root.style.setProperty('--brand-primary', primaryColor);
    root.style.setProperty('--brand-secondary', secondaryColor);
    root.style.setProperty('--brand-accent', accentColor);
    root.style.setProperty('--brand-text', textColor);
    root.style.setProperty('--brand-sidebar-text', sidebarTextColor);
    root.style.setProperty('--brand-background', backgroundColor);
    root.style.setProperty('--brand-border', borderColor);
    root.style.setProperty('--brand-success', successColor);
    root.style.setProperty('--brand-warning', warningColor);
    root.style.setProperty('--brand-danger', dangerColor);

    // Update CSS custom properties for text colors
    root.style.setProperty('--brand-primary-text', textColor);
    root.style.setProperty('--brand-secondary-text', textColor);
    root.style.setProperty('--brand-accent-text', textColor);
    root.style.setProperty('--brand-sidebar-text', sidebarTextColor);
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
    document.getElementById('brand_text_color').value = '{{ $branding ? $branding->text_color : '#1a1a1a' }}';
    document.getElementById('brand_sidebar_text_color').value = '{{ $branding ? $branding->sidebar_text_color : '#ffffff' }}';
    document.getElementById('brand_background_color').value = '{{ $branding ? $branding->background_color : '#ffffff' }}';
    document.getElementById('brand_border_color').value = '{{ $branding ? $branding->border_color : '#dee2e6' }}';
    document.getElementById('brand_success_color').value = '{{ $branding ? $branding->success_color : '#28a745' }}';
    document.getElementById('brand_warning_color').value = '{{ $branding ? $branding->warning_color : '#ffc107' }}';
    document.getElementById('brand_danger_color').value = '{{ $branding ? $branding->danger_color : '#dc3545' }}';

    // Reset text inputs (they are readonly and sync automatically)
    const primaryTextInput = document.getElementById('brand_primary_color').nextElementSibling;
    const secondaryTextInput = document.getElementById('brand_secondary_color').nextElementSibling;
    const accentTextInput = document.getElementById('brand_accent_color').nextElementSibling;
    const textTextInput = document.getElementById('brand_text_color').nextElementSibling;
    const sidebarTextTextInput = document.getElementById('brand_sidebar_text_color').nextElementSibling;
    const backgroundTextInput = document.getElementById('brand_background_color').nextElementSibling;
    const borderTextInput = document.getElementById('brand_border_color').nextElementSibling;
    const successTextInput = document.getElementById('brand_success_color').nextElementSibling;
    const warningTextInput = document.getElementById('brand_warning_color').nextElementSibling;
    const dangerTextInput = document.getElementById('brand_danger_color').nextElementSibling;

    if (primaryTextInput) primaryTextInput.value = '{{ $branding ? $branding->primary_color : '#1a4d3a' }}';
    if (secondaryTextInput) secondaryTextInput.value = '{{ $branding ? $branding->secondary_color : '#2d6a4f' }}';
    if (accentTextInput) accentTextInput.value = '{{ $branding ? $branding->accent_color : '#52b788' }}';
    if (textTextInput) textTextInput.value = '{{ $branding ? $branding->text_color : '#1a1a1a' }}';
    if (sidebarTextTextInput) sidebarTextTextInput.value = '{{ $branding ? $branding->sidebar_text_color : '#ffffff' }}';
    if (backgroundTextInput) backgroundTextInput.value = '{{ $branding ? $branding->background_color : '#ffffff' }}';
    if (borderTextInput) borderTextInput.value = '{{ $branding ? $branding->border_color : '#dee2e6' }}';
    if (successTextInput) successTextInput.value = '{{ $branding ? $branding->success_color : '#28a745' }}';
    if (warningTextInput) warningTextInput.value = '{{ $branding ? $branding->warning_color : '#ffc107' }}';
    if (dangerTextInput) dangerTextInput.value = '{{ $branding ? $branding->danger_color : '#dc3545' }}';

    // Update preview
    updateLivePreview();
}

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    updateLivePreview();
});

// Branding presets data (from config)
const brandingPresets = @json(config('branding.presets'));
const fontPresets = @json(config('branding.fonts'));

// Load theme preset colors
function loadThemePreset(presetKey) {
    if (presetKey === 'custom') return;
    
    const preset = brandingPresets[presetKey];
    if (!preset) return;
    
    // Update all color inputs
    Object.keys(preset.colors).forEach(colorKey => {
        const inputId = `brand_${colorKey}_color`;
        const colorPicker = document.getElementById(inputId);
        const textInput = colorPicker?.nextElementSibling;
        
        if (colorPicker && textInput) {
            colorPicker.value = preset.colors[colorKey];
            textInput.value = preset.colors[colorKey];
        }
    });
    
    // Update live preview
    updateLivePreview();
}

// Load font preset
function loadFontPreset(presetKey) {
    if (presetKey === 'custom') return;
    
    const preset = fontPresets[presetKey];
    if (!preset) return;
    
    document.getElementById('brand_font_heading').value = preset.heading;
    document.getElementById('brand_font_body').value = preset.body;
}

// Export branding settings
function exportBranding() {
    const form = document.getElementById('branding-form');
    const formData = new FormData(form);
    const brandingData = {};
    
    // Convert form data to object
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('brand_')) {
            const cleanKey = key.replace('brand_', '');
            brandingData[cleanKey] = value;
        }
    }
    
    // Create and download JSON file
    const dataStr = JSON.stringify(brandingData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'branding-settings.json';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Import branding settings
function importBranding() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const brandingData = JSON.parse(e.target.result);
                
                // Update form fields
                Object.keys(brandingData).forEach(key => {
                    const inputId = `brand_${key}`;
                    const input = document.getElementById(inputId);
                    if (input) {
                        if (input.type === 'checkbox') {
                            input.checked = brandingData[key];
                        } else {
                            input.value = brandingData[key];
                        }
                        
                        // Update color picker text inputs
                        if (key.includes('_color') && !key.includes('_text')) {
                            const textInput = input.nextElementSibling;
                            if (textInput && textInput.tagName === 'INPUT') {
                                textInput.value = brandingData[key];
                            }
                        }
                    }
                });
                
                // Update live preview
                updateLivePreview();
                
                alert('Branding settings imported successfully!');
            } catch (error) {
                alert('Error importing branding settings: ' + error.message);
            }
        };
        reader.readAsText(file);
    };
    input.click();
}

// Reset to defaults
function resetToDefaults() {
    if (!confirm('Are you sure you want to reset all branding settings to defaults? This cannot be undone.')) {
        return;
    }
    
    // Reset to default preset
    loadThemePreset('default');
    loadFontPreset('modern');
    
    // Reset other fields
    document.getElementById('brand_company_name').value = 'Middleworld Farms';
    document.getElementById('brand_tagline').value = '';
    document.getElementById('brand_custom_css').value = '';
    
    // Update live preview
    updateLivePreview();
}

// Show CSS examples
function showCssExamples() {
    const examples = `
/* Custom gradient header */
.custom-header {
    background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
}

/* Custom button styles */
.custom-btn {
    background: var(--brand-accent);
    border: 2px solid var(--brand-primary);
    color: var(--brand-text);
}

.custom-btn:hover {
    background: var(--brand-primary);
    color: var(--brand-primary-text);
}

/* Custom card styling */
.custom-card {
    border-left: 4px solid var(--brand-accent);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Custom navigation styling */
.custom-nav .nav-link {
    color: var(--brand-text);
    border-bottom: 2px solid transparent;
}

.custom-nav .nav-link:hover {
    color: var(--brand-primary);
    border-bottom-color: var(--brand-primary);
}`;
    
    // Create modal or alert with examples
    alert('CSS Custom Properties Examples:\n\n' + examples + '\n\nCopy and paste these examples into the Custom CSS field above.');
}
</script>

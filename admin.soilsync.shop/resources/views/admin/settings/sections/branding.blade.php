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
                           value="{{ $branding->company_name ?? 'Middleworld Farms' }}" placeholder="Middleworld Farms">
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
            </div>
        </div>
    </div>
</div>

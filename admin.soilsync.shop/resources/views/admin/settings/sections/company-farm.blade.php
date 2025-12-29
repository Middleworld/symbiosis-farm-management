{{-- Company Information --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-building"></i> Company Information
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Important:</strong> Your company type affects tax reporting, legal requirements, and system behavior.
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="company_type" class="form-label"><strong>Company Type</strong></label>
                        <select class="form-select" id="company_type" name="company_type">
                            <option value="">Select company type...</option>
                            <option value="cic" {{ ($settings['company_type'] ?? '') == 'cic' ? 'selected' : '' }}>Community Interest Company (CIC)</option>
                            <option value="ltd" {{ ($settings['company_type'] ?? '') == 'ltd' ? 'selected' : '' }}>Limited Company (Ltd)</option>
                            <option value="plc" {{ ($settings['company_type'] ?? '') == 'plc' ? 'selected' : '' }}>Public Limited Company (PLC)</option>
                            <option value="sole_trader" {{ ($settings['company_type'] ?? '') == 'sole_trader' ? 'selected' : '' }}>Sole Trader</option>
                            <option value="partnership" {{ ($settings['company_type'] ?? '') == 'partnership' ? 'selected' : '' }}>Partnership</option>
                            <option value="charity" {{ ($settings['company_type'] ?? '') == 'charity' ? 'selected' : '' }}>Registered Charity</option>
                            <option value="other" {{ ($settings['company_type'] ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="company_number" class="form-label"><strong>Company Number</strong></label>
                        <input type="text" class="form-control" id="company_number" name="company_number" 
                               value="{{ $settings['company_number'] ?? '' }}" placeholder="e.g., 13617115">
                        <div class="form-text">Companies House registration number</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tax_year_end" class="form-label"><strong>Tax Year End</strong></label>
                        <select class="form-select" id="tax_year_end" name="tax_year_end">
                            <option value="31-03" {{ ($settings['tax_year_end'] ?? '30-09') == '31-03' ? 'selected' : '' }}>31 March</option>
                            <option value="30-09" {{ ($settings['tax_year_end'] ?? '30-09') == '30-09' ? 'selected' : '' }}>30 September</option>
                            <option value="31-12" {{ ($settings['tax_year_end'] ?? '') == '31-12' ? 'selected' : '' }}>31 December</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="vat_registered" class="form-label"><strong>VAT Registration</strong></label>
                        <select class="form-select" id="vat_registered" name="vat_registered">
                            <option value="0" {{ ($settings['vat_registered'] ?? '0') == '0' ? 'selected' : '' }}>Not VAT registered</option>
                            <option value="1" {{ ($settings['vat_registered'] ?? '') == '1' ? 'selected' : '' }}>VAT registered</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Farm Season Settings --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-seedling"></i> Farm Season Settings
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="farm_name" class="form-label"><strong>Farm Name</strong></label>
                        <input type="text" class="form-control" id="farm_name" name="farm_name" 
                               value="{{ $settings['farm_name'] ?? 'Middle World Farms' }}" placeholder="e.g., Middle World Farms">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="season_weeks" class="form-label"><strong>Season Length (Weeks)</strong></label>
                        <input type="number" class="form-control" id="season_weeks" name="season_weeks" 
                               value="{{ $settings['season_weeks'] ?? 33 }}" min="1" max="52">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="season_start_date" class="form-label"><strong>Season Start Date</strong></label>
                        @php
                            $seasonStart = $settings['season_start_date'] ?? '';
                            // Convert DD/MM/YYYY to YYYY-MM-DD for HTML5 date input
                            if ($seasonStart && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $seasonStart)) {
                                try {
                                    $seasonStart = \Carbon\Carbon::createFromFormat('d/m/Y', $seasonStart)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    $seasonStart = '';
                                }
                            }
                        @endphp
                        <input type="date" class="form-control" id="season_start_date" name="season_start_date" 
                               value="{{ $seasonStart }}">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="season_end_date" class="form-label"><strong>Season End Date</strong></label>
                        @php
                            $seasonEnd = $settings['season_end_date'] ?? '';
                            // Convert DD/MM/YYYY to YYYY-MM-DD for HTML5 date input
                            if ($seasonEnd && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $seasonEnd)) {
                                try {
                                    $seasonEnd = \Carbon\Carbon::createFromFormat('d/m/Y', $seasonEnd)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    $seasonEnd = '';
                                }
                            }
                        @endphp
                        <input type="date" class="form-control" id="season_end_date" name="season_end_date" 
                               value="{{ $seasonEnd }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="delivery_days" class="form-label"><strong>Delivery Days</strong></label>
                        <select class="form-select" id="delivery_days" name="delivery_days[]" multiple>
                            @php
                                $selectedDays = $settings['delivery_days'] ?? ['Thursday'];
                                if (is_string($selectedDays)) {
                                    $selectedDays = json_decode($selectedDays, true) ?? ['Thursday'];
                                }
                            @endphp
                            <option value="Monday" {{ in_array('Monday', $selectedDays) ? 'selected' : '' }}>Monday</option>
                            <option value="Tuesday" {{ in_array('Tuesday', $selectedDays) ? 'selected' : '' }}>Tuesday</option>
                            <option value="Wednesday" {{ in_array('Wednesday', $selectedDays) ? 'selected' : '' }}>Wednesday</option>
                            <option value="Thursday" {{ in_array('Thursday', $selectedDays) ? 'selected' : '' }}>Thursday</option>
                            <option value="Friday" {{ in_array('Friday', $selectedDays) ? 'selected' : '' }}>Friday</option>
                            <option value="Saturday" {{ in_array('Saturday', $selectedDays) ? 'selected' : '' }}>Saturday</option>
                            <option value="Sunday" {{ in_array('Sunday', $selectedDays) ? 'selected' : '' }}>Sunday</option>
                        </select>
                        <div class="form-text">Ctrl+click to select multiple days</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="fortnightly_week_a_start" class="form-label"><strong>Week A Start Date</strong></label>
                        @php
                            $weekAStart = $settings['fortnightly_week_a_start'] ?? '';
                            if ($weekAStart && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekAStart)) {
                                try {
                                    $weekAStart = \Carbon\Carbon::createFromFormat('d/m/Y', $weekAStart)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    $weekAStart = '';
                                }
                            }
                        @endphp
                        <input type="date" class="form-control" id="fortnightly_week_a_start" name="fortnightly_week_a_start" 
                               value="{{ $weekAStart }}">
                        <div class="form-text">For fortnightly subscriptions</div>
                    </div>
                </div>

                <hr class="my-4">
                <h6><i class="fas fa-pause-circle"></i> Seasonal Closure (Optional)</h6>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="closure_start_date" class="form-label"><strong>Closure Start</strong></label>
                        @php
                            $closureStart = $settings['closure_start_date'] ?? '';
                            if ($closureStart && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $closureStart)) {
                                try {
                                    $closureStart = \Carbon\Carbon::createFromFormat('d/m/Y', $closureStart)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    $closureStart = '';
                                }
                            }
                        @endphp
                        <input type="date" class="form-control" id="closure_start_date" name="closure_start_date" 
                               value="{{ $closureStart }}">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="closure_end_date" class="form-label"><strong>Closure End</strong></label>
                        @php
                            $closureEnd = $settings['closure_end_date'] ?? '';
                            if ($closureEnd && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $closureEnd)) {
                                try {
                                    $closureEnd = \Carbon\Carbon::createFromFormat('d/m/Y', $closureEnd)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    $closureEnd = '';
                                }
                            }
                        @endphp
                        <input type="date" class="form-control" id="closure_end_date" name="closure_end_date" 
                               value="{{ $closureEnd }}">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="resume_billing_date" class="form-label"><strong>Resume Billing</strong></label>
                        @php
                            $resumeBilling = $settings['resume_billing_date'] ?? '';
                            if ($resumeBilling && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $resumeBilling)) {
                                try {
                                    $resumeBilling = \Carbon\Carbon::createFromFormat('d/m/Y', $resumeBilling)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    $resumeBilling = '';
                                }
                            }
                        @endphp
                        <input type="date" class="form-control" id="resume_billing_date" name="resume_billing_date" 
                               value="{{ $resumeBilling }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

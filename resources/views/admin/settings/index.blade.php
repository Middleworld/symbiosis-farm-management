@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('styles')
<style>
    .rag-dropzone {
        background-color: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        transition: background-color 0.2s ease, border-color 0.2s ease;
        cursor: pointer;
    }

    .rag-dropzone:hover {
        background-color: #e9ecef;
        border-color: #0d6efd;
    }

    .rag-dropzone i {
        color: #0d6efd;
    }

    :root {
        --settings-nav-offset: 96px;
    }

    .wizard-progress {
        position: sticky;
        top: var(--settings-nav-offset);
        z-index: 10;
        background: white;
        border-bottom: 1px solid #dee2e6;
        padding: 1rem 0;
    }

    .wizard-step {
        display: none;
    }

    .wizard-step.active {
        display: block;
    }

    .wizard-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
    }

    .wizard-navigation .btn {
        min-width: 120px;
    }

    .progress {
        height: 8px;
        margin-bottom: 0.5rem;
    }

    .progress-bar {
        transition: width 0.3s ease;
    }

    .settings-sidebar-card .list-group-item.active {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        border-left: 3px solid #0d6efd;
    }

    .wizard-steps-indicator {
        padding: 1rem;
    }

    .step-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0.75rem;
        border-radius: 0.375rem;
        transition: all 0.3s ease;
        opacity: 0.6;
    }

    .step-item.active {
        background-color: rgba(13, 110, 253, 0.1);
        border-left: 4px solid #0d6efd;
        opacity: 1;
    }

    .step-item.completed {
        opacity: 0.8;
    }

    .step-number {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 0.75rem;
        transition: all 0.3s ease;
    }

    .step-item.active .step-number {
        background-color: #0d6efd;
        color: white;
    }

    .step-item.completed .step-number {
        background-color: #198754;
        color: white;
    }

    .step-content {
        flex: 1;
    }

    .step-title {
        font-weight: 600;
        font-size: 0.875rem;
        color: #212529;
    }

    .step-desc {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.125rem;
    }

</style>
@endsection

@section('content')
<div class="container-fluid py-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <strong>Validation errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4 settings-layout">
        <div class="col-12 col-lg-3">
            <div class="card shadow-sm settings-sidebar-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-compass"></i> Setup Progress</h5>
                </div>
                <div class="wizard-steps-indicator">
                    <div class="step-item active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <div class="step-title">Overview</div>
                            <div class="step-desc">Operations</div>
                        </div>
                    </div>
                    <div class="step-item" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <div class="step-title">Commerce</div>
                            <div class="step-desc">API Integrations</div>
                        </div>
                    </div>
                    <div class="step-item" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <div class="step-title">AI</div>
                            <div class="step-desc">Automation</div>
                        </div>
                    </div>
                    <div class="step-item" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <div class="step-title">Printing</div>
                            <div class="step-desc">Documents</div>
                        </div>
                    </div>
                    <div class="step-item" data-step="5">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <div class="step-title">POS</div>
                            <div class="step-desc">Hardware</div>
                        </div>
                    </div>
                    <div class="step-item" data-step="6">
                        <div class="step-number">6</div>
                        <div class="step-content">
                            <div class="step-title">Communications</div>
                            <div class="step-desc">Email</div>
                        </div>
                    </div>
                    <div class="step-item" data-step="7">
                        <div class="step-number">7</div>
                        <div class="step-content">
                            <div class="step-title">Advanced</div>
                            <div class="step-desc">Diagnostics</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                <div class="col-12 col-lg-9">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="settings-form">
                @csrf

                <!-- Wizard Progress -->
                <div class="wizard-progress">
                    <div class="progress">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 14.28%" aria-valuenow="1" aria-valuemin="0" aria-valuemax="7"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Step 1 of 7</span>
                        <span>Overview & Operations</span>
                    </div>
                </div>

                <!-- Wizard Steps -->
                <div class="wizard-container">
                    <!-- Step 1: Overview & Operations -->
                    <div class="wizard-step active" data-step="1">
                        <h3 class="mb-4"><i class="fas fa-cog text-primary"></i> Overview & Operations</h3>
                        <p class="text-muted mb-4">Configure delivery schedules, notifications, and system status settings.</p>
                        
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
                                            <i class="fas fa-info-circle"></i> <strong>Important:</strong> Your company type affects tax reporting, legal requirements, and system behavior. Please select the correct type for your business.
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="company_type" class="form-label"><strong>Company Type</strong> <span class="text-danger">*</span></label>
                                                <select class="form-select" id="company_type" name="company_type" required>
                                                    <option value="">Select company type...</option>
                                                    <option value="cic" {{ ($settings['company_type'] ?? '') == 'cic' ? 'selected' : '' }}>
                                                        Community Interest Company (CIC)
                                                    </option>
                                                    <option value="ltd" {{ ($settings['company_type'] ?? '') == 'ltd' ? 'selected' : '' }}>
                                                        Limited Company (Ltd)
                                                    </option>
                                                    <option value="plc" {{ ($settings['company_type'] ?? '') == 'plc' ? 'selected' : '' }}>
                                                        Public Limited Company (PLC)
                                                    </option>
                                                    <option value="sole_trader" {{ ($settings['company_type'] ?? '') == 'sole_trader' ? 'selected' : '' }}>
                                                        Sole Trader / Self-Employed
                                                    </option>
                                                    <option value="partnership" {{ ($settings['company_type'] ?? '') == 'partnership' ? 'selected' : '' }}>
                                                        Partnership
                                                    </option>
                                                    <option value="charity" {{ ($settings['company_type'] ?? '') == 'charity' ? 'selected' : '' }}>
                                                        Registered Charity
                                                    </option>
                                                    <option value="other" {{ ($settings['company_type'] ?? '') == 'other' ? 'selected' : '' }}>
                                                        Other
                                                    </option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-question-circle"></i> This determines tax filing requirements and legal obligations
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="company_number" class="form-label"><strong>Company Number</strong></label>
                                                <input type="text" class="form-control" id="company_number" name="company_number" 
                                                       value="{{ $settings['company_number'] ?? '' }}" 
                                                       placeholder="e.g., 13617115">
                                                <div class="form-text">
                                                    <i class="fas fa-hashtag"></i> Companies House registration number (if applicable)
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="tax_year_end" class="form-label"><strong>Tax Year End</strong></label>
                                                <select class="form-select" id="tax_year_end" name="tax_year_end">
                                                    <option value="31-03" {{ ($settings['tax_year_end'] ?? '30-09') == '31-03' ? 'selected' : '' }}>
                                                        31 March (Standard business year)
                                                    </option>
                                                    <option value="30-09" {{ ($settings['tax_year_end'] ?? '30-09') == '30-09' ? 'selected' : '' }}>
                                                        30 September (Farm/CIC year)
                                                    </option>
                                                    <option value="31-12" {{ ($settings['tax_year_end'] ?? '') == '31-12' ? 'selected' : '' }}>
                                                        31 December
                                                    </option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-calendar"></i> When your accounting/tax year ends
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="vat_registered" class="form-label"><strong>VAT Registration</strong></label>
                                                <select class="form-select" id="vat_registered" name="vat_registered">
                                                    <option value="0" {{ ($settings['vat_registered'] ?? '0') == '0' ? 'selected' : '' }}>
                                                        Not VAT registered
                                                    </option>
                                                    <option value="1" {{ ($settings['vat_registered'] ?? '') == '1' ? 'selected' : '' }}>
                                                        VAT registered
                                                    </option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-pound-sign"></i> Affects pricing and tax calculations
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Farm Season Settings --}}
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-seedling"></i> Farm Season Settings
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> <strong>Season Configuration:</strong> Define your growing season dates, delivery days, and any seasonal closures. These settings affect billing calculations and delivery scheduling.
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="farm_name" class="form-label"><strong>Farm Name</strong></label>
                                                <input type="text" class="form-control" id="farm_name" name="farm_name" 
                                                       value="{{ $settings['farm_name'] ?? 'Middle World Farms' }}" 
                                                       placeholder="e.g., Middle World Farms">
                                                <div class="form-text">
                                                    <i class="fas fa-home"></i> Display name for the farm/CSA operation
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="season_weeks" class="form-label"><strong>Season Length (Weeks)</strong></label>
                                                <input type="number" class="form-control" id="season_weeks" name="season_weeks" 
                                                       value="{{ $settings['season_weeks'] ?? 33 }}" 
                                                       min="1" max="52" placeholder="33">
                                                <div class="form-text">
                                                    <i class="fas fa-calendar-alt"></i> Total number of delivery weeks in the season
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="season_start_date" class="form-label"><strong>Season Start Date</strong></label>
                                                <input type="date" class="form-control" id="season_start_date" name="season_start_date" 
                                                       value="{{ $settings['season_start_date'] ?? '' }}">
                                                <div class="form-text">
                                                    <i class="fas fa-play"></i> First delivery date of the season
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="season_end_date" class="form-label"><strong>Season End Date</strong></label>
                                                <input type="date" class="form-control" id="season_end_date" name="season_end_date" 
                                                       value="{{ $settings['season_end_date'] ?? '' }}">
                                                <div class="form-text">
                                                    <i class="fas fa-stop"></i> Last delivery date of the season
                                                </div>
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
                                                <div class="form-text">
                                                    <i class="fas fa-truck"></i> Days of the week when deliveries occur (Ctrl+click to select multiple)
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="fortnightly_week_a_start" class="form-label"><strong>Week A Start Date</strong></label>
                                                <input type="date" class="form-control" id="fortnightly_week_a_start" name="fortnightly_week_a_start" 
                                                       value="{{ $settings['fortnightly_week_a_start'] ?? '' }}">
                                                <div class="form-text">
                                                    <i class="fas fa-calendar-week"></i> Reference date for fortnightly "Week A" subscriptions
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">
                                        <h6 class="text-muted mb-3"><i class="fas fa-pause-circle"></i> Seasonal Closure (Optional)</h6>
                                        <p class="text-muted small">Configure a mid-season break (e.g., Christmas closure). Billing will be paused during this period.</p>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="closure_start_date" class="form-label"><strong>Closure Start</strong></label>
                                                <input type="date" class="form-control" id="closure_start_date" name="closure_start_date" 
                                                       value="{{ $settings['closure_start_date'] ?? '' }}">
                                                <div class="form-text">
                                                    <i class="fas fa-door-closed"></i> First day of closure period
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="closure_end_date" class="form-label"><strong>Closure End</strong></label>
                                                <input type="date" class="form-control" id="closure_end_date" name="closure_end_date" 
                                                       value="{{ $settings['closure_end_date'] ?? '' }}">
                                                <div class="form-text">
                                                    <i class="fas fa-door-open"></i> Last day of closure period
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="resume_billing_date" class="form-label"><strong>Resume Billing Date</strong></label>
                                                <input type="date" class="form-control" id="resume_billing_date" name="resume_billing_date" 
                                                       value="{{ $settings['resume_billing_date'] ?? '' }}">
                                                <div class="form-text">
                                                    <i class="fas fa-redo"></i> When billing resumes after closure
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-success mt-3">
                                            <i class="fas fa-sync-alt"></i> <strong>FarmOS Integration:</strong> When you save these season settings, they will be automatically synced to farmOS. The system will <strong>update the existing</strong> "Season Configuration" plan (or create one if it doesn't exist yet), so you won't get duplicates.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                                {{-- Delivery & Collection Settings --}}
                                <div class="col-lg-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-truck"></i> Delivery & Collection Settings
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            {{-- Route Optimization --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_route_optimization" 
                                                           name="enable_route_optimization" value="1" 
                                                           {{ ($settings['enable_route_optimization'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="enable_route_optimization">
                                                        <strong>Enable Route Optimization</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-route"></i> Show route planning and optimization features
                                                </div>
                                            </div>

                                            {{-- Delivery Time Slots --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="delivery_time_slots" 
                                                           name="delivery_time_slots" value="1" 
                                                           {{ ($settings['delivery_time_slots'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="delivery_time_slots">
                                                        <strong>Delivery Time Slots</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-clock"></i> Enable specific delivery time slot selection
                                                </div>
                                            </div>

                                            {{-- Delivery Cut-off Time --}}
                                            <div class="mb-3">
                                                <label for="delivery_cutoff_time" class="form-label">
                                                    <strong>Delivery Cut-off Time (Thursday)</strong>
                                                </label>
                                                <input type="time" class="form-control" id="delivery_cutoff_time" 
                                                       name="delivery_cutoff_time" 
                                                       value="{{ $settings['delivery_cutoff_time'] ?? '10:00' }}">
                                                <div class="form-text">
                                                    <i class="fas fa-clock"></i> Customers joining after this time on Thursday won't appear on this week's delivery schedule
                                                </div>
                                            </div>

                                            {{-- Collection Cut-off Time --}}
                                            <div class="mb-3">
                                                <label for="collection_cutoff_time" class="form-label">
                                                    <strong>Collection Cut-off Time (Friday)</strong>
                                                </label>
                                                <input type="time" class="form-control" id="collection_cutoff_time" 
                                                       name="collection_cutoff_time" 
                                                       value="{{ $settings['collection_cutoff_time'] ?? '12:00' }}">
                                                <div class="form-text">
                                                    <i class="fas fa-clock"></i> Customers joining after this time on Friday won't appear on this week's collection schedule
                                                </div>
                                            </div>

                                            {{-- Collection Reminder --}}
                                            <div class="mb-3">
                                                <label for="collection_reminder_hours" class="form-label">
                                                    <strong>Collection Reminder (Hours Before)</strong>
                                                </label>
                                                <select class="form-select" id="collection_reminder_hours" name="collection_reminder_hours">
                                                    <option value="2" {{ ($settings['collection_reminder_hours'] ?? 24) == 2 ? 'selected' : '' }}>2 hours before</option>
                                                    <option value="6" {{ ($settings['collection_reminder_hours'] ?? 24) == 6 ? 'selected' : '' }}>6 hours before</option>
                                                    <option value="24" {{ ($settings['collection_reminder_hours'] ?? 24) == 24 ? 'selected' : '' }}>1 day before</option>
                                                    <option value="48" {{ ($settings['collection_reminder_hours'] ?? 24) == 48 ? 'selected' : '' }}>2 days before</option>
                                                    <option value="72" {{ ($settings['collection_reminder_hours'] ?? 24) == 72 ? 'selected' : '' }}>3 days before</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-bell"></i> When to send collection reminder emails/notifications
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Notification Settings --}}
                                <div class="col-lg-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-envelope"></i> Notification Settings
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            {{-- Email Notifications --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="email_notifications" 
                                                           name="email_notifications" value="1" 
                                                           {{ ($settings['email_notifications'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="email_notifications">
                                                        <strong>Email Notifications</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-mail-bulk"></i> Send automated email notifications to customers
                                                </div>
                                            </div>

                                            {{-- Future notification settings --}}
                                            <div class="alert alert-light">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Coming Soon:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li>SMS notifications</li>
                                                    <li>Webhook integrations</li>
                                                    <li>Slack/Discord notifications</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                {{-- System Information --}}
                                <div class="col-lg-12 mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-info-circle"></i> System Information
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-4 mb-3">
                                                    <strong>Settings Storage:</strong>
                                                    <span class="badge bg-success">Database (Encrypted)</span>
                                                    <div class="form-text">
                                                        Settings and API keys are stored encrypted in the database for security and persistence.
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <strong>Application Version:</strong><br>
                                                    <span class="badge bg-primary">Admin Portal v2.0</span>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <strong>Environment:</strong><br>
                                                    <span class="badge bg-info">{{ config('app.env') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                    <!-- Step 2: Commerce & API Integrations -->
                    <div class="wizard-step" data-step="2">
                        <h3 class="mb-4"><i class="fas fa-plug text-primary"></i> Commerce & API Integrations</h3>
                        <p class="text-muted mb-4">Configure FarmOS, WooCommerce, Stripe, and other partner services.</p>
            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> All API keys are securely encrypted before being stored in the database.
                            </div>
                
                            <div class="row g-3">
                                {{-- FarmOS --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-tractor"></i> FarmOS Integration</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="farmos_api_url" class="form-label">FarmOS URL</label>
                                                <input type="url" class="form-control" id="farmos_api_url" name="farmos_api_url" 
                                                       value="{{ $settings['farmos_api_url'] ?? '' }}" placeholder="https://farm.example.com">
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="farmos_username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="farmos_username" name="farmos_username" 
                                                       value="{{ $settings['farmos_username'] ?? '' }}">
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="farmos_password" class="form-label">Password</label>
                                                <input type="password" class="form-control" id="farmos_password" name="farmos_password" 
                                                       value="{{ $settings['farmos_password'] ?? '' }}">
                                                <span class="toggle-password" data-target="#farmos_password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="farmos_oauth_client_id" class="form-label">OAuth Client ID</label>
                                                <input type="password" class="form-control" id="farmos_oauth_client_id" name="farmos_oauth_client_id" 
                                                       value="{{ $settings['farmos_oauth_client_id'] ?? '' }}">
                                                <span class="toggle-password" data-target="#farmos_oauth_client_id">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="farmos_oauth_client_secret" class="form-label">OAuth Client Secret</label>
                                                <input type="password" class="form-control" id="farmos_oauth_client_secret" name="farmos_oauth_client_secret" 
                                                       value="{{ $settings['farmos_oauth_client_secret'] ?? '' }}">
                                                <span class="toggle-password" data-target="#farmos_oauth_client_secret">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- WooCommerce --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fab fa-wordpress"></i> WooCommerce API</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="woocommerce_url" class="form-label">Store URL</label>
                                                <input type="url" class="form-control" id="woocommerce_url" name="woocommerce_url" 
                                                       value="{{ $settings['woocommerce_url'] ?? '' }}" placeholder="https://shop.example.com">
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="woocommerce_consumer_key" class="form-label">Consumer Key</label>
                                                <input type="password" class="form-control" id="woocommerce_consumer_key" name="woocommerce_consumer_key" 
                                                       value="{{ $settings['woocommerce_consumer_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#woocommerce_consumer_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="woocommerce_consumer_secret" class="form-label">Consumer Secret</label>
                                                <input type="password" class="form-control" id="woocommerce_consumer_secret" name="woocommerce_consumer_secret" 
                                                       value="{{ $settings['woocommerce_consumer_secret'] ?? '' }}">
                                                <span class="toggle-password" data-target="#woocommerce_consumer_secret">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Solidarity Pricing --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-balance-scale"></i> Solidarity Pricing</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info mb-3">
                                                <i class="fas fa-info-circle"></i> These percentages control the minimum and maximum solidarity pricing ranges across all products.
                                            </div>
                                            <div class="mb-3">
                                                <label for="solidarity_min_percent" class="form-label">
                                                    Minimum Price Percentage
                                                    <small class="text-muted">(% of recommended price)</small>
                                                </label>
                                                <input type="number" class="form-control" id="solidarity_min_percent" 
                                                       name="solidarity_min_percent" 
                                                       value="{{ $settings['solidarity_min_percent'] ?? 70 }}" 
                                                       min="0" max="100" step="1"
                                                       placeholder="70">
                                                <small class="text-muted d-block mt-1">
                                                    Default: 70% (customers pay at least 70% of recommended price)
                                                </small>
                                            </div>
                                            <div class="mb-0">
                                                <label for="solidarity_max_percent" class="form-label">
                                                    Maximum Price Percentage
                                                    <small class="text-muted">(% of recommended price)</small>
                                                </label>
                                                <input type="number" class="form-control" id="solidarity_max_percent" 
                                                       name="solidarity_max_percent" 
                                                       value="{{ $settings['solidarity_max_percent'] ?? 167 }}" 
                                                       min="100" max="500" step="1"
                                                       placeholder="167">
                                                <small class="text-muted d-block mt-1">
                                                    Default: 167% (customers can pay up to 167% to support others)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- MWF API --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-key"></i> MWF Shop Integration</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="mwf_api_base_url" class="form-label">API Base URL</label>
                                                <input type="url" class="form-control" id="mwf_api_base_url" name="mwf_api_base_url" 
                                                       value="{{ $settings['mwf_api_base_url'] ?? '' }}">
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="mwf_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="mwf_api_key" name="mwf_api_key" 
                                                       value="{{ $settings['mwf_api_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#mwf_api_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Google Maps --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-map-marked-alt"></i> Google Maps API</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 api-key-field">
                                                <label for="google_maps_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="google_maps_api_key" name="google_maps_api_key" 
                                                       value="{{ $settings['google_maps_api_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#google_maps_api_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Met Office APIs --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-cloud-sun-rain"></i> Met Office APIs</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 api-key-field">
                                                <label for="met_office_api_key" class="form-label">General API Key</label>
                                                <input type="password" class="form-control" id="met_office_api_key" name="met_office_api_key" 
                                                       value="{{ $settings['met_office_api_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#met_office_api_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="met_office_land_observations_key" class="form-label">Land Observations Key</label>
                                                <input type="password" class="form-control" id="met_office_land_observations_key" name="met_office_land_observations_key" 
                                                       value="{{ $settings['met_office_land_observations_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#met_office_land_observations_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="met_office_site_specific_key" class="form-label">Site Specific Forecast Key</label>
                                                <input type="password" class="form-control" id="met_office_site_specific_key" name="met_office_site_specific_key" 
                                                       value="{{ $settings['met_office_site_specific_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#met_office_site_specific_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="met_office_atmospheric_key" class="form-label">Atmospheric Models Key</label>
                                                <input type="password" class="form-control" id="met_office_atmospheric_key" name="met_office_atmospheric_key" 
                                                       value="{{ $settings['met_office_atmospheric_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#met_office_atmospheric_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="met_office_map_images_key" class="form-label">Map Images Key</label>
                                                <input type="password" class="form-control" id="met_office_map_images_key" name="met_office_map_images_key" 
                                                       value="{{ $settings['met_office_map_images_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#met_office_map_images_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- OpenWeather --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-cloud"></i> OpenWeather API</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 api-key-field">
                                                <label for="openweather_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="openweather_api_key" name="openweather_api_key" 
                                                       value="{{ $settings['openweather_api_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#openweather_api_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- AI APIs --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-brain"></i> AI Service APIs</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 api-key-field">
                                                <label for="huggingface_api_key" class="form-label">HuggingFace API Key</label>
                                                <input type="password" class="form-control" id="huggingface_api_key" name="huggingface_api_key" 
                                                       value="{{ $settings['huggingface_api_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#huggingface_api_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="claude_api_key" class="form-label">Claude API Key</label>
                                                <input type="password" class="form-control" id="claude_api_key" name="claude_api_key" 
                                                       value="{{ $settings['claude_api_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#claude_api_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Stripe --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fab fa-stripe"></i> Stripe Payment</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3 api-key-field">
                                                <label for="stripe_key" class="form-label">Publishable Key</label>
                                                <input type="password" class="form-control" id="stripe_key" name="stripe_key" 
                                                       value="{{ $settings['stripe_key'] ?? '' }}">
                                                <span class="toggle-password" data-target="#stripe_key">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mb-3 api-key-field">
                                                <label for="stripe_secret" class="form-label">Secret Key</label>
                                                <input type="password" class="form-control" id="stripe_secret" name="stripe_secret" 
                                                       value="{{ $settings['stripe_secret'] ?? '' }}">
                                                <span class="toggle-password" data-target="#stripe_secret">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 3CX Phone System --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-phone"></i> 3CX Phone System - CRM Integration</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="threecx_server_url" class="form-label">3CX Server URL</label>
                                                <input type="text" class="form-control" id="threecx_server_url" name="threecx_server_url" 
                                                       value="{{ $settings['threecx_server_url'] ?? '' }}" 
                                                       placeholder="https://pineappletelecoms2.3cx.uk:5001">
                                            </div>
                                            <div class="mb-3">
                                                <label for="threecx_extension" class="form-label">Extension Number</label>
                                                <input type="text" class="form-control" id="threecx_extension" name="threecx_extension" 
                                                       value="{{ $settings['threecx_extension'] ?? '' }}" 
                                                       placeholder="1036">
                                            </div>
                                            <div class="mb-3">
                                                <label for="threecx_did" class="form-label">DID Number (Public Phone)</label>
                                                <input type="text" class="form-control" id="threecx_did" name="threecx_did" 
                                                       value="{{ $settings['threecx_did'] ?? '' }}" 
                                                       placeholder="01522449610">
                                            </div>
                                            <div class="mb-3">
                                                <label for="threecx_mobile" class="form-label">Mobile Number</label>
                                                <input type="text" class="form-control" id="threecx_mobile" name="threecx_mobile" 
                                                       value="{{ $settings['threecx_mobile'] ?? '' }}" 
                                                       placeholder="07918526138">
                                            </div>
                                            <div class="mb-3">
                                                <label for="threecx_username" class="form-label">Username / Friendly Name</label>
                                                <input type="text" class="form-control" id="threecx_username" name="threecx_username" 
                                                       value="{{ $settings['threecx_username'] ?? '' }}" 
                                                       placeholder="martintaylor">
                                            </div>
                                            <div class="mb-3">
                                                <label for="threecx_crm_url" class="form-label">Open Contact URL</label>
                                                <input type="url" class="form-control" id="threecx_crm_url" name="threecx_crm_url" 
                                                       value="{{ $settings['threecx_crm_url'] ?? '' }}" 
                                                       placeholder="https://admin.middleworldfarms.org/admin/crm/contact?phone=%CallerNumber%&name=%CallerDisplayName%">
                                                <small class="text-muted">
                                                    <strong>Supported Variables:</strong><br>
                                                    <code>%CallerNumber%</code> - Incoming caller ID<br>
                                                    <code>%CallerDisplayName%</code> - Incoming display name
                                                </small>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="threecx_enable_tapi" name="threecx_enable_tapi" 
                                                           value="1" {{ ($settings['threecx_enable_tapi'] ?? 0) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="threecx_enable_tapi">
                                                        Enable TAPI Integration
                                                    </label>
                                                </div>
                                                <small class="text-muted">TAPI (Telephony Application Programming Interface) enables desktop app integration. Only needed for legacy Windows CRM software.</small>
                                            </div>
                                            <div class="alert alert-info mb-0">
                                                <small>
                                                    <strong>3CX Talk URL:</strong> {{ $settings['threecx_server_url'] ?? 'https://pineappletelecoms2.3cx.uk:5001' }}/{{ $settings['threecx_username'] ?? 'martintaylor' }}<br>
                                                    <strong>3CX Meet URL:</strong> {{ $settings['threecx_server_url'] ?? 'https://pineappletelecoms2.3cx.uk:5001' }}/meet/{{ $settings['threecx_username'] ?? 'martintaylor' }}<br>
                                                    <em class="text-muted mt-2 d-block">Configure in 3CX Management Console → Settings → Integration → CRM Integration → Set "Notify when" to "Ringing"</em>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Ollama --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-server"></i> Ollama (Local LLM)</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="ollama_url" class="form-label">Ollama Server URL</label>
                                                <input type="url" class="form-control" id="ollama_url" name="ollama_url" 
                                                       value="{{ $settings['ollama_url'] ?? '' }}" placeholder="http://localhost:8005">
                                            </div>
                                            <div class="mb-3">
                                                <label for="ollama_chat_model" class="form-label">Default Chat Model</label>
                                                <input type="text" class="form-control" id="ollama_chat_model" name="ollama_chat_model" 
                                                       value="{{ $settings['ollama_chat_model'] ?? '' }}" placeholder="phi3:latest">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                    <!-- Step 3: AI & Automation -->
                    <div class="wizard-step" data-step="3">
                        <h3 class="mb-4"><i class="fas fa-robot text-primary"></i> AI & Automation</h3>
                        <p class="text-muted mb-4">Configure language models, AI assistants, and knowledge ingestion settings.</p>
            <div class="alert alert-info">
                                <i class="fas fa-robot"></i> <strong>Local AI Models:</strong> Configure your Ollama instances for different AI-powered features across the farm management system.
                            </div>
                
                            <div class="row g-3">
                                {{-- Primary Model (Phi-3 3B - Port 8005) --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fas fa-rocket"></i> Primary Model (Phi 3)</h5>
                                            <small>Fast responses for chatbot & succession planner</small>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="ollama_primary_url" class="form-label">
                                                    <strong>Server URL</strong>
                                                </label>
                                                <input type="url" class="form-control" id="ollama_primary_url" name="ollama_primary_url" 
                                                       value="{{ $settings['ollama_primary_url'] ?? 'http://localhost:8005' }}" 
                                                       placeholder="http://localhost:8005">
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Primary Ollama server for quick responses
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="ollama_primary_model" class="form-label">
                                                    <strong>Model Name</strong>
                                                </label>
                                                <input type="text" class="form-control" id="ollama_primary_model" name="ollama_primary_model" 
                                                       value="{{ $settings['ollama_primary_model'] ?? 'phi3:latest' }}" 
                                                       placeholder="phi3:latest">
                                                <div class="form-text">
                                                    <i class="fas fa-tag"></i> Used for: Chatbot, Succession Planner, General Queries
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="ollama_primary_timeout" class="form-label">
                                                    <strong>Timeout (seconds)</strong>
                                                </label>
                                                <input type="number" class="form-control" id="ollama_primary_timeout" name="ollama_primary_timeout" 
                                                       value="{{ $settings['ollama_primary_timeout'] ?? '60' }}" 
                                                       min="30" max="600" step="10">
                                                <div class="form-text">
                                                    <i class="fas fa-clock"></i> Recommended: 60s for CPU-based processing
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ollama_primary_enabled" name="ollama_primary_enabled" 
                                                       value="1" {{ ($settings['ollama_primary_enabled'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ollama_primary_enabled">
                                                    <strong>Enable Primary Model</strong>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Processing Model (Mistral 7B - Port 8006) --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fas fa-cogs"></i> Processing Model (Mistral 7B)</h5>
                                            <small>Higher quality for background data processing</small>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="ollama_processing_url" class="form-label">
                                                    <strong>Server URL</strong>
                                                </label>
                                                <input type="url" class="form-control" id="ollama_processing_url" name="ollama_processing_url" 
                                                       value="{{ $settings['ollama_processing_url'] ?? 'http://localhost:8006' }}" 
                                                       placeholder="http://localhost:8006">
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> Slower but higher quality for batch jobs
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="ollama_processing_model" class="form-label">
                                                    <strong>Model Name</strong>
                                                </label>
                                                <input type="text" class="form-control" id="ollama_processing_model" name="ollama_processing_model" 
                                                       value="{{ $settings['ollama_processing_model'] ?? 'mistral:7b' }}" 
                                                       placeholder="mistral:7b">
                                                <div class="form-text">
                                                    <i class="fas fa-tag"></i> Used for: Data Analysis, Crop Planning, Complex Reports
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="ollama_processing_timeout" class="form-label">
                                                    <strong>Timeout (seconds)</strong>
                                                </label>
                                                <input type="number" class="form-control" id="ollama_processing_timeout" name="ollama_processing_timeout" 
                                                       value="{{ $settings['ollama_processing_timeout'] ?? '300' }}" 
                                                       min="60" max="1200" step="30">
                                                <div class="form-text">
                                                    <i class="fas fa-clock"></i> Recommended: 300s (5 min) - Slower but higher quality
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ollama_processing_enabled" name="ollama_processing_enabled" 
                                                       value="1" {{ ($settings['ollama_processing_enabled'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ollama_processing_enabled">
                                                    <strong>Enable Processing Model</strong>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- RAG Model (Port 8007) --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0"><i class="fas fa-database"></i> RAG Model</h5>
                                            <small>Retrieval-Augmented Generation for knowledge base</small>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="ollama_rag_url" class="form-label">
                                                    <strong>Server URL</strong>
                                                </label>
                                                <input type="url" class="form-control" id="ollama_rag_url" name="ollama_rag_url" 
                                                       value="{{ $settings['ollama_rag_url'] ?? 'http://localhost:8007' }}" 
                                                       placeholder="http://localhost:8007">
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> RAG server for context-aware responses
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="ollama_rag_model" class="form-label">
                                                    <strong>Model Name</strong>
                                                </label>
                                                <input type="text" class="form-control" id="ollama_rag_model" name="ollama_rag_model" 
                                                       value="{{ $settings['ollama_rag_model'] ?? 'llama2:latest' }}" 
                                                       placeholder="llama2:latest">
                                                <div class="form-text">
                                                    <i class="fas fa-tag"></i> Used for: Knowledge Base Queries, Document Search
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="ollama_rag_timeout" class="form-label">
                                                    <strong>Timeout (seconds)</strong>
                                                </label>
                                                <input type="number" class="form-control" id="ollama_rag_timeout" name="ollama_rag_timeout" 
                                                       value="{{ $settings['ollama_rag_timeout'] ?? '120' }}" 
                                                       min="30" max="600" step="15">
                                                <div class="form-text">
                                                    <i class="fas fa-clock"></i> Recommended: 120s (2 min) for document retrieval
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ollama_rag_enabled" name="ollama_rag_enabled" 
                                                       value="1" {{ ($settings['ollama_rag_enabled'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ollama_rag_enabled">
                                                    <strong>Enable RAG Model</strong>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- AI Feature Toggles --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-toggle-on"></i> AI Features</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ai_chatbot_enabled" name="ai_chatbot_enabled" 
                                                           value="1" {{ ($settings['ai_chatbot_enabled'] ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ai_chatbot_enabled">
                                                        <strong>AI Chatbot</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-comments"></i> Enable AI-powered chat assistant
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ai_succession_planner" name="ai_succession_planner" 
                                                           value="1" {{ ($settings['ai_succession_planner'] ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ai_succession_planner">
                                                        <strong>Succession Planner</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-calendar-alt"></i> AI-powered crop succession planning
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ai_harvest_planning" name="ai_harvest_planning" 
                                                           value="1" {{ ($settings['ai_harvest_planning'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ai_harvest_planning">
                                                        <strong>Harvest Planning</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-tractor"></i> AI-optimized harvest schedules
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ai_crop_recommendations" 
                                                           name="ai_crop_recommendations" value="1" {{ ($settings['ai_crop_recommendations'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ai_crop_recommendations">
                                                        <strong>Crop Recommendations</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-seedling"></i> AI suggestions for crop rotation and planting
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="ai_data_analysis" 
                                                           name="ai_data_analysis" value="1" {{ ($settings['ai_data_analysis'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="ai_data_analysis">
                                                        <strong>Automated Data Analysis</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-chart-line"></i> Use Mistral for background data processing
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- RAG Ingestion Settings --}}
                                <div class="col-12 mb-4">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0"><i class="fas fa-file-import"></i> RAG Knowledge Base Ingestion</h5>
                                            <small>Configure automatic document ingestion for your RAG system</small>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="rag_ingestion_enabled" class="form-label">
                                                            <strong>Auto Ingestion</strong>
                                                        </label>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="rag_ingestion_enabled" name="rag_ingestion_enabled" 
                                                                   value="1" {{ ($settings['rag_ingestion_enabled'] ?? false) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="rag_ingestion_enabled">
                                                                Enable automatic document ingestion
                                                            </label>
                                                        </div>
                                                        <div class="form-text">
                                                            <i class="fas fa-sync-alt"></i> Automatically process new documents added to watch folders
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="rag_watch_directory" class="form-label">
                                                            <strong>Watch Directory</strong>
                                                        </label>
                                                        <input type="text" class="form-control" id="rag_watch_directory" name="rag_watch_directory" 
                                                               value="{{ $settings['rag_watch_directory'] ?? '/opt/sites/admin.middleworldfarms.org/storage/app/rag/documents' }}" 
                                                               placeholder="/path/to/documents">
                                                        <div class="form-text">
                                                            <i class="fas fa-folder"></i> Directory to monitor for new documents
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="rag_processed_directory" class="form-label">
                                                            <strong>Processed Directory</strong>
                                                        </label>
                                                        <input type="text" class="form-control" id="rag_processed_directory" name="rag_processed_directory" 
                                                               value="{{ $settings['rag_processed_directory'] ?? '/opt/sites/admin.middleworldfarms.org/storage/app/rag/processed' }}" 
                                                               placeholder="/path/to/processed">
                                                        <div class="form-text">
                                                            <i class="fas fa-check-circle"></i> Where to move documents after processing
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="rag_chunk_size" class="form-label">
                                                            <strong>Chunk Size (characters)</strong>
                                                        </label>
                                                        <input type="number" class="form-control" id="rag_chunk_size" name="rag_chunk_size" 
                                                               value="{{ $settings['rag_chunk_size'] ?? '1000' }}" 
                                                               min="500" max="4000" step="100">
                                                        <div class="form-text">
                                                            <i class="fas fa-cut"></i> Size of text chunks for embedding (default: 1000)
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="rag_chunk_overlap" class="form-label">
                                                            <strong>Chunk Overlap (characters)</strong>
                                                        </label>
                                                        <input type="number" class="form-control" id="rag_chunk_overlap" name="rag_chunk_overlap" 
                                                               value="{{ $settings['rag_chunk_overlap'] ?? '200' }}" 
                                                               min="0" max="500" step="50">
                                                        <div class="form-text">
                                                            <i class="fas fa-layer-group"></i> Overlap between chunks for context (default: 200)
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="rag_supported_formats" class="form-label">
                                                            <strong>Supported File Formats</strong>
                                                        </label>
                                                        <input type="text" class="form-control" id="rag_supported_formats" name="rag_supported_formats" 
                                                               value="{{ $settings['rag_supported_formats'] ?? 'pdf,txt,docx,md' }}" 
                                                               placeholder="pdf,txt,docx,md">
                                                        <div class="form-text">
                                                            <i class="fas fa-file-alt"></i> Comma-separated list of file extensions
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="rag_embedding_model" class="form-label">
                                                            <strong>Embedding Model</strong>
                                                        </label>
                                                        <select class="form-select" id="rag_embedding_model" name="rag_embedding_model">
                                                            <option value="nomic-embed-text" {{ ($settings['rag_embedding_model'] ?? 'nomic-embed-text') == 'nomic-embed-text' ? 'selected' : '' }}>nomic-embed-text (Recommended)</option>
                                                            <option value="all-minilm" {{ ($settings['rag_embedding_model'] ?? '') == 'all-minilm' ? 'selected' : '' }}>all-minilm</option>
                                                            <option value="mxbai-embed-large" {{ ($settings['rag_embedding_model'] ?? '') == 'mxbai-embed-large' ? 'selected' : '' }}>mxbai-embed-large</option>
                                                        </select>
                                                        <div class="form-text">
                                                            <i class="fas fa-brain"></i> Model used for generating embeddings
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="rag_ingestion_schedule" class="form-label">
                                                            <strong>Ingestion Schedule</strong>
                                                        </label>
                                                        <select class="form-select" id="rag_ingestion_schedule" name="rag_ingestion_schedule">
                                                            <option value="*/5 * * * *" {{ ($settings['rag_ingestion_schedule'] ?? '') == '*/5 * * * *' ? 'selected' : '' }}>Every 5 minutes</option>
                                                            <option value="*/15 * * * *" {{ ($settings['rag_ingestion_schedule'] ?? '*/15 * * * *') == '*/15 * * * *' ? 'selected' : '' }}>Every 15 minutes (Recommended)</option>
                                                            <option value="*/30 * * * *" {{ ($settings['rag_ingestion_schedule'] ?? '') == '*/30 * * * *' ? 'selected' : '' }}>Every 30 minutes</option>
                                                            <option value="0 * * * *" {{ ($settings['rag_ingestion_schedule'] ?? '') == '0 * * * *' ? 'selected' : '' }}>Hourly</option>
                                                            <option value="0 0 * * *" {{ ($settings['rag_ingestion_schedule'] ?? '') == '0 0 * * *' ? 'selected' : '' }}>Daily at midnight</option>
                                                        </select>
                                                        <div class="form-text">
                                                            <i class="fas fa-clock"></i> How often to check for new documents
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle"></i> 
                                                <strong>How it works:</strong> Place documents in the watch directory. The system will automatically:
                                                <ol class="mb-0 mt-2">
                                                    <li>Extract text from supported file formats</li>
                                                    <li>Split content into overlapping chunks</li>
                                                    <li>Generate embeddings using the selected model</li>
                                                    <li>Store vectors in the PostgreSQL database</li>
                                                    <li>Move processed files to the processed directory</li>
                                                </ol>
                                            </div>

                                            <hr class="my-4">

                                            {{-- Drag and Drop Upload Zone --}}
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <strong><i class="fas fa-cloud-upload-alt"></i> Quick Upload Documents</strong>
                                                </label>
                                       <div id="rag-dropzone" class="rag-dropzone border border-2 border-dashed rounded p-5 text-center">
                                                    <i class="fas fa-file-upload fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">Drag & Drop Files Here</h5>
                                                    <p class="text-muted mb-3">or click to browse</p>
                                                    <button type="button" class="btn btn-primary" id="browse-files-btn">
                                                        <i class="fas fa-folder-open"></i> Browse Files
                                                    </button>
                                                    <input type="file" id="rag-file-input" multiple accept=".pdf,.txt,.docx,.doc,.md" 
                                                           style="display: none;">
                                                    <p class="text-muted mt-3 mb-0">
                                                        <small>Supported: PDF, TXT, DOCX, MD • Max 10MB per file</small>
                                                    </p>
                                                </div>
                                            </div>

                                            {{-- Upload Progress --}}
                                            <div id="upload-progress" class="d-none">
                                                <div class="progress mb-3" style="height: 25px;">
                                                    <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                                         role="progressbar" style="width: 0%">0%</div>
                                                </div>
                                                <p id="upload-status" class="text-muted mb-0">
                                                    <i class="fas fa-spinner fa-spin"></i> Uploading...
                                                </p>
                                            </div>

                                            {{-- Upload Results --}}
                                            <div id="upload-results" class="mt-3"></div>

                                            <hr class="my-4">

                                            {{-- Document Status List --}}
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <label class="form-label mb-0">
                                                        <strong><i class="fas fa-list"></i> Document Status</strong>
                                                    </label>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="refresh-docs-btn">
                                                        <i class="fas fa-sync-alt"></i> Refresh
                                                    </button>
                                                </div>

                                                <div class="card">
                                                    <div class="card-header">
                                                        <ul class="nav nav-tabs card-header-tabs" id="docs-tabs" role="tablist">
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" 
                                                                        data-bs-target="#pending-docs" type="button" role="tab">
                                                                    <i class="fas fa-clock text-warning"></i> Pending 
                                                                    <span class="badge bg-warning text-dark" id="pending-count">0</span>
                                                                </button>
                                                            </li>
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link" id="processed-tab" data-bs-toggle="tab" 
                                                                        data-bs-target="#processed-docs" type="button" role="tab">
                                                                    <i class="fas fa-check-circle text-success"></i> Processed 
                                                                    <span class="badge bg-success" id="processed-count">0</span>
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="tab-content" id="docs-tab-content">
                                                            {{-- Pending Documents --}}
                                                            <div class="tab-pane fade show active" id="pending-docs" role="tabpanel">
                                                                <div id="pending-docs-list">
                                                                    <div class="text-center text-muted py-4">
                                                                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                                                                        <p class="mt-2">Loading documents...</p>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            {{-- Processed Documents --}}
                                                            <div class="tab-pane fade" id="processed-docs" role="tabpanel">
                                                                <div id="processed-docs-list">
                                                                    <div class="text-center text-muted py-4">
                                                                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                                                                        <p class="mt-2">Loading documents...</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                    <!-- Step 4: Printing & Documents -->
                    <div class="wizard-step" data-step="4">
                        <h3 class="mb-4"><i class="fas fa-print text-primary"></i> Printing & Documents</h3>
                        <p class="text-muted mb-4">Configure packing slips, printer defaults, and document settings.</p>
            <div class="row g-3">
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-print"></i> Print Settings</h5>
                                        </div>
                                        <div class="card-body">
                                            {{-- Packing Slips Per Page --}}
                                            <div class="mb-3">
                                                <label for="packing_slips_per_page" class="form-label">
                                                    <strong>Packing Slips Per Page</strong>
                                                    <small class="text-muted">(Perfect for paper guillotine cutting)</small>
                                                </label>
                                                <select class="form-select" id="packing_slips_per_page" name="packing_slips_per_page">
                                                    <option value="1" {{ ($settings['packing_slips_per_page'] ?? 2) == 1 ? 'selected' : '' }}>1 per page (Full size)</option>
                                                    <option value="2" {{ ($settings['packing_slips_per_page'] ?? 2) == 2 ? 'selected' : '' }}>2 per page (Half size)</option>
                                                    <option value="4" {{ ($settings['packing_slips_per_page'] ?? 2) == 4 ? 'selected' : '' }}>4 per page (Quarter size)</option>
                                                    <option value="6" {{ ($settings['packing_slips_per_page'] ?? 2) == 6 ? 'selected' : '' }}>6 per page (Compact)</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-cut"></i> Higher numbers = more slips per sheet to cut with guillotine
                                                </div>
                                            </div>

                                            {{-- Auto Print Mode --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="auto_print_mode" name="auto_print_mode" 
                                                           value="1" {{ ($settings['auto_print_mode'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="auto_print_mode">
                                                        <strong>Auto Print Mode</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-rocket"></i> Skip preview and send directly to printer queue (recommended for Epson printers)
                                                </div>
                                            </div>

                                            {{-- Paper Size --}}
                                            <div class="mb-3">
                                                <label for="default_printer_paper_size" class="form-label">
                                                    <strong>Default Paper Size</strong>
                                                </label>
                                                <select class="form-select" id="default_printer_paper_size" name="default_printer_paper_size">
                                                    <option value="A4" {{ ($settings['default_printer_paper_size'] ?? 'A4') == 'A4' ? 'selected' : '' }}>A4 (210 × 297 mm)</option>
                                                    <option value="Letter" {{ ($settings['default_printer_paper_size'] ?? 'A4') == 'Letter' ? 'selected' : '' }}>Letter (8.5 × 11 in)</option>
                                                </select>
                                            </div>

                                            {{-- Company Logo --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="print_company_logo" 
                                                           name="print_company_logo" value="1" 
                                                           {{ ($settings['print_company_logo'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="print_company_logo">
                                                        <strong>Include Company Logo</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-image"></i> Print farm logo on packing slips
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 bg-light">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Printing Tips</h5>
                                        </div>
                                        <div class="card-body">
                                            <h6><i class="fas fa-check-circle text-success"></i> Best Practices:</h6>
                                            <ul class="mb-3">
                                                <li><strong>2 per page</strong> - Best for standard A4/Letter with guillotine cutting</li>
                                                <li><strong>4 per page</strong> - Ideal for smaller box labels</li>
                                                <li><strong>Auto Print</strong> - Works best with dedicated thermal/Epson printers</li>
                                            </ul>

                                            <h6><i class="fas fa-info-circle text-primary"></i> Paper Guillotine:</h6>
                                            <p class="small mb-0">
                                                Multiple slips per page are designed to align perfectly with paper guillotine cutting guides,
                                                allowing you to quickly cut multiple packing slips at once without measuring.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: POS & Hardware -->
                    <div class="wizard-step" data-step="5">
                        <h3 class="mb-4"><i class="fas fa-cash-register text-primary"></i> POS & Hardware</h3>
                        <p class="text-muted mb-4">Configure point-of-sale payments, scales, printers, and card readers.</p>
            <div class="row g-3">
                                <div class="col-lg-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-cog"></i> POS Settings
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            {{-- Receipt Printing --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="pos_auto_print_receipt" 
                                                           name="pos_auto_print_receipt" value="1" 
                                                           {{ (old('pos_auto_print_receipt', $settings['pos_auto_print_receipt'] ?? false)) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="pos_auto_print_receipt">
                                                        <i class="fas fa-receipt"></i> Auto-print receipts
                                                    </label>
                                                </div>
                                                <small class="text-muted ms-4">Automatically print receipt after each sale</small>
                                            </div>

                                            {{-- Receipt Email --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="pos_email_receipt" 
                                                           name="pos_email_receipt" value="1" 
                                                           {{ (old('pos_email_receipt', $settings['pos_email_receipt'] ?? false)) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="pos_email_receipt">
                                                        <i class="fas fa-envelope"></i> Offer email receipts
                                                    </label>
                                                </div>
                                                <small class="text-muted ms-4">Allow customers to receive receipts by email</small>
                                            </div>

                                            {{-- Tax Rate --}}
                                            <div class="mb-3">
                                                <label for="pos_tax_rate" class="form-label">
                                                    <i class="fas fa-percentage"></i> Default Tax Rate
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="pos_tax_rate" 
                                                           name="pos_tax_rate" 
                                                           value="{{ old('pos_tax_rate', $settings['pos_tax_rate'] ?? '0') }}" 
                                                           step="0.01" min="0" max="100">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <small class="text-muted">Default tax rate for products (0 for no tax)</small>
                                            </div>

                                            {{-- Currency Symbol --}}
                                            <div class="mb-3">
                                                <label for="pos_currency_symbol" class="form-label">
                                                    <i class="fas fa-pound-sign"></i> Currency Symbol
                                                </label>
                                                <input type="text" class="form-control" id="pos_currency_symbol" 
                                                       name="pos_currency_symbol" 
                                                       value="{{ old('pos_currency_symbol', $settings['pos_currency_symbol'] ?? '£') }}" 
                                                       maxlength="5">
                                                <small class="text-muted">Symbol to display for prices (£, $, €, etc.)</small>
                                            </div>

                                            {{-- Low Stock Alert --}}
                                            <div class="mb-3">
                                                <label for="pos_low_stock_threshold" class="form-label">
                                                    <i class="fas fa-exclamation-triangle"></i> Low Stock Alert Threshold
                                                </label>
                                                <input type="number" class="form-control" id="pos_low_stock_threshold" 
                                                       name="pos_low_stock_threshold" 
                                                       value="{{ old('pos_low_stock_threshold', $settings['pos_low_stock_threshold'] ?? '5') }}" 
                                                       min="0">
                                                <small class="text-muted">Alert when product stock falls below this number</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card Reader Integration Settings --}}
                                <div class="col-lg-12 mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-credit-card"></i> Card Reader Integration
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted mb-4">
                                                Configure card payment terminal integration for your POS system. Choose from manual entry, mobile card readers, or integrated payment terminals.
                                            </p>

                                            <div class="row g-3">
                                                {{-- Card Reader Type --}}
                                                <div class="col-lg-6 mb-3">
                                                    <label for="pos_card_reader_type" class="form-label">
                                                        <i class="fas fa-terminal"></i> Card Reader Type
                                                    </label>
                                                    <select class="form-select" id="pos_card_reader_type" name="pos_card_reader_type">
                                                        <option value="manual" {{ old('pos_card_reader_type', $settings['pos_card_reader_type'] ?? 'manual') == 'manual' ? 'selected' : '' }}>
                                                            Manual Entry (No Reader)
                                                        </option>
                                                        <option value="stripe_terminal" {{ old('pos_card_reader_type', $settings['pos_card_reader_type'] ?? 'manual') == 'stripe_terminal' ? 'selected' : '' }} {{ empty($settings['stripe_key']) || empty($settings['stripe_secret']) ? 'disabled' : '' }}>
                                                            Stripe Terminal (BBPOS WisePad, Verifone){{ empty($settings['stripe_key']) || empty($settings['stripe_secret']) ? ' - Configure Stripe API keys first' : '' }}
                                                        </option>
                                                    </select>
                                                    <small class="text-muted">
                                                        Choose your card payment method
                                                    </small>
                                                </div>

                                            </div>

                                            {{-- Stripe Terminal Settings --}}
                                            <div id="stripe-terminal-settings" class="card-reader-section" style="display: none;">
                                                <div class="border-top pt-2">
                                                    <h6 class="mb-3"><i class="fab fa-stripe text-info"></i> Stripe Terminal Configuration</h6>
                                                    
                                                    @php
                                                        $stripeKey = config('services.stripe.key');
                                                        $hasStripeKey = !empty($stripeKey);
                                                    @endphp
                                                    
                                                    @if($hasStripeKey)
                                                        <div class="alert alert-success mb-3">
                                                            <i class="fas fa-check-circle"></i>
                                                            <strong>Stripe API keys configured!</strong> Using keys from main Stripe settings.
                                                        </div>
                                                    @else
                                                        <div class="alert alert-danger mb-3">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            <strong>Error:</strong> Stripe API keys not found! Configure them in the <strong>API Settings</strong> tab first.
                                                        </div>
                                                    @endif
                                                    
                                                    <div class="row g-3">
                                                        <div class="col-lg-6 mb-3">
                                                            <label for="pos_stripe_publishable_key" class="form-label">Stripe Publishable Key</label>
                                                            <input type="text" class="form-control bg-light" id="pos_stripe_publishable_key" 
                                                                   name="pos_stripe_publishable_key" 
                                                                   value="{{ $stripeKey ?? old('pos_stripe_publishable_key', $settings['pos_stripe_publishable_key'] ?? '') }}"
                                                                   placeholder="pk_live_..." readonly>
                                                            <small class="text-muted"><i class="fas fa-info-circle"></i> Auto-populated from main Stripe settings (read-only)</small>
                                                        </div>
                                                        <div class="col-lg-6 mb-3">
                                                            <label for="pos_currency" class="form-label">Currency Code</label>
                                                            <input type="text" class="form-control" id="pos_currency" 
                                                                   name="pos_currency" 
                                                                   value="{{ old('pos_currency', $settings['pos_currency'] ?? 'gbp') }}"
                                                                   placeholder="gbp" maxlength="3">
                                                            <small class="text-muted">Currency code (gbp, usd, eur, etc.)</small>
                                                        </div>
                                                    </div>
                                                    <div class="row g-3">
                                                        <div class="col-lg-12 mb-3">
                                                            <label for="pos_stripe_location_id" class="form-label">
                                                                <i class="fas fa-map-marker-alt"></i> Stripe Terminal Location ID <span class="badge bg-primary">Required for Physical Readers</span>
                                                            </label>
                                                            <input type="text" class="form-control" id="pos_stripe_location_id" 
                                                                   name="pos_stripe_location_id" 
                                                                   value="{{ old('pos_stripe_location_id', $settings['pos_stripe_location_id'] ?? '') }}"
                                                                   placeholder="tml_...">
                                                            <small class="text-muted">
                                                                <i class="fas fa-lightbulb"></i> <strong>How to find:</strong> 
                                                                Go to <a href="https://dashboard.stripe.com/terminal/locations" target="_blank" class="text-decoration-none">Stripe Dashboard → Terminal → Locations</a>, 
                                                                copy the Location ID (starts with <code>tml_</code>)
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="alert alert-info mb-0">
                                                        <i class="fas fa-info-circle"></i>
                                                        <strong>Stripe Payment Intents:</strong> Works with card form on screen (all devices), Tap to Pay (iPhone XS+/iPad), or physical Stripe Terminal readers (WisePOS E, BBPOS WisePad, Verifone P400)
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Square Settings --}}
                                            <div id="square-reader-settings" class="card-reader-section mt-2" style="display: none;">
                                                <div class="border-top pt-2">
                                                    <h6 class="mb-3"><i class="fas fa-square text-dark"></i> Square Reader Configuration</h6>
                                                    <div class="row g-3">
                                                        <div class="col-lg-12 mb-3">
                                                            <label for="pos_square_app_id" class="form-label">Square Application ID</label>
                                                            <input type="text" class="form-control" id="pos_square_app_id" 
                                                                   name="pos_square_app_id" 
                                                                   value="{{ old('pos_square_app_id', $settings['pos_square_app_id'] ?? '') }}"
                                                                   placeholder="sq0idp-...">
                                                        </div>
                                                    </div>
                                                    <div class="alert alert-info mb-0">
                                                        <i class="fas fa-info-circle"></i>
                                                        Use Square Point of Sale app on mobile device. Transactions will open Square app for payment processing.
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Web NFC Settings --}}
                                            <div id="web-nfc-settings" class="card-reader-section mt-2" style="display: none;">
                                                <div class="border-top pt-2">
                                                    <div class="alert alert-warning mb-0">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <strong>Web NFC (Experimental):</strong> Only works on Android Chrome for contactless (tap-to-pay) cards. 
                                                        Not supported on iOS/iPadOS. Requires payment processor backend integration.
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- WebSocket Terminal Settings --}}
                                            <div id="websocket-terminal-settings" class="card-reader-section mt-2" style="display: none;">
                                                <div class="border-top pt-2">
                                                    <h6 class="mb-3"><i class="fas fa-server"></i> WebSocket Payment Terminal</h6>
                                                    <div class="row g-3">
                                                        <div class="col-lg-12 mb-3">
                                                            <label for="pos_card_websocket_url" class="form-label">WebSocket Server URL</label>
                                                            <input type="text" class="form-control" id="pos_card_websocket_url" 
                                                                   name="pos_card_websocket_url" 
                                                                   value="{{ old('pos_card_websocket_url', $settings['pos_card_websocket_url'] ?? 'ws://localhost:8766') }}"
                                                                   placeholder="ws://localhost:8766">
                                                            <small class="text-muted">URL of your payment terminal service</small>
                                                        </div>
                                                    </div>
                                                    <div class="alert alert-info mb-0">
                                                        <i class="fas fa-info-circle"></i>
                                                        Run a backend service that communicates with your payment terminal (PIN pad, etc.)
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Manual Entry Info --}}
                                            <div id="manual-card-entry" class="card-reader-section mt-2">
                                                <div class="alert alert-secondary mb-0">
                                                    <i class="fas fa-keyboard"></i>
                                                    <strong>Manual Entry:</strong> Mark payments as "Card" payment type manually. 
                                                    Use separate mobile card reader apps (Stripe Terminal, Square, SumUp) for actual payment processing.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Email Receipts Settings --}}
                                <div class="col-lg-12 mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-envelope"></i> Email Receipts
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted mb-4">
                                                Configure automatic email receipt delivery for completed transactions.
                                            </p>

                                            <div class="row g-3">
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label d-block">
                                                        <i class="fas fa-envelope"></i> Enable Email Receipts
                                                    </label>
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" id="pos_email_receipts_enabled"
                                                               name="pos_email_receipts_enabled" value="1"
                                                               {{ (old('pos_email_receipts_enabled', $settings['pos_email_receipts_enabled'] ?? true)) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="pos_email_receipts_enabled">
                                                            Send email receipts automatically after payment
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label d-block">
                                                        <i class="fas fa-user-check"></i> Customer Email Required
                                                    </label>
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" id="pos_require_customer_email"
                                                               name="pos_require_customer_email" value="1"
                                                               {{ (old('pos_require_customer_email', $settings['pos_require_customer_email'] ?? false)) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="pos_require_customer_email">
                                                            Require customer email for POS transactions
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Email Receipts:</strong> Customers will receive professional receipts via email after successful payments.
                                                No physical printing required - reduces costs and environmental impact.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                    <!-- Step 6: Communications & Email -->
                    <div class="wizard-step" data-step="6">
                        <h3 class="mb-4"><i class="fas fa-envelope text-primary"></i> Communications & Email</h3>
                        <p class="text-muted mb-4">Configure 3CX phone integration and email client settings.</p>
            <div class="row g-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-envelope"></i> Email Account Management
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                Manage your email accounts and settings through the dedicated Email Account Manager.
                                                You can add multiple email accounts, configure IMAP/SMTP settings, and organize your emails.
                                            </div>

                                            <div class="text-center py-4">
                                                <a href="{{ route('admin.email.accounts') }}" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-cog"></i> Open Email Account Manager
                                                </a>
                                                <p class="mt-3 text-muted">
                                                    <small>Configure multiple email accounts, test connections, and manage your email client settings</small>
                                                </p>
                                            </div>

                                            <hr>

                                            <h6>Quick Email Settings</h6>

                                            {{-- Email Client Enabled --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="email_client_enabled"
                                                           name="email_client_enabled" value="1"
                                                           {{ ($settings['email_client_enabled'] ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="email_client_enabled">
                                                        <strong>Enable Email Client</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-toggle-on"></i> Enable the email client functionality in the admin panel
                                                </div>
                                            </div>

                                            {{-- Auto-sync --}}
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="email_auto_sync"
                                                           name="email_auto_sync" value="1"
                                                           {{ ($settings['email_auto_sync'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="email_auto_sync">
                                                        <strong>Auto-sync Emails</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-sync"></i> Automatically sync emails from your accounts
                                                </div>
                                            </div>

                                            {{-- Sync Interval --}}
                                            <div class="mb-3">
                                                <label for="email_sync_interval" class="form-label">
                                                    <strong>Sync Interval (minutes)</strong>
                                                </label>
                                                <input type="number" class="form-control" id="email_sync_interval" name="email_sync_interval"
                                                       value="{{ $settings['email_sync_interval'] ?? 15 }}" min="5" max="1440">
                                                <div class="form-text">
                                                    <i class="fas fa-clock"></i> How often to check for new emails (5-1440 minutes)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>

                    <!-- Step 7: Advanced & Diagnostics -->
                    <div class="wizard-step" data-step="7">
                        <h3 class="mb-4"><i class="fas fa-sliders-h text-primary"></i> Advanced & Diagnostics</h3>
                        <p class="text-muted mb-4">Configure developer tools and low-level system settings.</p>
            <div class="row g-3">
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Advanced Settings</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                These settings should only be modified if you know what you are doing.
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" 
                                                           value="1" {{ ($settings['debug_mode'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="debug_mode">
                                                        <strong>Debug Mode</strong>
                                                    </label>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-bug"></i> Enable detailed error reporting and logging
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- POS Settings Tab --}}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wizard Navigation -->
                <div class="wizard-navigation">
                    <button type="button" class="btn btn-outline-secondary" id="wizard-prev" disabled>
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="wizard-next">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-success d-none" id="wizard-save">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wizard Navigation
    let currentStep = 1;
    const totalSteps = 7;
    const wizardSteps = document.querySelectorAll('.wizard-step');
    const stepIndicators = document.querySelectorAll('.step-item');
    const progressBar = document.querySelector('.progress-bar');
    const prevBtn = document.getElementById('wizard-prev');
    const nextBtn = document.getElementById('wizard-next');
    const saveBtn = document.getElementById('wizard-save');

    function updateWizard() {
        // Hide all steps
        wizardSteps.forEach(step => step.classList.remove('active'));

        // Show current step
        const currentStepEl = document.querySelector(`.wizard-step[data-step="${currentStep}"]`);
        if (currentStepEl) {
            currentStepEl.classList.add('active');
        }

        // Update step indicators
        stepIndicators.forEach((indicator, index) => {
            const stepNum = index + 1;
            indicator.classList.toggle('active', stepNum === currentStep);
            indicator.classList.toggle('completed', stepNum < currentStep);
        });

        // Update progress bar
        const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
        progressBar.style.width = progress + '%';

        // Update navigation buttons
        prevBtn.disabled = currentStep === 1;
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-block';
        saveBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';

        // Update button text
        nextBtn.textContent = currentStep === totalSteps - 1 ? 'Review & Save' : 'Next';

        // Scroll to top of wizard content
        const wizardContent = document.querySelector('.wizard-content');
        if (wizardContent) {
            wizardContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function goToStep(step) {
        if (step >= 1 && step <= totalSteps) {
            currentStep = step;
            updateWizard();
        }
    }

    function nextStep() {
        if (currentStep < totalSteps) {
            goToStep(currentStep + 1);
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            goToStep(currentStep - 1);
        }
    }

    // Event listeners
    if (nextBtn) {
        nextBtn.addEventListener('click', nextStep);
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', prevStep);
    }

    // Step indicator clicks
    stepIndicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            goToStep(index + 1);
        });
    });

    // Initialize wizard
    updateWizard();

    // Handle URL hash for direct step navigation
    const hash = window.location.hash;
    if (hash && hash.startsWith('#step-')) {
        const stepNum = parseInt(hash.replace('#step-', ''));
        if (stepNum >= 1 && stepNum <= totalSteps) {
            goToStep(stepNum);
        }
    }

    // Password toggle functionality
    document.querySelectorAll('.toggle-password').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const input = document.querySelector(this.dataset.target);
            if (!input) {
                return;
            }

            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });

    // RAG Drag & Drop File Upload
    const dropzone = document.getElementById('rag-dropzone');
    const fileInput = document.getElementById('rag-file-input');
    const browseBtn = document.getElementById('browse-files-btn');
    const uploadProgress = document.getElementById('upload-progress');
    const uploadProgressBar = document.getElementById('upload-progress-bar');
    const uploadStatus = document.getElementById('upload-status');
    const uploadResults = document.getElementById('upload-results');

    if (dropzone && fileInput) {
        // Click to browse
        browseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });

        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFiles(this.files);
            }
        });

        // Drag and drop events
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.style.backgroundColor = '#e3f2fd';
            this.style.borderColor = '#2196F3';
        });

        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.style.backgroundColor = '#f8f9fa';
            this.style.borderColor = '#dee2e6';
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.style.backgroundColor = '#f8f9fa';
            this.style.borderColor = '#dee2e6';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFiles(files);
            }
        });

        function handleFiles(files) {
            const formData = new FormData();
            let validFiles = 0;
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['.pdf', '.txt', '.docx', '.doc', '.md'];

            // Validate files
            Array.from(files).forEach(file => {
                const ext = '.' + file.name.split('.').pop().toLowerCase();
                if (!allowedTypes.includes(ext)) {
                    showError(`File "${file.name}" is not supported. Allowed types: ${allowedTypes.join(', ')}`);
                    return;
                }
                if (file.size > maxSize) {
                    showError(`File "${file.name}" exceeds 10MB limit.`);
                    return;
                }
                formData.append('files[]', file);
                validFiles++;
            });

            if (validFiles === 0) {
                return;
            }

            // Show progress
            uploadProgress.classList.remove('d-none');
            uploadResults.innerHTML = '';

            // Upload files
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    uploadProgressBar.style.width = percentComplete + '%';
                    uploadProgressBar.textContent = percentComplete + '%';
                }
            });

            xhr.addEventListener('load', function() {
                uploadProgress.classList.add('d-none');

                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        showSuccess(response.message || `Successfully uploaded ${validFiles} file(s). They will be processed shortly.`);
                        fileInput.value = ''; // Clear input
                    } catch (e) {
                        showSuccess(`Successfully uploaded ${validFiles} file(s). They will be processed shortly.`);
                        fileInput.value = '';
                    }
                } else {
                    showError('Upload failed. Please try again.');
                }
            });

            xhr.addEventListener('error', function() {
                uploadProgress.classList.add('d-none');
                showError('Upload failed. Please check your connection and try again.');
            });

            // Add CSRF token
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

            xhr.open('POST', '/admin/rag/upload', true);
            xhr.send(formData);
        }

        function showSuccess(message) {
            uploadResults.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        function showError(message) {
            uploadResults.innerHTML += `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        // Load RAG documents status
        function loadRagDocuments() {
            const pendingList = document.getElementById('pending-docs-list');
            const processedList = document.getElementById('processed-docs-list');
            const pendingCount = document.getElementById('pending-count');
            const processedCount = document.getElementById('processed-count');

            fetch('/admin/rag/documents', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update pending documents
                    const pending = data.documents.pending || [];
                    pendingCount.textContent = pending.length;

                    if (pending.length === 0) {
                        pendingList.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-inbox"></i> No pending documents</div>';
                    } else {
                        pendingList.innerHTML = pending.map(doc => `
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <i class="fas fa-file-${getFileIcon(doc.extension)} text-warning me-2"></i>
                                    <strong>${doc.name}</strong>
                                    <small class="text-muted d-block ms-4">
                                        ${formatFileSize(doc.size)} • Uploaded ${formatDate(doc.created_at)}
                                    </small>
                                </div>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock"></i> Pending
                                </span>
                            </div>
                        `).join('');
                    }

                    // Update processed documents
                    const processed = data.documents.processed || [];
                    processedCount.textContent = processed.length;

                    if (processed.length === 0) {
                        processedList.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-inbox"></i> No processed documents</div>';
                    } else {
                        processedList.innerHTML = processed.map(doc => `
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <i class="fas fa-file-${getFileIcon(doc.extension)} text-success me-2"></i>
                                    <strong>${doc.name}</strong>
                                    <small class="text-muted d-block ms-4">
                                        ${formatFileSize(doc.size)} • Processed ${formatDate(doc.processed_at)}
                                        ${doc.chunks ? ` • ${doc.chunks} chunks` : ''}
                                    </small>
                                </div>
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Processed
                                </span>
                            </div>
                        `).join('');
                    }
                } else {
                    pendingList.innerHTML = '<div class="alert alert-danger mb-0">Failed to load documents</div>';
                    processedList.innerHTML = '<div class="alert alert-danger mb-0">Failed to load documents</div>';
                }
            })
            .catch(error => {
                console.error('Error loading documents:', error);
                pendingList.innerHTML = '<div class="alert alert-danger mb-0">Error loading documents</div>';
                processedList.innerHTML = '<div class="alert alert-danger mb-0">Error loading documents</div>';
            });
        }

        // Helper function to get file icon based on extension
        function getFileIcon(extension) {
            const icons = {
                'pdf': 'pdf',
                'doc': 'word',
                'docx': 'word',
                'txt': 'alt',
                'md': 'alt'
            };
            return icons[extension] || 'alt';
        }

        // Helper function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Helper function to format date
        function formatDate(dateString) {
            if (!dateString) return 'Unknown';
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} min ago`;
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            return date.toLocaleDateString();
        }

        // Refresh button handler
        const refreshBtn = document.getElementById('refresh-docs-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                icon.classList.add('fa-spin');
                loadRagDocuments();
                setTimeout(() => icon.classList.remove('fa-spin'), 1000);
            });
        }

        // Load documents on page load and after successful upload
        if (document.getElementById('pending-docs-list')) {
            loadRagDocuments();

            // Reload documents after successful upload
            const originalShowSuccess = showSuccess;
            showSuccess = function(message) {
                originalShowSuccess(message);
                setTimeout(loadRagDocuments, 1000); // Refresh list after 1 second
            };
        }
    }

    // Card Reader Type Selector
    const cardReaderSelect = document.getElementById('pos_card_reader_type');
    if (cardReaderSelect) {
        function toggleCardReaderSettings() {
            const selectedType = cardReaderSelect.value;
            const selectedOption = cardReaderSelect.querySelector(`option[value="${selectedType}"]`);
            
            // Check if selected option is disabled
            if (selectedOption && selectedOption.disabled) {
                alert('Please configure Stripe API keys in the API Settings tab first before selecting Stripe Terminal.');
                cardReaderSelect.value = 'manual'; // Reset to manual
                toggleCardReaderSettings();
                return;
            }

            // Hide all card reader sections
            document.querySelectorAll('.card-reader-section').forEach(section => {
                section.style.display = 'none';
            });

            // Show the appropriate section
            switch(selectedType) {
                case 'stripe_terminal':
                    document.getElementById('stripe-terminal-settings').style.display = 'block';
                    break;
                case 'manual':
                default:
                    document.getElementById('manual-card-entry').style.display = 'block';
                    break;
            }
        }

        // Prevent selecting disabled options
        cardReaderSelect.addEventListener('change', function(e) {
            const selectedOption = this.querySelector(`option[value="${this.value}"]`);
            if (selectedOption && selectedOption.disabled) {
                e.preventDefault();
                alert('Please configure Stripe API keys in the API Settings tab first before selecting Stripe Terminal.');
                this.value = 'manual'; // Reset to manual
                toggleCardReaderSettings();
            }
        });

        // Initialize on page load
        toggleCardReaderSettings();

        // Update when selection changes (for valid options)
        cardReaderSelect.addEventListener('change', toggleCardReaderSettings);
    }
});
</script>
@endsection

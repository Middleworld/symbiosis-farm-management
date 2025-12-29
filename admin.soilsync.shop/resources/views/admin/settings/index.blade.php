@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('styles')
<style>
    .settings-accordion .accordion-button {
        font-size: 1.1rem;
        font-weight: 600;
        padding: 1.25rem 1.5rem;
    }
    
    .settings-accordion .accordion-button:not(.collapsed) {
        background-color: #0d6efd;
        color: white;
    }
    
    .settings-accordion .accordion-button .badge {
        margin-left: auto;
        margin-right: 1rem;
    }
    
    .section-save-btn {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 1rem;
        border-top: 2px solid #dee2e6;
        margin: 0 -1rem -1rem -1rem;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
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

    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <strong>Tip:</strong> Each section can be saved independently. Expand a section, make your changes, and click the Save button at the bottom of that section.
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="accordion settings-accordion" id="settingsAccordion">
            
            <!-- Section 1: Company & Farm -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#section-company">
                        <i class="fas fa-building me-2"></i> Company & Farm Operations
                        <span class="badge bg-primary ms-auto me-3">Basic Info</span>
                    </button>
                </h2>
                <div id="section-company" class="accordion-collapse collapse show" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.company-farm')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Company & Farm Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Deliveries & WooCommerce -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-deliveries">
                        <i class="fas fa-truck me-2"></i> Deliveries & WooCommerce
                        <span class="badge bg-success ms-auto me-3">Orders & Routes</span>
                    </button>
                </h2>
                <div id="section-deliveries" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.deliveries-woocommerce')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Delivery Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: FarmOS Integration -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-farmos">
                        <i class="fas fa-leaf me-2"></i> FarmOS Integration
                        <span class="badge bg-success ms-auto me-3">Farm Data</span>
                    </button>
                </h2>
                <div id="section-farmos" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.farmos')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save FarmOS Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: AI & Automation -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-ai">
                        <i class="fas fa-robot me-2"></i> AI & Automation
                        <span class="badge bg-warning text-dark ms-auto me-3">Intelligence</span>
                    </button>
                </h2>
                <div id="section-ai" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.ai-automation')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save AI Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Weather Services -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-weather">
                        <i class="fas fa-cloud-sun me-2"></i> Weather Services
                        <span class="badge bg-info ms-auto me-3">Climate Data</span>
                    </button>
                </h2>
                <div id="section-weather" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.weather')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Weather Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6: Communications -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-comms">
                        <i class="fas fa-comments me-2"></i> Communications
                        <span class="badge bg-secondary ms-auto me-3">Email & Phone</span>
                    </button>
                </h2>
                <div id="section-comms" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.communications')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Communication Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 7: Payments -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-payments">
                        <i class="fas fa-credit-card me-2"></i> Payments & Billing
                        <span class="badge bg-success ms-auto me-3">Stripe & MWF</span>
                    </button>
                </h2>
                <div id="section-payments" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.payments')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Payment Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 8: Branding -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-branding">
                        <i class="fas fa-palette me-2"></i> Branding & Identity
                        <span class="badge bg-primary ms-auto me-3">Colors & Logos</span>
                    </button>
                </h2>
                <div id="section-branding" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.branding')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Branding Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 9: Printing -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-printing">
                        <i class="fas fa-print me-2"></i> Printing & Documents
                        <span class="badge bg-dark ms-auto me-3">Labels & Reports</span>
                    </button>
                </h2>
                <div id="section-printing" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.printing')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Printing Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 10: POS & Hardware -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-pos">
                        <i class="fas fa-cash-register me-2"></i> POS & Hardware
                        <span class="badge bg-warning text-dark ms-auto me-3">Point of Sale</span>
                    </button>
                </h2>
                <div id="section-pos" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        @include('admin.settings.sections.pos-hardware')
                        <div class="section-save-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save POS Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Global Save Button -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-lg btn-success">
                            <i class="fas fa-save"></i> Save All Settings
                        </button>
                        <a href="{{ route('admin.settings.reset') }}" class="btn btn-lg btn-outline-danger ms-2" 
                           onclick="return confirm('Are you sure you want to reset all settings to defaults?')">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

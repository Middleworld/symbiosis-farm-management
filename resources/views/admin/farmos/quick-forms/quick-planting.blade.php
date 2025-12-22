@extends('layouts.app')

@section('title', 'Quick Planting Form')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Quick Form Header -->
                    <div class="text-center mb-4">
                        <h3><i class="fas fa-rocket text-primary"></i> Quick Planting Form</h3>
                        <p class="text-muted">Record a planting with optional seeding, transplanting, and harvest logs</p>
                    </div>

                    <form id="quickForm" method="POST" action="{{ route('admin.farmos.succession-planning.submit-log') }}">
                        @csrf
                        <input type="hidden" name="log_type" value="quick">

                        <!-- Succession Info -->
                        @include('admin.farmos.quick-forms._partials._succession_info')

                        <!-- Season Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-calendar-alt text-primary"></i> Season</h5>
                                <div class="mb-3">
                                    <label for="season" class="form-label">What season(s) will this be part of? *</label>
                                    <input type="text" class="form-control" id="season" name="season"
                                           value="{{ request('season', date('Y') . ' Succession') }}" required
                                           placeholder="e.g., 2025, 2025 Summer, 2025 Succession">
                                    <div class="form-text">This will be prepended to the plant asset name for organization.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Crops/Varieties Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-leaf text-success"></i> Crop/Variety</h5>
                                <div id="crops-container">
                                    <div class="crop-item mb-3">
                                        <div class="row">
                                            <div class="col-md-10">
                                                <input type="text" class="form-control crop-input" name="crops[0]"
                                                       value="{{ request('variety_name', request('crop_name', '')) }}"
                                                       placeholder="Enter crop/variety (e.g., Lettuce, Carrot, Tomato)" required>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-outline-danger remove-crop" style="display: none;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button type="button" id="add-crop" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add another crop/variety
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <label for="crop_count" class="form-label">If this is a mix, how many crops/varieties are included?</label>
                                    <select class="form-select" id="crop_count" name="crop_count">
                                        <option value="1" selected>1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Plant Asset Name -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-tag text-info"></i> Plant Asset Name</h5>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="custom_name" name="custom_name" value="1">
                                        <label class="form-check-label" for="custom_name">
                                            Customize plant asset name
                                        </label>
                                    </div>
                                    <div class="form-text">The plant asset name will default to "[Season] [Crop]" but can be customized if desired.</div>
                                </div>
                                <div id="custom-name-container" style="display: none;">
                                    <label for="name" class="form-label">Plant asset name</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                           value="{{ request('season', date('Y') . ' Succession') }} {{ request('variety_name', request('crop_name', '')) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Log Types Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-tasks text-warning"></i> What events would you like to record?</h5>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input log-type-checkbox" type="checkbox" id="seeding_enabled" name="log_types[seeding]" value="seeding" checked>
                                        <label class="form-check-label" for="seeding_enabled">
                                            <strong>Seeding</strong> - Record when seeds are planted
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input log-type-checkbox" type="checkbox" id="transplanting_enabled" name="log_types[transplanting]" value="transplanting">
                                        <label class="form-check-label" for="transplanting_enabled">
                                            <strong>Transplanting</strong> - Record when seedlings are transplanted
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input log-type-checkbox" type="checkbox" id="harvest_enabled" name="log_types[harvest]" value="harvest">
                                        <label class="form-check-label" for="harvest_enabled">
                                            <strong>Harvest</strong> - Record harvest dates and quantities
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logs Container -->
                        <div id="logs-container">
                            <!-- Hidden partials that will be shown/hidden dynamically -->
                            <div id="seeding-section" class="log-section" style="display: block;">
                                <h6><i class="fas fa-seedling text-success"></i> Seeding</h6>
                                @include('admin.farmos.quick-forms._partials._seeding_fields')
                            </div>
                            <div id="transplanting-section" class="log-section" style="display: none;">
                                <h6><i class="fas fa-shipping-fast text-warning"></i> Transplanting</h6>
                                @include('admin.farmos.quick-forms._partials._transplant_fields')
                            </div>
                            <div id="harvest-section" class="log-section" style="display: none;">
                                <h6><i class="fas fa-shopping-basket text-danger"></i> Harvest</h6>
                                @include('admin.farmos.quick-forms._partials._harvest_fields')
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> Create Planting Records
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quick-form-tabs {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.quick-form-tabs .nav-tabs {
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.quick-form-tabs .nav-tabs .nav-link {
    border: none;
    border-radius: 0;
    color: #6c757d;
}

.quick-form-tabs .nav-tabs .nav-link.active {
    background-color: #fff;
    color: #495057;
    border-bottom: 2px solid #007bff;
}

.quick-form-tabs .tab-content {
    padding: 1rem;
}

.log-section {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.log-section h6 {
    color: #495057;
    margin-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.5rem;
}

.inline-quantity {
    display: flex;
    gap: 0.5rem;
    align-items: flex-end;
}

.inline-quantity .form-control,
.inline-quantity .form-select {
    flex: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cropIndex = 1;

    // Add crop functionality
    document.getElementById('add-crop').addEventListener('click', function() {
        const container = document.getElementById('crops-container');
        const cropItem = document.createElement('div');
        cropItem.className = 'crop-item mb-3';
        cropItem.innerHTML = `
            <div class="row">
                <div class="col-md-10">
                    <input type="text" class="form-control crop-input" name="crops[${cropIndex}]" placeholder="Enter crop/variety">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger remove-crop">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(cropItem);
        cropIndex++;

        // Show remove buttons if more than one crop
        updateRemoveButtons();
    });

    // Remove crop functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-crop') || e.target.closest('.remove-crop')) {
            e.target.closest('.crop-item').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const cropItems = document.querySelectorAll('.crop-item');
        const removeButtons = document.querySelectorAll('.remove-crop');
        removeButtons.forEach(btn => {
            btn.style.display = cropItems.length > 1 ? 'block' : 'none';
        });
    }

    // Custom name toggle
    document.getElementById('custom_name').addEventListener('change', function() {
        const container = document.getElementById('custom-name-container');
        container.style.display = this.checked ? 'block' : 'none';
    });

    // Log type checkboxes - show/hide sections based on FarmOS behavior
    document.querySelectorAll('.log-type-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateLogsContainer);
    });

    // Initial load - show seeding by default since it's checked
    updateLogsContainer();

    function updateLogsContainer() {
        const seedingChecked = document.getElementById('seeding_enabled').checked;
        const transplantingChecked = document.getElementById('transplanting_enabled').checked;
        const harvestChecked = document.getElementById('harvest_enabled').checked;

        // Show/hide sections based on checkboxes (FarmOS style)
        document.getElementById('seeding-section').style.display = seedingChecked ? 'block' : 'none';
        document.getElementById('transplanting-section').style.display = transplantingChecked ? 'block' : 'none';
        document.getElementById('harvest-section').style.display = harvestChecked ? 'block' : 'none';

        // Show warning if no logs selected
        const warningDiv = document.getElementById('no-logs-warning');
        if (!seedingChecked && !transplantingChecked && !harvestChecked) {
            if (!warningDiv) {
                const container = document.getElementById('logs-container');
                const warning = document.createElement('div');
                warning.id = 'no-logs-warning';
                warning.className = 'alert alert-warning';
                warning.textContent = 'Please select at least one log type to record.';
                container.appendChild(warning);
            }
        } else if (warningDiv) {
            warningDiv.remove();
        }
    }
});

// Auto-populate dates if transplant date is set
document.getElementById('transplant_date')?.addEventListener('change', function() {
    const transplantDate = new Date(this.value);
    if (transplantDate && !document.getElementById('harvest_date').value) {
        // Estimate harvest date (typically 60-90 days after transplant for many crops)
        const harvestDate = new Date(transplantDate);
        harvestDate.setDate(harvestDate.getDate() + 75); // 75 days average
        document.getElementById('harvest_date').value = harvestDate.toISOString().split('T')[0];
    }
});
</script>
@endsection
@extends('layouts.app')

@section('title', 'Planting Chart - farmOS Integration')

@section('page-title', 'Planting Chart')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center w-100 gap-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary btn-sm" id="applyFilters">
                <i class="fas fa-filter"></i> Apply
            </button>
            <button class="btn btn-outline-secondary btn-sm" id="clearFilters">
                <i class="fas fa-times"></i> Clear
            </button>
            <button class="btn btn-success btn-sm" id="refreshData">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-outline-info btn-sm" id="extendBackward">
                <i class="fas fa-backward"></i> Previous Year
            </button>
            <button class="btn btn-outline-info btn-sm" id="extendForward">
                <i class="fas fa-forward"></i> Next Year
            </button>
            <div class="btn-group" role="group">
                <button class="btn btn-outline-warning btn-sm" id="zoomOut" title="Zoom Out">
                    <i class="fas fa-search-minus"></i>
                </button>
                <button class="btn btn-outline-warning btn-sm" id="zoomReset" title="Reset Zoom">
                    <i class="fas fa-compress-arrows-alt"></i>
                </button>
                <button class="btn btn-outline-warning btn-sm" id="zoomIn" title="Zoom In">
                    <i class="fas fa-search-plus"></i>
                </button>
            </div>
        </div>
        <div class="text-center flex-grow-1">
            <p class="lead mb-0">Visual timeline of crop cycles across all farm blocks</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="startDate" class="mb-0 text-nowrap small fw-bold">Start:</label>
                <input type="date" class="form-control form-control-sm" id="startDate" 
                       value="{{ now()->subYear()->startOfYear()->format('Y-m-d') }}" style="width: 140px;">
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="endDate" class="mb-0 text-nowrap small fw-bold">End:</label>
                <input type="date" class="form-control form-control-sm" id="endDate" 
                       value="{{ now()->addYear()->endOfYear()->format('Y-m-d') }}" style="width: 140px;">
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.farmos.dashboard') }}">
                    <i class="fas fa-tractor"></i> farmOS
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="fas fa-seedling"></i> Planting Chart
            </li>
        </ol>
    </nav>

    <!-- Legend Card -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <small class="text-muted fw-bold">Legend:</small>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #28a745; width: 16px; height: 16px; border-radius: 3px; margin-right: 5px;"></div>
                    <small>Seeding</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #007bff; width: 16px; height: 16px; border-radius: 3px; margin-right: 5px;"></div>
                    <small>Growing</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #ffc107; width: 16px; height: 16px; border-radius: 3px; margin-right: 5px;"></div>
                    <small>Harvest</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Planting Chart Container -->
    <div class="card">
        <div class="card-header p-0" style="border-radius: 8px 8px 0 0; overflow: hidden;">
            <div class="hedgerow-divider" style="margin: 0; border-radius: 8px 8px 0 0;"></div>
        </div>
        <div class="card-body">
            <!-- Block Tabs -->
            <ul class="nav nav-tabs mb-3" id="blockTabs" role="tablist">
                <!-- Tabs will be generated dynamically by JavaScript -->
            </ul>
            
            <!-- Loading State -->
            <div id="chartLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading chart data...</span>
                </div>
                <p class="text-muted mt-2">Loading planting timeline...</p>
            </div>
            
            <!-- Tab Content -->
            <div class="tab-content" id="blockTabContent" style="display: none;">
                <!-- Tab panes will be generated dynamically by JavaScript -->
            </div>
            
            <!-- No Data State -->
            <div id="noDataMessage" class="text-center py-5" style="display: none;">
                <i class="fas fa-seedling fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Planting Data Available</h5>
                <p class="text-muted">No plantings found in farmOS for the selected date range. Try expanding the date range or add some plantings!</p>
                <div class="mt-4">
                    <button class="btn btn-primary me-2" onclick="window.location.href='{{ route('admin.farmos.succession-planning') }}'">
                        <i class="fas fa-plus"></i> Create Succession Plan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title text-success" id="activePlantings">-</h5>
                    <p class="card-text text-muted">Active Plantings</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary" id="upcomingHarvests">-</h5>
                    <p class="card-text text-muted">Upcoming Harvests</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title text-info" id="totalBeds">-</h5>
                    <p class="card-text text-muted">Total Beds</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title text-info" id="totalBlocks">{{ count($locations ?? []) }}</h5>
                    <p class="card-text text-muted">Farm Blocks</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentData = null;
let zoomLevel = 1.0; // Default zoom level (10px per day)
const MIN_ZOOM = 0.5; // 5px per day (zoomed out)
const MAX_ZOOM = 3.0; // 30px per day (zoomed in)

// Load data from server-side variables
const serverData = @json($chartData ?? []);
const usingFarmOSData = @json($usingFarmOSData ?? false);

// Initialize on page load
document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Debug: Log the data we're working with
        console.log('=== Planting Chart Debug ===');
        console.log('Server Data:', serverData);
        console.log('Using FarmOS Data:', usingFarmOSData);
        console.log('Data type:', typeof serverData);
        console.log('Data keys:', Object.keys(serverData || {}));
        
        // Check if serverData is valid and has content
        let hasValidData = false;
        if (serverData && typeof serverData === 'object') {
            if (Array.isArray(serverData)) {
                hasValidData = serverData.length > 0;
                console.log('Server data is an array with length:', serverData.length);
            } else {
                hasValidData = Object.keys(serverData).length > 0;
                console.log('Server data is an object with keys:', Object.keys(serverData));
            }
        }
        
        if (hasValidData) {
            console.log('Rendering timeline with server data...');
            currentData = { data: serverData };
            renderTimelineChart(serverData);
            updateStats(serverData);
            showChart();
        } else {
            // No server data - fetch from FarmOS API instead of using test data
            console.log('No valid server data, fetching from FarmOS API...');
            showLoading(true);
            
            try {
                const startDateInput = document.getElementById('startDate').value;
                const endDateInput = document.getElementById('endDate').value;
                
                const response = await fetch(`/admin/farmos/succession-planning/bed-occupancy?start_date=${startDateInput}&end_date=${endDateInput}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`API request failed: ${response.status}`);
                }

                const apiResponse = await response.json();
                
                if (apiResponse.success && apiResponse.data) {
                    const farmOSData = transformFarmOSBedData(apiResponse.data);
                    currentData = { data: farmOSData };
                    renderTimelineChart(farmOSData);
                    updateStats(farmOSData);
                    showChart();
                } else {
                    throw new Error(apiResponse.message || 'No data returned from API');
                }
            } catch (error) {
                console.error('Failed to fetch FarmOS data on page load:', error);
                showError('Unable to load planting data: ' + error.message);
            } finally {
                showLoading(false);
            }
        }
        
        setupEventListeners();
    } catch (error) {
        console.error('Error initializing planting chart:', error);
        showLoading(false);
        showError('Failed to initialize planting chart: ' + error.message);
    }
});

function setupEventListeners() {
    // Filter controls
    const applyBtn = document.getElementById('applyFilters');
    const clearBtn = document.getElementById('clearFilters');
    const refreshBtn = document.getElementById('refreshData');
    const extendBackBtn = document.getElementById('extendBackward');
    const extendForwardBtn = document.getElementById('extendForward');
    
    if (applyBtn) applyBtn.addEventListener('click', applyFilters);
    if (clearBtn) clearBtn.addEventListener('click', clearFilters);
    if (refreshBtn) refreshBtn.addEventListener('click', initializeChart);
    if (extendBackBtn) extendBackBtn.addEventListener('click', extendDateRangeBackward);
    if (extendForwardBtn) extendForwardBtn.addEventListener('click', extendDateRangeForward);
    
    // Setup horizontal scrolling for timeline
    setupTimelineScrolling();
}

function setupTimelineScrolling() {
    // Add horizontal scrolling with mouse wheel and zoom with Ctrl+wheel
    document.addEventListener('wheel', function(e) {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (timelineContainer && timelineContainer.contains(e.target)) {
            
            // Ctrl+wheel = Zoom
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                
                const delta = e.deltaY > 0 ? -0.1 : 0.1; // Zoom out/in
                const newZoom = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, zoomLevel + delta));
                
                if (newZoom !== zoomLevel) {
                    zoomLevel = newZoom;
                    console.log('🔍 Zoom level:', zoomLevel.toFixed(2));
                    initializeChart(); // Redraw with new zoom
                }
                return;
            }
            
            // Check if this should be timeline horizontal scrolling
            let shouldScrollTimeline = false;
            let scrollAmount = 0;
            
            // Use horizontal wheel (trackpad horizontal swipe)
            if (e.deltaX && Math.abs(e.deltaX) > 0) {
                shouldScrollTimeline = true;
                scrollAmount = e.deltaX > 0 ? 200 : -200;
            }
            // Use Shift + vertical wheel as alternative
            else if (e.shiftKey && e.deltaY && Math.abs(e.deltaY) > 0) {
                shouldScrollTimeline = true;
                scrollAmount = e.deltaY > 0 ? 200 : -200;
            }
            
            if (shouldScrollTimeline) {
                e.preventDefault();
                timelineContainer.scrollLeft += scrollAmount;
                console.log('Timeline horizontal scroll:', scrollAmount, 'scrollLeft:', timelineContainer.scrollLeft);
            }
            // Otherwise allow normal vertical scrolling
        }
    }, { passive: false });
    
    // Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (timelineContainer && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
            e.preventDefault();
            const scrollAmount = e.key === 'ArrowRight' ? 200 : -200;
            timelineContainer.scrollLeft += scrollAmount;
        }
    });
    
    // Add touch/drag scrolling for mobile
    let isScrolling = false;
    let startX = 0;
    let scrollLeft = 0;
    
    document.addEventListener('mousedown', function(e) {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (timelineContainer && timelineContainer.contains(e.target)) {
            isScrolling = true;
            startX = e.pageX - timelineContainer.offsetLeft;
            scrollLeft = timelineContainer.scrollLeft;
            timelineContainer.style.cursor = 'grabbing';
        }
    });
    
    document.addEventListener('mousemove', function(e) {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (!isScrolling || !timelineContainer) return;
        
        e.preventDefault();
        const x = e.pageX - timelineContainer.offsetLeft;
        const walk = (x - startX) * 3; // Increased scroll speed multiplier from 2 to 3
        timelineContainer.scrollLeft = scrollLeft - walk;
    });
    
    document.addEventListener('mouseup', function() {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (timelineContainer) {
            isScrolling = false;
            timelineContainer.style.cursor = 'grab';
        }
    });
}

// Timeline navigation functions
function scrollToYear(year) {
    const timelineContainer = document.querySelector('.horizontal-timeline');
    if (!timelineContainer) return;
    
    const targetDate = new Date(year, 6, 1); // July 1st (middle of the year)
    
    // Get timeline start date from the date input
    const startDateInput = document.getElementById('startDate');
    const timelineStart = startDateInput ? new Date(startDateInput.value) : new Date(2024, 0, 1);
    
    // Calculate days from timeline start to target date
    const daysFromStart = Math.floor((targetDate - timelineStart) / (1000 * 60 * 60 * 24));
    const dayWidth = 10 * zoomLevel;
    const position = daysFromStart * dayWidth;
    
    // Center the target date on screen
    timelineContainer.scrollTo({
        left: Math.max(0, position - timelineContainer.clientWidth / 2),
        behavior: 'smooth'
    });
    
    console.log(`📅 Scrolling to year ${year}: ${targetDate.toLocaleDateString()}, position: ${position}px`);
}

function scrollToSeason(season) {
    const timelineContainer = document.querySelector('.horizontal-timeline');
    if (!timelineContainer) return;
    
    const now = new Date();
    const currentYear = now.getFullYear();
    let targetDate;
    
    // Define season start dates for current year
    switch(season) {
        case 'spring': // March 1
            targetDate = new Date(currentYear, 2, 1);
            break;
        case 'summer': // June 1
            targetDate = new Date(currentYear, 5, 1);
            break;
        case 'autumn': // September 1
            targetDate = new Date(currentYear, 8, 1);
            break;
        case 'winter': // December 1 (or current year's winter if before March)
            targetDate = now.getMonth() < 2 ? new Date(currentYear - 1, 11, 1) : new Date(currentYear, 11, 1);
            break;
        default:
            targetDate = now;
    }
    
    // Get timeline start date from the date input
    const startDateInput = document.getElementById('startDate');
    const timelineStart = startDateInput ? new Date(startDateInput.value) : new Date(currentYear, 0, 1);
    
    // Calculate days from timeline start to target date
    const daysFromStart = Math.floor((targetDate - timelineStart) / (1000 * 60 * 60 * 24));
    const dayWidth = 10 * zoomLevel;
    const position = daysFromStart * dayWidth;
    
    // Center the target date on screen
    timelineContainer.scrollTo({
        left: Math.max(0, position - timelineContainer.clientWidth / 2),
        behavior: 'smooth'
    });
    
    console.log(`📅 Scrolling to ${season}: ${targetDate.toLocaleDateString()}, position: ${position}px`);
}

async function initializeChart() {
    showLoading(true);
    
    try {
        // Get date range for FarmOS API call
        const startDateInput = document.getElementById('startDate').value;
        const endDateInput = document.getElementById('endDate').value;
        
        console.log('🌐 Fetching real FarmOS bed occupancy data from API...');
        console.log('Date range:', startDateInput, 'to', endDateInput);
        
        // Call the FarmOS bed occupancy API (same as succession planner timeline)
        const response = await fetch(`/admin/farmos/succession-planning/bed-occupancy?start_date=${startDateInput}&end_date=${endDateInput}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`FarmOS API request failed: ${response.status} ${response.statusText}`);
        }

        const apiResponse = await response.json();

        if (apiResponse.error || (apiResponse.success !== undefined && apiResponse.success === false)) {
            throw new Error(apiResponse.message || apiResponse.error || 'FarmOS API error');
        }

        console.log('✅ Successfully fetched real FarmOS bed data:', {
            beds: apiResponse.data?.beds?.length || 0,
            plantings: apiResponse.data?.plantings?.length || 0
        });

        // Transform FarmOS bed occupancy data to planting chart format
        const farmOSData = transformFarmOSBedData(apiResponse.data);
        
        // Apply client-side filtering if needed
        let filteredData = farmOSData;
        const locationFilter = document.getElementById('locationFilter').value;
        const cropTypeFilter = document.getElementById('cropTypeFilter').value;
        
        if (locationFilter || cropTypeFilter) {
            filteredData = applyFiltersToData(farmOSData, {
                location: locationFilter,
                crop_type: cropTypeFilter,
                start_date: startDateInput,
                end_date: endDateInput
            });
        }
        
        // Update display
        currentData = { data: filteredData };
        
        if (filteredData && Object.keys(filteredData).length > 0) {
            renderTimelineChart(filteredData);
            updateStats(filteredData);
            showChart();
        } else {
            showNoData();
        }
        
    } catch (error) {
        console.error('Failed to fetch FarmOS bed occupancy data:', error);
        showError('Failed to load planting data: ' + error.message);
    } finally {
        showLoading(false);
    }
}

function applyFiltersToData(data, filters) {
    const filtered = {};
    
    Object.keys(data).forEach(location => {
        // Filter by location
        if (filters.location && location !== filters.location) {
            return;
        }
        
        const activities = data[location] || [];
        const filteredActivities = activities.filter(activity => {
            // Filter by crop type
            if (filters.crop_type && activity.crop !== filters.crop_type) {
                return false;
            }
            
            // Filter by date range
            if (filters.start_date && activity.end < filters.start_date) {
                return false;
            }
            
            if (filters.end_date && activity.start > filters.end_date) {
                return false;
            }
            
            return true;
        });
        
        if (filteredActivities.length > 0) {
            filtered[location] = filteredActivities;
        }
    });
    
    return filtered;
}

/**
 * Transform FarmOS bed occupancy data to planting chart format
 * Same structure as succession planner timeline uses
 */
function transformFarmOSBedData(bedData) {
    console.log('🔄 Transforming FarmOS bed data to planting chart format...');
    
    if (!bedData || !bedData.beds || bedData.beds.length === 0) {
        console.warn('No FarmOS bed data to transform');
        return {};
    }

    const chartData = {};
    const bedsWithoutProperBlock = [];
    
    // List of non-growing locations to exclude from planting chart
    const excludedLocations = [
        'Middle World Farms CIC',
        'Ducks',
        'Duck House',
        'Propagation'
    ];

    // Initialize all beds with empty arrays (excluding non-growing locations)
    bedData.beds.forEach(bed => {
        const bedName = bed.name || 'Unnamed Bed';
        
        // Skip excluded locations (office, duck house, etc.)
        if (excludedLocations.includes(bedName)) {
            console.log(`⏭️ Skipping non-growing location: "${bedName}"`);
            return;
        }
        
        chartData[bedName] = [];
        
        // Also add block-level entry if bed has a block
        if (bed.block) {
            // Skip "Block Unknown" or "Block Unkown" entries
            if (bed.block.match(/Block\s+(Unknown|Unkown)/i)) {
                bedsWithoutProperBlock.push({
                    bedName: bedName,
                    assignedBlock: bed.block,
                    bedId: bed.id || 'N/A'
                });
                return;
            }
            
            if (!chartData[bed.block]) {
                chartData[bed.block] = [];
            }
        }
    });

    // Report beds that need proper block assignment
    if (bedsWithoutProperBlock.length > 0) {
        console.warn('⚠️ BEDS WITHOUT PROPER BLOCK ASSIGNMENT:');
        console.table(bedsWithoutProperBlock);
        console.log('📋 These beds need to be assigned to proper blocks in FarmOS:');
        bedsWithoutProperBlock.forEach(bed => {
            console.log(`   - Bed: "${bed.bedName}" (ID: ${bed.bedId}) → Currently: "${bed.assignedBlock}"`);
        });
    }

    // Populate location and crop type dropdowns from API data
    populateDropdownsFromAPIData(bedData);

    // Add plantings/activities to beds
    if (bedData.plantings && bedData.plantings.length > 0) {
        bedData.plantings.forEach(planting => {
            const bedName = planting.bed_id || planting.bed_name || planting.location || 'Unknown';
            
            // Skip plantings for excluded locations
            if (excludedLocations.includes(bedName)) {
                return;
            }
            
            // Skip if bed doesn't exist in chartData (filtered out earlier)
            if (!chartData[bedName]) {
                return;
            }

            const cropName = planting.crop_name || planting.crop || 'Unknown Crop';
            const varietyName = planting.variety || '';
            
            // Determine the bed occupation start date (transplant or direct seed, NOT seeding in trays)
            const bedStartDate = planting.transplant_date || planting.start_date || planting.seeding_date;
            const harvestStartDate = planting.harvest_date || planting.harvest_start_date || planting.harvest_start;
            let harvestEndDate = planting.harvest_end_date || planting.harvest_end || planting.end_date || planting.bed_end_date;
            
            // If we have harvest start but no harvest end, calculate a reasonable harvest window
            if (harvestStartDate && !harvestEndDate) {
                const harvestStart = new Date(harvestStartDate);
                // Default harvest window: 3 weeks for most crops
                // Can be adjusted based on crop type in the future
                const defaultHarvestDays = 21; // 3 weeks
                harvestStart.setDate(harvestStart.getDate() + defaultHarvestDays);
                harvestEndDate = harvestStart.toISOString().split('T')[0];
                console.log(`📅 ${cropName}: No harvest end date, using ${defaultHarvestDays}-day window: ${harvestStartDate} → ${harvestEndDate}`);
            }
            
            if (!bedStartDate) {
                console.warn('Planting has no bed start date, skipping:', planting);
                return;
            }
            
            // Create GROWING activity (from transplant/direct seed to harvest start)
            if (harvestStartDate) {
                const growingActivity = {
                    id: planting.id ? `${planting.id}_growing` : `planting_${Date.now()}_${Math.random()}_growing`,
                    type: 'growing',
                    crop: cropName.toLowerCase(),
                    variety: varietyName,
                    location: bedName,
                    start: bedStartDate,
                    end: harvestStartDate,
                    status: planting.status || 'active',
                    notes: planting.notes || `${cropName} ${varietyName} - Growing`,
                    source: 'farmOS'
                };
                chartData[bedName].push(growingActivity);
                
                // Create HARVEST activity (harvest window)
                const harvestActivity = {
                    id: planting.id ? `${planting.id}_harvest` : `planting_${Date.now()}_${Math.random()}_harvest`,
                    type: 'harvest',
                    crop: cropName.toLowerCase(),
                    variety: varietyName,
                    location: bedName,
                    start: harvestStartDate,
                    end: harvestEndDate || harvestStartDate,
                    status: planting.status || 'active',
                    notes: planting.notes || `${cropName} ${varietyName} - Harvest`,
                    source: 'farmOS'
                };
                chartData[bedName].push(harvestActivity);
            } else {
                // No harvest date specified, just show as growing until end date
                const growingActivity = {
                    id: planting.id || `planting_${Date.now()}_${Math.random()}`,
                    type: 'growing',
                    crop: cropName.toLowerCase(),
                    variety: varietyName,
                    location: bedName,
                    start: bedStartDate,
                    end: planting.end_date || bedStartDate,
                    status: planting.status || 'active',
                    notes: planting.notes || `${cropName} ${varietyName}`,
                    source: 'farmOS'
                };
                chartData[bedName].push(growingActivity);
            }
        });
    }

    console.log('✅ Transformed FarmOS data:', {
        beds: Object.keys(chartData).length,
        totalActivities: Object.values(chartData).reduce((sum, activities) => sum + activities.length, 0)
    });

    return chartData;
}

/**
 * Populate location and crop type dropdowns from API data
 */
function populateDropdownsFromAPIData(bedData) {
    // List of non-growing locations to exclude
    const excludedLocations = [
        'Middle World Farms CIC',
        'Ducks',
        'Duck House',
        'Propagation'
    ];
    
    // Populate locations dropdown
    const locationSelect = document.getElementById('locationFilter');
    if (locationSelect && bedData.beds) {
        // Clear existing options except "All Locations"
        locationSelect.innerHTML = '<option value="">All Locations</option>';
        
        // Get unique blocks and beds (excluding non-growing locations)
        const locations = new Set();
        bedData.beds.forEach(bed => {
            // Skip excluded locations and "Block Unknown" variants
            if (excludedLocations.includes(bed.name)) return;
            if (bed.block && bed.block.match(/Block\s+(Unknown|Unkown)/i)) return;
            
            if (bed.name) locations.add(bed.name);
            if (bed.block) locations.add(bed.block);
        });
        
        // Sort and add to dropdown
        Array.from(locations).sort((a, b) => {
            // Natural sort for bed names like "1/1", "Block 1"
            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        }).forEach(location => {
            const option = document.createElement('option');
            option.value = location;
            option.textContent = location;
            locationSelect.appendChild(option);
        });
    }
    
    // Populate crop types dropdown
    const cropTypeSelect = document.getElementById('cropTypeFilter');
    if (cropTypeSelect && bedData.plantings) {
        // Clear existing options except "All Crops"
        cropTypeSelect.innerHTML = '<option value="">All Crops</option>';
        
        // Get unique crops
        const crops = new Set();
        bedData.plantings.forEach(planting => {
            const crop = planting.crop || planting.crop_name;
            if (crop) crops.add(crop.toLowerCase());
        });
        
        // Sort and add to dropdown
        Array.from(crops).sort().forEach(crop => {
            const option = document.createElement('option');
            option.value = crop;
            option.textContent = crop.charAt(0).toUpperCase() + crop.slice(1);
            cropTypeSelect.appendChild(option);
        });
    }
}

/**
 * Determine activity type from planting dates
 */
function determineActivityType(planting) {
    const now = new Date();
    const startDate = planting.start_date ? new Date(planting.start_date) : null;
    const endDate = planting.end_date ? new Date(planting.end_date) : null;

    if (!startDate) return 'growing';

    // If we have harvest dates, determine based on timeline
    if (planting.harvest_date || planting.harvest_end_date) {
        const harvestStart = planting.harvest_date ? new Date(planting.harvest_date) : null;
        
        if (harvestStart && now >= harvestStart) {
            return 'harvest';
        }
    }

    // If transplant date exists and we're past it, we're growing
    if (planting.transplant_date) {
        const transplantDate = new Date(planting.transplant_date);
        if (now >= transplantDate) {
            return 'growing';
        }
        return 'seeding';
    }

    // Default to growing if we're between start and end
    if (endDate && now >= startDate && now <= endDate) {
        return 'growing';
    }

    // Before start = seeding/planned, after end = completed/harvest
    if (now < startDate) {
        return 'seeding';
    }

    return 'growing';
}

function renderTimelineChart(data) {
    // Create tabs for blocks and organize data
    createBlockTabs(data);
    createBlockTabContent(data);
    
    // Show the tab content
    document.getElementById('blockTabContent').style.display = 'block';
}

function createBlockTabs(data) {
    const tabsContainer = document.getElementById('blockTabs');
    tabsContainer.innerHTML = '';
    
    // Extract blocks from data (anything starting with "Block")
    // Filter out "Block Unknown" variants (Unknown, Unkown, etc.)
    const blocks = [];
    Object.keys(data).forEach(location => {
        if (location.startsWith('Block ')) {
            // Skip "Block Unknown" or similar variants
            if (!location.match(/Block\s+(Unknown|Unkown)/i)) {
                blocks.push(location);
            }
        }
    });
    
    // Sort blocks naturally
    blocks.sort((a, b) => {
        const aNum = parseInt(a.replace('Block ', ''));
        const bNum = parseInt(b.replace('Block ', ''));
        return aNum - bNum;
    });
    
    // If no blocks, create default tabs for Block 1-10
    if (blocks.length === 0) {
        for (let i = 1; i <= 10; i++) {
            blocks.push(`Block ${i}`);
        }
    }
    
    // Create tab navigation
    blocks.forEach((block, index) => {
        const tabId = block.replace(' ', '').toLowerCase(); // "block1", "block2", etc.
        const isActive = index === 0 ? 'active' : '';
        
        const tabHtml = `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${isActive}" id="${tabId}-tab" data-bs-toggle="tab" 
                        data-bs-target="#${tabId}" type="button" role="tab" 
                        aria-controls="${tabId}" aria-selected="${index === 0 ? 'true' : 'false'}">
                    ${block}
                </button>
            </li>
        `;
        tabsContainer.innerHTML += tabHtml;
    });
}

function createBlockTabContent(data) {
    const contentContainer = document.getElementById('blockTabContent');
    contentContainer.innerHTML = '';
    
    // Extract blocks from data
    // Filter out "Block Unknown" variants (Unknown, Unkown, etc.)
    const blocks = [];
    Object.keys(data).forEach(location => {
        if (location.startsWith('Block ')) {
            // Skip "Block Unknown" or similar variants
            if (!location.match(/Block\s+(Unknown|Unkown)/i)) {
                blocks.push(location);
            }
        }
    });
    
    // Sort blocks naturally
    blocks.sort((a, b) => {
        const aNum = parseInt(a.replace('Block ', ''));
        const bNum = parseInt(b.replace('Block ', ''));
        return aNum - bNum;
    });
    
    // If no blocks, create default tabs for Block 1-10
    if (blocks.length === 0) {
        for (let i = 1; i <= 10; i++) {
            blocks.push(`Block ${i}`);
        }
    }
    
    // Create content for each block
    blocks.forEach((block, index) => {
        const tabId = block.replace(' ', '').toLowerCase();
        const isActive = index === 0 ? 'show active' : '';
        
        // Get data for this block and related beds
        const blockData = getBlockData(data, block);
        const blockTimelineHtml = generateBlockTimeline(block, blockData);
        
        const tabContentHtml = `
            <div class="tab-pane fade ${isActive}" id="${tabId}" role="tabpanel" 
                 aria-labelledby="${tabId}-tab">
                <div class="block-timeline-container">
                    ${blockTimelineHtml}
                </div>
            </div>
        `;
        contentContainer.innerHTML += tabContentHtml;
    });
}

function getBlockData(data, targetBlock) {
    const blockData = {};
    
    // Get beds that belong to this block (look for bed naming patterns)
    const blockNumber = targetBlock.replace('Block ', '');
    Object.keys(data).forEach(location => {
        // Only include beds with format "X/Y" that match this block number
        // Exclude "Block X" entries
        if (location.includes('/') && location.startsWith(`${blockNumber}/`)) {
            blockData[location] = data[location];
        }
    });
    
    console.log(`📦 ${targetBlock}: Found ${Object.keys(blockData).length} beds`, Object.keys(blockData));
    
    return blockData;
}

function generateBlockTimeline(blockName, blockData) {
    if (!blockData || Object.keys(blockData).length === 0) {
        return `
            <div class="empty-block-message text-center py-4">
                <i class="fas fa-seedling fa-2x text-muted mb-3"></i>
                <h6 class="text-muted">${blockName} - No Activities</h6>
                <p class="text-muted small">This block has no current plantings or activities.</p>
                <small class="text-muted">Beds: Ready for planting</small>
            </div>
        `;
    }
    
    return `
        <div class="timeline-container">
            <div class="timeline-content">
                ${generateTimelineItems(blockData)}
            </div>
        </div>
    `;
}

function generateTimelineItems(data) {
    if (!data || Object.keys(data).length === 0) {
        return '<div class="no-timeline-data">No planting activities found</div>';
    }

    // Create a horizontal timeline chart
    return generateHorizontalTimeline(data);
}

function generateHorizontalTimeline(data) {
    // Get date range for the timeline
    const { startDate, endDate, allActivities } = getTimelineData(data);
    
    // Always show the timeline structure, even with no activities
    // This allows users to see all beds and plan future plantings

    // Generate timeline HTML with scroll container
    return `
        <div class="horizontal-timeline">
            <div class="timeline-header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-4">
                        <div>
                            <strong>Timeline:</strong> ${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()}
                        </div>
                        <small class="text-muted">Scroll horizontally to navigate • Use Shift+scroll, horizontal scroll, or arrow keys • Ctrl+scroll to zoom</small>
                    </div>
                    <div class="timeline-navigation">
                        <button class="btn btn-outline-primary btn-sm" onclick="scrollToYear(2024)">
                            <i class="fas fa-calendar"></i> 2024
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="scrollToYear(2025)">
                            <i class="fas fa-calendar"></i> 2025
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="scrollToYear(2026)">
                            <i class="fas fa-calendar"></i> 2026
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="scrollToSeason('spring')">
                            <i class="fas fa-seedling"></i> Spring
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="scrollToSeason('summer')">
                            <i class="fas fa-sun"></i> Summer
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="scrollToSeason('autumn')">
                            <i class="fas fa-leaf"></i> Autumn
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="scrollToSeason('winter')">
                            <i class="fas fa-snowflake"></i> Winter
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="timeline-scroll-container">
                <!-- Date Scale -->
                <div class="date-scale">
                    ${generateDateScale(startDate, endDate)}
                </div>
                
                <!-- Timeline Tracks -->
                <div class="timeline-tracks">
                    ${generateTimelineTracks(data, startDate, endDate)}
                </div>
            </div>
        </div>
    `;
}

function getTimelineData(data) {
    const allActivities = [];
    
    // Get the date range from the filter inputs
    const startDateInput = document.getElementById('startDate').value;
    const endDateInput = document.getElementById('endDate').value;
    
    let earliestDate = startDateInput ? new Date(startDateInput) : new Date();
    let latestDate = endDateInput ? new Date(endDateInput) : new Date();
    
    // Collect all activities with dates
    Object.keys(data).forEach(location => {
        const activities = data[location] || [];
        // Ensure activities is an array before calling forEach
        if (Array.isArray(activities)) {
            activities.forEach(activity => {
                if (activity.start && activity.end) {
                    const start = new Date(activity.start);
                    const end = new Date(activity.end);
                    
                    if (!isNaN(start.getTime()) && !isNaN(end.getTime())) {
                        allActivities.push({
                            ...activity,
                            location: location,
                            startDate: start,
                            endDate: end
                        });
                        
                        // Optionally extend range if activities go beyond
                        if (start < earliestDate) earliestDate = start;
                        if (end > latestDate) latestDate = end;
                    }
                }
            });
        }
    });
    
    // Use the date range from inputs (or extended by activities)
    const startDate = earliestDate;
    const endDate = latestDate;
    
    // If we have activities, extend the range to include them
    if (allActivities.length > 0) {
        if (earliestDate < startDate) {
            startDate.setFullYear(earliestDate.getFullYear(), 0, 1);
        }
        if (latestDate > endDate) {
            endDate.setFullYear(latestDate.getFullYear(), 11, 31);
        }
    }
    
    return { startDate, endDate, allActivities };
}

function generateDateScale(startDate, endDate) {
    const totalDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    const dayWidth = 10 * zoomLevel; // Apply zoom level
    const totalWidth = totalDays * dayWidth;
    
    const markers = [];
    
    // Create daily markers
    let currentDate = new Date(startDate);
    let dayIndex = 0;
    let lastYear = null;
    
    while (currentDate <= endDate) {
        const leftPosition = dayIndex * dayWidth;
        const isFirstOfMonth = currentDate.getDate() === 1;
        const isFirstOfYear = currentDate.getMonth() === 0 && currentDate.getDate() === 1;
        const isMonday = currentDate.getDay() === 1;
        const currentYear = currentDate.getFullYear();
        
        // Year marker (at start of each year)
        if (isFirstOfYear || (dayIndex === 0 && currentYear !== lastYear)) {
            markers.push(`
                <div class="date-marker year-marker" style="left: ${leftPosition}px;">
                    <div class="date-line year-line"></div>
                    <div class="date-label year-label">${currentYear}</div>
                </div>
            `);
            lastYear = currentYear;
        }
        // Month markers (major)
        else if (isFirstOfMonth) {
            const monthName = currentDate.toLocaleDateString('en-US', { month: 'short' });
            // Add year to label if it changed
            const showYear = currentYear !== lastYear;
            const label = showYear ? `${monthName} ${currentYear}` : monthName;
            
            markers.push(`
                <div class="date-marker month-marker" style="left: ${leftPosition}px;">
                    <div class="date-line major-line"></div>
                    <div class="date-label month-label">${label}</div>
                </div>
            `);
            
            if (showYear) lastYear = currentYear;
        }
        // Week markers (minor) - show on Mondays
        else if (isMonday) {
            const weekLabel = currentDate.getDate();
            markers.push(`
                <div class="date-marker week-marker" style="left: ${leftPosition}px;">
                    <div class="date-line minor-line"></div>
                    <div class="date-label week-label">${weekLabel}</div>
                </div>
            `);
        }
        // Day markers (minimal) - just a small tick
        else {
            markers.push(`
                <div class="date-marker day-marker" style="left: ${leftPosition}px;">
                    <div class="date-line day-line"></div>
                </div>
            `);
        }
        
        // Move to next day
        currentDate.setDate(currentDate.getDate() + 1);
        dayIndex++;
    }
    
    console.log(`📅 Generated date scale: ${totalDays} days, ${totalWidth}px wide, ${markers.length} markers`);
    
    return markers.join('');
}

function generateTimelineTracks(data, startDate, endDate) {
    const tracks = [];
    const totalDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    const dayWidth = 10 * zoomLevel; // Apply zoom level
    
    // Sort bed names naturally (1/1, 1/2, 1/3... not 1/1, 1/10, 1/11, 1/2...)
    // and filter out block names (anything without a slash)
    const sortedLocations = Object.keys(data)
        .filter(location => location.includes('/')) // Only include bed names with slashes
        .sort((a, b) => {
            // Split by slash to get block and bed numbers
            const [blockA, bedA] = a.split('/').map(num => parseInt(num) || 0);
            const [blockB, bedB] = b.split('/').map(num => parseInt(num) || 0);
            
            // Sort by block first, then by bed
            if (blockA !== blockB) return blockA - blockB;
            return bedA - bedB;
        });
    
    console.log('📋 All beds in data:', Object.keys(data).filter(k => k.includes('/')).sort());
    console.log('📊 Sorted beds to display:', sortedLocations);
    
    sortedLocations.forEach(location => {
        const activities = data[location] || [];
        // Ensure activities is an array before calling filter
        const validActivities = Array.isArray(activities) ? activities.filter(activity => 
            activity.start && activity.end && 
            !isNaN(new Date(activity.start).getTime()) && 
            !isNaN(new Date(activity.end).getTime())
        ) : [];
        
        if (validActivities.length > 0 || true) { // Show all locations
            tracks.push(`
                <div class="timeline-track">
                    <div class="track-label">
                        <span class="location-name">${location}</span>
                        <small class="activity-count">${validActivities.length} activities</small>
                    </div>
                    <div class="track-timeline">
                        ${generateTrackBars(validActivities, startDate, endDate, totalDays, dayWidth)}
                    </div>
                </div>
            `);
        }
    });
    
    return tracks.join('');
}

function generateTrackBars(activities, startDate, endDate, totalDays, dayWidth) {
    if (activities.length === 0) {
        return '<div class="empty-track">No activities scheduled</div>';
    }
    
    return activities.map(activity => {
        const activityStart = new Date(activity.start);
        const activityEnd = new Date(activity.end);
        
        // Calculate days from start date
        const startDayOffset = Math.floor((activityStart - startDate) / (1000 * 60 * 60 * 24));
        const activityDays = Math.ceil((activityEnd - activityStart) / (1000 * 60 * 60 * 24));
        
        // Convert to pixels
        const leftPx = Math.max(0, startDayOffset * dayWidth);
        const widthPx = Math.max(dayWidth, activityDays * dayWidth);
        
        const activityClass = `activity-bar activity-${activity.type}`;
        const duration = Math.ceil((activityEnd - activityStart) / (1000 * 60 * 60 * 24));
        
        // Format dates for display
        const startDateStr = activityStart.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
        const endDateStr = activityEnd.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
        
        // Build display text with variety if available
        const varietyText = activity.variety ? ` (${activity.variety})` : '';
        const dateRange = `${startDateStr} - ${endDateStr}`;
        
        return `
            <div class="${activityClass}" 
                 style="left: ${leftPx}px; width: ${widthPx}px"
                 onclick="showCropDetails('${activity.crop}', '${activity.type}', '${activity.location || 'Unknown'}', '${activity.variety || 'N/A'}', '${activityStart.toLocaleDateString()}', '${activityEnd.toLocaleDateString()}', '${duration}')"
                 title="${activity.crop}${varietyText} - ${activity.type} (${activityStart.toLocaleDateString()} - ${activityEnd.toLocaleDateString()})">
                <div class="activity-content">
                    <span class="activity-name">${activity.crop}${varietyText}</span>
                    <span class="activity-dates">${dateRange}</span>
                </div>
            </div>
        `;
    }).join('');
}

function applyFilters() {
    initializeChart();
}

function clearFilters() {
    document.getElementById('locationFilter').value = '';
    document.getElementById('cropTypeFilter').value = '';
    document.getElementById('startDate').value = '{{ now()->subYear()->startOfYear()->format('Y-m-d') }}';
    document.getElementById('endDate').value = '{{ now()->addYear()->endOfYear()->format('Y-m-d') }}';
    initializeChart();
}

function extendDateRangeBackward() {
    const startDateInput = document.getElementById('startDate');
    const currentStart = new Date(startDateInput.value);
    
    // Subtract one year from start date
    currentStart.setFullYear(currentStart.getFullYear() - 1);
    startDateInput.value = currentStart.toISOString().split('T')[0];
    
    console.log('📅 Extended timeline backward by 1 year');
    initializeChart();
}

function extendDateRangeForward() {
    const endDateInput = document.getElementById('endDate');
    const currentEnd = new Date(endDateInput.value);
    
    // Add one year to end date
    currentEnd.setFullYear(currentEnd.getFullYear() + 1);
    endDateInput.value = currentEnd.toISOString().split('T')[0];
    
    console.log('📅 Extended timeline forward by 1 year');
    initializeChart();
}

function updateStats(data) {
    let activePlantings = 0;
    let upcomingHarvests = 0;
    let totalBeds = 0;
    const now = new Date();
    const twoWeeksFromNow = new Date(now.getTime() + 14 * 24 * 60 * 60 * 1000);
    
    Object.keys(data).forEach(location => {
        const activities = data[location] || [];
        totalBeds++; // Count each location/bed
        
        // Ensure activities is an array before calling forEach
        if (Array.isArray(activities)) {
            activities.forEach(activity => {
                const startDate = new Date(activity.start);
                const endDate = new Date(activity.end);
                if (activity.type === 'growing' && startDate <= now && endDate >= now) {
                    activePlantings++;
                }
                if (activity.type === 'harvest' && startDate >= now && startDate <= twoWeeksFromNow) {
                    upcomingHarvests++;
                }
            });
        }
    });
    
    document.getElementById('activePlantings').textContent = activePlantings;
    document.getElementById('upcomingHarvests').textContent = upcomingHarvests;
    document.getElementById('totalBeds').textContent = totalBeds;
}

function showCropDetails(crop, phase, location, variety, startDate, endDate, duration) {
    alert(`Crop Details:\n\nCrop: ${crop}\nPhase: ${phase}\nLocation: ${location}\nVariety: ${variety}\nStart: ${startDate}\nEnd: ${endDate}\nDuration: ${duration} days`);
}

function showLoading(show) {
    document.getElementById('chartLoading').style.display = show ? 'block' : 'none';
}

function showChart() {
    document.getElementById('blockTabContent').style.display = 'block';
    document.getElementById('blockTabs').style.display = 'flex';
    document.getElementById('noDataMessage').style.display = 'none';
    document.getElementById('chartLoading').style.display = 'none';
}

function showNoData() {
    document.getElementById('noDataMessage').style.display = 'block';
    document.getElementById('blockTabContent').style.display = 'none';
    document.getElementById('blockTabs').style.display = 'none';
    document.getElementById('chartLoading').style.display = 'none';
}

function showError(message) {
    document.getElementById('noDataMessage').style.display = 'block';
    document.getElementById('noDataMessage').querySelector('h5').textContent = 'Error Loading Data';
    document.getElementById('noDataMessage').querySelector('p').textContent = 'Error: ' + message;
    document.getElementById('blockTabContent').style.display = 'none';
    document.getElementById('blockTabs').style.display = 'none';
    document.getElementById('chartLoading').style.display = 'none';
}

function showTestData() {
    const testData = {
        'Block 1': [
            {
                id: 'test_lettuce_seeding',
                type: 'seeding',
                crop: 'lettuce',
                variety: 'Butter Lettuce',
                start: '2025-03-15',
                end: '2025-04-01',
                color: '#28a745',
                label: 'Lettuce (Seeding)'
            },
            {
                id: 'test_lettuce_growing',
                type: 'growing', 
                crop: 'lettuce',
                variety: 'Butter Lettuce',
                start: '2025-04-01',
                end: '2025-05-15',
                color: '#007bff',
                label: 'Lettuce (Growing)'
            },
            {
                id: 'test_lettuce_harvest',
                type: 'harvest',
                crop: 'lettuce',
                variety: 'Butter Lettuce',
                start: '2025-05-15',
                end: '2025-05-30',
                color: '#ffc107',
                label: 'Lettuce (Harvest)'
            }
        ],
        '1/1': [
            {
                id: 'bed_1_1_spinach',
                type: 'seeding',
                crop: 'spinach',
                variety: 'Space Spinach',
                start: '2025-02-15',
                end: '2025-03-01',
                color: '#28a745',
                label: 'Spinach (Seeding)'
            },
            {
                id: 'bed_1_1_spinach_growing',
                type: 'growing',
                crop: 'spinach',
                variety: 'Space Spinach',
                start: '2025-03-01',
                end: '2025-04-15',
                color: '#007bff',
                label: 'Spinach (Growing)'
            }
        ],
        '1/2': [
            {
                id: 'bed_1_2_radish',
                type: 'seeding',
                crop: 'radish',
                variety: 'Cherry Belle',
                start: '2025-03-01',
                end: '2025-03-10',
                color: '#28a745',
                label: 'Radish (Seeding)'
            }
        ],
        '1/3': [
            {
                id: 'bed_1_3_kale',
                type: 'seeding',
                crop: 'kale',
                variety: 'Red Russian',
                start: '2025-04-01',
                end: '2025-04-15',
                color: '#28a745',
                label: 'Kale (Seeding)'
            },
            {
                id: 'bed_1_3_kale_growing',
                type: 'growing',
                crop: 'kale',
                variety: 'Red Russian',
                start: '2025-04-15',
                end: '2025-07-01',
                color: '#007bff',
                label: 'Kale (Growing)'
            }
        ],
        '1/4': [],
        '1/5': [
            {
                id: 'bed_1_5_peas',
                type: 'seeding',
                crop: 'peas',
                variety: 'Sugar Snap',
                start: '2025-03-15',
                end: '2025-03-25',
                color: '#28a745',
                label: 'Peas (Seeding)'
            }
        ],
        '1/6': [],
        '1/7': [
            {
                id: 'bed_1_7_arugula',
                type: 'seeding',
                crop: 'arugula',
                variety: 'Wild Rocket',
                start: '2025-05-01',
                end: '2025-05-10',
                color: '#28a745',
                label: 'Arugula (Seeding)'
            }
        ],
        '1/8': [],
        '1/9': [
            {
                id: 'bed_1_9_beets',
                type: 'seeding',
                crop: 'beets',
                variety: 'Detroit Dark Red',
                start: '2025-06-01',
                end: '2025-06-15',
                color: '#28a745',
                label: 'Beets (Seeding)'
            }
        ],
        '1/10': [],
        '1/11': [],
        '1/12': [
            {
                id: 'bed_1_12_cilantro',
                type: 'seeding',
                crop: 'cilantro',
                variety: 'Slow Bolt',
                start: '2025-04-15',
                end: '2025-04-25',
                color: '#28a745',
                label: 'Cilantro (Seeding)'
            }
        ],
        '1/13': [],
        '1/14': [],
        '1/15': [
            {
                id: 'bed_1_15_chard',
                type: 'seeding',
                crop: 'chard',
                variety: 'Rainbow',
                start: '2025-07-01',
                end: '2025-07-15',
                color: '#28a745',
                label: 'Chard (Seeding)'
            }
        ],
        '1/16': [],
        'Block 2': [
            {
                id: 'test_tomato_seeding',
                type: 'seeding',
                crop: 'tomato',
                variety: 'Cherry Tomato',
                start: '2025-02-01',
                end: '2025-02-20',
                color: '#28a745',
                label: 'Tomato (Seeding)'
            },
            {
                id: 'test_tomato_growing',
                type: 'growing',
                crop: 'tomato',
                variety: 'Cherry Tomato',
                start: '2025-02-20',
                end: '2025-07-30',
                color: '#007bff',
                label: 'Tomato (Growing)'
            },
            {
                id: 'test_tomato_harvest',
                type: 'harvest',
                crop: 'tomato',
                variety: 'Cherry Tomato',
                start: '2025-07-30',
                end: '2025-10-15',
                color: '#ffc107',
                label: 'Tomato (Harvest)'
            }
        ],
        '2/1': [
            {
                id: 'bed_2_1_basil',
                type: 'seeding',
                crop: 'basil',
                variety: 'Genovese',
                start: '2025-04-15',
                end: '2025-05-01',
                color: '#28a745',
                label: 'Basil (Seeding)'
            }
        ],
        '2/2': [
            {
                id: 'bed_2_2_peppers',
                type: 'seeding',
                crop: 'peppers',
                variety: 'Bell Pepper',
                start: '2025-03-01',
                end: '2025-03-20',
                color: '#28a745',
                label: 'Peppers (Seeding)'
            },
            {
                id: 'bed_2_2_peppers_growing',
                type: 'growing',
                crop: 'peppers',
                variety: 'Bell Pepper',
                start: '2025-03-20',
                end: '2025-08-15',
                color: '#007bff',
                label: 'Peppers (Growing)'
            }
        ],
        '2/3': [],
        '2/4': [
            {
                id: 'bed_2_4_eggplant',
                type: 'seeding',
                crop: 'eggplant',
                variety: 'Black Beauty',
                start: '2025-03-15',
                end: '2025-04-01',
                color: '#28a745',
                label: 'Eggplant (Seeding)'
            }
        ],
        '2/5': [],
        '2/6': [],
        '2/7': [
            {
                id: 'bed_2_7_cucumbers',
                type: 'seeding',
                crop: 'cucumbers',
                variety: 'Boston Pickling',
                start: '2025-05-01',
                end: '2025-05-15',
                color: '#28a745',
                label: 'Cucumbers (Seeding)'
            }
        ],
        '2/8': [],
        '2/9': [],
        '2/10': [
            {
                id: 'bed_2_10_zucchini',
                type: 'seeding',
                crop: 'zucchini',
                variety: 'Black Beauty',
                start: '2025-05-15',
                end: '2025-06-01',
                color: '#28a745',
                label: 'Zucchini (Seeding)'
            }
        ],
        '2/11': [],
        '2/12': [],
        '2/13': [
            {
                id: 'bed_2_13_herbs',
                type: 'seeding',
                crop: 'oregano',
                variety: 'Greek',
                start: '2025-04-01',
                end: '2025-04-15',
                color: '#28a745',
                label: 'Oregano (Seeding)'
            }
        ],
        '2/14': [],
        '2/15': [],
        '2/16': [
            {
                id: 'bed_2_16_parsley',
                type: 'seeding',
                crop: 'parsley',
                variety: 'Flat Leaf',
                start: '2025-03-15',
                end: '2025-03-30',
                color: '#28a745',
                label: 'Parsley (Seeding)'
            }
        ],
        'Block 3': [
            {
                id: 'test_carrot_seeding',
                type: 'seeding',
                crop: 'carrot',
                variety: 'Nantes',
                start: '2025-06-01',
                end: '2025-06-15',
                color: '#28a745',
                label: 'Carrot (Seeding)'
            },
            {
                id: 'test_carrot_growing',
                type: 'growing',
                crop: 'carrot',
                variety: 'Nantes',
                start: '2025-06-15',
                end: '2025-09-15',
                color: '#007bff',
                label: 'Carrot (Growing)'
            }
        ],
        '3/1': [],
        '3/2': [],
        '3/3': [],
        '3/4': [],
        '3/5': [],
        '3/6': [],
        '3/7': [],
        '3/8': [],
        '3/9': [],
        '3/10': [],
        '3/11': [],
        '3/12': [],
        '3/13': [],
        '3/14': [],
        '3/15': [],
        '3/16': [],
        'Block 4': [
            {
                id: 'test_winter_prep',
                type: 'seeding',
                crop: 'winter cover',
                variety: 'Rye Grass',
                start: '2025-10-01',
                end: '2025-10-15',
                color: '#28a745',
                label: 'Winter Cover (Seeding)'
            },
            {
                id: 'test_winter_growing',
                type: 'growing',
                crop: 'winter cover',
                variety: 'Rye Grass',
                start: '2025-10-15',
                end: '2025-12-31',
                color: '#007bff',
                label: 'Winter Cover (Growing)'
            }
        ],
        '4/1': [],
        '4/2': [],
        '4/3': [],
        '4/4': [],
        '4/5': [],
        '4/6': [],
        '4/7': [],
        '4/8': [],
        '4/9': [],
        '4/10': [],
        '4/11': [],
        '4/12': [],
        '4/13': [],
        '4/14': [],
        '4/15': [],
        '4/16': [],
        'Block 5': [],
        'Block 6': [],
        'Block 7': [],
        'Block 8': [],
        'Block 9': [],
        'Block 10': []
    };
    renderTimelineChart(testData);
    updateStats(testData);
    showChart();
}
</script>

<style>
/* Timeline Styles */
.timeline-container {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-header {
    margin-bottom: 20px;
    text-align: center;
}

.timeline-location {
    margin-bottom: 30px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
}

.location-header {
    color: #495057;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #007bff;
}

.timeline-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.timeline-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    border-left: 4px solid #6c757d;
}

.timeline-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.timeline-item.seeding {
    border-left-color: #28a745;
    background-color: #f8fff9;
}

.timeline-item.growing {
    border-left-color: #007bff;
    background-color: #f8fcff;
}

.timeline-item.harvest {
    border-left-color: #ffc107;
    background-color: #fffcf5;
}

.timeline-marker {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 15px;
    flex-shrink: 0;
}

.timeline-marker.seeding {
    background-color: #28a745;
}

.timeline-marker.growing {
    background-color: #007bff;
}

.timeline-marker.harvest {
    background-color: #ffc107;
}

.timeline-content-item {
    flex-grow: 1;
}

.timeline-title {
    font-weight: 600;
    color: #343a40;
    margin-bottom: 4px;
}

.timeline-dates {
    font-size: 0.9em;
    color: #6c757d;
    margin-bottom: 2px;
}

.timeline-variety {
    font-size: 0.8em;
    color: #868e96;
}

.no-timeline-data {
    text-align: center;
    padding: 40px;
    color: #6c757d;
    font-style: italic;
}

#plantingChart { 
    min-height: 500px; 
    overflow-x: auto; 
}

/* Block Tab Styles - Folder Tab Design */
.nav-tabs {
    border-bottom: 3px solid #28a745;
    margin-bottom: 0;
    background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
    padding: 0 15px;
    border-radius: 8px 8px 0 0;
}

.nav-tabs .nav-item {
    margin-bottom: -3px;
    margin-right: 3px;
}

.nav-tabs .nav-link {
    color: #6c757d;
    background: linear-gradient(to bottom, #f1f3f4 0%, #e2e6ea 100%);
    border: 2px solid #dee2e6;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    padding: 12px 20px 15px 20px;
    font-weight: 600;
    font-size: 0.95rem;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
    margin-right: 2px;
}

.nav-tabs .nav-link:hover {
    background: linear-gradient(to bottom, #fff 0%, #f8f9fa 100%);
    border-color: #28a745;
    color: #28a745;
    transform: translateY(-2px);
    box-shadow: 0 -4px 8px rgba(40, 167, 69, 0.2);
}

.nav-tabs .nav-link.active {
    color: #fff;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: #28a745;
    border-bottom: 3px solid #28a745;
    transform: translateY(-3px);
    box-shadow: 0 -6px 12px rgba(40, 167, 69, 0.3);
    z-index: 10;
    position: relative;
}

.nav-tabs .nav-link.active::before {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    right: 0;
    height: 3px;
    background: #28a745;
    border-radius: 0 0 3px 3px;
}

.nav-tabs .nav-link.active::after {
    content: '📁';
    margin-right: 8px;
    font-size: 1.1em;
}

.nav-tabs .nav-link:not(.active)::after {
    content: '📂';
    margin-right: 8px;
    font-size: 1.1em;
    opacity: 0.6;
}

/* Tab Content Styling */
.tab-content {
    background: #fff;
    border: 2px solid #28a745;
    border-top: none;
    border-radius: 0 0 8px 8px;
    min-height: 400px;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.1);
}

.block-timeline-container {
    min-height: 400px;
    padding: 25px;
    background: linear-gradient(135deg, #f8fff9 0%, #ffffff 100%);
}

/* Enhanced Empty Block Message */
.empty-block-message {
    border: 2px dashed #28a745;
    border-radius: 12px;
    margin: 20px 0;
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    position: relative;
    overflow: hidden;
}

.empty-block-message::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 10px,
        rgba(40, 167, 69, 0.05) 10px,
        rgba(40, 167, 69, 0.05) 20px
    );
    animation: shimmer 20s linear infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-50%) translateY(-50%) rotate(0deg); }
    100% { transform: translateX(-50%) translateY(-50%) rotate(360deg); }
}

.empty-block-message i {
    color: #28a745;
}

.empty-block-message h6 {
    color: #155724;
    font-weight: 600;
}

.empty-block-message p,
.empty-block-message small {
    color: #155724;
}

/* Enhanced Timeline Styling */
.timeline-header h6 {
    color: #28a745;
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
}

.timeline-header p {
    color: #6c757d;
    font-style: italic;
}

.timeline-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.timeline-location {
    margin-bottom: 25px;
    border: 1px solid rgba(40, 167, 69, 0.3);
    border-radius: 8px;
    padding: 20px;
    background: linear-gradient(135deg, #fff 0%, #f8fff9 100%);
    box-shadow: 0 2px 6px rgba(40, 167, 69, 0.1);
}

.location-header {
    color: #155724;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #28a745;
    font-weight: 700;
}

/* Horizontal Timeline Styles */
.horizontal-timeline {
    background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.2);
    margin: 20px 0;
    overflow-x: auto;
    overflow-y: hidden;
    min-width: 100%;
    position: relative;
    scroll-behavior: smooth;
    cursor: grab;
}

.horizontal-timeline:active {
    cursor: grabbing;
}

/* Custom scrollbar */
.horizontal-timeline::-webkit-scrollbar {
    height: 12px;
}

.horizontal-timeline::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 6px;
}

.horizontal-timeline::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 6px;
    border: 2px solid #f1f1f1;
}

.horizontal-timeline::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
}

.timeline-scroll-container {
    min-width: 10950px; /* ~3 years * 365 days * 10px = 10,950px */
    width: max-content;
    position: relative;
}

.hedgerow-divider {
    margin: 0 0 20px 0;
    padding: 0;
    height: 60px;
    background-image: url('/hedgerow.png');
    background-repeat: repeat-x;
    background-position: center center;
    background-size: auto 60px;
    position: relative;
    overflow: hidden;
    border-top: 2px solid #c3e6cb;
    border-bottom: 2px solid #c3e6cb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.timeline-header-section {
    position: sticky;
    top: 0;
    left: 0;
    z-index: 100;
    background: white;
    margin-bottom: 30px;
    padding: 15px 20px;
    border-bottom: 2px solid #28a745;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.timeline-header-section h6 {
    color: #28a745;
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 5px;
}

.timeline-header-section p {
    color: #6c757d;
    font-style: italic;
    margin-bottom: 0;
}

.date-scale {
    position: sticky;
    top: 0;
    z-index: 50;
    height: 60px;
    margin-bottom: 30px;
    margin-left: 115px; /* track-label width (100px) + margin-right (15px) */
    border-bottom: 3px solid #28a745;
    background: linear-gradient(to bottom, white 0%, white 80%, rgba(40, 167, 69, 0.1) 100%);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    min-width: 10950px; /* ~3 years * 365 days * 10px */
    width: 100%;
}

.date-marker {
    position: absolute;
    top: 0;
    height: 100%;
}

/* Year markers (extra major) */
.year-marker .year-line {
    width: 4px;
    height: 60px;
    background: linear-gradient(to bottom, #dc3545 0%, #28a745 100%);
    margin: 0 auto;
    border-radius: 2px;
}

.year-label {
    position: absolute;
    top: 0px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 1.1rem;
    color: #dc3545;
    font-weight: 900;
    background: #fff;
    padding: 5px 12px;
    border-radius: 4px;
    border: 2px solid #dc3545;
    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
    z-index: 5;
}

/* Month markers (major) */
.month-marker .major-line {
    width: 3px;
    height: 50px;
    background-color: #28a745;
    margin: 0 auto;
    border-radius: 1px;
}

.month-label {
    position: absolute;
    top: 45px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.9rem;
    color: #28a745;
    font-weight: 700;
    background: #fff;
    padding: 3px 8px;
    border-radius: 4px;
    border: 1px solid #28a745;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Week markers (minor) */
.week-marker .minor-line {
    width: 2px;
    height: 30px;
    background-color: #6c757d;
    margin: 0 auto;
    border-radius: 1px;
}

.week-label {
    position: absolute;
    top: 35px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    color: #6c757d;
    font-weight: 500;
    background: #f8f9fa;
    padding: 1px 4px;
    border-radius: 2px;
    white-space: nowrap;
}

/* Day markers (minimal) */
.day-marker .day-line {
    width: 1px;
    height: 15px;
    background-color: #dee2e6;
    margin: 0 auto;
}

.date-label {
    position: absolute;
    top: 45px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #dee2e6;
    white-space: nowrap;
}

.timeline-tracks {
    display: flex;
    flex-direction: column;
    gap: 15px;
    min-width: 10950px; /* ~3 years * 365 days * 10px */
    width: 100%;
}

.timeline-track {
    display: flex;
    align-items: center;
    min-height: 50px;
    border-bottom: 1px solid #e9ecef;
    padding: 10px 0;
}

.track-label {
    width: 100px;
    flex-shrink: 0;
    padding-right: 15px;
    text-align: right;
    border-right: 2px solid #28a745;
    margin-right: 15px;
    position: sticky;
    left: 0;
    background: linear-gradient(to right, rgba(255, 255, 255, 0.75) 0%, rgba(255, 255, 255, 0.65) 100%);
    backdrop-filter: blur(4px);
    z-index: 10;
}

.location-name {
    display: block;
    font-weight: 600;
    color: #495057;
    font-size: 0.95rem;
}

.activity-count {
    display: block;
    color: #6c757d;
    font-size: 0.75rem;
    margin-top: 2px;
}

.track-timeline {
    flex-grow: 1;
    position: relative;
    height: 40px;
    background: linear-gradient(to right, #f8f9fa 0%, #ffffff 50%, #f8f9fa 100%);
    border-radius: 20px;
    border: 1px solid #dee2e6;
    overflow: hidden;
}

.empty-track {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #adb5bd;
    font-style: italic;
    font-size: 0.85rem;
}

.activity-bar {
    position: absolute;
    top: 2px;
    height: 36px;
    border-radius: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 0 10px;
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    border: 2px solid rgba(255,255,255,0.3);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    min-width: 60px;
    overflow: hidden;
}

.activity-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    z-index: 10;
}

.activity-bar.activity-seeding {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: rgba(255,255,255,0.4);
}

.activity-bar.activity-growing {
    background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);
    border-color: rgba(255,255,255,0.4);
}

.activity-bar.activity-harvest {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    border-color: rgba(255,255,255,0.4);
    color: #212529;
}

.activity-content {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
}

.activity-name {
    font-weight: 700;
    font-size: 0.85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.activity-dates {
    font-size: 0.7rem;
    opacity: 0.95;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
    margin-top: 2px;
}

.activity-type {
    font-size: 0.7rem;
    opacity: 0.9;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-transform: capitalize;
}

/* Responsive */
@media (max-width: 768px) {
    .timeline-container {
        padding: 15px;
    }
    
    .horizontal-timeline {
        padding: 15px;
    }
    
    .timeline-track {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .track-label {
        width: 100%;
        text-align: left;
        border-right: none;
        border-bottom: 1px solid #28a745;
        padding-bottom: 10px;
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .track-timeline {
        width: 100%;
    }
    
    .timeline-item {
        padding: 10px;
    }
    
    .timeline-marker {
        width: 10px;
        height: 10px;
        margin-right: 10px;
    }
    
    .block-timeline-container {
        padding: 10px;
    }
    
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .date-label {
        font-size: 0.7rem;
        padding: 1px 4px;
    }
    
    .activity-bar {
        min-width: 40px;
        padding: 0 6px;
    }
    
    .activity-name {
        font-size: 0.75rem;
    }
    
    .activity-type {
        font-size: 0.65rem;
    }
}
</style>
@endsection

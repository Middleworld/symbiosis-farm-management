/**
 * Succession Planning JavaScript Module
 * Handles UI interactions, calculations, and API calls for succession planning
 */

class SuccessionPlanner {
    constructor(config = {}) {
        this.cropTypes = config.cropTypes || [];
        this.cropVarieties = config.cropVarieties || [];
        this.availableBeds = config.availableBeds || [];
        this.currentSuccessionPlan = null;
        this.isDragging = false;
        this.cacheBuster = Date.now();
        this.apiBase = window.location.origin + '/admin/farmos/succession-planning';
        this.farmosBase = config.farmosBase || '';
        this.cropId = null;
        this.calculationPending = false; // Prevent duplicate calculations
        this.calculationTimer = null;
    }

    /**
     * Initialize the succession planner
     */
    async initialize() {
        console.log('🚀 Initializing Succession Planner Module');

        // Setup event listeners
        this.setupEventListeners();

        // Initialize UI components
        this.initializeUI();

        // Restore saved state
        this.restorePlannerState();

        // Test connections
        await this.testConnections();

        console.log('✅ Succession Planner initialized');
    }

    /**
     * Setup event listeners for the succession planner
     */
    setupEventListeners() {
        console.log('📋 Setting up event listeners');

        // Crop selection change
        const cropSelect = document.getElementById('cropSelect');
        if (cropSelect) {
            cropSelect.addEventListener('change', (e) => this.handleCropSelection(e.target.value));
        }

        // Variety selection change
        const varietySelect = document.getElementById('varietySelect');
        if (varietySelect) {
            varietySelect.addEventListener('change', (e) => this.handleVarietySelection(e.target.value));
        }

        // Planting method change
        const plantingMethodRadios = document.querySelectorAll('input[name="plantingMethod"]');
        plantingMethodRadios.forEach(radio => {
            radio.addEventListener('change', (e) => this.handlePlantingMethodChange(e.target.value));
        });

        // Season/Year handlers
        this.setupSeasonYearHandlers();

        // AI status monitoring
        this.setupAIStatusMonitoring();

        // Harvest window selector
        this.initializeHarvestWindowSelector();
    }

    /**
     * Handle crop selection change
     */
    async handleCropSelection(cropId) {
        console.log('🌱 handleCropSelection called with cropId:', cropId, typeof cropId);
        this.cropId = cropId;

        // Update the crop select element to reflect the selection
        const cropSelect = document.getElementById('cropSelect');
        console.log('🌱 cropSelect element:', cropSelect);
        if (cropSelect) {
            cropSelect.value = cropId;
            console.log('🌱 Set cropSelect value to:', cropId);
        }

        console.log('🌱 About to call updateVarieties');
        await this.updateVarieties();
        console.log('🌱 updateVarieties completed');
        this.savePlannerState();
        
        // Only trigger succession calculation if a variety is already selected
        // This prevents premature calculation when just selecting the crop type
        const varietySelect = document.getElementById('varietySelect');
        const selectedVariety = varietySelect?.value;
        if (selectedVariety && selectedVariety !== '') {
            console.log('✅ Variety already selected, updating succession impact');
            this.updateSuccessionImpact();
        } else {
            console.log('⏸️ No variety selected yet, skipping succession calculation');
        }

        // Check if there are varieties available for this crop
        const hasVarieties = varietySelect && varietySelect.options.length > 1; // More than just "Select variety..."

        // NEVER initialize harvest window on crop selection alone
        // Wait for variety selection to provide crop + variety context
        console.log(`📊 Crop ${cropId} has ${hasVarieties ? 'varieties available' : 'no varieties'} - waiting for variety selection`);
    }

    /**
     * Handle variety selection change
     */
    async handleVarietySelection(varietyId) {
        console.log('🔄 Variety selected:', varietyId);
        this.savePlannerState();
        this.updateSuccessionImpact();
        
        // Call the global handleVarietySelection function to display variety info
        if (typeof window.handleVarietySelection === 'function') {
            await window.handleVarietySelection(varietyId);
        }

        // Initialize harvest window now that we have crop + variety (or just crop)
        if (this.cropId) {
            this.initializeBasicHarvestWindow(this.cropId, varietyId);
        }

        // Removed automatic AI harvest window calculation - now manual only
    }

    /**
     * Handle planting method change
     */
    handlePlantingMethodChange(method) {
        console.log('🌱 Planting method changed to:', method);
        
        // Update hint text
        const hintElement = document.getElementById('plantingMethodHint');
        if (hintElement) {
            const hints = {
                'direct': 'Direct sowing: Seeds planted directly in final location. Faster setup, less transplant shock.',
                'transplant': 'Transplanting: Start seeds indoors/greenhouse, transplant later. Better control, earlier start possible.',
                'either': 'Auto mode: System will choose best method based on variety data and growing conditions.'
            };
            hintElement.textContent = hints[method] || hints['either'];
        }
        
        // Check for method warnings based on crop/variety
        this.checkPlantingMethodWarning(method);
        
        // Trigger succession plan regeneration with new method (debounced)
        if (this.cropId && typeof calculateSuccessionPlan === 'function') {
            console.log('🔄 Regenerating succession plan with method:', method);
            this.debouncedCalculation();
        }
    }
    
    /**
     * Debounced calculation to prevent duplicate calls
     */
    debouncedCalculation() {
        // Clear any pending calculation
        if (this.calculationTimer) {
            clearTimeout(this.calculationTimer);
        }
        
        // Schedule new calculation
        this.calculationTimer = setTimeout(() => {
            if (!this.calculationPending) {
                this.calculationPending = true;
                calculateSuccessionPlan().finally(() => {
                    this.calculationPending = false;
                });
            }
        }, 150);
    }

    /**
     * Check if selected planting method conflicts with variety recommendations
     */
    checkPlantingMethodWarning(selectedMethod) {
        const warningElement = document.getElementById('plantingMethodWarning');
        if (!warningElement) return;
        
        // Get current crop and variety info
        const cropSelect = document.getElementById('cropSelect');
        const varietyData = window.currentVarietyData;
        const cropName = cropSelect?.options[cropSelect.selectedIndex]?.text?.toLowerCase() || '';
        
        let warningMessage = '';
        
        // Check for conflicts
        if (selectedMethod === 'transplant') {
            // Crops that should NOT be transplanted
            if (cropName.includes('cucumber') || cropName.includes('courgette') || 
                cropName.includes('zucchini') || cropName.includes('squash')) {
                warningMessage = 'ℹ️ Note: Cucurbits have sensitive roots and are usually direct sown. However, transplanting works well for early crops under protection (greenhouse/polytunnel).';
            } else if (cropName.includes('carrot') || cropName.includes('parsnip') || 
                       cropName.includes('radish') || cropName.includes('beetroot')) {
                warningMessage = 'ℹ️ Note: Root vegetables develop tap roots and generally prefer direct sowing for best root development.';
            } else if (cropName.includes('pea') || cropName.includes('bean')) {
                warningMessage = 'ℹ️ Note: Peas and beans are usually direct sown but can be transplanted for early crops under protection.';
            }
        } else if (selectedMethod === 'direct') {
            // Crops that benefit from transplanting
            if (cropName.includes('tomato') || cropName.includes('pepper') || cropName.includes('aubergine')) {
                warningMessage = 'ℹ️ Note: Solanaceae (tomatoes, peppers) benefit from transplanting for earlier harvests and better growth control.';
            } else if (cropName.includes('brussels') || cropName.includes('cabbage') || 
                       cropName.includes('broccoli') || cropName.includes('cauliflower')) {
                warningMessage = 'ℹ️ Note: Brassicas are typically transplanted for better spacing control and protection from pests. Consider transplanting for best results.';
            }
        }
        
        // Show or hide warning
        if (warningMessage) {
            warningElement.innerHTML = warningMessage;
            warningElement.style.display = 'block';
        } else {
            warningElement.style.display = 'none';
        }
    }

    /**
     * Update varieties dropdown based on selected crop
     */
    async updateVarieties() {
        console.log('🌱 updateVarieties called, this.cropId:', this.cropId);
        const varietySelect = document.getElementById('varietySelect');
        console.log('🌱 varietySelect element:', varietySelect);
        if (!varietySelect) {
            console.error('❌ varietySelect element not found!');
            return;
        }

        if (!this.cropId) {
            console.log('🌱 No cropId set, showing "Select crop first..."');
            varietySelect.innerHTML = '<option value="">Select crop first...</option>';
            return;
        }

        // Show loading state
        console.log('🌱 Showing loading state');
        varietySelect.innerHTML = '<option value="">Loading varieties...</option>';
        varietySelect.disabled = true;

        try {
            console.log(`🌐 Fetching varieties for crop ID: ${this.cropId}`);

            const response = await fetch(`${this.apiBase}/varieties-by-season/${this.cropId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success && data.varieties) {
                console.log(`✅ Loaded ${data.varieties.length} varieties for crop ${this.cropId}`);

                // Update the varieties dropdown
                varietySelect.innerHTML = '<option value="">Select variety...</option>' +
                    data.varieties.map(v => `<option value="${v.id}" data-crop="${v.parent_id}" data-name="${v.name}" data-season-type="${v.season_type || ''}">${v.name}</option>`).join('');

                // Store varieties for later use (for varietal succession)
                this.cropVarieties = data.varieties;

                // Check if any varieties support varietal succession
                const hasSeasonTypes = data.varieties.some(v => v.season_type);
                if (hasSeasonTypes) {
                    console.log('🌱 Varieties with season_type found - enabling varietal succession option');
                    this.enableVarietalSuccession();
                } else {
                    console.log('🌱 No varieties with season_type - varietal succession not available');
                    this.disableVarietalSuccession();
                }

            } else {
                console.warn('⚠️ No varieties returned from API');
                varietySelect.innerHTML = '<option value="">No varieties available</option>';
                this.disableVarietalSuccession();
            }

        } catch (error) {
            console.error('❌ Error fetching varieties:', error);
            varietySelect.innerHTML = '<option value="">Error loading varieties</option>';
            this.disableVarietalSuccession();
        } finally {
            varietySelect.disabled = false;
        }
    }

    /**
     * Update succession impact
     * Note: This calls the global updateSuccessionImpact function
     */
    updateSuccessionImpact() {
        // Call the global updateSuccessionImpact function
        if (typeof window.updateSuccessionImpact === 'function') {
            window.updateSuccessionImpact();
        } else {
            console.log('📈 SuccessionPlanner: Global updateSuccessionImpact not available yet');
        }
    }

    /**
     * Update harvest window display with retry (in case functions not loaded yet)
     */
    updateHarvestWindowDisplayWithRetry(retryCount = 0) {
        const maxRetries = 10;

        if (typeof updateHarvestWindowDisplay === 'function') {
            updateHarvestWindowDisplay();

            // Also update the drag bar
            if (typeof updateDragBar === 'function') {
                setTimeout(() => updateDragBar(), 100);
            }

            console.log('✅ Harvest window display updated');
        } else if (retryCount < maxRetries) {
            // Retry after a short delay
            setTimeout(() => {
                this.updateHarvestWindowDisplayWithRetry(retryCount + 1);
            }, 100);
            console.log(`⏳ Waiting for harvest window functions to load (attempt ${retryCount + 1}/${maxRetries})`);
        } else {
            console.warn('❌ Harvest window display functions not available after retries');
        }
    }

    /**
     * Initialize basic harvest window display for selected crop and variety
     */
    initializeBasicHarvestWindow(cropId, varietyId = null) {
        if (!cropId) return;

        // Get crop name for fallback logic
        const cropSelect = document.getElementById('cropSelect');
        const cropName = cropSelect ? cropSelect.options[cropSelect.selectedIndex]?.text?.toLowerCase() : '';

        // Get variety name if available
        let varietyName = '';
        if (varietyId) {
            const varietySelect = document.getElementById('varietySelect');
            varietyName = varietySelect ? varietySelect.options[varietySelect.selectedIndex]?.text?.toLowerCase() : '';
        }

        console.log('🌾 Initializing harvest window for:', { cropName, varietyName, cropId, varietyId });

        // Set basic harvest window data based on crop type (and potentially variety)
        const year = new Date().getFullYear();
        let maxStart, maxEnd, userStart, userEnd;

        // Default harvest windows based on crop type
        // TODO: In future, variety-specific adjustments can be added here
        switch (cropName) {
            case 'carrots':
            case 'carrot':
                maxStart = `${year}-05-01`;
                maxEnd = `${year}-12-31`;
                userStart = `${year}-08-01`;
                userEnd = `${year}-10-31`;
                break;
            case 'beets':
            case 'beetroot':
                maxStart = `${year}-06-01`;
                maxEnd = `${year}-12-31`;
                userStart = `${year}-09-01`;
                userEnd = `${year}-11-30`;
                break;
            case 'lettuce':
                maxStart = `${year}-03-01`;
                maxEnd = `${year}-11-30`;
                userStart = `${year}-05-01`;
                userEnd = `${year}-09-30`;
                break;
            case 'radish':
            case 'radishes':
                maxStart = `${year}-04-01`;
                maxEnd = `${year}-10-31`;
                userStart = `${year}-05-15`;
                userEnd = `${year}-08-31`;
                break;
            case 'onion':
            case 'onions':
                maxStart = `${year}-07-01`;
                maxEnd = `${year}-09-30`;
                userStart = `${year}-07-15`;
                userEnd = `${year}-09-15`;
                break;
            case 'brussels sprouts':
            case 'brussels sprout':
            case 'brussel sprouts':
            case 'brussel sprout':
                // Brussels sprouts harvest from October to March/April
                maxStart = `${year}-10-01`;
                maxEnd = `${year + 1}-03-31`;
                userStart = `${year}-11-01`;
                userEnd = `${year + 1}-02-28`;
                break;
            default:
                // Generic default
                maxStart = `${year}-05-01`;
                maxEnd = `${year}-10-31`;
                userStart = `${year}-06-15`;
                userEnd = `${year}-09-15`;
        }

        // Update global harvestWindowData
        if (typeof harvestWindowData !== 'undefined') {
            harvestWindowData.maxStart = maxStart;
            harvestWindowData.maxEnd = maxEnd;
            harvestWindowData.userStart = userStart;
            harvestWindowData.userEnd = userEnd;
            
            // Calculate AI recommended window as 80% of maximum duration
            const maxStartDate = new Date(maxStart);
            const maxEndDate = new Date(maxEnd);
            const maxDuration = maxEndDate - maxStartDate;
            const aiDuration = maxDuration * 0.8;
            const aiEndDate = new Date(maxStartDate.getTime() + aiDuration);
            
            harvestWindowData.aiStart = maxStart; // AI starts at maximum start
            harvestWindowData.aiEnd = aiEndDate.toISOString().split('T')[0];
            console.log('🔄 Updated harvestWindowData:', harvestWindowData);
        } else {
            console.warn('❌ harvestWindowData not available');
        }

        // Update the date inputs
        const harvestStartInput = document.getElementById('harvestStart');
        const harvestEndInput = document.getElementById('harvestEnd');
        if (harvestStartInput) harvestStartInput.value = userStart;
        if (harvestEndInput) harvestEndInput.value = userEnd;

        // Update the harvest window display (with retry if functions not yet loaded)
        this.updateHarvestWindowDisplayWithRetry();

        console.log('🌾 Initialized basic harvest window for:', cropName, varietyName || 'no variety', { maxStart, maxEnd, userStart, userEnd });
        
        // Only trigger succession calculation if a variety is selected
        // This prevents generating plans with "Select variety..." placeholder
        if (varietyId && typeof calculateSuccessionPlan === 'function') {
            console.log('🚀 Triggering auto succession calculation...');
            this.debouncedCalculation();
        } else {
            console.log('⏸️ Skipping auto succession calculation - waiting for variety selection');
        }
    }

    /**
     * Setup season and year handlers
     */
    setupSeasonYearHandlers() {
        const yearEl = document.getElementById('planningYear');
        const seasonEl = document.getElementById('planningSeason');

        if (yearEl) {
            yearEl.addEventListener('change', () => this.savePlannerState());
        }
        if (seasonEl) {
            seasonEl.addEventListener('change', () => this.savePlannerState());
        }
    }

    /**
     * Setup AI status monitoring
     */
    setupAIStatusMonitoring() {
        // Implementation for AI status monitoring
        console.log('🤖 Setting up AI status monitoring');
    }

    /**
     * Initialize harvest window selector
     */
    initializeHarvestWindowSelector() {
        // Implementation for harvest window selector
        console.log('📅 Initializing harvest window selector');
    }

    /**
     * Initialize UI components
     */
    initializeUI() {
        // Initialize variety select
        this.updateVarieties();

        // Initialize harvest bar
        this.initializeHarvestBar();

        // Update export button
        this.updateExportButton();
    }

    /**
     * Initialize harvest bar
     */
    initializeHarvestBar() {
        // Implementation for harvest bar initialization
        console.log('📊 Initializing harvest bar');
    }

    /**
     * Enable varietal succession UI when varieties with season_type are available
     */
    enableVarietalSuccession() {
        const section = document.getElementById('varietalSuccessionSection');
        if (section) {
            section.style.display = 'block';
            console.log('✅ Varietal succession enabled');
        }
    }

    /**
     * Disable varietal succession UI when no varieties support it
     */
    disableVarietalSuccession() {
        const section = document.getElementById('varietalSuccessionSection');
        if (section) {
            section.style.display = 'none';
            // Also uncheck the checkbox if it was checked
            const checkbox = document.getElementById('useVarietalSuccession');
            if (checkbox) {
                checkbox.checked = false;
                // Trigger the change event to hide the variety selectors
                checkbox.dispatchEvent(new Event('change'));
            }
            console.log('🚫 Varietal succession disabled');
        }
    }

    /**
     * Update export button state
     */
    updateExportButton() {
        // Implementation for export button update
        console.log('📤 Updating export button');
    }

    /**
     * Save planner state to localStorage
     */
    savePlannerState() {
        try {
            const state = {
                cropId: this.cropId,
                planningYear: document.getElementById('planningYear')?.value,
                planningSeason: document.getElementById('planningSeason')?.value,
                selectedVariety: document.getElementById('varietySelect')?.value
            };
            localStorage.setItem('successionPlannerState', JSON.stringify(state));
        } catch (error) {
            console.warn('Failed to save planner state:', error);
        }
    }

    /**
     * Restore planner state from localStorage
     */
    restorePlannerState() {
        try {
            const state = JSON.parse(localStorage.getItem('successionPlannerState') || '{}');

            // Only restore planning year and season, not crop selection
            // This keeps the UI clean with placeholders on page load
            if (state.planningYear) {
                const yearEl = document.getElementById('planningYear');
                if (yearEl) yearEl.value = state.planningYear;
            }
            if (state.planningSeason) {
                const seasonEl = document.getElementById('planningSeason');
                if (seasonEl) seasonEl.value = state.planningSeason;
            }

            // Note: We don't restore cropId or selectedVariety to keep clean UI
            // Users can re-select their preferences each session
        } catch (error) {
            console.warn('Failed to restore planner state:', error);
        }
    }

    /**
     * Test connections to external services
     */
    async testConnections() {
        console.log('🔗 Testing connections...');
        // Implementation for connection testing
    }
}
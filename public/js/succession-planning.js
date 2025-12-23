// ========================================
// SUCCESSION PLANNING JAVASCRIPT
// ========================================

console.log('Succession planning JS loaded');

// Global variables
let ganttChart = null;
let currentPlan = null;

// Crop timing presets and crop data will be passed from the blade template
let cropPresets = {};
let cropData = {};

// Global test function for debugging
window.testCropChange = function() {
    console.log('Manual test function called');
    const debugOutput = document.getElementById('debugOutput');
    if (debugOutput) {
        debugOutput.innerHTML += 'Manual test function called!\n';
    }
    
    const cropType = document.getElementById('cropType');
    if (cropType) {
        console.log('Crop type element found, current value:', cropType.value);
        if (debugOutput) {
            debugOutput.innerHTML += `Crop type found: ${cropType.value}\n`;
        }
        
        // Try to trigger change event
        const event = new Event('change', { bubbles: true });
        cropType.dispatchEvent(event);
        
        if (debugOutput) {
            debugOutput.innerHTML += 'Change event dispatched\n';
        }
    } else {
        console.log('Crop type element NOT found');
        if (debugOutput) {
            debugOutput.innerHTML += 'ERROR: Crop type element not found\n';
        }
    }
};

// Debug logging function
function debugLog(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const logMessage = `[${timestamp}] ${message}`;
    
    console.log(logMessage);
    
    const debugOutput = document.getElementById('debugOutput');
    if (debugOutput) {
        const colorClass = type === 'error' ? 'text-danger' : 
                          type === 'warning' ? 'text-warning' : 
                          type === 'success' ? 'text-success' : 'text-info';
        
        debugOutput.innerHTML += `<span class="${colorClass}">${logMessage}</span>\n`;
        debugOutput.scrollTop = debugOutput.scrollHeight;
    }
}

// Crop type change handler
function handleCropTypeChange(event) {
    const crop = event.target.value;
    debugLog(`CROP TYPE CHANGED TO: ${crop}`);
    
    // Clear and populate varieties
    const varietySelect = document.getElementById('variety');
    if (varietySelect) {
        varietySelect.innerHTML = '<option value="">Select variety (optional)...</option>';
        debugLog('Varieties cleared');
    }
    
    // Apply preset if available
    if (crop && cropPresets && cropPresets[crop]) {
        debugLog(`Applying preset for: ${crop}`);
        applyPreset(crop);
    } else {
        debugLog(`No preset found for: ${crop}`);
        // Try to get AI timing if available
        if (crop && typeof getSeasonalTimingFromAI === 'function') {
            getSeasonalTimingFromAI(crop);
        }
    }
    
    // Update AI assistant button
    updateAIAssistant();
}

// Apply crop preset
function applyPreset(cropType) {
    const normalizedCrop = (cropType || '').toLowerCase();
    const preset = cropPresets[cropType] || cropPresets[normalizedCrop];

    if (!preset) {
        debugLog(`No preset available for ${cropType}`, 'warning');
        return;
    }
    debugLog(`Applying preset: ${JSON.stringify(preset)}`);
    
    // Set timing values
    const seedingToTransplant = document.getElementById('seedingToTransplant');
    const transplantToHarvest = document.getElementById('transplantToHarvest');
    const harvestDuration = document.getElementById('harvestDuration');
    const directSowCheckbox = document.getElementById('directSow');
    
    if (seedingToTransplant) {
        seedingToTransplant.value = preset.propagation_days || 0;
    }
    
    if (transplantToHarvest) {
        transplantToHarvest.value = (preset.harvest_days || 60) - (preset.propagation_days || 0);
    }
    
    if (harvestDuration) {
        harvestDuration.value = preset.yield_period || 14;
    }
    
    // Handle direct sow
    let isDirectSow = (preset.propagation_days || 0) === 0;
    if (normalizedCrop.includes('broad bean')) {
        isDirectSow = true;
        if (seedingToTransplant) {
            seedingToTransplant.value = 0;
        }
        debugLog('Broad beans detected — defaulting to direct sow (estimated >200 plants/row)', 'info');
    }
    if (directSowCheckbox) {
        directSowCheckbox.checked = isDirectSow;
        toggleDirectSowMode(isDirectSow);
    }
    
    debugLog(`Preset applied successfully for ${cropType}`);
}

// Toggle direct sow mode
function toggleDirectSowMode(isDirectSow) {
    const seedingGroup = document.getElementById('seedingToTransplantGroup');
    const transplantLabel = document.getElementById('transplantToHarvestLabel');
    const transplantHelp = document.getElementById('transplantToHarvestHelp');
    const transplantBadge = document.getElementById('transplantOnlyBadge');
    
    if (seedingGroup) {
        seedingGroup.style.display = isDirectSow ? 'none' : 'block';
    }
    
    if (transplantLabel) {
        transplantLabel.textContent = isDirectSow ? 'Seeding to Harvest (Days)' : 'Transplant to Harvest (Days)';
    }
    
    if (transplantHelp) {
        transplantHelp.textContent = isDirectSow ? 'Growing period from seeding to harvest' : 'Growing period from transplant to harvest';
    }
    
    if (transplantBadge) {
        transplantBadge.style.display = isDirectSow ? 'none' : 'inline';
    }
}

// Update AI assistant button
function updateAIAssistant() {
    const askBtn = document.getElementById('askAI');
    const cropType = document.getElementById('cropType')?.value;
    
    if (askBtn) {
        if (cropType) {
            askBtn.disabled = false;
            askBtn.innerHTML = `<i class="fas fa-brain"></i> Get ${cropType} AI Tips`;
        } else {
            askBtn.disabled = true;
            askBtn.innerHTML = `<i class="fas fa-brain"></i> Get AI Recommendations`;
        }
    }
}

// Get seasonal timing from AI (placeholder)
function getSeasonalTimingFromAI(cropType) {
    debugLog(`Getting AI timing for ${cropType}...`);
    // This would normally make an API call
    // For now, just log that it was called
    debugLog(`AI timing requested for ${cropType} (function available)`);
}

// Initialize data from blade template
window.initSuccessionPlanningData = function(presets, data) {
    cropPresets = presets || {};
    cropData = data || {};
    debugLog(`Data initialized: ${Object.keys(cropPresets).length} presets, crop data available: ${!!data}`);
};

// Main initialization
document.addEventListener('DOMContentLoaded', function() {
    debugLog('Succession planning page loaded');
    
    // Check for required elements
    const cropTypeElement = document.getElementById('cropType');
    const debugOutput = document.getElementById('debugOutput');
    
    debugLog(`Elements found: cropType=${!!cropTypeElement}, debugOutput=${!!debugOutput}`);
    
    // Setup crop type change listener
    if (cropTypeElement) {
        cropTypeElement.addEventListener('change', handleCropTypeChange);
        debugLog('Crop type change listener added');
    } else {
        debugLog('ERROR: cropType element not found!', 'error');
    }
    
    // Setup debug buttons
    const clearBtn = document.getElementById('clearDebug');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (debugOutput) {
                debugOutput.innerHTML = 'Debug cleared...\n';
            }
        });
        debugLog('Clear button listener added');
    }
    
    // Setup other form elements
    const directSowCheckbox = document.getElementById('directSow');
    if (directSowCheckbox) {
        directSowCheckbox.addEventListener('change', function() {
            toggleDirectSowMode(this.checked);
        });
        // Initialize to default state
        toggleDirectSowMode(false);
        debugLog('Direct sow checkbox listener added');
    }
    
    // Setup generation button
    const generateBtn = document.getElementById('generatePlan');
    if (generateBtn && typeof generateSuccessionPlan === 'function') {
        generateBtn.addEventListener('click', generateSuccessionPlan);
        debugLog('Generate plan button listener added');
    }
    
    debugLog('Succession planning initialization complete');
});

console.log('Succession planning JS file loaded successfully');

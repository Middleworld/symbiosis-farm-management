<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="fas fa-cloud-sun text-primary"></i> Weather API Configuration
        </h5>
        
        <p class="text-muted mb-4">
            Configure your weather service API keys to enable weather data integration for your farm.
        </p>
        
        <form id="weather-settings-form">
            @csrf
            
            <!-- Farm Location -->
            <div class="alert alert-info mb-4">
                <strong><i class="fas fa-map-marker-alt"></i> Farm Location</strong>
                <p class="mb-0 mt-2">Required for all weather services</p>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="farm_latitude" class="form-label">Farm Latitude</label>
                    <input type="text" class="form-control" id="farm_latitude" name="farm_latitude" 
                           value="{{ env('FARM_LATITUDE', '') }}" placeholder="51.5074">
                    <small class="text-muted">Decimal degrees (e.g., 51.5074)</small>
                </div>
                <div class="col-md-6">
                    <label for="farm_longitude" class="form-label">Farm Longitude</label>
                    <input type="text" class="form-control" id="farm_longitude" name="farm_longitude" 
                           value="{{ env('FARM_LONGITUDE', '') }}" placeholder="-0.1278">
                    <small class="text-muted">Decimal degrees (e.g., -0.1278)</small>
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- WeatherAPI.com (Primary) -->
            <div class="mb-4">
                <h6 class="mb-3">
                    <i class="fas fa-cloud text-primary"></i> WeatherAPI.com (Recommended)
                    <span class="badge bg-success ms-2">Free Tier: 1M calls/month</span>
                </h6>
                <p class="text-muted small">Best for UK farms. Sign up at <a href="https://www.weatherapi.com/" target="_blank">weatherapi.com</a></p>
                
                <div class="input-group">
                    <input type="password" class="form-control" id="weatherapi_key" name="weatherapi_key" 
                           value="{{ env('WEATHERAPI_KEY', '') }}" placeholder="Enter your WeatherAPI.com key">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('weatherapi_key')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-primary" type="button" onclick="testWeatherAPI('weatherapi')">
                        <i class="fas fa-vial"></i> Test
                    </button>
                </div>
                <div id="weatherapi-test-result" class="mt-2" style="display:none;"></div>
            </div>
            
            <!-- OpenWeather -->
            <div class="mb-4">
                <h6 class="mb-3">
                    <i class="fas fa-sun text-warning"></i> OpenWeather (Alternative)
                    <span class="badge bg-info ms-2">Free Tier: 1K calls/day</span>
                </h6>
                <p class="text-muted small">Sign up at <a href="https://openweathermap.org/api" target="_blank">openweathermap.org</a></p>
                
                <div class="input-group">
                    <input type="password" class="form-control" id="openweather_api_key" name="openweather_api_key" 
                           value="{{ env('OPENWEATHER_API_KEY', '') }}" placeholder="Enter your OpenWeather API key">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('openweather_api_key')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-primary" type="button" onclick="testWeatherAPI('openweather')">
                        <i class="fas fa-vial"></i> Test
                    </button>
                </div>
                <div id="openweather-test-result" class="mt-2" style="display:none;"></div>
            </div>
            
            <!-- Met Office (UK Only) -->
            <div class="mb-4">
                <h6 class="mb-3">
                    <i class="fas fa-cloud-rain text-info"></i> UK Met Office DataHub (UK Only)
                    <span class="badge bg-secondary ms-2">Free for non-commercial</span>
                </h6>
                <p class="text-muted small">Sign up at <a href="https://datahub.metoffice.gov.uk/" target="_blank">datahub.metoffice.gov.uk</a></p>
                
                <div class="mb-3">
                    <label for="met_office_api_key" class="form-label">General API Key</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="met_office_api_key" name="met_office_api_key" 
                               value="{{ env('MET_OFFICE_API_KEY', '') }}" placeholder="General Met Office key">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('met_office_api_key')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="accordion" id="metOfficeAdvanced">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#metOfficeKeys">
                                <small>Advanced: Specific API Keys (Optional)</small>
                            </button>
                        </h2>
                        <div id="metOfficeKeys" class="accordion-collapse collapse" data-bs-parent="#metOfficeAdvanced">
                            <div class="accordion-body">
                                <div class="mb-2">
                                    <label class="form-label small">Land Observations</label>
                                    <input type="password" class="form-control form-control-sm" name="met_office_land_observations" 
                                           value="{{ env('MET_OFFICE_LAND_OBSERVATIONS_KEY', '') }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Site-Specific Forecasts</label>
                                    <input type="password" class="form-control form-control-sm" name="met_office_site_specific" 
                                           value="{{ env('MET_OFFICE_SITE_SPECIFIC_KEY', '') }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Atmospheric Models</label>
                                    <input type="password" class="form-control form-control-sm" name="met_office_atmospheric" 
                                           value="{{ env('MET_OFFICE_ATMOSPHERIC_KEY', '') }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Map/Overlay Images</label>
                                    <input type="password" class="form-control form-control-sm" name="met_office_map_images" 
                                           value="{{ env('MET_OFFICE_MAP_IMAGES_KEY', '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Weather Settings
            </button>
            
            <div id="weather-save-result" class="mt-3" style="display:none;"></div>
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

function testWeatherAPI(provider) {
    const resultDiv = document.getElementById(provider + '-test-result');
    const apiKey = provider === 'weatherapi' 
        ? document.getElementById('weatherapi_key').value 
        : document.getElementById('openweather_api_key').value;
    const latitude = document.getElementById('farm_latitude').value;
    const longitude = document.getElementById('farm_longitude').value;
    
    if (!apiKey) {
        resultDiv.innerHTML = '<div class="alert alert-warning small mb-0">Please enter an API key first</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    if (!latitude || !longitude) {
        resultDiv.innerHTML = '<div class="alert alert-warning small mb-0">Please enter farm coordinates first</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    resultDiv.innerHTML = '<div class="alert alert-info small mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Testing connection...</div>';
    resultDiv.style.display = 'block';
    
    fetch('/admin/settings/test-weather-api', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            provider: provider,
            api_key: apiKey,
            latitude: latitude,
            longitude: longitude
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success small mb-0">
                    <i class="fas fa-check-circle"></i> ${data.message}
                    ${data.temperature ? `<br><small>Current temperature: ${data.temperature}°C</small>` : ''}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger small mb-0"><i class="fas fa-times-circle"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger small mb-0">Connection error</div>';
    });
}

document.getElementById('weather-settings-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const resultDiv = document.getElementById('weather-save-result');
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    fetch('/admin/settings/update-weather', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger">Failed to save settings</div>';
        resultDiv.style.display = 'block';
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});
</script>

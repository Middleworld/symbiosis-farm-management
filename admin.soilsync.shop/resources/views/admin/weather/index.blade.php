@extends('layouts.app')

@section('title', 'Weather Dashboard - MWF Admin')
@section('page-title', 'Weather Dashboard')

@section('styles')
<style>
    .weather-card {
        transition: transform 0.2s;
    }
    .weather-card:hover {
        transform: translateY(-2px);
    }
    .temperature-display {
        font-size: 2.5rem;
        font-weight: 300;
    }
    .weather-icon {
        font-size: 3rem;
    }
    .frost-warning {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.8; }
        100% { opacity: 1; }
    }
    .gdd-progress {
        height: 8px;
        border-radius: 4px;
    }
    .weather-alert {
        border-left: 4px solid #f39c12;
        background: rgba(243, 156, 18, 0.1);
    }
    .field-work-good {
        background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    }
    .field-work-poor {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    }
    .field-work-moderate {
        background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    }
</style>
@endsection

@section('content')
<div class="row">
    <!-- Current Weather -->
    <div class="col-md-4 mb-4">
        <div class="card weather-card" id="current-weather">
            <div class="card-body text-center">
                <h5 class="card-title">
                    <i class="fas fa-cloud-sun me-2"></i>Current Weather
                </h5>
                <div class="weather-icon mb-3">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="temperature-display mb-2">
                    <span id="current-temp">--°C</span>
                </div>
                <p class="text-muted mb-2" id="current-desc">Loading...</p>
                <div class="row text-center">
                    <div class="col-4">
                        <small class="text-muted d-block">Humidity</small>
                        <strong id="current-humidity">--%</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">Wind</small>
                        <strong id="current-wind">-- mph</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">Pressure</small>
                        <strong id="current-pressure">-- mb</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Frost Risk -->
    <div class="col-md-4 mb-4">
        <div class="card weather-card" id="frost-risk">
            <div class="card-body text-center">
                <h5 class="card-title">
                    <i class="fas fa-snowflake me-2"></i>Frost Risk
                </h5>
                <div class="mb-3">
                    <i class="fas fa-thermometer-quarter fa-3x" id="frost-icon"></i>
                </div>
                <h3 class="mb-2" id="frost-level">Loading...</h3>
                <p class="text-muted mb-2" id="frost-desc">Checking conditions...</p>
                <div class="mt-3">
                    <small class="text-muted d-block">Next 48 Hours</small>
                    <div id="frost-timeline" class="mt-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Field Work Conditions -->
    <div class="col-md-4 mb-4">
        <div class="card weather-card" id="field-work">
            <div class="card-body text-center text-white">
                <h5 class="card-title">
                    <i class="fas fa-tractor me-2"></i>Field Work
                </h5>
                <div class="mb-3">
                    <i class="fas fa-question-circle fa-3x" id="work-icon"></i>
                </div>
                <h3 class="mb-2" id="work-status">Loading...</h3>
                <p class="mb-2" id="work-desc">Analyzing conditions...</p>
                <div class="mt-3">
                    <small class="d-block opacity-75">Best Work Window</small>
                    <strong id="work-window">Calculating...</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 7-Day Forecast -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-week me-2"></i>7-Day Forecast
                </h5>
            </div>
            <div class="card-body">
                <div class="row" id="forecast-container">
                    <div class="col-12 text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading forecast...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Growing Degree Days -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Growing Degree Days
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h4 class="mb-1" id="gdd-total">-- GDD</h4>
                    <small class="text-muted">This Season</small>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Weekly Accumulation</small>
                    <div class="progress gdd-progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 0%" id="gdd-progress"></div>
                    </div>
                    <small class="text-muted" id="gdd-week">0 GDD this week</small>
                </div>
                <div class="row text-center">
                    <div class="col-6">
                        <small class="text-muted d-block">Yesterday</small>
                        <strong id="gdd-yesterday">-- GDD</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Today</small>
                        <strong id="gdd-today">-- GDD</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Weather Alerts -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Weather Alerts
                </h5>
            </div>
            <div class="card-body">
                <div id="weather-alerts">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading alerts...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tools me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="checkPlantingWindow()">
                        <i class="fas fa-seedling me-2"></i>Check Planting Window
                    </button>
                    <button class="btn btn-info" onclick="viewHistoricalData()">
                        <i class="fas fa-chart-bar me-2"></i>Historical Weather
                    </button>
                    <button class="btn btn-success" onclick="generateWeatherReport()">
                        <i class="fas fa-file-alt me-2"></i>Generate Report
                    </button>
                    <a href="{{ route('admin.farmos.succession-planning') }}" class="btn btn-warning">
                        <i class="fas fa-calendar-alt me-2"></i>Succession Planning
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Planting Analysis -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-check me-2"></i>Optimal Planting Analysis
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="crop-select" class="form-label">Select Crop:</label>
                        <select class="form-select" id="crop-select">
                            <option value="">Choose a crop...</option>
                            <option value="lettuce">Lettuce</option>
                            <option value="carrots">Carrots</option>
                            <option value="beans">Beans</option>
                            <option value="tomatoes">Tomatoes</option>
                            <option value="potatoes">Potatoes</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="analyzePlantingWindow()">
                            <i class="fas fa-search me-2"></i>Analyze Planting Window
                        </button>
                    </div>
                </div>
                <div id="planting-results" class="mt-4" style="display: none;">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Planting Recommendation</h6>
                        <div id="planting-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    loadWeatherData();
    
    // Refresh weather data every 5 minutes
    setInterval(loadWeatherData, 300000);
});

function loadWeatherData() {
    // Load current weather
    $.get('{{ route('admin.weather.current') }}')
        .done(function(data) {
            updateCurrentWeather(data);
        })
        .fail(function() {
            $('#current-weather .card-body').html('<p class="text-danger">Unable to load current weather</p>');
        });
    
    // Load forecast
    $.get('{{ route('admin.weather.forecast') }}')
        .done(function(data) {
            updateForecast(data);
        })
        .fail(function() {
            $('#forecast-container').html('<p class="text-danger">Unable to load forecast</p>');
        });
    
    // Load frost risk
    $.get('{{ route('admin.weather.frost-risk') }}')
        .done(function(data) {
            updateFrostRisk(data);
        })
        .fail(function() {
            $('#frost-risk .card-body').html('<p class="text-danger">Unable to load frost risk</p>');
        });
    
    // Load field work conditions
    $.get('{{ route('admin.weather.field-work') }}')
        .done(function(data) {
            updateFieldWork(data);
        })
        .fail(function() {
            $('#field-work .card-body').html('<p class="text-danger">Unable to load field work conditions</p>');
        });
    
    // Load GDD data
    $.get('{{ route('admin.weather.growing-degree-days') }}')
        .done(function(data) {
            updateGrowingDegreeDays(data);
        })
        .fail(function() {
            $('#gdd-total').text('Error');
        });
    
    // Load weather alerts
    $.get('{{ route('admin.weather.alerts') }}')
        .done(function(data) {
            updateWeatherAlerts(data);
        })
        .fail(function() {
            $('#weather-alerts').html('<p class="text-danger">Unable to load weather alerts</p>');
        });
}

function updateCurrentWeather(data) {
    if (data.success) {
        const weather = data.data;
        $('#current-temp').text(Math.round(weather.temperature) + '°C');
        $('#current-desc').text(weather.description);
        $('#current-humidity').text(weather.humidity + '%');
        $('#current-wind').text(Math.round(weather.wind_speed) + ' mph');
        $('#current-pressure').text(weather.pressure + ' mb');
        
        // Update weather icon
        const iconClass = getWeatherIconClass(weather.condition);
        $('#current-weather .weather-icon').html('<i class="' + iconClass + '"></i>');
    }
}

function updateForecast(data) {
    if (data.success && data.data.length > 0) {
        let forecastHtml = '';
        data.data.slice(0, 7).forEach(function(day, index) {
            const date = new Date(day.date);
            const dayName = index === 0 ? 'Today' : date.toLocaleDateString('en-GB', { weekday: 'short' });
            
            forecastHtml += `
                <div class="col text-center">
                    <small class="text-muted d-block">${dayName}</small>
                    <i class="${getWeatherIconClass(day.condition)} mb-2"></i>
                    <div class="fw-bold">${Math.round(day.max_temp)}°</div>
                    <small class="text-muted">${Math.round(day.min_temp)}°</small>
                </div>
            `;
        });
        $('#forecast-container').html(forecastHtml);
    }
}

function updateFrostRisk(data) {
    if (data.success) {
        const risk = data.data;
        $('#frost-level').text(risk.risk_level);
        $('#frost-desc').text(risk.description);
        
        // Update card styling based on risk
        const card = $('#frost-risk');
        card.removeClass('frost-warning');
        if (risk.risk_level === 'High' || risk.risk_level === 'Critical') {
            card.addClass('frost-warning');
        }
        
        // Update frost timeline
        if (risk.timeline && risk.timeline.length > 0) {
            let timelineHtml = '';
            risk.timeline.forEach(function(item) {
                const icon = item.frost_risk ? '<i class="fas fa-snowflake text-primary"></i>' : '<i class="fas fa-check text-success"></i>';
                timelineHtml += `<span class="me-2">${item.time} ${icon}</span>`;
            });
            $('#frost-timeline').html(timelineHtml);
        }
    }
}

function updateFieldWork(data) {
    if (data.success) {
        const work = data.data;
        $('#work-status').text(work.status);
        $('#work-desc').text(work.description);
        $('#work-window').text(work.best_window || 'Not available');
        
        // Update card styling
        const card = $('#field-work');
        card.removeClass('field-work-good field-work-poor field-work-moderate');
        
        if (work.status === 'Excellent' || work.status === 'Good') {
            card.addClass('field-work-good');
            $('#work-icon').attr('class', 'fas fa-check-circle fa-3x');
        } else if (work.status === 'Poor') {
            card.addClass('field-work-poor');
            $('#work-icon').attr('class', 'fas fa-times-circle fa-3x');
        } else {
            card.addClass('field-work-moderate');
            $('#work-icon').attr('class', 'fas fa-exclamation-circle fa-3x');
        }
    }
}

function updateGrowingDegreeDays(data) {
    if (data.success) {
        const gdd = data.data;
        $('#gdd-total').text(gdd.season_total + ' GDD');
        $('#gdd-week').text(gdd.week_total + ' GDD this week');
        $('#gdd-yesterday').text(gdd.yesterday + ' GDD');
        $('#gdd-today').text(gdd.today + ' GDD');
        
        // Update progress bar (assuming max 50 GDD per week)
        const weekProgress = Math.min((gdd.week_total / 50) * 100, 100);
        $('#gdd-progress').css('width', weekProgress + '%');
    }
}

function updateWeatherAlerts(data) {
    if (data.success && data.data.length > 0) {
        let alertsHtml = '';
        data.data.forEach(function(alert) {
            alertsHtml += `
                <div class="weather-alert p-3 rounded mb-2">
                    <h6 class="mb-1"><i class="fas fa-exclamation-triangle me-2"></i>${alert.title}</h6>
                    <p class="mb-1">${alert.description}</p>
                    <small class="text-muted">${alert.issued_at}</small>
                </div>
            `;
        });
        $('#weather-alerts').html(alertsHtml);
    } else {
        $('#weather-alerts').html('<p class="text-success"><i class="fas fa-check-circle me-2"></i>No weather alerts</p>');
    }
}

function getWeatherIconClass(condition) {
    const iconMap = {
        'clear': 'fas fa-sun text-warning',
        'cloudy': 'fas fa-cloud text-secondary',
        'partly_cloudy': 'fas fa-cloud-sun text-info',
        'rain': 'fas fa-cloud-rain text-primary',
        'heavy_rain': 'fas fa-cloud-showers-heavy text-primary',
        'snow': 'fas fa-snowflake text-light',
        'fog': 'fas fa-smog text-muted',
        'thunderstorm': 'fas fa-bolt text-warning',
        'default': 'fas fa-cloud text-secondary'
    };
    
    return iconMap[condition] || iconMap['default'];
}

function checkPlantingWindow() {
    const crop = $('#crop-select').val();
    if (!crop) {
        alert('Please select a crop first');
        return;
    }
    
    analyzePlantingWindow();
}

function analyzePlantingWindow() {
    const crop = $('#crop-select').val();
    if (!crop) {
        alert('Please select a crop first');
        return;
    }
    
    $.post('{{ route('admin.weather.planting-analysis') }}', {
        crop: crop,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(data) {
        if (data.success) {
            const analysis = data.data;
            let content = `
                <p><strong>Crop:</strong> ${crop.charAt(0).toUpperCase() + crop.slice(1)}</p>
                <p><strong>Recommendation:</strong> ${analysis.recommendation}</p>
                <p><strong>Optimal Window:</strong> ${analysis.optimal_window}</p>
                <p><strong>Confidence:</strong> ${analysis.confidence}</p>
                <p>${analysis.reasoning}</p>
            `;
            $('#planting-content').html(content);
            $('#planting-results').show();
        } else {
            alert('Error analyzing planting window: ' + data.error);
        }
    })
    .fail(function() {
        alert('Error connecting to weather service');
    });
}

function viewHistoricalData() {
    window.open('{{ route('admin.weather.historical') }}', '_blank');
}

function generateWeatherReport() {
    // This could generate a PDF report or open a new page
    alert('Weather report generation coming soon!');
}
</script>
@endsection

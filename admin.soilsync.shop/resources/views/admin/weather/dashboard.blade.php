@extends('layouts.app')

@section('title', 'Weather Dashboard - MWF Admin')
@section('page-title', 'Weather Dashboard')

@section('styles')
<style>
    .weather-card {
        transition: transform 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .weather-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .temperature-display {
        font-size: 2.5rem;
        font-weight: 300;
        color: #2c3e50;
    }
    .weather-icon {
        font-size: 3rem;
    }
    .frost-warning {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.8; }
        100% { opacity: 1; }
    }
    .gdd-display {
        font-size: 2rem;
        font-weight: bold;
        color: #27ae60;
    }
    .weather-alert {
        border-left: 4px solid #f39c12;
        background: rgba(243, 156, 18, 0.1);
        margin-bottom: 10px;
    }
    .field-work-excellent {
        background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        color: white;
    }
    .field-work-good {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
    }
    .field-work-fair {
        background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        color: white;
    }
    .field-work-poor {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
    }
    .forecast-day {
        text-align: center;
        padding: 15px;
        border-radius: 8px;
        margin: 5px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
    }
    .mini-temp {
        font-size: 1.2rem;
        font-weight: 600;
    }
    .risk-high { color: #e74c3c; }
    .risk-medium { color: #f39c12; }
    .risk-low { color: #27ae60; }
    .risk-none { color: #95a5a6; }
</style>
@endsection

@section('content')
<div class="container-fluid">

    <!-- Update indicator -->
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>WeatherAPI Integration Active!</strong> Now showing UV index, air quality, precipitation, cloud cover, and more.
        <span class="badge bg-dark float-end">Updated: {{ now()->format('Y-m-d H:i:s') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    @if(isset($error))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $error }}
        </div>
    @endif

    <!-- Current Weather Row -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card weather-card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cloud-sun me-2"></i>
                        Current Weather
                        @if($currentWeather)
                            <span class="badge bg-light text-dark float-end">{{ ucfirst($currentWeather['source'] ?? 'Unknown') }}</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if($currentWeather)
                        <div class="row">
                            <!-- Left column: Main weather -->
                            <div class="col-md-6 text-center border-end">
                                <div class="temperature-display">
                                    {{ round($currentWeather['temperature'] ?? 0) }}°C
                                </div>
                                <h5 class="text-primary mb-2">{{ $currentWeather['description'] ?? 'N/A' }}</h5>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-temperature-low me-1"></i>
                                    Feels like {{ round($currentWeather['feels_like'] ?? 0) }}°C
                                </p>
                                @if(isset($currentWeather['location']))
                                    <p class="text-muted small mb-0">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ $currentWeather['location'] }}, {{ $currentWeather['region'] ?? '' }}
                                    </p>
                                @endif
                            </div>
                            
                            <!-- Right column: Details -->
                            <div class="col-md-6">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block"><i class="fas fa-tint me-1"></i>Humidity</small>
                                        <strong>{{ $currentWeather['humidity'] ?? 'N/A' }}%</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block"><i class="fas fa-wind me-1"></i>Wind</small>
                                        <strong>{{ round($currentWeather['wind_speed_mph'] ?? $currentWeather['wind_speed'] ?? 0) }} mph {{ $currentWeather['wind_dir'] ?? '' }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block"><i class="fas fa-compress-arrows-alt me-1"></i>Pressure</small>
                                        <strong>{{ round($currentWeather['pressure'] ?? 0) }} mb</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block"><i class="fas fa-eye me-1"></i>Visibility</small>
                                        <strong>{{ round($currentWeather['visibility'] ?? 0) }} km</strong>
                                    </div>
                                    @if(isset($currentWeather['uv_index']))
                                    <div class="col-6">
                                        <small class="text-muted d-block"><i class="fas fa-sun me-1"></i>UV Index</small>
                                        <strong class="{{ $currentWeather['uv_index'] > 6 ? 'text-danger' : ($currentWeather['uv_index'] > 3 ? 'text-warning' : 'text-success') }}">
                                            {{ $currentWeather['uv_index'] }}
                                        </strong>
                                    </div>
                                    @endif
                                    @if(isset($currentWeather['precipitation_mm']))
                                    <div class="col-6">
                                        <small class="text-muted d-block"><i class="fas fa-cloud-rain me-1"></i>Rain</small>
                                        <strong>{{ $currentWeather['precipitation_mm'] }} mm</strong>
                                    </div>
                                    @endif
                                    @if(isset($currentWeather['cloud_cover']))
                                    <div class="col-6">
                                        <small class="text-muted d-block"><i class="fas fa-cloud me-1"></i>Cloud</small>
                                        <strong>{{ $currentWeather['cloud_cover'] }}%</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Air Quality Section -->
                        @if(isset($currentWeather['air_quality']))
                        <hr class="my-3">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-2"><i class="fas fa-smog me-2"></i>Air Quality</h6>
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <small class="text-muted d-block">GB DEFRA</small>
                                        <strong class="text-{{ $currentWeather['air_quality']['gb_defra_index'] <= 3 ? 'success' : ($currentWeather['air_quality']['gb_defra_index'] <= 6 ? 'warning' : 'danger') }}">
                                            {{ $currentWeather['air_quality']['gb_defra_index'] }}/10
                                        </strong>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <small class="text-muted d-block">PM2.5</small>
                                        <strong>{{ round($currentWeather['air_quality']['pm2_5'] ?? 0, 1) }}</strong>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <small class="text-muted d-block">PM10</small>
                                        <strong>{{ round($currentWeather['air_quality']['pm10'] ?? 0, 1) }}</strong>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <small class="text-muted d-block">O₃</small>
                                        <strong>{{ round($currentWeather['air_quality']['o3'] ?? 0, 1) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p>Unable to load current weather</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card weather-card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-seedling me-2"></i>
                        Growing Degree Days
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="gdd-display">
                        {{ round($todayGDD ?? 0, 1) }}
                    </div>
                    <p class="text-muted mb-0">GDD accumulated today</p>
                    <small class="text-muted">(Base temperature: 10°C)</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card weather-card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Field Work Status
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($fieldConditions))
                        @php
                            $today = $fieldConditions[0] ?? null;
                            $ratingClass = 'field-work-' . strtolower(str_replace(' ', '-', $today['overall_rating'] ?? 'poor'));
                        @endphp
                        @if($today)
                            <div class="text-center p-3 rounded {{ $ratingClass }}">
                                <h4 class="mb-1">{{ $today['overall_rating'] ?? 'Unknown' }}</h4>
                                <p class="mb-0">{{ $today['temperature_range'] ?? 'N/A' }}</p>
                                <small>{{ $today['rainfall'] ?? '0mm' }} rain</small>
                            </div>
                        @endif
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-question-circle fa-2x mb-2"></i>
                            <p class="mb-0">Field conditions unavailable</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Frost Risk Alerts -->
    @if(!empty($frostRisk))
        @php
            $frostAlerts = array_filter($frostRisk, fn($day) => $day['risk'] !== 'none');
        @endphp
        @if(!empty($frostAlerts))
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert frost-warning">
                        <h5><i class="fas fa-snowflake me-2"></i>Frost Risk Alert</h5>
                        @foreach($frostAlerts as $alert)
                            <div class="mb-1">
                                <strong>{{ $alert['date'] }}</strong>: 
                                {{ ucfirst($alert['risk']) }} risk 
                                ({{ $alert['min_temp'] }}°C minimum)
                            </div>
                        @endforeach
                        <small class="d-block mt-2">
                            <i class="fas fa-shield-alt me-1"></i>
                            Protect sensitive plants and check water systems
                        </small>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- 7-Day Forecast -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card weather-card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-week me-2"></i>
                        7-Day Forecast
                        @if($forecast)
                            <span class="badge bg-light text-dark float-end">{{ ucfirst($forecast['source'] ?? 'Unknown') }}</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($forecast) && isset($forecast['daily']))
                        <div class="row">
                            @foreach(array_slice($forecast['daily'], 0, 7) as $day)
                                <div class="col-md forecast-day">
                                    <div class="fw-bold">
                                        {{ \Carbon\Carbon::parse($day['date'])->format('D') }}
                                    </div>
                                    <div class="small text-muted mb-2">
                                        {{ \Carbon\Carbon::parse($day['date'])->format('M j') }}
                                    </div>
                                    <div class="mini-temp">
                                        {{ round($day['temp']['max'] ?? 0) }}°
                                    </div>
                                    <div class="small text-muted">
                                        {{ round($day['temp']['min'] ?? 0) }}°
                                    </div>
                                    @if(isset($day['rain']) && $day['rain'] > 0)
                                        <div class="small text-primary mt-1">
                                            <i class="fas fa-tint"></i>
                                            {{ round($day['rain'], 1) }}mm
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-cloud-rain fa-2x mb-2"></i>
                            <p class="mb-0">Forecast data unavailable</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Field Work Recommendations -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card weather-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tractor me-2"></i>
                        5-Day Field Work Outlook
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($fieldConditions))
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Temperature</th>
                                        <th>Rain</th>
                                        <th>Wind</th>
                                        <th>Rating</th>
                                        <th>Recommendations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fieldConditions as $day)
                                        <tr>
                                            <td>
                                                <strong>{{ \Carbon\Carbon::parse($day['date'])->format('D, M j') }}</strong>
                                            </td>
                                            <td>{{ $day['temperature_range'] ?? 'N/A' }}</td>
                                            <td>{{ $day['rainfall'] ?? '0mm' }}</td>
                                            <td>{{ $day['wind_speed'] ?? '0 mph' }}</td>
                                            <td>
                                                @php
                                                    $rating = $day['overall_rating'] ?? 'Poor';
                                                    $badgeClass = match(strtolower($rating)) {
                                                        'excellent' => 'bg-success',
                                                        'good' => 'bg-primary',
                                                        'fair' => 'bg-warning text-dark',
                                                        default => 'bg-danger'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ $rating }}</span>
                                            </td>
                                            <td>
                                                @if(isset($day['conditions']))
                                                    <ul class="list-unstyled mb-0 small">
                                                        @foreach($day['conditions'] as $condition)
                                                            <li>
                                                                @if(str_contains(strtolower($condition), 'good'))
                                                                    <i class="fas fa-check text-success me-1"></i>
                                                                @elseif(str_contains(strtolower($condition), 'avoid') || str_contains(strtolower($condition), 'poor'))
                                                                    <i class="fas fa-times text-danger me-1"></i>
                                                                @else
                                                                    <i class="fas fa-minus text-warning me-1"></i>
                                                                @endif
                                                                {{ $condition }}
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                            <p class="mb-0">Field work recommendations unavailable</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Weather Map -->
    <div class="row">
        <div class="col-12">
            <div class="card weather-card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-map me-2"></i>
                        Weather Map
                    </h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-layer="precipitation">
                            <i class="fas fa-cloud-rain me-1"></i>Rain
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-layer="clouds">
                            <i class="fas fa-cloud me-1"></i>Clouds
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-layer="wind">
                            <i class="fas fa-wind me-1"></i>Wind
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-layer="temp">
                            <i class="fas fa-thermometer-half me-1"></i>Temperature
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="weather-map" style="height: 800px; width: 100%;">
                        <!-- OpenWeatherMap integrated map -->
                        <iframe 
                            id="weather-map-frame"
                            src="https://openweathermap.org/weathermap?basemap=map&cities=false&layer=precipitation&lat=51.4934&lon=0.0098&zoom=10"
                            width="100%" 
                            height="800" 
                            frameborder="0" 
                            style="border: 0;">
                        </iframe>
                    </div>
                    <div class="p-3 bg-light">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Map shows weather conditions around your farm location. Click layer buttons to switch between rain, clouds, wind, and temperature views.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Weather Data Sources -->
    <div class="row">
        <div class="col-12">
            <div class="card weather-card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        About This Data
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Weather Sources</h6>
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-cloud me-2 text-primary"></i>WeatherAPI.com (UK priority)</li>
                                <li><i class="fas fa-cloud-sun me-2 text-success"></i>Met Office Site-Specific (premium)</li>
                                <li><i class="fas fa-map me-2 text-info"></i>Met Office Maps (visual)</li>
                                <li><i class="fas fa-globe me-2 text-secondary"></i>OpenWeatherMap (backup)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Farming Focus</h6>
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-snowflake me-2 text-info"></i>Frost risk analysis</li>
                                <li><i class="fas fa-thermometer me-2 text-success"></i>Growing degree days</li>
                                <li><i class="fas fa-tractor me-2 text-warning"></i>Field work conditions</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-refresh weather data every 10 minutes
    setInterval(function() {
        window.location.reload();
    }, 600000);

    // Add timestamp of last update
    document.addEventListener('DOMContentLoaded', function() {
        const timestamp = new Date().toLocaleTimeString();
        const timestampElement = document.createElement('small');
        timestampElement.className = 'text-muted d-block text-center mt-3';
        timestampElement.innerHTML = `<i class="fas fa-clock me-1"></i>Last updated: ${timestamp}`;
        document.querySelector('.container-fluid').appendChild(timestampElement);
    });

    // Weather map layer switching
    document.addEventListener('DOMContentLoaded', function() {
        const layerButtons = document.querySelectorAll('[data-layer]');
        const mapFrame = document.getElementById('weather-map-frame');
        const farmLat = 51.4934; // Farm latitude
        const farmLon = 0.0098;  // Farm longitude
        
        layerButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                layerButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get the selected layer
                const layer = this.getAttribute('data-layer');
                
                // Update the iframe src with new layer
                const newSrc = `https://openweathermap.org/weathermap?basemap=map&cities=false&layer=${layer}&lat=${farmLat}&lon=${farmLon}&zoom=10`;
                mapFrame.src = newSrc;
            });
        });
    });
</script>
@endsection

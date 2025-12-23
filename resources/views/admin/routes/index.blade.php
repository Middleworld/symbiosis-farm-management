@extends('layouts.app')

@section('title', 'Route Planning & Optimization')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-route"></i> Route Planning & Optimization
                    </h3>
                    <div class="card-tools">
                        <input type="date" id="delivery-date" class="form-control" value="{{ $delivery_date }}" onchange="loadDeliveriesForDate(this.value)">
                    </div>
                </div>
                
                <div class="card-body">
                    @if(isset($error))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                        </div>
                    @endif

                    @if(isset($message))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> {{ $message }}
                        </div>
                    @endif

                    @if(!empty($deliveries))
                        <div class="row">
                            <!-- Deliveries List -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-truck"></i> Deliveries ({{ count($deliveries) }})</h5>
                                        <button type="button" id="btn-optimize-enhanced" class="btn btn-primary btn-sm" onclick="optimizeRouteEnhanced()">
                                            <span class="btn-text">
                                                <i class="fas fa-magic"></i> Optimize Route (WP Go Maps Pro)
                                            </span>
                                            <span class="btn-loading" style="display: none;">
                                                <i class="fas fa-spinner fa-spin"></i> Optimizing...
                                            </span>
                                        </button>
                                        <button type="button" id="btn-optimize-standard" class="btn btn-outline-primary btn-sm" onclick="optimizeRoute()">
                                            <span class="btn-text">
                                                <i class="fas fa-route"></i> Standard Optimize
                                            </span>
                                            <span class="btn-loading" style="display: none;">
                                                <i class="fas fa-spinner fa-spin"></i> Optimizing...
                                            </span>
                                        </button>
                                    </div>
                                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                        <div id="deliveries-list">
                                            @foreach($deliveries as $index => $delivery)
                                                <div class="delivery-item border rounded p-2 mb-2" data-index="{{ $index }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $delivery['name'] ?? 'Unknown Customer' }}</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                @if(is_array($delivery['address'] ?? null))
                                                                    {{ implode(', ', $delivery['address']) }}
                                                                @else
                                                                    {{ $delivery['address'] ?? 'No address' }}
                                                                @endif
                                                            </small>
                                                            @if(isset($delivery['products']) && !empty($delivery['products']))
                                                                <br>
                                                                <small class="text-info">
                                                                    @foreach($delivery['products'] as $product)
                                                                        {{ $product['name'] ?? 'Product' }}{{ !$loop->last ? ', ' : '' }}
                                                                    @endforeach
                                                                </small>
                                                            @endif
                                                        </div>
                                                        <span class="badge badge-primary">{{ $index + 1 }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Route Actions -->
                                <div class="card mt-3" id="route-actions" style="display: none;">
                                    <div class="card-header">
                                        <h5><i class="fas fa-paper-plane"></i> Send to Driver</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="driver-email">Driver Email:</label>
                                            <input type="email" id="driver-email" class="form-control" placeholder="driver@example.com">
                                        </div>
                                        <div class="form-group">
                                            <label for="driver-phone">Driver Phone (optional):</label>
                                            <input type="tel" id="driver-phone" class="form-control" placeholder="+44 7xxx xxx xxx">
                                        </div>
                                        <div class="btn-group w-100">
                                            <button type="button" class="btn btn-success" onclick="sendToDriverEmail()">
                                                <i class="fas fa-envelope"></i> Send Email
                                            </button>
                                            <button type="button" class="btn btn-info" onclick="sendToDriverSMS()">
                                                <i class="fas fa-sms"></i> Send SMS
                                            </button>
                                        </div>
                                        
                                        <!-- Google Maps Actions -->
                                        <div class="mt-3">
                                            <hr>
                                            <h6><i class="fab fa-google"></i> Google Maps Actions</h6>
                                            <div class="btn-group w-100">
                                                <button type="button" class="btn btn-outline-primary" onclick="openInGoogleMaps()">
                                                    <i class="fas fa-external-link-alt"></i> Open in Google Maps
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="shareRouteLink()">
                                                    <i class="fas fa-share"></i> Share Route Link
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- WP Go Maps Integration -->
                                <div class="card mt-3" id="wp-maps-integration">
                                    <div class="card-header">
                                        <h5><i class="fas fa-wordpress"></i> WP Go Maps Pro</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <button type="button" class="btn btn-info btn-block" onclick="createShareableMap()">
                                                <i class="fas fa-share-alt"></i> Create Shareable Map
                                            </button>
                                        </div>
                                        <div id="shareable-map-result" style="display: none;">
                                            <div class="alert alert-success">
                                                <strong>Shareable Map Created!</strong><br>
                                                <a id="shareable-link" href="#" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="fas fa-external-link-alt"></i> View Public Map
                                                </a>
                                                <br><small class="text-muted">
                                                    Shortcode: <code id="map-shortcode">[wpgmza id=""]</code>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Enhanced Features:</label>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success"></i> Customer location history</li>
                                                <li><i class="fas fa-check text-success"></i> Advanced route optimization</li>
                                                <li><i class="fas fa-check text-success"></i> Public shareable maps</li>
                                                <li><i class="fas fa-check text-success"></i> WordPress integration</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Map -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-map"></i> Route Map</h5>
                                        <div id="route-summary" style="display: none;">
                                            <small class="text-muted">
                                                <span id="total-distance">-</span> | <span id="total-duration">-</span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="map" style="height: 500px; width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No deliveries found</h4>
                            <p class="text-muted">Select a different date or check your delivery schedule.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Loading button animations */
.btn-loading .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Additional pulse effect for the button when loading */
.btn:disabled .btn-loading {
    animation: pulse 1.5s ease-in-out infinite alternate;
}

@keyframes pulse {
    0% { opacity: 0.6; }
    100% { opacity: 1; }
}
</style>

<!-- Hidden data -->
<script>
window.deliveriesData = @json($deliveries ?? []);
window.googleMapsKey = '{{ $google_maps_key ?? '' }}';
window.deliveryDate = '{{ $delivery_date }}';

// Debug CSRF token
console.log('CSRF Token available:', !!document.querySelector('meta[name="csrf-token"]'));
console.log('CSRF Token value:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
console.log('Deliveries data loaded:', window.deliveriesData?.length || 0, 'deliveries');
</script>
@endsection

@section('scripts')
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $google_maps_key ?? '' }}&callback=initMap"></script>

<script>
let map;
let directionsService;
let directionsRenderer;
let markers = [];
let optimizedRoute = null;

// Initialize Google Map
function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 11,
        center: { lat: 53.214542, lng: -0.421672 } // Middle World Farms driveway
    });

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        draggable: false,
        suppressMarkers: false
    });
    directionsRenderer.setMap(map);

    // Load initial markers
    loadMapMarkers();
}

// Load markers for deliveries
function loadMapMarkers() {
    clearMarkers();
    
    if (!window.deliveriesData || window.deliveriesData.length === 0) {
        return;
    }

    // Add depot marker (Middle World Farms location)
    const depotMarker = new google.maps.Marker({
        position: { lat: 53.214542, lng: -0.421672 }, // Middle World Farms driveway
        map: map,
        title: 'Middle World Farms (Depot)',
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="16" cy="16" r="14" fill="#28a745" stroke="white" stroke-width="2"/>
                    <text x="16" y="21" text-anchor="middle" fill="white" font-size="12" font-weight="bold">D</text>
                </svg>
            `),
            scaledSize: new google.maps.Size(32, 32),
            anchor: new google.maps.Point(16, 16)
        }
    });
    markers.push(depotMarker);

    // Geocode delivery addresses and add markers
    const geocoder = new google.maps.Geocoder();
    const bounds = new google.maps.LatLngBounds();
    
    let geocodedCount = 0;
    
    window.deliveriesData.forEach((delivery, index) => {
        if (delivery.address) {
            geocoder.geocode({ address: delivery.address }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const position = results[0].geometry.location;
                    
                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: `${delivery.name || 'Customer'} - ${delivery.address}`,
                        icon: {
                            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="16" r="14" fill="#007bff" stroke="white" stroke-width="2"/>
                                    <text x="16" y="21" text-anchor="middle" fill="white" font-size="10" font-weight="bold">${index + 1}</text>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 16)
                        }
                    });
                    
                    markers.push(marker);
                    bounds.extend(position);
                    
                    // Add info window
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div>
                                <strong>${delivery.name || 'Customer'}</strong><br>
                                <small>${delivery.address}</small><br>
                                Phone: ${delivery.phone || 'N/A'}<br>
                                ${delivery.products ? delivery.products.map(p => `${p.quantity}x ${p.name}`).join('<br>') : ''}
                            </div>
                        `
                    });
                    
                    marker.addListener('click', () => {
                        infoWindow.open(map, marker);
                    });
                    
                    geocodedCount++;
                    
                    // Fit map to show all markers after all geocoding is complete
                    if (geocodedCount === window.deliveriesData.length) {
                        bounds.extend(depotMarker.getPosition());
                        map.fitBounds(bounds);
                        
                        // Ensure minimum zoom level
                        const listener = google.maps.event.addListener(map, "idle", function() {
                            if (map.getZoom() > 15) map.setZoom(15);
                            google.maps.event.removeListener(listener);
                        });
                    }
                } else {
                    console.error('Geocoding failed for address: ' + delivery.address + ', Status: ' + status);
                    
                    // Still count failed geocodes to complete the bounds fitting
                    geocodedCount++;
                    if (geocodedCount === window.deliveriesData.length) {
                        bounds.extend(depotMarker.getPosition());
                        if (!bounds.isEmpty()) {
                            map.fitBounds(bounds);
                        }
                    }
                }
            });
        } else {
            console.warn('No address provided for delivery:', delivery);
            geocodedCount++;
        }
    });
}

// Clear all markers
function clearMarkers() {
    markers.forEach(marker => marker.setMap(null));
    markers = [];
}

// Optimize route
function optimizeRoute() {
    console.log('optimizeRoute() called');
    
    if (!window.deliveriesData || window.deliveriesData.length === 0) {
        console.log('No deliveries data available');
        alert('No deliveries to optimize');
        return;
    }

    console.log('Starting optimization with', window.deliveriesData.length, 'deliveries');
    showLoading('Optimizing route...', 'btn-optimize-standard');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    console.log('CSRF token for request:', csrfToken);

    fetch('/admin/routes/optimize', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            deliveries: window.deliveriesData
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Optimization response:', data);
        hideLoading();
        
        if (data.success) {
            optimizedRoute = data;
            updateDeliveriesList(data.optimized_deliveries);
            updateRouteMap(data);
            showRouteActions();
            showRouteStats(data.total_distance, data.total_duration);
        } else {
            alert('Route optimization failed: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Route optimization error:', error);
        alert('Route optimization failed. Please try again.');
    });
}

// Update deliveries list with optimized order
function updateDeliveriesList(optimizedDeliveries) {
    const listContainer = document.getElementById('deliveries-list');
    listContainer.innerHTML = '';
    
    optimizedDeliveries.forEach((delivery, index) => {
        const item = document.createElement('div');
        item.className = 'delivery-item border rounded p-2 mb-2';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>${delivery.name || 'Unknown Customer'}</strong>
                    <br>
                    <small class="text-muted">
                        ${Array.isArray(delivery.address) ? delivery.address.join(', ') : (delivery.address || 'No address')}
                    </small>
                    ${delivery.products && delivery.products.length > 0 ? `
                        <br>
                        <small class="text-info">
                            ${delivery.products.map(p => p.name || 'Product').join(', ')}
                        </small>
                    ` : ''}
                </div>
                <span class="badge badge-success">${index + 1}</span>
            </div>
        `;
        listContainer.appendChild(item);
    });
}

// Update route on map
function updateRouteMap(routeData) {
    console.log('Updating map with route data:', routeData);
    
    if (!routeData || !routeData.optimized_deliveries || !map) {
        console.log('Missing route data or map not initialized');
        return;
    }

    // Clear existing route
    if (directionsRenderer) {
        directionsRenderer.setMap(null);
    }
    
    // Create new directions renderer for the optimized route
    directionsRenderer = new google.maps.DirectionsRenderer({
        draggable: false,
        suppressMarkers: false,
        polylineOptions: {
            strokeColor: '#007bff',
            strokeWeight: 4,
            strokeOpacity: 0.8
        }
    });
    directionsRenderer.setMap(map);

    // Build waypoints for the route
    const deliveries = routeData.optimized_deliveries;
    if (deliveries.length < 2) {
        console.log('Need at least 2 deliveries for route');
        return;
    }

    // Start from depot (Middle World Farms)
    const origin = { lat: 53.214542, lng: -0.421672 }; // Middle World Farms driveway
    const destination = origin; // Return to depot
    
    // Create waypoints from delivery addresses
    const waypoints = [];
    
    // Use the geocoded positions from our markers, or geocode if needed
    deliveries.forEach((delivery, index) => {
        if (delivery.address) {
            // For now, we'll use geocoding service for each address
            // In a real implementation, you might want to cache these coordinates
            waypoints.push({
                location: delivery.address,
                stopover: true
            });
        }
    });

    if (waypoints.length === 0) {
        console.log('No valid waypoints found');
        return;
    }

    // Calculate and display route
    directionsService.route({
        origin: origin,
        destination: destination,
        waypoints: waypoints,
        optimizeWaypoints: false, // We already optimized the order
        travelMode: google.maps.TravelMode.DRIVING,
        avoidTolls: true,
        avoidHighways: false
    }, (result, status) => {
        if (status === 'OK') {
            directionsRenderer.setDirections(result);
            
            // Store route for Google Maps integration
            window.currentRoute = {
                directions: result,
                deliveries: deliveries,
                routeData: routeData
            };
            
            console.log('Route displayed successfully');
        } else {
            console.error('Directions request failed due to ' + status);
            
            // Fallback: just show markers without route line
            console.log('Falling back to marker-only display');
            loadMapMarkers();
        }
    });
}

// Show route actions panel
function showRouteActions() {
    document.getElementById('route-actions').style.display = 'block';
}

// Show route statistics
function showRouteStats(distance, duration) {
    document.getElementById('total-distance').textContent = distance;
    document.getElementById('total-duration').textContent = duration;
    document.getElementById('route-summary').style.display = 'block';
}

// Send route to driver via email
function sendToDriverEmail() {
    const email = document.getElementById('driver-email').value;
    if (!email) {
        alert('Please enter driver email');
        return;
    }

    if (!optimizedRoute) {
        alert('Please optimize route first');
        return;
    }

    showLoading('Sending email...');

    fetch('/admin/routes/send-to-driver', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            driver_email: email,
            deliveries: optimizedRoute.optimized_deliveries,
            route_details: optimizedRoute.route_details,
            delivery_date: window.deliveryDate
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            alert('Route sent to driver successfully!');
        } else {
            alert('Failed to send route: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Send email error:', error);
        alert('Failed to send email. Please try again.');
    });
}

// Send route to driver via SMS
function sendToDriverSMS() {
    const phone = document.getElementById('driver-phone').value;
    if (!phone) {
        alert('Please enter driver phone number');
        return;
    }

    if (!optimizedRoute) {
        alert('Please optimize route first');
        return;
    }

    showLoading('Sending SMS...');

    fetch('/admin/routes/send-to-driver-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            driver_phone: phone,
            deliveries: optimizedRoute.optimized_deliveries,
            route_details: optimizedRoute.route_details,
            delivery_date: window.deliveryDate
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            alert('Route sent to driver via SMS successfully!');
        } else {
            alert('Failed to send SMS: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Send SMS error:', error);
        alert('Failed to send SMS. Please try again.');
    });
}

// Load deliveries for different date
function loadDeliveriesForDate(date) {
    window.location.href = '/admin/routes?date=' + date;
}

// Loading helpers
function showLoading(message, buttonId) {
    console.log('Loading:', message);
    
    // Disable and show loading state for the specific button
    if (buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = true;
            button.querySelector('.btn-text').style.display = 'none';
            button.querySelector('.btn-loading').style.display = 'inline';
        }
    }
    
    // Also disable both buttons to prevent multiple requests
    document.getElementById('btn-optimize-enhanced').disabled = true;
    document.getElementById('btn-optimize-standard').disabled = true;
}

function hideLoading() {
    console.log('Loading complete');
    
    // Re-enable and hide loading state for both buttons
    const buttons = ['btn-optimize-enhanced', 'btn-optimize-standard'];
    buttons.forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = false;
            button.querySelector('.btn-text').style.display = 'inline';
            button.querySelector('.btn-loading').style.display = 'none';
        }
    });
}

// Create shareable WP Go Maps map
function createShareableMap() {
    if (!optimizedRoute) {
        alert('Please optimize route first');
        return;
    }

    showLoading('Creating shareable map...');

    fetch('/admin/routes/create-shareable-map', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            deliveries: optimizedRoute.optimized_deliveries,
            route_details: optimizedRoute.route_details,
            delivery_date: window.deliveryDate
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // Show shareable map result
            document.getElementById('shareable-link').href = data.shareable_link;
            document.getElementById('map-shortcode').textContent = data.shortcode;
            document.getElementById('shareable-map-result').style.display = 'block';
            
            console.log('WP Go Maps integration:', data);
        } else {
            alert('Failed to create shareable map: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Shareable map creation error:', error);
        alert('Failed to create shareable map. Please try again.');
    });
}

// Enhanced route optimization with WP Go Maps data
function optimizeRouteEnhanced() {
    console.log('optimizeRouteEnhanced() called');
    
    if (!window.deliveriesData || window.deliveriesData.length === 0) {
        console.log('No deliveries data available for enhanced optimization');
        alert('No deliveries to optimize');
        return;
    }

    console.log('Starting enhanced optimization with', window.deliveriesData.length, 'deliveries');
    showLoading('Optimizing route with WP Go Maps Pro...', 'btn-optimize-enhanced');

    // First, get WP Go Maps data for customers
    const customerEmails = window.deliveriesData
        .map(d => d.email)
        .filter(email => email);

    Promise.all(customerEmails.map(email => 
        fetch('/admin/routes/wp-go-maps-data?customer_email=' + encodeURIComponent(email))
        .then(response => response.json())
        .catch(() => ({ success: false }))
    ))
    .then(locationResults => {
        // Enhance deliveries with location data
        const enhancedDeliveries = window.deliveriesData.map((delivery, index) => {
            const locationData = locationResults[index];
            if (locationData && locationData.success) {
                delivery.wp_maps_location = locationData.location_data;
            }
            return delivery;
        });

        // Now optimize with enhanced data
        return fetch('/admin/routes/optimize', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                deliveries: enhancedDeliveries
            })
        });
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            optimizedRoute = data;
            updateDeliveriesList(data.optimized_deliveries);
            updateRouteMap(data);
            showRouteActions();
            showRouteStats(data.total_distance, data.total_duration);
            
            // Show optimization source
            if (data.optimization_source) {
                console.log('Route optimized using:', data.optimization_source);
            }
            
            // Show WP Go Maps integration status
            if (data.wp_maps) {
                console.log('WP Go Maps integration available:', data.wp_maps);
            }
        } else {
            alert('Route optimization failed: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Enhanced route optimization error:', error);
        
        // Fallback to standard optimization
        console.log('Falling back to standard optimization...');
        optimizeRoute();
    });
}

// Google Maps Integration Functions
function openInGoogleMaps() {
    if (!window.currentRoute || !window.currentRoute.deliveries) {
        alert('Please optimize route first');
        return;
    }

    const deliveries = window.currentRoute.deliveries;
    const depot = "Middle World Farms, LN4 1AQ, UK";
    
    // Build Google Maps URL with waypoints
    let url = "https://www.google.com/maps/dir/";
    
    // Start from depot
    url += encodeURIComponent(depot);
    
    // Add each delivery address as waypoint
    deliveries.forEach(delivery => {
        if (delivery.address) {
            url += "/" + encodeURIComponent(delivery.address);
        }
    });
    
    // Return to depot
    url += "/" + encodeURIComponent(depot);
    
    // Open in new tab
    window.open(url, '_blank');
}

function shareRouteLink() {
    if (!window.currentRoute || !window.currentRoute.deliveries) {
        alert('Please optimize route first');
        return;
    }

    const deliveries = window.currentRoute.deliveries;
    const depot = "Middle World Farms, LN4 1AQ, UK";
    
    // Build shareable Google Maps URL
    let url = "https://www.google.com/maps/dir/";
    url += encodeURIComponent(depot);
    
    deliveries.forEach(delivery => {
        if (delivery.address) {
            url += "/" + encodeURIComponent(delivery.address);
        }
    });
    
    url += "/" + encodeURIComponent(depot);
    
    // Copy to clipboard
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            alert('Route link copied to clipboard!');
        }).catch(() => {
            // Fallback - show in prompt
            prompt('Copy this route link:', url);
        });
    } else {
        // Fallback for older browsers
        prompt('Copy this route link:', url);
    }
}

// ...existing functions...
</script>
@endsection

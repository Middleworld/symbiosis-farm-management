@extends('layouts.app')

@section('title', 'Planting Recommendations')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Planting Recommendations</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-seedling"></i> Smart Planting Recommendations</h5>
                        <p>Get AI-powered planting recommendations based on your local weather conditions, soil data, and historical performance.</p>
                    </div>

                    <div id="planting-recommendations-content">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading planting recommendations...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadPlantingRecommendations();
});

function loadPlantingRecommendations() {
    const contentDiv = document.getElementById('planting-recommendations-content');

    // Show loading state
    contentDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Analyzing weather data and generating recommendations...</p>
        </div>
    `;

    // Fetch planting recommendations
    fetch('/admin/weather/planting-analysis', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            analysis_type: 'planting_recommendations'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPlantingRecommendations(data.data);
        } else {
            contentDiv.innerHTML = `
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Unable to Load Recommendations</h5>
                    <p>${data.message || 'An error occurred while generating planting recommendations.'}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading planting recommendations:', error);
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle"></i> Error</h5>
                <p>Failed to load planting recommendations. Please try again later.</p>
            </div>
        `;
    });
}

function displayPlantingRecommendations(data) {
    const contentDiv = document.getElementById('planting-recommendations-content');

    let html = '<div class="row">';

    if (data.recommendations && data.recommendations.length > 0) {
        data.recommendations.forEach(rec => {
            html += `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">${rec.crop_name || 'Unknown Crop'}</h5>
                            <div class="mb-3">
                                <span class="badge bg-success">${rec.confidence || 0}% Match</span>
                            </div>
                            <p class="card-text">${rec.reasoning || 'AI-generated recommendation based on weather conditions.'}</p>
                            <div class="small text-muted">
                                <p><strong>Best planting time:</strong> ${rec.planting_window || 'Unknown'}</p>
                                <p><strong>Expected yield:</strong> ${rec.expected_yield || 'Unknown'}</p>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewCropDetails('${rec.crop_id || ''}')">
                                <i class="fas fa-info-circle"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> No Recommendations Available</h5>
                    <p>No planting recommendations are currently available. This may be due to insufficient weather data or crop information.</p>
                </div>
            </div>
        `;
    }

    html += '</div>';
    contentDiv.innerHTML = html;
}

function viewCropDetails(cropId) {
    if (cropId) {
        window.open(`/admin/farmos/crops/${cropId}`, '_blank');
    } else {
        alert('Crop details not available');
    }
}
</script>
@endsection
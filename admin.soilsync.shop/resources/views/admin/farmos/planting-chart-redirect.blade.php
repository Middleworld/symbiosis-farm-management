@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">🌱 Planting Planning Tools</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>📍 This page has been updated!</strong>
                        <p class="mb-0 mt-2">Planting chart functionality is now integrated with farmOS native tools for better data management and real-time updates.</p>
                    </div>

                    <h5 class="mt-4 mb-3">Choose Your Planning Tool:</h5>

                    <div class="row g-3">
                        <!-- Succession Planner -->
                        <div class="col-md-6">
                            <a href="{{ $successionPlannerUrl }}" class="text-decoration-none">
                                <div class="card h-100 border-primary hover-shadow">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="fas fa-calendar-alt"></i> Succession Planner
                                        </h5>
                                        <p class="card-text">
                                            Plan crop successions with AI assistance, view bed availability timeline, and generate seeding/transplanting schedules.
                                        </p>
                                        <ul class="small">
                                            <li>Bed occupancy visualization</li>
                                            <li>Drag-and-drop timeline</li>
                                            <li>AI crop planning chat</li>
                                            <li>Export to farmOS</li>
                                        </ul>
                                        <span class="btn btn-primary btn-sm mt-2">
                                            Open Succession Planner →
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- farmOS Crop Plans -->
                        <div class="col-md-6">
                            <a href="{{ $farmosTimelineUrl }}" target="_blank" class="text-decoration-none">
                                <div class="card h-100 border-success hover-shadow">
                                    <div class="card-body">
                                        <h5 class="card-title text-success">
                                            <i class="fas fa-seedling"></i> farmOS Crop Plans
                                        </h5>
                                        <p class="card-text">
                                            Create and manage crop plans directly in farmOS with native timeline visualization by variety and location.
                                        </p>
                                        <ul class="small">
                                            <li>Native farmOS integration</li>
                                            <li>Timeline by plant type</li>
                                            <li>Timeline by location</li>
                                            <li>Season management</li>
                                        </ul>
                                        <span class="btn btn-success btn-sm mt-2">
                                            Open farmOS Plans →
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- farmOS Map View -->
                        <div class="col-md-6">
                            <a href="{{ $farmosMapUrl }}" target="_blank" class="text-decoration-none">
                                <div class="card h-100 border-info hover-shadow">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="fas fa-map"></i> farmOS Map View
                                        </h5>
                                        <p class="card-text">
                                            View all beds, plantings, and farm assets on an interactive map with real-time data.
                                        </p>
                                        <ul class="small">
                                            <li>Satellite imagery</li>
                                            <li>Bed locations</li>
                                            <li>Current plantings</li>
                                            <li>Asset tracking</li>
                                        </ul>
                                        <span class="btn btn-info btn-sm mt-2">
                                            Open Map View →
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- farmOS Timeline -->
                        <div class="col-md-6">
                            <a href="{{ $farmosUrl }}" target="_blank" class="text-decoration-none">
                                <div class="card h-100 border-warning hover-shadow">
                                    <div class="card-body">
                                        <h5 class="card-title text-warning">
                                            <i class="fas fa-stream"></i> farmOS Dashboard
                                        </h5>
                                        <p class="card-text">
                                            Access the complete farmOS dashboard with all farm management features and reporting tools.
                                        </p>
                                        <ul class="small">
                                            <li>Global timeline view</li>
                                            <li>Log management</li>
                                            <li>Asset management</li>
                                            <li>Inventory tracking</li>
                                        </ul>
                                        <span class="btn btn-warning btn-sm mt-2">
                                            Open farmOS →
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-light mt-4 border">
                        <h6 class="mb-2">💡 <strong>Why the change?</strong></h6>
                        <p class="mb-0 small">
                            This standalone planting chart duplicated functionality that farmOS already provides natively. 
                            By using farmOS's built-in tools, your data stays in one place, updates in real-time, 
                            and integrates seamlessly with other farm operations. The Succession Planner still provides 
                            advanced planning features with AI assistance while working directly with farmOS data.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
}
</style>
@endsection

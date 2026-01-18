@extends('layouts.app')

@section('title', 'Deliveries & Collections Management')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-truck"></i> Deliveries & Collections Management
                    </h1>
                    <a href="{{ route('admin.docs.user-manual') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Manual
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h2>Overview</h2>
                            <p>The Deliveries & Collections Management system helps you efficiently organize and optimize your farm delivery operations. This guide covers everything from basic delivery scheduling to advanced route optimization using Google Maps.</p>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Key Features:</strong> Bulk operations, Google Maps route optimization, delivery tracking, and collection management.
                            </div>

                            <h3 class="mt-4">📅 Delivery Scheduling</h3>

                            <h4>Viewing Deliveries</h4>
                            <p>Access the delivery management interface through the main navigation:</p>
                            <ol>
                                <li>Go to <strong>Admin → Deliveries & Collections</strong></li>
                                <li>View upcoming deliveries organized by date and time</li>
                                <li>Use filters to show deliveries by week (A/B), status, or customer</li>
                            </ol>

                            <h4>Delivery Status Types</h4>
                            <ul>
                                <li><span class="badge bg-warning">Pending</span> - Delivery scheduled but not yet processed</li>
                                <li><span class="badge bg-info">In Progress</span> - Driver has started delivery route</li>
                                <li><span class="badge bg-success">Completed</span> - Delivery successfully completed</li>
                                <li><span class="badge bg-danger">Failed</span> - Delivery could not be completed</li>
                            </ul>

                            <h3 class="mt-4">📦 Bulk Operations</h3>

                            <h4>Selecting Multiple Deliveries</h4>
                            <p>You can perform bulk operations on multiple deliveries at once:</p>
                            <ol>
                                <li>Check the <strong>master checkbox</strong> in the table header to select all deliveries</li>
                                <li>Or check individual checkboxes next to specific deliveries</li>
                                <li>Use the bulk action dropdown to apply operations to selected items</li>
                            </ol>

                            <h4>Available Bulk Actions</h4>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>Use Case</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Mark as Completed</strong></td>
                                            <td>Mark selected deliveries as successfully completed</td>
                                            <td>After completing a delivery run</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Mark as Failed</strong></td>
                                            <td>Mark deliveries as failed with reason</td>
                                            <td>When delivery cannot be completed</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Update Status</strong></td>
                                            <td>Change status for multiple deliveries</td>
                                            <td>Bulk status updates</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Export Selected</strong></td>
                                            <td>Export selected deliveries to CSV/PDF</td>
                                            <td>Creating delivery manifests</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h3 class="mt-4">🗺️ Route Optimization</h3>

                            <h4>Google Maps Integration</h4>
                            <p>The system uses Google Maps API for advanced route optimization:</p>
                            <ul>
                                <li><strong>Automatic Optimization:</strong> Calculates the most efficient delivery route</li>
                                <li><strong>Real-time Traffic:</strong> Considers current traffic conditions</li>
                                <li><strong>Multiple Stops:</strong> Handles complex routes with many delivery points</li>
                                <li><strong>Distance & Time:</strong> Provides accurate estimates for planning</li>
                            </ul>

                            <h4>Using Route Optimization</h4>
                            <ol>
                                <li>Select the deliveries you want to optimize</li>
                                <li>Click <strong>"Optimize Route (Google Maps API)"</strong></li>
                                <li>Review the optimized route on the interactive map</li>
                                <li>Export the route or send to drivers</li>
                            </ol>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Note:</strong> Route optimization requires a valid Google Maps API key configured in your system settings.
                            </div>

                            <h3 class="mt-4">🏪 Collection Management</h3>

                            <h4>Collection Types</h4>
                            <p>The system supports different collection methods:</p>
                            <ul>
                                <li><strong>Farm Collection:</strong> Customers pick up orders at the farm</li>
                                <li><strong>Delivery Point Collection:</strong> Pick up from designated collection points</li>
                                <li><strong>Home Delivery:</strong> Direct delivery to customer address</li>
                            </ul>

                            <h4>Managing Collections</h4>
                            <ol>
                                <li>View collection schedules in the deliveries interface</li>
                                <li>Mark collections as ready for pickup</li>
                                <li>Track collection status and notify customers</li>
                                <li>Handle special collection requests</li>
                            </ol>

                            <h3 class="mt-4">📊 Reporting & Analytics</h3>

                            <h4>Delivery Reports</h4>
                            <p>Generate various reports to track delivery performance:</p>
                            <ul>
                                <li><strong>Delivery Success Rate:</strong> Percentage of successful deliveries</li>
                                <li><strong>Average Delivery Time:</strong> Time taken per delivery</li>
                                <li><strong>Route Efficiency:</strong> Distance and time optimization metrics</li>
                                <li><strong>Customer Satisfaction:</strong> Feedback and ratings</li>
                            </ul>

                            <h4>Exporting Data</h4>
                            <p>Export delivery data for external analysis:</p>
                            <ul>
                                <li><strong>CSV Export:</strong> Raw data for spreadsheet analysis</li>
                                <li><strong>PDF Reports:</strong> Formatted reports for sharing</li>
                                <li><strong>Route Maps:</strong> Visual route representations</li>
                            </ul>

                            <h3 class="mt-4">⚙️ Configuration</h3>

                            <h4>Delivery Settings</h4>
                            <p>Configure delivery preferences in the admin settings:</p>
                            <ul>
                                <li><strong>Delivery Windows:</strong> Set preferred delivery time slots</li>
                                <li><strong>Route Optimization:</strong> Configure Google Maps API settings</li>
                                <li><strong>Notification Settings:</strong> Customer and driver notifications</li>
                                <li><strong>Geographic Boundaries:</strong> Define delivery service areas</li>
                            </ul>

                            <h4>Driver Management</h4>
                            <p>Manage delivery drivers and assign routes:</p>
                            <ul>
                                <li><strong>Driver Profiles:</strong> Contact information and vehicle details</li>
                                <li><strong>Route Assignment:</strong> Assign optimized routes to drivers</li>
                                <li><strong>Performance Tracking:</strong> Monitor driver efficiency</li>
                                <li><strong>Communication:</strong> Send route updates and instructions</li>
                            </ul>

                            <h3 class="mt-4">🚨 Troubleshooting</h3>

                            <h4>Common Issues</h4>
                            <div class="accordion" id="troubleshootingAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#issue1">
                                            Route optimization not working
                                        </button>
                                    </h2>
                                    <div id="issue1" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Check Google Maps API key configuration</li>
                                                <li>Verify API quota hasn't been exceeded</li>
                                                <li>Ensure delivery addresses are properly formatted</li>
                                                <li>Check network connectivity</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#issue2">
                                            Bulk operations not working
                                        </button>
                                    </h2>
                                    <div id="issue2" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Ensure at least one delivery is selected</li>
                                                <li>Check user permissions for bulk operations</li>
                                                <li>Verify JavaScript is enabled in your browser</li>
                                                <li>Try refreshing the page</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#issue3">
                                            Delivery status not updating
                                        </button>
                                    </h2>
                                    <div id="issue3" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Check database connectivity</li>
                                                <li>Verify user has update permissions</li>
                                                <li>Look for error messages in the browser console</li>
                                                <li>Try clearing browser cache</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h3 class="mt-4">📞 Support</h3>
                            <p>If you encounter issues not covered in this guide:</p>
                            <ul>
                                <li><strong>AI Helper:</strong> Ask the AI assistant in the sidebar for immediate help</li>
                                <li><strong>System Logs:</strong> Check the admin logs for detailed error information</li>
                                <li><strong>Administrator:</strong> Contact your system administrator for technical issues</li>
                                <li><strong>Documentation:</strong> Refer to the full system documentation</li>
                            </ul>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-list-check"></i> Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('admin.deliveries.index') }}" class="btn btn-success btn-sm">
                                            <i class="fas fa-truck"></i> View Deliveries
                                        </a>
                                        <a href="{{ route('admin.routes.index') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-route"></i> Route Planner
                                        </a>
                                        <a href="{{ route('admin.deliveries.create') }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-plus"></i> New Delivery
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-info text-white mt-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-lightbulb"></i> Pro Tips</h6>
                                    <ul class="mb-0 small">
                                        <li>Use bulk selection for efficient processing</li>
                                        <li>Always optimize routes before starting deliveries</li>
                                        <li>Update delivery status immediately after completion</li>
                                        <li>Export routes for drivers who prefer paper maps</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><kbd>Ctrl</kbd>+<kbd>A</kbd></td>
                                            <td><small>Select all deliveries</small></td>
                                        </tr>
                                        <tr>
                                            <td><kbd>Enter</kbd></td>
                                            <td><small>Quick status update</small></td>
                                        </tr>
                                        <tr>
                                            <td><kbd>R</kbd></td>
                                            <td><small>Optimize route</small></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'User Manual')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-book"></i> User Manual
                    </h1>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h2>Welcome to the Farm Delivery System</h2>
                            <p class="lead">Comprehensive guides to help you manage your farm operations efficiently.</p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Quick Start:</strong> New to the system? Start with the guides below, or ask the AI Helper in the sidebar for contextual help.
                            </div>
                            
                            <h3 class="mt-4">📚 Feature Guides</h3>
                            
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-file-invoice text-primary"></i> 
                                                Subscription Management
                                            </h5>
                                            <p class="card-text">Create and manage customer subscriptions, handle renewals, and process plan changes.</p>
                                            <a href="{{ route('admin.docs.page', 'subscription-management') }}" class="btn btn-sm btn-outline-primary">
                                                Read Guide <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-truck text-success"></i> 
                                                Delivery Management
                                            </h5>
                                            <p class="card-text">Plan delivery routes, assign drivers, track deliveries, and manage collection days.</p>
                                            <a href="{{ route('admin.docs.page', 'delivery-management') }}" class="btn btn-sm btn-outline-success">
                                                Read Guide <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-seedling text-success"></i> 
                                                Succession Planning
                                            </h5>
                                            <p class="card-text">Create crop succession plans, schedule plantings, and optimize harvest timing.</p>
                                            <a href="{{ route('admin.docs.page', 'succession-planning') }}" class="btn btn-sm btn-outline-success">
                                                Read Guide <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-tasks text-info"></i> 
                                                Task System
                                            </h5>
                                            <p class="card-text">Create, assign, and track farm tasks. Manage priorities and deadlines effectively.</p>
                                            <a href="{{ route('admin.docs.page', 'task-system') }}" class="btn btn-sm btn-outline-info">
                                                Read Guide <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-users text-warning"></i> 
                                                CRM & Customer Management
                                            </h5>
                                            <p class="card-text">Manage customer relationships, track interactions, and handle support requests.</p>
                                            <a href="{{ route('admin.docs.page', 'crm-usage') }}" class="btn btn-sm btn-outline-warning">
                                                Read Guide <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="fas fa-user-shield text-secondary"></i> 
                                                User Management
                                            </h5>
                                            <p class="card-text">Manage staff accounts, set permissions, and control system access.</p>
                                            <a href="{{ route('admin.docs.page', 'user-management') }}" class="btn btn-sm btn-outline-secondary">
                                                Read Guide <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="mt-4">💡 Quick Tips</h3>
                            <ul>
                                <li><strong>AI Helper:</strong> Use the AI Helper in the sidebar for quick contextual help on any page</li>
                                <li><strong>Keyboard Shortcuts:</strong> Press <kbd>?</kbd> on any page to see available shortcuts</li>
                                <li><strong>Search:</strong> Use the search bar at the top to quickly find customers, orders, or tasks</li>
                                <li><strong>Notifications:</strong> Check the bell icon for important system notifications</li>
                            </ul>
                            
                            <h3 class="mt-4">🆘 Need More Help?</h3>
                            <div class="alert alert-light">
                                <ul class="mb-0">
                                    <li>Ask the <strong>AI Helper</strong> in the sidebar for instant assistance</li>
                                    <li>Check the relevant feature guide above</li>
                                    <li>Contact your system administrator</li>
                                    <li>Visit the <a href="https://github.com/middleworldfarms/admin-middleworldfarms/wiki" target="_blank">Wiki</a> for detailed documentation</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bookmark"></i> Quick Reference</h5>
                                </div>
                                <div class="card-body">
                                    <h6>Common Tasks</h6>
                                    <ul class="list-unstyled">
                                        <li><a href="{{ route('admin.vegbox-subscriptions.index') }}">View Subscriptions</a></li>
                                        <li><a href="{{ route('admin.deliveries.index') }}">Check Deliveries</a></li>
                                        <li><a href="{{ route('admin.tasks.index') }}">Manage Tasks</a></li>
                                        <li><a href="{{ route('admin.users.index') }}">Find Customer</a></li>
                                    </ul>
                                    
                                    <h6 class="mt-3">System Status</h6>
                                    <ul class="list-unstyled">
                                        <li>
                                            <i class="fas fa-circle text-success"></i> 
                                            <small>All systems operational</small>
                                        </li>
                                    </ul>
                                    
                                    <h6 class="mt-3">Keyboard Shortcuts</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><kbd>?</kbd></td>
                                            <td><small>Show shortcuts</small></td>
                                        </tr>
                                        <tr>
                                            <td><kbd>Ctrl</kbd>+<kbd>K</kbd></td>
                                            <td><small>Quick search</small></td>
                                        </tr>
                                        <tr>
                                            <td><kbd>Esc</kbd></td>
                                            <td><small>Close modals</small></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card bg-info text-white mt-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-lightbulb"></i> Pro Tip</h6>
                                    <p class="mb-0 small">
                                        Hover over any element with an info icon <i class="fas fa-info-circle"></i> for contextual help!
                                    </p>
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

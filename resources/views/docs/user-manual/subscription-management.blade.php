@extends('layouts.app')

@section('title', 'Subscription Management Guide')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-file-invoice"></i> Subscription Management Guide
                    </h1>
                    <a href="{{ route('admin.docs.user-manual') }}" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left"></i> Back to Manual
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Quick Start:</strong> This guide covers all aspects of subscription management in the Farm Delivery System.
                            </div>
                            
                            <h2>Overview</h2>
                            <p>The subscription management system allows you to create, manage, and track recurring customer orders for vegetable boxes and other farm products.</p>
                            
                            <h3>Key Features</h3>
                            <ul>
                                <li>Create and manage customer subscriptions</li>
                                <li>Handle subscription renewals automatically</li>
                                <li>Process plan changes (upgrades/downgrades)</li>
                                <li>Pause and resume subscriptions</li>
                                <li>Track payment status and history</li>
                                <li>Manage delivery schedules</li>
                            </ul>
                            
                            <h2 class="mt-4">Creating a New Subscription</h2>
                            <ol>
                                <li>Navigate to <strong>Subscriptions</strong> in the sidebar</li>
                                <li>Click <strong>"Create Subscription"</strong></li>
                                <li>Select the customer or create a new customer account</li>
                                <li>Choose the subscription plan (Weekly Box, Fortnightly Box, etc.)</li>
                                <li>Set delivery day and time preferences</li>
                                <li>Enter delivery address</li>
                                <li>Configure payment method</li>
                                <li>Review and save</li>
                            </ol>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Important:</strong> Always verify the customer's delivery address before creating a subscription to ensure successful deliveries.
                            </div>
                            
                            <h2 class="mt-4">Managing Existing Subscriptions</h2>
                            
                            <h3>Viewing Subscription Details</h3>
                            <p>Click on any subscription in the list to view full details including:</p>
                            <ul>
                                <li>Customer information</li>
                                <li>Current plan and pricing</li>
                                <li>Delivery schedule</li>
                                <li>Payment history</li>
                                <li>Upcoming deliveries</li>
                            </ul>
                            
                            <h3>Pausing a Subscription</h3>
                            <ol>
                                <li>Open the subscription details</li>
                                <li>Click <strong>"Pause Subscription"</strong></li>
                                <li>Select pause start date</li>
                                <li>Optionally set an automatic resume date</li>
                                <li>Confirm the pause</li>
                            </ol>
                            
                            <h3>Changing Subscription Plans</h3>
                            <ol>
                                <li>Open the subscription details</li>
                                <li>Click <strong>"Change Plan"</strong></li>
                                <li>Select new plan (upgrade or downgrade)</li>
                                <li>Choose when to apply: immediate or next billing cycle</li>
                                <li>Review pricing changes</li>
                                <li>Confirm the change</li>
                            </ol>
                            
                            <h2 class="mt-4">Subscription Renewals</h2>
                            
                            <h3>Automatic Renewals</h3>
                            <p>The system automatically processes subscription renewals based on the billing cycle:</p>
                            <ul>
                                <li><strong>Weekly:</strong> Charged every 7 days</li>
                                <li><strong>Fortnightly:</strong> Charged every 14 days</li>
                                <li><strong>Monthly:</strong> Charged on the same day each month</li>
                            </ul>
                            
                            <h3>Failed Payments</h3>
                            <p>When a renewal payment fails:</p>
                            <ol>
                                <li>System sends automatic notification to customer</li>
                                <li>Enters grace period (default 7 days)</li>
                                <li>Retries payment automatically</li>
                                <li>If still failing, subscription is suspended</li>
                            </ol>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Tip:</strong> Check the "Failed Payments" tab regularly to follow up with customers who have payment issues.
                            </div>
                            
                            <h2 class="mt-4">Common Tasks</h2>
                            
                            <div class="accordion" id="commonTasks">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#task1">
                                            How to handle customer vacation requests
                                        </button>
                                    </h2>
                                    <div id="task1" class="accordion-collapse collapse show" data-bs-parent="#commonTasks">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Open the customer's subscription</li>
                                                <li>Click "Pause Subscription"</li>
                                                <li>Set start date to their departure date</li>
                                                <li>Set resume date to their return date</li>
                                                <li>System will automatically skip deliveries during this period</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#task2">
                                            How to change delivery address
                                        </button>
                                    </h2>
                                    <div id="task2" class="accordion-collapse collapse" data-bs-parent="#commonTasks">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Open the subscription details</li>
                                                <li>Click "Edit" in the delivery section</li>
                                                <li>Update the address fields</li>
                                                <li>Verify the new address on the map</li>
                                                <li>Save changes</li>
                                                <li>System updates all future deliveries automatically</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#task3">
                                            How to process a refund
                                        </button>
                                    </h2>
                                    <div id="task3" class="accordion-collapse collapse" data-bs-parent="#commonTasks">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Open the subscription payment history</li>
                                                <li>Find the payment to refund</li>
                                                <li>Click "Issue Refund"</li>
                                                <li>Select refund type (full or partial)</li>
                                                <li>Enter reason for refund</li>
                                                <li>Confirm refund</li>
                                                <li>Customer receives notification automatically</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h2 class="mt-4">Reports & Analytics</h2>
                            <p>Access subscription analytics from the main subscriptions page:</p>
                            <ul>
                                <li><strong>Active Subscriptions:</strong> Current subscriber count</li>
                                <li><strong>Revenue Metrics:</strong> Monthly recurring revenue (MRR)</li>
                                <li><strong>Churn Rate:</strong> Subscription cancellations</li>
                                <li><strong>Growth Rate:</strong> New subscription trends</li>
                            </ul>
                            
                            <h2 class="mt-4">Troubleshooting</h2>
                            
                            <h3>Subscription Not Renewing</h3>
                            <ul>
                                <li>Check if payment method is valid</li>
                                <li>Verify customer has sufficient balance</li>
                                <li>Check for any holds or restrictions on account</li>
                                <li>Review error logs for payment processor issues</li>
                            </ul>
                            
                            <h3>Delivery Schedule Conflicts</h3>
                            <ul>
                                <li>Verify delivery day is set correctly</li>
                                <li>Check for holiday/closure dates</li>
                                <li>Ensure fortnightly week type is correct (A/B)</li>
                                <li>Review route capacity for that day</li>
                            </ul>
                            
                            <div class="alert alert-primary mt-4">
                                <i class="fas fa-robot"></i>
                                <strong>Need Help?</strong> Ask the AI Helper in the sidebar for instant assistance with subscription management tasks.
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-light sticky-top" style="top: 1rem;">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-list"></i> Quick Links</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled small">
                                        <li><a href="{{ route('admin.vegbox-subscriptions.index') }}">View All Subscriptions</a></li>
                                        <li><a href="{{ route('admin.vegbox-subscriptions.index') }}">Failed Payments</a></li>
                                        <li><a href="{{ route('admin.vegbox-subscriptions.index') }}">Upcoming Renewals</a></li>
                                    </ul>
                                    
                                    <hr>
                                    
                                    <h6 class="mt-3"><i class="fas fa-book"></i> Related Guides</h6>
                                    <ul class="list-unstyled small">
                                        <li><a href="{{ route('admin.docs.page', 'delivery-management') }}">Delivery Management</a></li>
                                        <li><a href="{{ route('admin.docs.page', 'crm-usage') }}">CRM Usage</a></li>
                                        <li><a href="{{ route('admin.docs.page', 'user-management') }}">User Management</a></li>
                                    </ul>
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

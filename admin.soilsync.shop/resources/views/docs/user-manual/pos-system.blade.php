@extends('layouts.app')

@section('title', 'POS System - Terminal, Inventory & Orders')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-cash-register"></i> POS System Guide
                    </h1>
                    <a href="{{ route('admin.docs.user-manual') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Manual
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i>
                        <strong>Single Sign-On:</strong> As a super admin, you can access the POS system directly from the admin dashboard without separate login.
                    </div>

                    <h2><i class="fas fa-cash-register text-success"></i> POS Terminal</h2>
                    <p>The Point of Sale (POS) Terminal is your market stall checkout system for processing customer orders at farmers' markets and events.</p>

                    <h3>Accessing the POS Terminal</h3>
                    <ol>
                        <li>From the admin dashboard, click <strong>"Point of Sale"</strong> in the sidebar</li>
                        <li>Select <strong>"POS Terminal"</strong> from the dropdown menu</li>
                        <li>The system will automatically authenticate you (no separate login required)</li>
                    </ol>

                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5><i class="fas fa-mobile-alt"></i> Mobile-Optimized Interface</h5>
                            <p>The POS Terminal is designed for tablet and mobile use at market stalls. It features:</p>
                            <ul>
                                <li>Large, touch-friendly buttons</li>
                                <li>Responsive design for various screen sizes</li>
                                <li>Offline-capable transaction processing</li>
                                <li>Quick product search and selection</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Processing a Sale</h3>
                    <ol>
                        <li><strong>Add Products:</strong> Search for products or browse categories</li>
                        <li><strong>Adjust Quantities:</strong> Use +/- buttons or type quantities</li>
                        <li><strong>Apply Discounts:</strong> Add percentage or fixed amount discounts</li>
                        <li><strong>Select Payment Method:</strong> Cash, Card, or Digital Wallet</li>
                        <li><strong>Process Payment:</strong> Complete the transaction</li>
                        <li><strong>Print Receipt:</strong> Generate customer receipt (optional)</li>
                    </ol>

                    <h2><i class="fas fa-boxes text-primary"></i> POS Inventory Management</h2>
                    <p>Control which products are available for sale at your market stall, separate from your main online inventory.</p>

                    <h3>Managing Market Inventory</h3>
                    <ol>
                        <li>Navigate to <strong>"Point of Sale" → "POS Inventory"</strong></li>
                        <li>View all products available for market sales</li>
                        <li><strong>Toggle Availability:</strong> Enable/disable products for market stalls</li>
                        <li><strong>Set Market Prices:</strong> Override regular prices for market sales</li>
                        <li><strong>Stock Levels:</strong> Track market-specific inventory</li>
                    </ol>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-toggle-on"></i> Quick Toggle</h6>
                                </div>
                                <div class="card-body">
                                    <p>Use the toggle switches to quickly enable/disable products for market sales without affecting your online store.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning">
                                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Stock Alerts</h6>
                                </div>
                                <div class="card-body">
                                    <p>Get notified when market inventory runs low. Set minimum stock levels to avoid selling out at markets.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h3>Bulk Inventory Updates</h3>
                    <ul>
                        <li><strong>Category Management:</strong> Enable/disable entire product categories</li>
                        <li><strong>Bulk Price Changes:</strong> Apply percentage increases for market pricing</li>
                        <li><strong>Seasonal Adjustments:</strong> Quickly update for seasonal availability</li>
                    </ul>

                    <h2><i class="fas fa-receipt text-info"></i> POS Orders & Transactions</h2>
                    <p>Track all market stall sales and manage transaction history.</p>

                    <h3>Viewing POS Orders</h3>
                    <ol>
                        <li>Go to <strong>"Point of Sale" → "POS Orders"</strong></li>
                        <li>View all completed transactions</li>
                        <li><strong>Filter by Date:</strong> See sales for specific market days</li>
                        <li><strong>Payment Methods:</strong> Track cash vs card transactions</li>
                        <li><strong>Staff Performance:</strong> Monitor sales by team member</li>
                    </ol>

                    <h3>Order Details</h3>
                    <p>Each POS order includes:</p>
                    <ul>
                        <li>Transaction timestamp and location</li>
                        <li>Items sold with quantities and prices</li>
                        <li>Discounts applied</li>
                        <li>Payment method and amount received</li>
                        <li>Staff member who processed the sale</li>
                        <li>Customer information (if collected)</li>
                    </ul>

                    <h3>Reporting & Analytics</h3>
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><i class="fas fa-chart-bar"></i> Market Performance Insights</h5>
                            <ul>
                                <li><strong>Daily Totals:</strong> Track revenue per market day</li>
                                <li><strong>Product Performance:</strong> See which items sell best</li>
                                <li><strong>Payment Trends:</strong> Cash vs digital payment preferences</li>
                                <li><strong>Staff Metrics:</strong> Sales volume by team member</li>
                                <li><strong>Time Analysis:</strong> Peak selling hours and busy periods</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Advanced Analytics & Reports</h3>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-chart-line"></i> Beyond POS: Full Farm Analytics</h6>
                        <p>The POS system is just one part of comprehensive farm analytics. Access detailed reports and analytics through the main admin panel:</p>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6><i class="fas fa-chart-bar"></i> Reports Dashboard</h6>
                                <ul class="small">
                                    <li><strong>Delivery Performance:</strong> Track delivery completion rates and times</li>
                                    <li><strong>Task Management:</strong> Monitor farm task completion and productivity</li>
                                    <li><strong>Harvest Analytics:</strong> Analyze crop yields and harvest efficiency</li>
                                    <li><strong>Customer Insights:</strong> View customer engagement and retention metrics</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-chart-line"></i> Analytics Dashboard</h6>
                                <ul class="small">
                                    <li><strong>Customer Growth:</strong> Track new customer acquisition year-to-date</li>
                                    <li><strong>Task Trends:</strong> Monitor farm activity patterns over time</li>
                                    <li><strong>Harvest Trends:</strong> Analyze seasonal harvest patterns</li>
                                    <li><strong>AI Usage:</strong> Track AI-powered crop planning interactions</li>
                                </ul>
                            </div>
                        </div>

                        <p class="mt-3 mb-0">
                            <strong>Access:</strong> Navigate to <strong>"Analytics" → "Reports"</strong> or <strong>"Analytics" → "Analytics"</strong> in the admin sidebar to access these comprehensive dashboards.
                        </p>
                    </div>

                    <h2><i class="fas fa-credit-card text-success"></i> Payment Processing</h2>
                    <p>The POS system supports multiple payment methods for maximum flexibility at markets.</p>

                    <h3>Supported Payment Methods</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                    <h6>Cash</h6>
                                    <small>Traditional cash payments with change calculation</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                                    <h6>Card</h6>
                                    <small>Contactless and chip payments via integrated terminal</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-mobile fa-2x text-info mb-2"></i>
                                    <h6>Digital Wallets</h6>
                                    <small>Apple Pay, Google Pay, and other mobile wallets</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h3>Payment Security</h3>
                    <ul>
                        <li><strong>PCI Compliance:</strong> Secure payment processing standards</li>
                        <li><strong>Transaction Logging:</strong> All payments are recorded with timestamps</li>
                        <li><strong>Staff Accountability:</strong> Each transaction is linked to the processing staff member</li>
                        <li><strong>Receipt Generation:</strong> Automatic receipt printing for customers</li>
                    </ul>

                    <h2><i class="fas fa-users text-warning"></i> Staff Management</h2>
                    <p>Control who can access the POS system and track their performance.</p>

                    <h3>POS Staff Permissions</h3>
                    <ul>
                        <li><strong>Super Admin Access:</strong> Full access to all POS features (your current access)</li>
                        <li><strong>POS Staff:</strong> Limited to terminal operations and their own transaction history</li>
                        <li><strong>Market Managers:</strong> Can manage inventory and view all staff reports</li>
                    </ul>

                    <h3>Staff Training Tips</h3>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-graduation-cap"></i> Best Practices for POS Staff</h6>
                        <ul class="mb-0">
                            <li>Always verify product prices before completing transactions</li>
                            <li>Count change accurately for cash transactions</li>
                            <li>Keep the POS device charged and protected from weather</li>
                            <li>Report any technical issues immediately</li>
                            <li>Be friendly and efficient with customers</li>
                        </ul>
                    </div>

                    <h2><i class="fas fa-tools text-secondary"></i> Troubleshooting</h2>

                    <h3>Common Issues</h3>
                    <div class="accordion" id="posTroubleshooting">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#authIssue">
                                    POS Login Issues
                                </button>
                            </h2>
                            <div id="authIssue" class="accordion-collapse collapse show" data-bs-parent="#posTroubleshooting">
                                <div class="accordion-body">
                                    <strong>Solution:</strong> As a super admin, you should have automatic access. If you see a login screen:
                                    <ol>
                                        <li>Ensure you're logged into the main admin system first</li>
                                        <li>Try refreshing the page</li>
                                        <li>Clear your browser cache and cookies</li>
                                        <li>Contact IT support if issues persist</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentIssue">
                                    Payment Processing Problems
                                </button>
                            </h2>
                            <div id="paymentIssue" class="accordion-collapse collapse" data-bs-parent="#posTroubleshooting">
                                <div class="accordion-body">
                                    <strong>Solutions:</strong>
                                    <ul>
                                        <li>Check internet connection for card payments</li>
                                        <li>Ensure card reader is properly connected</li>
                                        <li>Try processing as cash if card fails</li>
                                        <li>Restart the POS device if all else fails</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#inventoryIssue">
                                    Inventory Sync Issues
                                </button>
                            </h2>
                            <div id="inventoryIssue" class="accordion-collapse collapse" data-bs-parent="#posTroubleshooting">
                                <div class="accordion-body">
                                    <strong>Solution:</strong> POS inventory is separate from online inventory. If products aren't appearing:
                                    <ol>
                                        <li>Go to POS Inventory and enable the products</li>
                                        <li>Check if products are set to "Available for POS"</li>
                                        <li>Refresh the POS terminal page</li>
                                        <li>Verify product categories are enabled</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2><i class="fas fa-question-circle text-info"></i> Quick Reference</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Keyboard Shortcuts</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><kbd>F1</kbd></td>
                                    <td>Help / Shortcuts</td>
                                </tr>
                                <tr>
                                    <td><kbd>F2</kbd></td>
                                    <td>Search Products</td>
                                </tr>
                                <tr>
                                    <td><kbd>F3</kbd></td>
                                    <td>Void Transaction</td>
                                </tr>
                                <tr>
                                    <td><kbd>F4</kbd></td>
                                    <td>Customer Lookup</td>
                                </tr>
                                <tr>
                                    <td><kbd>Enter</kbd></td>
                                    <td>Complete Sale</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Quick Actions</h5>
                            <ul>
                                <li><strong>Double-click product:</strong> Add to cart instantly</li>
                                <li><strong>Right-click transaction:</strong> View details</li>
                                <li><strong>Ctrl+Click:</strong> Select multiple items</li>
                                <li><strong>Shift+Click:</strong> Range select items</li>
                            </ul>
                        </div>
                    </div>

                    <h2><i class="fas fa-chart-line text-primary"></i> Analytics & Reports</h2>
                    <p>Access comprehensive business intelligence and performance metrics through the Analytics and Reports dashboards.</p>

                    <h3>Analytics Dashboard</h3>
                    <p>The Analytics page provides real-time insights into your farm operations and business performance.</p>

                    <h4>Accessing Analytics</h4>
                    <ol>
                        <li>From the admin dashboard, click <strong>"Analytics"</strong> in the sidebar</li>
                        <li>The dashboard loads with current year-to-date data</li>
                        <li>Use date range buttons (7 Days, 30 Days, 90 Days, 1 Year) to filter other metrics</li>
                    </ol>

                    <h4>Key Analytics Metrics</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-users text-primary"></i> Customer Retention</h6>
                                    <p>Percentage of returning customers based on order history</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-tasks text-success"></i> Task Completion Rate</h6>
                                    <p>Farm tasks completed vs total tasks assigned</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-leaf text-success"></i> Harvest Efficiency</h6>
                                    <p>Total harvest weight and productivity metrics</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-robot text-info"></i> AI Requests</h6>
                                    <p>Number of AI assistant interactions for farm planning</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4>Growth Trends</h4>
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5><i class="fas fa-chart-line"></i> Customer Growth Chart</h5>
                            <ul>
                                <li><strong>Year-to-Date View:</strong> Shows customer registrations from January 1st of the current year</li>
                                <li><strong>Daily Tracking:</strong> Visual representation of new customer acquisition</li>
                                <li><strong>Growth Patterns:</strong> Identify peak registration periods and trends</li>
                            </ul>
                        </div>
                    </div>

                    <h3>Reports Dashboard</h3>
                    <p>The Reports page provides detailed operational data and export capabilities for business analysis.</p>

                    <h4>Accessing Reports</h4>
                    <ol>
                        <li>From the admin dashboard, click <strong>"Reports"</strong> in the sidebar</li>
                        <li>Choose your preferred date range (7 Days, 30 Days, 90 Days, 1 Year)</li>
                        <li>View metrics cards and detailed breakdowns</li>
                        <li>Export data for external analysis</li>
                    </ol>

                    <h4>Report Categories</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-truck text-primary"></i> Delivery Reports</h5>
                            <ul>
                                <li>Total deliveries completed</li>
                                <li>Average delivery time</li>
                                <li>Deliveries by week</li>
                                <li>Top customers by delivery volume</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-tasks text-success"></i> Task Reports</h5>
                            <ul>
                                <li>Tasks completed vs total</li>
                                <li>Completion rate percentage</li>
                                <li>Tasks by category</li>
                                <li>Overdue task tracking</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-apple-alt text-warning"></i> Harvest Reports</h5>
                            <ul>
                                <li>Total harvest quantities</li>
                                <li>Harvests by crop variety</li>
                                <li>Productivity trends</li>
                                <li>Export capabilities</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-users text-info"></i> Customer Reports</h5>
                            <ul>
                                <li>Customer engagement metrics</li>
                                <li>Week assignment tracking</li>
                                <li>Customer distribution analysis</li>
                            </ul>
                        </div>
                    </div>

                    <h4>Data Export</h4>
                    <p>Export report data for external analysis and record keeping:</p>
                    <ul>
                        <li><strong>CSV Format:</strong> Compatible with Excel and other spreadsheet applications</li>
                        <li><strong>Date-Range Specific:</strong> Export data for any selected time period</li>
                        <li><strong>Category Filters:</strong> Export specific report types (deliveries, tasks, harvests)</li>
                        <li><strong>Historical Data:</strong> Access complete operational history</li>
                    </ul>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Pro Tip:</strong> Use the Analytics dashboard for quick insights and the Reports dashboard for detailed data analysis and exports.
                    </div>

                    <div class="alert alert-light mt-4">
                        <h5><i class="fas fa-headset"></i> Need Help?</h5>
                        <ul class="mb-0">
                            <li>Use the <strong>AI Helper</strong> in the sidebar for instant assistance</li>
                            <li>Check the troubleshooting section above for common issues</li>
                            <li>Contact your system administrator for technical support</li>
                            <li>Access this guide anytime from the User Manual</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
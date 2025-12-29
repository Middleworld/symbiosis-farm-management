<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Delivery & Collection Schedule - Week {{ $selectedWeek ?? date('W') }}</title>
    <style>
        @media print {
            .no-print { display: none !important; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #28a745;
            margin-bottom: 5px;
        }
        
        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        
        .summary-item {
            flex: 1;
        }
        
        .summary-item h3 {
            margin: 0;
            color: #28a745;
            font-size: 24px;
        }
        
        .summary-item p {
            margin: 5px 0 0 0;
            color: #666;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section-title {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .date-group {
            margin-bottom: 30px;
        }
        
        .date-header {
            background-color: #e9ecef;
            padding: 8px 15px;
            margin-bottom: 15px;
            font-weight: bold;
            border-left: 4px solid #28a745;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-primary { background-color: #d6e9ff; color: #004085; }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button:hover {
            background-color: #0056b3;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>
    
    <div class="header">
        <h1>Middleworld Farms</h1>
        <h2>Active Delivery & Collection Schedule</h2>
        <p>Week {{ $selectedWeek ?? date('W') }} - {{ date('Y') }}</p>
        <p>Generated: {{ date('F j, Y \a\t g:i A') }}</p>
    </div>

    @if(isset($error))
        <div class="error">
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif

    <div class="summary">
        <div class="summary-item">
            <h3>{{ $totalActiveDeliveries }}</h3>
            <p>Active Deliveries</p>
        </div>
        <div class="summary-item">
            <h3>{{ $totalActiveCollections }}</h3>
            <p>Active Collections</p>
        </div>
        <div class="summary-item">
            <h3>{{ $totalActiveDeliveries + $totalActiveCollections }}</h3>
            <p>Total Active</p>
        </div>
    </div>

    @if($totalActiveDeliveries > 0)
        <div class="section">
            <div class="section-title">🚛 Active Deliveries ({{ $totalActiveDeliveries }})</div>
            
            @forelse($activeDeliveries as $date => $dateData)
                <div class="date-group">
                    <div class="date-header">{{ $dateData['date_formatted'] }}</div>
                    
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Products</th>
                                <th>Frequency</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dateData['deliveries'] as $delivery)
                                <tr>
                                    <td>
                                        <strong>{{ $delivery['name'] ?: 'Unknown' }}</strong><br>
                                        <small>{{ $delivery['customer_email'] }}</small>
                                    </td>
                                    <td>
                                        @if(!empty($delivery['address']))
                                            {{ implode(', ', array_filter($delivery['address'])) }}
                                        @else
                                            <em>No address</em>
                                        @endif
                                    </td>
                                    <td>{{ $delivery['phone'] ?: '-' }}</td>
                                    <td>
                                        @if(!empty($delivery['products']))
                                            @foreach($delivery['products'] as $product)
                                                <div>{{ $product['quantity'] }}x {{ $product['name'] }}</div>
                                            @endforeach
                                        @else
                                            <em>No products</em>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $delivery['frequency_badge'] ?? 'primary' }}">
                                            {{ $delivery['frequency'] ?? 'Weekly' }}
                                        </span>
                                        @if(isset($delivery['customer_week_type']) && in_array($delivery['customer_week_type'], ['A', 'B']))
                                            <br><span class="badge badge-{{ $delivery['week_badge'] ?? 'primary' }}">
                                                Week {{ $delivery['customer_week_type'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td><strong>£{{ number_format((float)($delivery['total'] ?? 0), 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="no-data">No active deliveries found for this week.</div>
            @endforelse
        </div>
    @endif

    @if($totalActiveCollections > 0)
        <div class="section">
            <div class="section-title">📦 Active Collections ({{ $totalActiveCollections }})</div>
            
            @forelse($activeCollections as $date => $dateData)
                <div class="date-group">
                    <div class="date-header">{{ $dateData['date_formatted'] }}</div>
                    
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Products</th>
                                <th>Frequency</th>
                                <th>Collection Day</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dateData['collections'] as $collection)
                                <tr>
                                    <td>
                                        <strong>{{ $collection['name'] ?: 'Unknown' }}</strong><br>
                                        <small>{{ $collection['customer_email'] }}</small>
                                    </td>
                                    <td>
                                        @if($collection['phone'])
                                            <strong>{{ $collection['phone'] }}</strong><br>
                                        @endif
                                        {{ $collection['customer_email'] }}
                                    </td>
                                    <td>
                                        @if(!empty($collection['products']))
                                            @foreach($collection['products'] as $product)
                                                <div>{{ $product['quantity'] }}x {{ $product['name'] }}</div>
                                            @endforeach
                                        @else
                                            <em>No products</em>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $collection['frequency_badge'] ?? 'primary' }}">
                                            {{ $collection['frequency'] ?? 'Weekly' }}
                                        </span>
                                        @if(isset($collection['customer_week_type']) && in_array($collection['customer_week_type'], ['A', 'B']))
                                            <br><span class="badge badge-{{ $collection['week_badge'] ?? 'primary' }}">
                                                Week {{ $collection['customer_week_type'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $collection['preferred_collection_day'] ?? 'Friday' }}</strong>
                                    </td>
                                    <td><strong>£{{ number_format((float)($collection['total'] ?? 0), 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="no-data">No active collections found for this week.</div>
            @endforelse
        </div>
    @endif

    @if($totalActiveDeliveries == 0 && $totalActiveCollections == 0)
        <div class="no-data">
            <h3>No Active Deliveries or Collections</h3>
            <p>There are no active deliveries or collections scheduled for Week {{ $selectedWeek ?? date('W') }}.</p>
        </div>
    @endif

    <div class="footer">
        <p>Middleworld Farms - Active Schedule Report</p>
        <p>Generated on {{ date('F j, Y \a\t g:i A') }} | Week {{ $selectedWeek ?? date('W') }} {{ date('Y') }}</p>
        <p><em>This report shows only active deliveries and collections. On-hold, cancelled, and pending items are excluded.</em></p>
    </div>

    <script>
        // Auto-focus print button for keyboard accessibility
        document.addEventListener('DOMContentLoaded', function() {
            const printButton = document.querySelector('.print-button');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    // Small delay to ensure print dialog opens properly
                    setTimeout(() => window.print(), 100);
                });
            }
        });
    </script>
</body>
</html>

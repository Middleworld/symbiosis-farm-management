<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Week {{ $selectedWeek }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .summary {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .date-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .date-header {
            background-color: {{ $type === 'deliveries' ? '#007bff' : '#28a745' }};
            color: white;
            padding: 8px 15px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .customer-name {
            font-weight: bold;
        }
        .address {
            font-size: 11px;
            color: #666;
        }
        .products {
            font-size: 11px;
        }
        .week-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .week-badge-A {
            background-color: #28a745;
            color: white;
        }
        .week-badge-B {
            background-color: #17a2b8;
            color: white;
        }
        .week-badge-Weekly {
            background-color: #007bff;
            color: white;
        }
        .frequency-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        .frequency-fortnightly {
            background-color: #ffc107;
            color: #000;
        }
        .frequency-weekly {
            background-color: #28a745;
            color: white;
        }
        .no-items {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        @media print {
            body {
                margin: 0;
            }
            .no-print {
                display: none;
            }
            .date-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <h2>{{ $dayInfo }} - Week {{ $selectedWeek }}</h2>
        <p><strong>Generated:</strong> {{ date('l, F j, Y \a\t g:i A') }}</p>
        @if($totalItems > 0)
            <p><strong>Total {{ ucfirst($type) }}:</strong> {{ $totalItems }}</p>
        @endif
    </div>

    @if(isset($error) && $error)
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;">
            <strong>Error:</strong> {{ $error }}
        </div>
    @elseif($totalItems > 0)
        <div class="summary">
            <h3>Summary</h3>
            <p><strong>Schedule Type:</strong> {{ ucfirst($type) }} Only</p>
            <p><strong>Week:</strong> {{ $selectedWeek }} ({{ date('Y') }})</p>
            <p><strong>Total Active {{ ucfirst($type) }}:</strong> {{ $totalItems }}</p>
            <p><strong>Status:</strong> Active subscriptions only</p>
        </div>

        @foreach($printData as $date => $dateData)
            <div class="date-section">
                <div class="date-header">
                    {{ $dateData['date_formatted'] }} - {{ count($dateData['items']) }} {{ $type }}
                </div>

                @if(count($dateData['items']) > 0)
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 20%">Customer</th>
                                <th style="width: 25%">Address</th>
                                <th style="width: 20%">Products</th>
                                <th style="width: 12%">Phone</th>
                                <th style="width: 8%">Frequency</th>
                                <th style="width: 8%">Week</th>
                                @if($type === 'collections')
                                    <th style="width: 7%">Collection Day</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dateData['items'] as $item)
                                <tr>
                                    <td>
                                        <div class="customer-name">{{ $item['name'] ?? 'N/A' }}</div>
                                        <div style="font-size: 10px; color: #666;">
                                            {{ $item['customer_email'] ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="address">
                                            @if(isset($item['address']) && is_array($item['address']))
                                                {{ implode(', ', array_filter($item['address'])) }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="products">
                                            @if(isset($item['products']) && is_array($item['products']))
                                                @foreach($item['products'] as $product)
                                                    {{ $product['quantity'] ?? 1 }}x {{ $product['name'] ?? 'Product' }}<br>
                                                @endforeach
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $item['phone'] ?? 'N/A' }}</td>
                                    <td>
                                        <span class="frequency-badge frequency-{{ strtolower($item['frequency'] ?? 'weekly') }}">
                                            {{ $item['frequency'] ?? 'Weekly' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="week-badge week-badge-{{ $item['customer_week_type'] ?? 'Weekly' }}">
                                            {{ $item['customer_week_type'] ?? 'Weekly' }}
                                        </span>
                                    </td>
                                    @if($type === 'collections')
                                        <td>{{ $item['preferred_collection_day'] ?? 'Friday' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-items">No {{ $type }} scheduled for this date</div>
                @endif
            </div>
        @endforeach
    @else
        <div class="no-items">
            <h3>No Active {{ ucfirst($type) }} Found</h3>
            <p>There are currently no active {{ $type }} scheduled for Week {{ $selectedWeek }}.</p>
        </div>
    @endif

    <div class="no-print" style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print This Schedule
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Close Window
        </button>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Print function for manual printing
        function printPage() {
            window.print();
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title>Collection Schedule</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 15px; 
            font-size: 12px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #28a745;
        }
        .header img {
            height: 50px;
            margin-bottom: 8px;
        }
        .header h1 {
            margin: 8px 0;
            color: #28a745;
            font-size: 18px;
        }
        .header p {
            margin: 4px 0;
            font-size: 11px;
            color: #666;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .schedule-table th,
        .schedule-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
            font-size: 11px;
        }
        .schedule-table th {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        .schedule-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .customer-name {
            font-weight: bold;
            font-size: 11px;
        }
        .collection-id {
            font-size: 9px;
            color: #666;
        }
        .products {
            font-size: 10px;
            line-height: 1.2;
        }
        .amount {
            font-weight: bold;
            text-align: center;
        }
        .contact {
            font-size: 9px;
        }
        .notes {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 2px;
            font-size: 9px;
        }
        .total-summary {
            text-align: center;
            font-weight: bold;
            margin-top: 15px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        @media print {
            body { margin: 8px; font-size: 11px; }
            .schedule-table th,
            .schedule-table td { padding: 4px; }
            .print-controls { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ url('/Middle_World_Logo_Round.png') }}" alt="Middle World Farms">
        <h1>Collection Schedule</h1>
        <p>Generated: {{ date('l, F j, Y \a\t g:i A') }}</p>
        <p>Total Collections: {{ count($collections ?? []) }}</p>
    </div>

    @if(isset($collections) && count($collections) > 0)
        <table class="schedule-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Customer</th>
                    <th style="width: 25%;">Address</th>
                    <th style="width: 25%;">Products</th>
                    <th style="width: 10%;">Amount</th>
                    <th style="width: 15%;">Contact</th>
                    <th style="width: 10%;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collections as $collection)
                    <tr>
                        <td>
                            <div class="customer-name">{{ $collection['customer_name'] ?? 'N/A' }}</div>
                            @if(isset($collection['order_number']))
                                <div class="collection-id">ID: {{ $collection['order_number'] }}</div>
                            @endif
                        </td>
                        <td>
                            @if(isset($collection['shipping_address']) && is_array($collection['shipping_address']))
                                @php $addr = $collection['shipping_address']; @endphp
                                {{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}<br>
                                {{ $addr['address_1'] ?? '' }}
                                @if(!empty($addr['address_2']))<br>{{ $addr['address_2'] }}@endif
                                @if(!empty($addr['city']))<br>{{ $addr['city'] }} {{ $addr['postcode'] ?? '' }}@endif
                            @elseif(isset($collection['billing_address']) && is_array($collection['billing_address']))
                                @php $addr = $collection['billing_address']; @endphp
                                {{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}<br>
                                {{ $addr['address_1'] ?? '' }}
                                @if(!empty($addr['address_2']))<br>{{ $addr['address_2'] }}@endif
                                @if(!empty($addr['city']))<br>{{ $addr['city'] }} {{ $addr['postcode'] ?? '' }}@endif
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="products">
                            @if(!empty($collection['products']) && is_array($collection['products']))
                                @foreach($collection['products'] as $product)
                                    {{ $product['quantity'] ?? 1 }}x {{ $product['name'] ?? 'Unknown Product' }}<br>
                                @endforeach
                            @else
                                No products
                            @endif
                        </td>
                        <td class="amount">
                            £{{ number_format($collection['total'] ?? 0, 2) }}
                        </td>
                        <td class="contact">
                            @if(!empty($collection['billing_address']['phone']))
                                📞 {{ $collection['billing_address']['phone'] }}<br>
                            @endif
                            @if(!empty($collection['customer_email']))
                                📧 {{ $collection['customer_email'] }}
                            @endif
                        </td>
                        <td>
                            @if(!empty($collection['special_instructions']) || !empty($collection['delivery_notes']))
                                <div class="notes">
                                    {{ $collection['special_instructions'] ?? $collection['delivery_notes'] }}
                                </div>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-summary">
            Total Value: £{{ number_format(collect($collections)->sum('total'), 2) }}
        </div>
        
        {{-- Print Button (visible on screen, hidden when printing) --}}
        <div class="print-controls" style="text-align: center; margin-top: 20px; page-break-inside: avoid;">
            <button onclick="window.print()" class="btn btn-primary" style="padding: 10px 20px; font-size: 14px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                🖨️ Print Schedule
            </button>
            <button onclick="window.close()" class="btn btn-secondary" style="padding: 10px 20px; font-size: 14px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                ❌ Close
            </button>
        </div>
    @else
        <p style="text-align: center; padding: 30px; color: #666;">
            No collections found for the selected period.
        </p>
        
        {{-- Print Button for empty state --}}
        <div class="print-controls" style="text-align: center; margin-top: 20px;">
            <button onclick="window.close()" class="btn btn-secondary" style="padding: 10px 20px; font-size: 14px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                ❌ Close
            </button>
        </div>
    @endif

    <script>
        // Auto-print when page loads (after a short delay)
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


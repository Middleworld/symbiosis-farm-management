<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery Route - {{ date('l, F j, Y', strtotime($delivery_date)) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .delivery-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: white;
        }
        .stop-number {
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .customer-name {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        .address {
            color: #666;
            margin: 5px 0;
        }
        .products {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        .contact-info {
            font-size: 14px;
            color: #666;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .estimated-time {
            float: right;
            background-color: #ffc107;
            color: #212529;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚚 Delivery Route</h1>
        <h2>{{ date('l, F j, Y', strtotime($delivery_date)) }}</h2>
    </div>

    <div class="summary">
        <h3>📊 Route Summary</h3>
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
            <div><strong>Total Deliveries:</strong> {{ $total_deliveries }}</div>
            <div><strong>Total Distance:</strong> {{ $total_distance }}</div>
            <div><strong>Estimated Time:</strong> {{ $estimated_time }}</div>
        </div>
    </div>

    <h3>📍 Delivery Stops</h3>

    @foreach($deliveries as $index => $delivery)
        <div class="delivery-item">
            <div style="display: flex; align-items: flex-start;">
                <div class="stop-number">{{ $index + 1 }}</div>
                <div style="flex: 1;">
                    <div class="estimated-time">
                        Est: {{ $delivery['estimated_delivery_time'] ?? '08:00' }}
                    </div>
                    
                    <div class="customer-name">
                        {{ $delivery['name'] ?? 'Unknown Customer' }}
                    </div>
                    
                    <div class="address">
                        📍 {{ is_array($delivery['address'] ?? null) ? implode(', ', $delivery['address']) : ($delivery['address'] ?? 'No address provided') }}
                    </div>
                    
                    @if(isset($delivery['phone']) && $delivery['phone'])
                        <div class="contact-info">
                            📞 <a href="tel:{{ $delivery['phone'] }}">{{ $delivery['phone'] }}</a>
                        </div>
                    @endif

                    @if(isset($delivery['email']) && $delivery['email'])
                        <div class="contact-info">
                            ✉️ <a href="mailto:{{ $delivery['email'] }}">{{ $delivery['email'] }}</a>
                        </div>
                    @endif

                    @if(isset($delivery['products']) && !empty($delivery['products']))
                        <div class="products">
                            <strong>🥬 Products:</strong>
                            <ul style="margin: 5px 0; padding-left: 20px;">
                                @foreach($delivery['products'] as $product)
                                    <li>{{ $product['name'] ?? 'Product' }}{{ isset($product['quantity']) ? ' (x' . $product['quantity'] . ')' : '' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(isset($delivery['notes']) && $delivery['notes'])
                        <div style="background-color: #fff3cd; padding: 8px; border-radius: 3px; margin-top: 10px;">
                            <strong>📝 Special Instructions:</strong> {{ $delivery['notes'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="footer">
        <h3>🗺️ Navigation</h3>
        <p>Click the button below to open the route in Google Maps:</p>
        
        @php
            $start = config('services.delivery.depot_address', 'Middleworld Farms, UK');
            $waypoints = [];
            foreach($deliveries as $delivery) {
                $address = is_array($delivery['address'] ?? null) ? implode(', ', $delivery['address']) : ($delivery['address'] ?? '');
                if ($address) {
                    $waypoints[] = urlencode($address);
                }
            }
            $waypointsStr = implode('/', $waypoints);
            $mapsUrl = "https://www.google.com/maps/dir/" . urlencode($start) . "/" . $waypointsStr . "/" . urlencode($start);
        @endphp
        
        <a href="{{ $mapsUrl }}" class="btn" target="_blank">
            🗺️ Open Route in Google Maps
        </a>

        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><small>
                📧 This route was generated automatically by the Middleworld Farms delivery system.<br>
                If you have any questions, please contact the office.
            </small></p>
        </div>
    </div>
</body>
</html>

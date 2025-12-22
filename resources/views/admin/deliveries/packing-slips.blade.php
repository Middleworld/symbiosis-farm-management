<!DOCTYPE html>
<html>
<head>
    <title>{{ isset($type) && $type === 'collection' ? 'Collection' : 'Packing' }} Slips</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 10px;
            @if($slipsPerPage > 2)
                font-size: 12px;
            @endif
        }
        .slip { 
            border: 1px solid #ccc; 
            margin-bottom: 10px; 
            padding: 15px; 
            page-break-after: 
            @if($slipsPerPage == 1)
                always;
            @elseif($slipsPerPage == 2)
                auto;
                width: 48%;
                display: inline-block;
                vertical-align: top;
                margin-right: 2%;
            @elseif($slipsPerPage == 4)
                auto;
                width: 48%;
                display: inline-block;
                vertical-align: top;
                margin-right: 2%;
                height: 45vh;
                overflow: hidden;
            @elseif($slipsPerPage == 6)
                auto;
                width: 31%;
                display: inline-block;
                vertical-align: top;
                margin-right: 2%;
                height: 30vh;
                overflow: hidden;
                font-size: 10px;
            @endif
        }
        
        @if($slipsPerPage == 2)
        .slip:nth-child(2n) { margin-right: 0; }
        .slip:nth-child(2n+1) { page-break-after: avoid; }
        .slip:nth-child(2n) { page-break-after: always; }
        @elseif($slipsPerPage == 4)
        .slip:nth-child(4n) { margin-right: 0; }
        .slip:nth-child(4n-1) { margin-right: 0; page-break-after: always; }
        @elseif($slipsPerPage == 6)
        .slip:nth-child(6n) { margin-right: 0; page-break-after: always; }
        .slip:nth-child(3n) { margin-right: 0; }
        @endif
        
        .header { text-align: center; margin-bottom: 15px; }
        .customer-info { margin-bottom: 10px; line-height: 1.3; }
        .products { margin-top: 10px; }
        .product-item { margin-bottom: 3px; }
        .special-notes { 
            margin-top: 10px; 
            padding: 8px; 
            background-color: #fff3cd; 
            border: 1px solid #ffeeba;
            border-radius: 3px;
        }
        
        @media print {
            body { margin: 0; }
            .slip { 
                @if($slipsPerPage > 1)
                    page-break-inside: avoid;
                @else
                    page-break-after: always;
                @endif
            }
        }
    </style>
</head>
<body>
    <h1 style="text-align: center; margin-bottom: 20px;">
        {{ isset($type) && $type === 'collection' ? 'Collection' : 'Packing' }} Slips - {{ count($deliveries) }} items
        <small style="display: block; font-size: 14px; color: #666;">
            ({{ $slipsPerPage }} per page)
        </small>
    </h1>
    
    @if(empty($deliveries))
        <p>No deliveries found for the selected IDs.</p>
    @else
        @foreach($deliveries as $delivery)
            <div class="slip">
                <div class="header">
                    <h2>{{ $companyInfo['name'] ?? 'Middle World Farms' }}</h2>
                    <p>Packing Slip</p>
                </div>
                
                <div class="customer-info">
                    <strong>Customer:</strong> {{ $delivery['customer_name'] ?? 'N/A' }}<br>
                    <strong>{{ isset($type) && $type === 'collection' ? 'Collection' : 'Order' }} #:</strong> {{ $delivery['order_number'] ?? $delivery['id'] ?? 'N/A' }}<br>
                    
                    @if(isset($delivery['shipping_address']) && is_array($delivery['shipping_address']))
                        <strong>{{ isset($type) && $type === 'collection' ? 'Collection' : 'Delivery' }} Address:</strong><br>
                        {{ $delivery['shipping_address']['first_name'] ?? '' }} {{ $delivery['shipping_address']['last_name'] ?? '' }}<br>
                        {{ $delivery['shipping_address']['address_1'] ?? '' }}<br>
                        @if(!empty($delivery['shipping_address']['address_2']))
                            {{ $delivery['shipping_address']['address_2'] }}<br>
                        @endif
                        {{ $delivery['shipping_address']['city'] ?? '' }} {{ $delivery['shipping_address']['postcode'] ?? '' }}
                    @endif
                </div>
                
                <div class="products">
                    <strong>Products:</strong>
                    @if(!empty($delivery['products']) && is_array($delivery['products']))
                        @foreach($delivery['products'] as $product)
                            <div class="product-item">
                                {{ $product['quantity'] ?? 1 }}x {{ $product['name'] ?? 'Unknown Product' }}
                            </div>
                        @endforeach
                    @else
                        <div class="product-item">No products found</div>
                    @endif
                </div>
                
                @if(!empty($delivery['special_instructions']) || !empty($delivery['delivery_notes']))
                    <div class="special-notes">
                        <strong>Special Instructions:</strong><br>
                        {{ $delivery['special_instructions'] ?? $delivery['delivery_notes'] }}
                    </div>
                @endif
            </div>
        @endforeach
    @endif
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>

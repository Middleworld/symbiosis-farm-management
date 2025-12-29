<!DOCTYPE html>
<html>
<head>
    <title>Collection Slips</title>
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
            border: 2px solid #28a745; 
            margin-bottom: 10px; 
            padding: 15px; 
            background-color: #f8f9fa;
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
        
        .header { 
            text-align: center; 
            margin-bottom: 15px; 
            background-color: #28a745;
            color: white;
            padding: 10px;
            margin: -15px -15px 15px -15px;
            border-radius: 5px 5px 0 0;
        }
        .customer-info { 
            margin-bottom: 10px; 
            line-height: 1.3; 
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .products { 
            margin-top: 10px; 
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .product-item { 
            margin-bottom: 3px; 
            padding: 2px 0;
            border-bottom: 1px dotted #ddd;
        }
        .special-notes { 
            margin-top: 10px; 
            padding: 8px; 
            background-color: #fff3cd; 
            border: 1px solid #ffeeba;
            border-radius: 3px;
        }
        .collection-badge {
            background-color: #ffc107;
            color: #000;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            float: right;
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
    <h1 style="text-align: center; margin-bottom: 20px; color: #28a745;">
        📦 Collection Slips - {{ count($collections) }} items
        <small style="display: block; font-size: 14px; color: #666;">
            ({{ $slipsPerPage }} per page)
        </small>
    </h1>
    
    @if(empty($collections))
        <p style="text-align: center; color: #666; padding: 50px;">No collections found for the selected IDs.</p>
    @else
        @foreach($collections as $collection)
            <div class="slip">
                <div class="header">
                    <h3 style="margin: 0;">{{ $companyInfo['name'] ?? 'Middle World Farms' }}</h3>
                    <p style="margin: 5px 0 0 0; font-size: 14px;">Collection Slip</p>
                    <span class="collection-badge">COLLECTION</span>
                </div>
                
                <div class="customer-info">
                    <strong>Customer:</strong> {{ $collection['customer_name'] ?? 'N/A' }}<br>
                    <strong>Collection #:</strong> {{ $collection['order_number'] ?? $collection['id'] ?? 'N/A' }}<br>
                    
                    @if(isset($collection['shipping_address']) && is_array($collection['shipping_address']))
                        <strong>Collection Address:</strong><br>
                        {{ $collection['shipping_address']['first_name'] ?? '' }} {{ $collection['shipping_address']['last_name'] ?? '' }}<br>
                        {{ $collection['shipping_address']['address_1'] ?? '' }}<br>
                        @if(!empty($collection['shipping_address']['address_2']))
                            {{ $collection['shipping_address']['address_2'] }}<br>
                        @endif
                        {{ $collection['shipping_address']['city'] ?? '' }} {{ $collection['shipping_address']['postcode'] ?? '' }}
                    @endif
                    
                    @if(!empty($collection['customer_email']) || !empty($collection['billing_address']['phone']))
                        <br><strong>Contact:</strong><br>
                        @if(!empty($collection['customer_email']))
                            📧 {{ $collection['customer_email'] }}<br>
                        @endif
                        @if(!empty($collection['billing_address']['phone']))
                            📞 {{ $collection['billing_address']['phone'] }}
                        @endif
                    @endif
                </div>
                
                <div class="products">
                    <strong>Items to Collect:</strong>
                    @if(!empty($collection['products']) && is_array($collection['products']))
                        @foreach($collection['products'] as $product)
                            <div class="product-item">
                                <strong>{{ $product['quantity'] ?? 1 }}x</strong> {{ $product['name'] ?? 'Unknown Product' }}
                            </div>
                        @endforeach
                    @else
                        <div class="product-item">No products specified</div>
                    @endif
                </div>
                
                @if(!empty($collection['special_instructions']) || !empty($collection['delivery_notes']))
                    <div class="special-notes">
                        <strong>⚠️ Special Collection Instructions:</strong><br>
                        {{ $collection['special_instructions'] ?? $collection['delivery_notes'] }}
                    </div>
                @endif
                
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 10px; color: #666;">
                    <strong>Date:</strong> {{ $collection['collection_date'] ?? $collection['delivery_date'] ?? date('Y-m-d') }} |
                    <strong>Status:</strong> {{ ucfirst($collection['status'] ?? 'pending') }}
                </div>
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

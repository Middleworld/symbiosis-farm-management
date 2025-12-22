@extends('layouts.app')

@section('title', 'Symbiosis - Delivery Schedule Management')

@section('header-hint')
Navigate between weeks • Thursday = Deliveries, Friday/Saturday = Collections
@endsection

@section('page-header')
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            {{-- Week Navigation Buttons --}}
            <div class="d-flex align-items-center gap-1">
                @php
                    $selectedWeek = isset($selectedWeek) ? (int)$selectedWeek : (int)date('W');
                    $currentWeekActual = (int)date('W');
                    $prevWeek = max(1, $selectedWeek - 1);
                    $nextWeek = min(53, $selectedWeek + 1);
                @endphp
                
                {{-- Previous Week Button --}}
                <form method="GET" action="{{ route('admin.deliveries.index') }}" class="d-inline">
                    <input type="hidden" name="week" value="{{ $prevWeek }}">
                    <button type="submit" class="btn btn-outline-light btn-sm" title="Previous Week ({{ $prevWeek }})" @if($selectedWeek <= 1) disabled @endif>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                </form>
                
                {{-- Current Week Button --}}
                <form method="GET" action="{{ route('admin.deliveries.index') }}" class="d-inline">
                    <input type="hidden" name="week" value="{{ $currentWeekActual }}">
                    <button type="submit" class="btn btn-{{ $selectedWeek == $currentWeekActual ? 'primary' : 'light' }} btn-sm" title="Current Week ({{ $currentWeekActual }})">
                        <i class="fas fa-calendar-day"></i> 
                        @if($selectedWeek == $currentWeekActual)
                            This Week
                        @else
                            Week {{ $currentWeekActual }}
                        @endif
                    </button>
                </form>
                
                {{-- Next Week Button --}}
                <form method="GET" action="{{ route('admin.deliveries.index') }}" class="d-inline">
                    <input type="hidden" name="week" value="{{ $nextWeek }}">
                    <button type="submit" class="btn btn-outline-light btn-sm" title="Next Week ({{ $nextWeek }})" @if($selectedWeek >= 53) disabled @endif>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </form>
                
                {{-- Week Dropdown --}}
                <form method="GET" action="{{ route('admin.deliveries.index') }}" class="d-inline ms-2">
                    <select name="week" class="form-select form-select-sm bg-light" style="width: auto;" onchange="this.form.submit();" title="Jump to specific week">
                        @for($w = 1; $w <= 53; $w++)
                            <option value="{{ $w }}" @if($selectedWeek == $w) selected @endif>
                                Week {{ $w }}
                            </option>
                        @endfor
                    </select>
                </form>
            </div>
        </div>
        <div class="text-center flex-grow-1">
            <h1>Delivery Schedule Management</h1>
            <p class="lead mb-0">Real-time delivery data from WooCommerce</p>
        </div>
        <div></div>
    </div>
@endsection

@section('content')
<style>
/* Ensure delivery page content respects main layout */
body {
    overflow-x: hidden;
}

/* Force this page to respect sidebar layout */
.content-wrapper {
    position: relative !important;
    z-index: 1 !important;
}

/* Add spacing between bulk action buttons */
.btn-group .btn {
    margin-right: 5px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Add space before bulk action button groups */
.mb-3 .btn-group {
    margin-top: 20px;
}

/* Folder-style main tabs */
#scheduleTab {
    border-bottom: none;
    margin-bottom: 0;
}

#scheduleTab .nav-item {
    margin-right: 5px;
}

#scheduleTab .nav-link {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #dee2e6;
    border-bottom: none;
    border-radius: 15px 15px 0 0;
    padding: 12px 24px;
    font-weight: 700;
    font-size: 16px;
    color: #495057;
    position: relative;
    margin-bottom: 0;
    transform: perspective(20px) rotateX(2deg);
    box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

#scheduleTab .nav-link:hover {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    transform: perspective(20px) rotateX(0deg) translateY(-2px);
    box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
    border-color: #adb5bd;
}

#scheduleTab .nav-link.active {
    background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
    border-color: #007bff;
    color: #007bff;
    transform: perspective(20px) rotateX(0deg) translateY(-3px);
    box-shadow: 0 -6px 16px rgba(0,123,255,0.2);
    z-index: 10;
}

#scheduleTab .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 4px;
    background: #ffffff;
    z-index: 11;
}

/* Main tab content area */
.tab-content {
    background: #ffffff;
    border: 2px solid #dee2e6;
    border-top: 2px solid #007bff;
    border-radius: 0 8px 8px 8px;
    padding: 0;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

/* Subtabs styling - connected to main tabs */
.card.mt-3 {
    margin-top: 0 !important;
    border: none;
    box-shadow: none;
}

.card.mt-3 .card-header {
    background: transparent;
    border: none;
    padding: 0;
    margin-bottom: 0;
}

.card.mt-3 .card-header h5 {
    display: none; /* Hide the status header */
}

/* Subtabs - 3D folder style the right way up */
.nav-pills.nav-sm {
    gap: 4px;
    margin-top: 15px; /* Add space below main tabs */
    padding-left: 30px; /* Indent subtabs slightly */
}

.nav-pills.nav-sm .nav-link {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #ced4da;
    border-bottom: none;
    border-radius: 10px 10px 0 0; /* Right way up folder shape */
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 700;
    color: #495057;
    position: relative;
    margin-bottom: 0;
    transform: perspective(15px) rotateX(2deg); /* Right way up 3D effect */
    box-shadow: 0 -3px 8px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.nav-pills.nav-sm .nav-link:hover {
    background: linear-gradient(135deg, #ffffff 0%, #f1f3f5 100%);
    border-color: #6c757d;
    color: #212529;
    transform: perspective(15px) rotateX(0deg) translateY(-2px); /* Lift up on hover */
    box-shadow: 0 -4px 12px rgba(0,0,0,0.2);
    text-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

.nav-pills.nav-sm .nav-link.active {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border-color: #0056b3;
    color: #ffffff;
    transform: perspective(15px) rotateX(0deg) translateY(-3px); /* Lift more when active */
    box-shadow: 0 -6px 16px rgba(0,123,255,0.4);
    z-index: 5;
    text-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

.nav-pills.nav-sm .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 3px;
    background: #ffffff;
    z-index: 6;
}

/* Card body for subtab content */
.card.mt-3 .card-body {
    padding: 0;
    background: #ffffff;
}

/* Week information banner styling */
.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border: 1px solid #b6d4d9;
    border-radius: 8px;
}

/* Badge styling in tabs */
.badge {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
}

/* Table responsive styling */
.table-responsive {
    border-radius: 0 0 8px 8px;
    overflow: hidden;
}
</style>

{{-- 🚀 CACHE TEST - LAST UPDATED: {{ date('Y-m-d H:i:s') }} --}}
    
    {{-- API Status --}}
    @if(isset($api_test))
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="alert {{ $api_test['connection']['success'] ? 'alert-success' : 'alert-danger' }}">
                    <strong>API Connection:</strong> {{ $api_test['connection']['success'] ? 'Connected' : 'Failed' }}
                    @if($api_test['connection']['success'])
                        <br><small>{{ $api_test['connection']['message'] ?? '' }}</small>
                    @endif
                </div>
            </div>
            @if(isset($api_test['auth']))
            <div class="col-md-6">
                <div class="alert {{ $api_test['auth']['success'] ? 'alert-success' : 'alert-danger' }}">
                    <strong>Authentication:</strong> {{ $api_test['auth']['success'] ? 'Authenticated' : 'Failed' }}
                </div>
            </div>
            @endif
        </div>
    @endif
    
    {{-- Error Display --}}
    @if(isset($error) && $error)
        <div class="alert alert-danger">
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif
    
    {{-- Schedule Data --}}
    @if(isset($scheduleData) && $scheduleData)
        @if(isset($scheduleData['success']) && $scheduleData['success'] && isset($scheduleData['data']))
            @php
                $totalDeliveries = 0;
                $totalCollections = 0;
                $activeDeliveries = 0;
                $activeCollections = 0;
                $currentWeekActual = (int)date('W');
                $displayWeek = isset($selectedWeek) ? (int)$selectedWeek : $currentWeekActual;
                $displayWeekType = ($displayWeek % 2 === 1) ? 'A' : 'B'; // Odd weeks = A, Even weeks = B
                $isCurrentWeek = ($displayWeek == $currentWeekActual);
                foreach($scheduleData['data'] as $dateData) {
                    $totalDeliveries += count($dateData['deliveries'] ?? []);
                    $totalCollections += count($dateData['collections'] ?? []);
                    
                    // Count only active items for main tabs
                    if(isset($dateData['deliveries'])) {
                        foreach($dateData['deliveries'] as $delivery) {
                            if(isset($delivery['status']) && $delivery['status'] === 'active') {
                                $activeDeliveries++;
                            }
                        }
                    }
                    if(isset($dateData['collections'])) {
                        foreach($dateData['collections'] as $collection) {
                            if(isset($collection['status']) && $collection['status'] === 'active') {
                                $activeCollections++;
                            }
                        }
                    }
                }
            @endphp
            
            {{-- Main Content Cards --}}
            <div class="card">
                <div class="card-header">
                    {{-- Navigation Tabs --}}
                    <ul class="nav nav-tabs mt-3" id="scheduleTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                                📋 All Subscriptions ({{ $totalDeliveries + $totalCollections }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="deliveries-tab" data-bs-toggle="tab" data-bs-target="#deliveries" type="button" role="tab" aria-controls="deliveries" aria-selected="false">
                                🚚 Deliveries
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="collections-tab" data-bs-toggle="tab" data-bs-target="#collections" type="button" role="tab" aria-controls="collections" aria-selected="false">
                                📦 Collections
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="scheduleTabContent">
                        {{-- All Tab - Show only active items --}}
                        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                            @if($activeDeliveries + $activeCollections > 0)
                                @foreach($scheduleData['data'] as $date => $dateData)
                                    @php
                                        $activeDeliveriesForDate = collect($dateData['deliveries'] ?? [])->filter(function($delivery) {
                                            return isset($delivery['status']) && $delivery['status'] === 'active';
                                        })->toArray();
                                        $activeCollectionsForDate = collect($dateData['collections'] ?? [])->filter(function($collection) {
                                            return isset($collection['status']) && $collection['status'] === 'active';
                                        })->toArray();
                                    @endphp
                                    @if(count($activeDeliveriesForDate) > 0 || count($activeCollectionsForDate) > 0)
                                        <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                        
                                        {{-- Active Deliveries for this date --}}
                                        @if(count($activeDeliveriesForDate) > 0)
                                            <h5 class="text-primary">🚚 Active Deliveries ({{ count($activeDeliveriesForDate) }})</h5>
                                            @include('admin.deliveries.partials.delivery-table', ['items' => $activeDeliveriesForDate, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                        @endif
                                        
                                        {{-- Active Collections for this date --}}
                                        @if(count($activeCollectionsForDate) > 0)
                                            <h5 class="text-success">📦 Active Collections ({{ count($activeCollectionsForDate) }})</h5>
                                            @include('admin.deliveries.partials.collection-table', ['items' => $activeCollectionsForDate, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                        @endif
                                    @endif
                                @endforeach
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No active deliveries or collections scheduled for the current period.
                                </div>
                            @endif
                        </div>
                        
                        {{-- Deliveries Only Tab --}}
                        <div class="tab-pane fade" id="deliveries" role="tabpanel" aria-labelledby="deliveries-tab">
                            {{-- Deliveries Status Subtabs --}}
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">🚚 Deliveries by Status</h5>
                                    <ul class="nav nav-pills nav-sm mt-2" id="deliveriesStatusTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link btn-sm" id="deliveries-all-tab" data-bs-toggle="pill" data-bs-target="#deliveries-all" type="button" role="tab">
                                                All ({{ $totalDeliveries }})
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active btn-sm" id="deliveries-active-tab" data-bs-toggle="pill" data-bs-target="#deliveries-active" type="button" role="tab">
                                                Active ({{ $deliveryStatusCounts['active'] ?? 0 }})
                                            </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="deliveries-processing-tab" data-bs-toggle="pill" data-bs-target="#deliveries-processing" type="button" role="tab">
                                                    Processing ({{ $deliveryStatusCounts['processing'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="deliveries-pending-tab" data-bs-toggle="pill" data-bs-target="#deliveries-pending" type="button" role="tab">
                                                    Pending ({{ $deliveryStatusCounts['pending'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="deliveries-completed-tab" data-bs-toggle="pill" data-bs-target="#deliveries-completed" type="button" role="tab">
                                                    Completed ({{ $deliveryStatusCounts['completed'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="deliveries-on-hold-tab" data-bs-toggle="pill" data-bs-target="#deliveries-on-hold" type="button" role="tab">
                                                    On Hold ({{ $deliveryStatusCounts['on-hold'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm text-muted" id="deliveries-cancelled-tab" data-bs-toggle="pill" data-bs-target="#deliveries-cancelled" type="button" role="tab">
                                                    Cancelled ({{ $deliveryStatusCounts['cancelled'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm text-muted" id="deliveries-refunded-tab" data-bs-toggle="pill" data-bs-target="#deliveries-refunded" type="button" role="tab">
                                                    Refunded ({{ $deliveryStatusCounts['refunded'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm text-muted" id="deliveries-other-tab" data-bs-toggle="pill" data-bs-target="#deliveries-other" type="button" role="tab">
                                                    Other ({{ $deliveryStatusCounts['other'] ?? 0 }})
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div class="tab-content" id="deliveriesStatusTabContent">
                                            {{-- All Deliveries --}}
                                            <div class="tab-pane fade" id="deliveries-all" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                        <h5 class="text-primary">🚚 Deliveries ({{ count($dateData['deliveries']) }})</h5>
                                                        @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Active Deliveries - Default shown --}}
                                            <div class="tab-pane fade show active" id="deliveries-active" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        @php
                                                            $activeDeliveries = collect($dateData['deliveries'])->filter(function($delivery) {
                                                                return isset($delivery['status']) && $delivery['status'] === 'active';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($activeDeliveries) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-primary">🚚 Active Deliveries ({{ count($activeDeliveries) }})</h5>
                                                            @include('admin.deliveries.partials.delivery-table', ['items' => $activeDeliveries, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Processing Deliveries --}}
                                            <div class="tab-pane fade" id="deliveries-processing" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        @php
                                                            $processingDeliveries = collect($dateData['deliveries'])->filter(function($delivery) {
                                                                return isset($delivery['status']) && $delivery['status'] === 'processing';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($processingDeliveries) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-primary">🚚 Processing Deliveries ({{ count($processingDeliveries) }})</h5>
                                                            @include('admin.deliveries.partials.delivery-table', ['items' => $processingDeliveries, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Pending Deliveries --}}
                                            <div class="tab-pane fade" id="deliveries-pending" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        @php
                                                            $pendingDeliveries = collect($dateData['deliveries'])->filter(function($delivery) {
                                                                return isset($delivery['status']) && $delivery['status'] === 'pending';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($pendingDeliveries) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-primary">🚚 Pending Deliveries ({{ count($pendingDeliveries) }})</h5>
                                                            @include('admin.deliveries.partials.delivery-table', ['items' => $pendingDeliveries, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Completed Deliveries --}}
                                            <div class="tab-pane fade" id="deliveries-completed" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        @php
                                                            $completedDeliveries = collect($dateData['deliveries'])->filter(function($delivery) {
                                                                return isset($delivery['status']) && $delivery['status'] === 'completed';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($completedDeliveries) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-primary">🚚 Completed Deliveries ({{ count($completedDeliveries) }})</h5>
                                                            @include('admin.deliveries.partials.delivery-table', ['items' => $completedDeliveries, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- On Hold Deliveries --}}
                                            <div class="tab-pane fade" id="deliveries-on-hold" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        @php
                                                            $onHoldDeliveries = collect($dateData['deliveries'])->filter(function($delivery) {
                                                                return isset($delivery['status']) && $delivery['status'] === 'on-hold';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($onHoldDeliveries) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-primary">🚚 On Hold Deliveries ({{ count($onHoldDeliveries) }})</h5>
                                                            @include('admin.deliveries.partials.delivery-table', ['items' => $onHoldDeliveries, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Cancelled Deliveries --}}
                                            <div class="tab-pane fade" id="deliveries-cancelled" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        @php
                                                            $cancelledDeliveries = collect($dateData['deliveries'])->filter(function($delivery) {
                                                                return isset($delivery['status']) && $delivery['status'] === 'cancelled';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($cancelledDeliveries) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-primary">🚚 Cancelled Deliveries ({{ count($cancelledDeliveries) }})</h5>
                                                            @include('admin.deliveries.partials.delivery-table', ['items' => $cancelledDeliveries, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Refunded Deliveries --}}
                                            <div class="tab-pane fade" id="deliveries-refunded" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        @php
                                                            $refundedDeliveries = collect($dateData['deliveries'])->filter(function($delivery) {
                                                                return isset($delivery['status']) && $delivery['status'] === 'refunded';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($refundedDeliveries) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-primary">🚚 Refunded Deliveries ({{ count($refundedDeliveries) }})</h5>
                                                            @include('admin.deliveries.partials.delivery-table', ['items' => $refundedDeliveries, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Other Status Deliveries --}}
                                            <div class="tab-pane fade" id="deliveries-other" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['deliveries'] ?? []) > 0)
                                                        @php
                                                            $otherDeliveries = collect($dateData['deliveries'])->filter(function($delivery) {
                                                                $status = $delivery['status'] ?? 'unknown';
                                                                return !in_array($status, ['active', 'processing', 'pending', 'completed', 'on-hold', 'cancelled', 'refunded']);
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($otherDeliveries) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-primary">🚚 Other Status Deliveries ({{ count($otherDeliveries) }})</h5>
                                                            @include('admin.deliveries.partials.delivery-table', ['items' => $otherDeliveries, 'type' => 'delivery', 'showDeliveryActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-truck"></i> No deliveries scheduled for the current period.
                                </div>
                            @endif
                        </div>
                        
                        {{-- Collections Only Tab --}}
                        <div class="tab-pane fade" id="collections" role="tabpanel" aria-labelledby="collections-tab">
                            @if($totalCollections > 0)
                                {{-- Collections Status Subtabs --}}
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">📦 Collections by Status</h5>
                                        <ul class="nav nav-pills nav-sm mt-2" id="collectionsStatusTab" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="collections-all-tab" data-bs-toggle="pill" data-bs-target="#collections-all" type="button" role="tab">
                                                    All ({{ $totalCollections }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active btn-sm" id="collections-active-tab" data-bs-toggle="pill" data-bs-target="#collections-active" type="button" role="tab">
                                                    📦 Active Collections ({{ $statusCounts['active'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="collections-processing-tab" data-bs-toggle="pill" data-bs-target="#collections-processing" type="button" role="tab">
                                                    Processing ({{ $statusCounts['processing'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="collections-on-hold-tab" data-bs-toggle="pill" data-bs-target="#collections-on-hold" type="button" role="tab">
                                                    On Hold ({{ $statusCounts['on-hold'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="collections-pending-tab" data-bs-toggle="pill" data-bs-target="#collections-pending" type="button" role="tab">
                                                    Pending ({{ $statusCounts['pending'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm" id="collections-completed-tab" data-bs-toggle="pill" data-bs-target="#collections-completed" type="button" role="tab">
                                                    Completed ({{ $statusCounts['completed'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm text-muted" id="collections-cancelled-tab" data-bs-toggle="pill" data-bs-target="#collections-cancelled" type="button" role="tab">
                                                    Cancelled ({{ $statusCounts['cancelled'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm text-muted" id="collections-refunded-tab" data-bs-toggle="pill" data-bs-target="#collections-refunded" type="button" role="tab">
                                                    Refunded ({{ $statusCounts['refunded'] ?? 0 }})
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm text-muted" id="collections-other-tab" data-bs-toggle="pill" data-bs-target="#collections-other" type="button" role="tab">
                                                    Other ({{ $statusCounts['other'] ?? 0 }})
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div class="tab-content" id="collectionsStatusTabContent">
                                            {{-- All Collections --}}
                                            <div class="tab-pane fade" id="collections-all" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                        @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Active Collections - Default shown --}}
                                            <div class="tab-pane fade show active" id="collections-active" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        @php
                                                            $activeCollections = collect($dateData['collections'])->filter(function($collection) {
                                                                return isset($collection['status']) && $collection['status'] === 'active';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($activeCollections) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            <h5 class="text-success">📦 Active Collections ({{ count($activeCollections) }})</h5>
                                                            @include('admin.deliveries.partials.collection-table', ['items' => $activeCollections, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Processing Collections --}}
                                            <div class="tab-pane fade" id="collections-processing" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        @php
                                                            $processingCollections = collect($dateData['collections'])->filter(function($collection) {
                                                                return isset($collection['status']) && $collection['status'] === 'processing';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($processingCollections) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            @include('admin.deliveries.partials.collection-table', ['items' => $processingCollections, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- On Hold Collections --}}
                                            <div class="tab-pane fade" id="collections-on-hold" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        @php
                                                            $onHoldCollections = collect($dateData['collections'])->filter(function($collection) {
                                                                return isset($collection['status']) && $collection['status'] === 'on-hold';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($onHoldCollections) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            @include('admin.deliveries.partials.collection-table', ['items' => $onHoldCollections, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Pending Collections --}}
                                            <div class="tab-pane fade" id="collections-pending" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        @php
                                                            $pendingCollections = collect($dateData['collections'])->filter(function($collection) {
                                                                return isset($collection['status']) && $collection['status'] === 'pending';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($pendingCollections) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            @include('admin.deliveries.partials.collection-table', ['items' => $pendingCollections, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Completed Collections --}}
                                            <div class="tab-pane fade" id="collections-completed" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        @php
                                                            $completedCollections = collect($dateData['collections'])->filter(function($collection) {
                                                                return isset($collection['status']) && $collection['status'] === 'completed';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($completedCollections) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            @include('admin.deliveries.partials.collection-table', ['items' => $completedCollections, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Cancelled Collections --}}
                                            <div class="tab-pane fade" id="collections-cancelled" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        @php
                                                            $cancelledCollections = collect($dateData['collections'])->filter(function($collection) {
                                                                return isset($collection['status']) && $collection['status'] === 'cancelled';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($cancelledCollections) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            @include('admin.deliveries.partials.collection-table', ['items' => $cancelledCollections, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Refunded Collections --}}
                                            <div class="tab-pane fade" id="collections-refunded" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        @php
                                                            $refundedCollections = collect($dateData['collections'])->filter(function($collection) {
                                                                return isset($collection['status']) && $collection['status'] === 'refunded';
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($refundedCollections) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            @include('admin.deliveries.partials.collection-table', ['items' => $refundedCollections, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Other Status Collections --}}
                                            <div class="tab-pane fade" id="collections-other" role="tabpanel">
                                                @foreach($scheduleData['data'] as $date => $dateData)
                                                    @if(count($dateData['collections'] ?? []) > 0)
                                                        @php
                                                            $otherCollections = collect($dateData['collections'])->filter(function($collection) {
                                                                $status = $collection['status'] ?? 'unknown';
                                                                return !in_array($status, ['active', 'processing', 'on-hold', 'pending', 'completed', 'cancelled', 'refunded']);
                                                            })->toArray();
                                                        @endphp
                                                        @if(count($otherCollections) > 0)
                                                            <h4 class="mt-3 mb-3">{{ $dateData['date_formatted'] ?? $date }}</h4>
                                                            @include('admin.deliveries.partials.collection-table', ['items' => $otherCollections, 'type' => 'collection', 'showCollectionActions' => true, 'currentDate' => $date])
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-box"></i> No collections scheduled for the current period.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No schedule data available.
        </div>
    @endif

@endsection

@section('scripts')
<script>
console.log('Delivery schedule JavaScript loaded at', new Date().toISOString());

document.addEventListener('DOMContentLoaded', function() {
    // Bulk Operations for both Deliveries and Collections
    // Handle multiple instances of select/deselect buttons across tabs
    document.addEventListener('click', function(e) {
        // Select All buttons (for any table type)
        if (e.target.id === 'selectAllDeliveries' || e.target.closest('[id="selectAllDeliveries"]')) {
            const checkboxes = document.querySelectorAll('.delivery-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) selectAllCheckbox.checked = true;
            updateBulkButtonStates();
        }
        
        // Deselect All buttons (for any table type)
        if (e.target.id === 'deselectAllDeliveries' || e.target.closest('[id="deselectAllDeliveries"]')) {
            const checkboxes = document.querySelectorAll('.delivery-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            updateBulkButtonStates();
        }
        
        // Select All Collections (now using class)
        if (e.target.classList.contains('select-all-collections-btn') || e.target.closest('.select-all-collections-btn')) {
            const checkboxes = document.querySelectorAll('.collection-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            const selectAllCollectionCheckbox = document.getElementById('selectAllCollectionCheckbox');
            if (selectAllCollectionCheckbox) selectAllCollectionCheckbox.checked = true;
            updateCollectionBulkButtonStates();
        }
        
        // Deselect All Collections (now using class)
        if (e.target.classList.contains('deselect-all-collections-btn') || e.target.closest('.deselect-all-collections-btn')) {
            const checkboxes = document.querySelectorAll('.collection-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            const selectAllCollectionCheckbox = document.getElementById('selectAllCollectionCheckbox');
            if (selectAllCollectionCheckbox) selectAllCollectionCheckbox.checked = false;
            updateCollectionBulkButtonStates();
        }
        
        // Print Schedule buttons (for both deliveries and collections)
        if (e.target.id === 'printScheduleBtn' || e.target.closest('#printScheduleBtn')) {
            e.preventDefault();
            const selectedIds = getSelectedDeliveryIds();
            if (selectedIds.length === 0) {
                alert('Please select at least one delivery to print schedule.');
                return;
            }
            
            const printUrl = '{{ route("admin.deliveries.print") }}?ids=' + selectedIds.join(',');
            window.open(printUrl, '_blank');
        }
        
        // Print Collection Schedule button (now using class)
        if (e.target.classList.contains('print-collection-schedule-btn') || e.target.closest('.print-collection-schedule-btn')) {
            e.preventDefault();
            const selectedIds = getSelectedCollectionIds();
            if (selectedIds.length === 0) {
                alert('Please select at least one collection to print schedule.');
                return;
            }
            
            const printUrl = '{{ route("admin.deliveries.print") }}?ids=' + selectedIds.join(',') + '&type=collection';
            window.open(printUrl, '_blank');
        }
        
        // Print Packing Slips buttons
        if (e.target.id === 'printPackingSlipsBtn' || e.target.closest('#printPackingSlipsBtn')) {
            e.preventDefault();
            const selectedIds = getSelectedDeliveryIds();
            if (selectedIds.length === 0) {
                alert('Please select at least one delivery to print packing slips.');
                return;
            }
            
            const printUrl = '{{ route("admin.deliveries.print-slips") }}?ids=' + selectedIds.join(',');
            window.open(printUrl, '_blank');
        }
        
        // Print Collection Slips button (now using class)
        if (e.target.classList.contains('print-collection-slips-btn') || e.target.closest('.print-collection-slips-btn')) {
            e.preventDefault();
            const selectedIds = getSelectedCollectionIds();
            if (selectedIds.length === 0) {
                alert('Please select at least one collection to print slips.');
                return;
            }
            
            const printUrl = '{{ route("admin.deliveries.print-slips") }}?ids=' + selectedIds.join(',') + '&type=collection';
            window.open(printUrl, '_blank');
        }
        
        // Add to Route Planner button
        if (e.target.id === 'addToRouteBtn' || e.target.closest('#addToRouteBtn')) {
            e.preventDefault();
            const selectedIds = getSelectedDeliveryIds();
            if (selectedIds.length === 0) {
                alert('Please select at least one delivery to add to route planner.');
                return;
            }
            
            const routeUrl = '{{ route("admin.route-planner") }}?delivery_ids=' + selectedIds.join(',');
            window.location.href = routeUrl;
        }
        
        // Handle completion button clicks
        if (e.target.classList.contains('mark-complete-btn') || e.target.closest('.mark-complete-btn')) {
            e.preventDefault();
            const button = e.target.classList.contains('mark-complete-btn') ? e.target : e.target.closest('.mark-complete-btn');
            const deliveryId = button.getAttribute('data-delivery-id');
            const customerName = button.getAttribute('data-customer-name');
            const deliveryDate = button.getAttribute('data-delivery-date');
            
            // Confirm action
            if (confirm(`Mark ${customerName}'s order as completed for ${deliveryDate}?`)) {
                // Disable button and show loading
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Completing...';
                
                // Send completion request
                fetch('{{ route("admin.deliveries.mark-complete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        delivery_id: deliveryId,
                        delivery_date: deliveryDate,
                        type: button.closest('table').querySelector('.delivery-checkbox') ? 'delivery' : 'collection'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button to show completion
                        button.className = 'btn btn-success btn-sm';
                        button.disabled = true;
                        button.innerHTML = '<i class="fas fa-check-circle"></i> Done';
                        
                        // Add timestamp below button using server timestamp
                        const timestamp = data.completed_at || new Date().toLocaleString();
                        const timestampElement = document.createElement('br');
                        const timestampText = document.createElement('small');
                        timestampText.className = 'text-muted';
                        timestampText.textContent = timestamp;
                        
                        button.parentNode.appendChild(timestampElement);
                        button.parentNode.appendChild(timestampText);
                        
                        // Show success message
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', `${customerName}'s order marked as completed for ${deliveryDate} at ${timestamp}`);
                        } else {
                            alert(`${customerName}'s order marked as completed for ${deliveryDate} at ${timestamp}`);
                        }
                    } else {
                        // Handle error
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-check"></i> Complete';
                        alert('Error marking order as complete: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-check"></i> Complete';
                    alert('Error marking order as complete. Please try again.');
                });
            }
        }
    });

    // Initialize all Bootstrap dropdowns
    function initializeDropdowns() {
        // Initialize all dropdowns on the page
        const dropdownElements = document.querySelectorAll('.dropdown-toggle');
        console.log('Found', dropdownElements.length, 'dropdown toggles to initialize');
        dropdownElements.forEach(function(element, index) {
            // Only initialize if not already initialized
            if (!bootstrap.Dropdown.getInstance(element)) {
                new bootstrap.Dropdown(element);
                console.log('Initialized dropdown', index, element);
            }
        });
    }

    // Initialize dropdowns on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeDropdowns();
    });

    // Also initialize dropdowns after any dynamic content changes
    // This handles cases where content is loaded via AJAX or tabs are switched
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                // Check if any added nodes contain dropdown toggles
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        const dropdowns = node.querySelectorAll ? node.querySelectorAll('.dropdown-toggle') : [];
                        if (dropdowns.length > 0 || node.classList?.contains('dropdown-toggle')) {
                            setTimeout(initializeDropdowns, 100); // Small delay to ensure DOM is ready
                        }
                    }
                });
            }
        });
    });

    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Initialize dropdowns when Bootstrap tabs are shown
    document.addEventListener('shown.bs.tab', function(e) {
        setTimeout(initializeDropdowns, 100);
    });

    // Initialize dropdowns immediately in case content is already loaded
    setTimeout(initializeDropdowns, 500);

    // Debug: Log all dropdown toggles found
    setTimeout(function() {
        const toggles = document.querySelectorAll('.week-toggle-btn');
        console.log('Found', toggles.length, 'week toggle buttons:');
        toggles.forEach(function(toggle, index) {
            console.log('Toggle', index, ':', toggle, 'text:', toggle.textContent.trim());
            const dropdown = toggle.closest('.dropdown');
            if (dropdown) {
                const menu = dropdown.querySelector('.dropdown-menu');
                console.log('  Has dropdown menu:', !!menu);
                if (menu) {
                    const items = menu.querySelectorAll('.week-change-btn');
                    console.log('  Has', items.length, 'week change items');
                }
            }
        });
    }, 1000);

    // Handle week toggle button clicks to manually open dropdown
    document.addEventListener('click', function(e) {
        const toggleBtn = e.target.classList.contains('week-toggle-btn') ? e.target : e.target.closest('.week-toggle-btn');
        
        if (toggleBtn && !e.target.classList.contains('week-change-btn') && !e.target.closest('.week-change-btn')) {
            console.log('Week toggle button clicked:', toggleBtn);
            
            // Get or create Bootstrap dropdown instance
            let dropdown = bootstrap.Dropdown.getInstance(toggleBtn);
            if (!dropdown) {
                console.log('Creating new dropdown instance');
                dropdown = new bootstrap.Dropdown(toggleBtn);
            }
            
            // Toggle the dropdown
            const menu = toggleBtn.nextElementSibling;
            if (menu && menu.classList.contains('dropdown-menu')) {
                if (menu.classList.contains('show')) {
                    console.log('Hiding dropdown');
                    dropdown.hide();
                } else {
                    console.log('Showing dropdown');
                    dropdown.show();
                }
            }
        }
        
        // Debug: Check if week-change-btn is being clicked
        if (e.target.classList.contains('week-change-btn')) {
            console.log('Direct week-change-btn click detected!', e.target);
        }
        if (e.target.closest('.week-change-btn')) {
            console.log('Week-change-btn parent click detected!', e.target, 'closest:', e.target.closest('.week-change-btn'));
        }
    });

    // Handle week change button clicks
    document.addEventListener('click', function(e) {
        // Only handle clicks on week-change-btn elements, not dropdown toggles
        if (e.target.classList.contains('week-change-btn') || e.target.closest('.week-change-btn')) {
            console.log('Week change button clicked!', e.target);
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling that might interfere with dropdown

            const button = e.target.classList.contains('week-change-btn') ? e.target : e.target.closest('.week-change-btn');
            const customerId = button.getAttribute('data-customer-id');
            const newWeek = button.getAttribute('data-new-week');
            const currentWeek = button.getAttribute('data-current-week');
            
            console.log('Week change data:', {
                customerId: customerId,
                newWeek: newWeek,
                currentWeek: currentWeek,
                button: button
            });

            // Don't do anything if they're clicking the same week
            if (newWeek === currentWeek) {
                return;
            }

            // Confirm action
            if (confirm(`Change this customer from Week ${currentWeek} to Week ${newWeek}?`)) {
                // Disable button and show loading
                button.style.pointerEvents = 'none';
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing...';

                // Send week change request
                fetch('{{ route("admin.customers.update-week") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        customer_id: customerId,
                        week_type: newWeek
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the badge text and color
                        const dropdownButton = button.closest('.dropdown').querySelector('.dropdown-toggle');
                        dropdownButton.className = `btn btn-sm badge bg-${newWeek === 'A' ? 'success' : 'info'} dropdown-toggle week-toggle-btn`;
                        dropdownButton.innerHTML = `Week ${newWeek}`;

                        // Update data attributes for future clicks
                        const weekButtons = button.closest('.dropdown-menu').querySelectorAll('.week-change-btn');
                        weekButtons.forEach(btn => {
                            if (btn.getAttribute('data-new-week') === newWeek) {
                                btn.setAttribute('data-current-week', newWeek);
                            } else {
                                btn.setAttribute('data-current-week', newWeek);
                            }
                        });

                        // Close the dropdown
                        const bsDropdown = bootstrap.Dropdown.getInstance(dropdownButton);
                        if (bsDropdown) {
                            bsDropdown.hide();
                        }

                        // Show success message
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', `Customer week changed to Week ${newWeek}`);
                        } else {
                            alert(`Customer week changed to Week ${newWeek}`);
                        }

                        // Reload the page to show updated schedule
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Handle error
                        button.style.pointerEvents = 'auto';
                        button.innerHTML = `<span class="badge bg-${newWeek === 'A' ? 'success' : 'info'} me-2">${newWeek}</span>Week ${newWeek} (${newWeek === 'A' ? 'Odd' : 'Even'} weeks)`;
                        alert('Error changing week: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    button.style.pointerEvents = 'auto';
                    button.innerHTML = `<span class="badge bg-${newWeek === 'A' ? 'success' : 'info'} me-2">${newWeek}</span>Week ${newWeek} (${newWeek === 'A' ? 'Odd' : 'Even'} weeks)`;
                    alert('Error changing week. Please try again.');
                });
            }
        }
    });    const selectAllBtn = document.getElementById('selectAllDeliveries');
    const deselectAllBtn = document.getElementById('deselectAllDeliveries');
    const printScheduleBtn = document.getElementById('printScheduleBtn');
    const printPackingSlipsBtn = document.getElementById('printPackingSlipsBtn');
    const addToRouteBtn = document.getElementById('addToRouteBtn');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    // Handle select all checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.delivery-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkButtonStates();
        });
    }

    // Handle individual checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('delivery-checkbox')) {
            updateBulkButtonStates();
            updateSelectAllCheckbox();
        }
        if (e.target.classList.contains('collection-checkbox')) {
            updateCollectionBulkButtonStates();
            updateSelectAllCollectionCheckbox();
        }
    });

    function getSelectedDeliveryIds() {
        const checkboxes = document.querySelectorAll('.delivery-checkbox:checked');
        return Array.from(checkboxes).map(checkbox => checkbox.value);
    }

    function updateBulkButtonStates() {
        const selectedDeliveryCount = document.querySelectorAll('.delivery-checkbox:checked').length;
        const selectedCollectionCount = document.querySelectorAll('.collection-checkbox:checked').length;
        
        // Update delivery print schedule buttons (only for deliveries)
        document.querySelectorAll('#printScheduleBtn').forEach(btn => {
            btn.disabled = selectedDeliveryCount === 0;
            btn.title = selectedDeliveryCount === 0 ? 'Select deliveries to print schedule' : `Print schedule for ${selectedDeliveryCount} delivery(s)`;
        });
        
        // Update all delivery packing slip buttons
        document.querySelectorAll('#printPackingSlipsBtn').forEach(btn => {
            btn.disabled = selectedDeliveryCount === 0;
            btn.title = selectedDeliveryCount === 0 ? 'Select deliveries to print packing slips' : `Print ${selectedDeliveryCount} packing slip(s)`;
        });
        
        // Update all route planner buttons
        document.querySelectorAll('#addToRouteBtn').forEach(btn => {
            btn.disabled = selectedDeliveryCount === 0;
            btn.title = selectedDeliveryCount === 0 ? 'Select deliveries to add to route planner' : `Add ${selectedDeliveryCount} delivery(s) to route planner`;
        });
    }

    function updateSelectAllCheckbox() {
        if (!selectAllCheckbox) return;
        
        const checkboxes = document.querySelectorAll('.delivery-checkbox');
        const checkedBoxes = document.querySelectorAll('.delivery-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedBoxes.length === checkboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }

    // Collection-specific functions
    function getSelectedCollectionIds() {
        const checkboxes = document.querySelectorAll('.collection-checkbox:checked');
        return Array.from(checkboxes).map(checkbox => checkbox.value);
    }

    function updateCollectionBulkButtonStates() {
        const selectedCount = document.querySelectorAll('.collection-checkbox:checked').length;
        
        // Update all collection print schedule buttons (now using class instead of ID)
        document.querySelectorAll('.print-collection-schedule-btn').forEach(btn => {
            btn.disabled = selectedCount === 0;
            btn.title = selectedCount === 0 ? 'Select collections to print schedule' : `Print schedule for ${selectedCount} collection(s)`;
        });
        
        // Update all collection slip buttons (now using class instead of ID)
        document.querySelectorAll('.print-collection-slips-btn').forEach(btn => {
            btn.disabled = selectedCount === 0;
            btn.title = selectedCount === 0 ? 'Select collections to print slips' : `Print ${selectedCount} collection slip(s)`;
        });
    }

    function updateSelectAllCollectionCheckbox() {
        const selectAllCollectionCheckbox = document.getElementById('selectAllCollectionCheckbox');
        if (!selectAllCollectionCheckbox) return;
        
        const checkboxes = document.querySelectorAll('.collection-checkbox');
        const checkedBoxes = document.querySelectorAll('.collection-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            selectAllCollectionCheckbox.checked = false;
            selectAllCollectionCheckbox.indeterminate = false;
        } else if (checkedBoxes.length === checkboxes.length) {
            selectAllCollectionCheckbox.checked = true;
            selectAllCollectionCheckbox.indeterminate = false;
        } else {
            selectAllCollectionCheckbox.checked = false;
            selectAllCollectionCheckbox.indeterminate = true;
        }
    }

    // Initialize bulk button states
    updateBulkButtonStates();
    updateCollectionBulkButtonStates();

    // User switching functionality
    document.querySelectorAll('.user-switch-btn').forEach(button => {
        button.addEventListener('click', function() {
            const email = this.dataset.email;
            const customerName = this.dataset.name || 'Customer';
            
            if (!email) {
                alert('No email available for this customer');
                return;
            }

            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Switching...';
            this.disabled = true;

            // Make AJAX request to switch user
            fetch('/admin/users/switch-by-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    email: email,
                    customer_name: customerName,
                    redirect_to: '/my-account/'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.switch_url) {
                    // Open in new tab
                    window.open(data.switch_url, '_blank');
                    
                    // Show success message
                    const toast = document.createElement('div');
                    toast.className = 'alert alert-success position-fixed';
                    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                    toast.innerHTML = `
                        <i class="fas fa-check-circle"></i> Successfully switched to ${customerName}
                        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                    `;
                    document.body.appendChild(toast);
                    
                    // Auto-remove toast after 3 seconds
                    setTimeout(() => toast.remove(), 3000);
                } else {
                    alert('Failed to switch user: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error switching user:', error);
                alert('Error switching user. Please try again.');
            })
            .finally(() => {
                // Restore button state
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    });

    // Subscription functionality
    document.querySelectorAll('.subscription-btn').forEach(button => {
        button.addEventListener('click', function() {
            const email = this.dataset.email;
            const customerName = this.dataset.name || 'Customer';
            
            if (!email) {
                alert('No email available for this customer');
                return;
            }

            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            this.disabled = true;

            // Make AJAX request to get subscription URL
            fetch('/admin/users/get-subscription-url', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    email: email,
                    customer_name: customerName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.subscription_url) {
                    // Open in new tab
                    window.open(data.subscription_url, '_blank');
                } else {
                    alert('Failed to get subscription URL: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error getting subscription URL:', error);
                alert('Error loading subscription. Please try again.');
            })
            .finally(() => {
                // Restore button state
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    });

    // Profile functionality
    document.querySelectorAll('.profile-btn').forEach(button => {
        button.addEventListener('click', function() {
            const email = this.dataset.email;
            const customerName = this.dataset.name || 'Customer';
            
            if (!email) {
                alert('No email available for this customer');
                return;
            }

            // For now, just show customer info in a modal or alert
            alert(`Customer Profile\n\nName: ${customerName}\nEmail: ${email}\n\nProfile management coming soon!`);
        });
    });
});

// Print Deliveries Button Handler
function printDeliveries() {
    var printWindow = window.open('', '_blank');
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    var dateStr = tomorrow.toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    var printContent = `<!DOCTYPE html><html><head><title>Delivery Schedule - ${dateStr}</title><style>body{margin:30px;font-family:Arial,sans-serif;font-size:12px;}@page{margin:25mm;size:A4;}h1{text-align:center;margin-bottom:20px;font-size:18px;}h2{color:#0066cc;border-bottom:2px solid #0066cc;padding-bottom:5px;font-size:16px;}table{width:100%;border-collapse:collapse;margin-bottom:20px;}th,td{border:1px solid #000;padding:8px;text-align:left;vertical-align:top;}th{background-color:#f8f9fa;font-weight:bold;}.badge{border:1px solid #000;padding:2px 6px;font-size:10px;border-radius:3px;}.page-break{page-break-before:always;}.date-header{background-color:#f8f9fa;padding:10px;margin:20px 0 10px 0;border:1px solid #000;font-weight:bold;}</style></head><body><h1>🚚 DELIVERY SCHEDULE - ${dateStr}</h1>`;
    var dayCards = document.querySelectorAll('.card.mb-3');
    var hasDeliveries = false;
    dayCards.forEach(function(card, index) {
        var dateHeader = card.querySelector('.card-header h5');
        var deliverySection = card.querySelector('.col-md-6:first-child');
        if (deliverySection && deliverySection.querySelector('h6.text-primary')) {
            hasDeliveries = true;
            if (index > 0) { printContent += '<div class="page-break"></div>'; }
            var dateText = dateHeader ? dateHeader.textContent.replace('📅', '').trim() : 'Unknown Date';
            printContent += `<div class="date-header">📅 ${dateText}</div>`;
            var deliveryTable = deliverySection.querySelector('table');
            if (deliveryTable) {
                var deliveryCount = deliverySection.querySelector('h6.text-primary').textContent.match(/\((\d+)\)/);
                printContent += `<h2>🚚 Deliveries${deliveryCount ? ' (' + deliveryCount[1] + ')' : ''}</h2>`;
                printContent += deliveryTable.outerHTML;
            }
        }
    });
    if (!hasDeliveries) {
        printContent += '<p style="text-align: center; font-size: 16px; margin-top: 50px;">No deliveries scheduled for tomorrow.</p>';
    }
    printContent += `<div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">Generated on ${new Date().toLocaleString()} | Middleworld Farms Admin</div></body></html>`;
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() { printWindow.print(); printWindow.close(); }, 500);
}

// Print Collections Button Handler
function printCollections() {
    var printWindow = window.open('', '_blank');
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    var dateStr = tomorrow.toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    var printContent = `<!DOCTYPE html><html><head><title>Collection Schedule - ${dateStr}</title><style>body{margin:30px;font-family:Arial,sans-serif;font-size:12px;}@page{margin:25mm;size:A4;}h1{text-align:center;margin-bottom:20px;font-size:18px;}h2{color:#28a745;border-bottom:2px solid #28a745;padding-bottom:5px;font-size:16px;}table{width:100%;border-collapse:collapse;margin-bottom:20px;}th,td{border:1px solid #000;padding:8px;text-align:left;vertical-align:top;}th{background-color:#f8f9fa;font-weight:bold;}.badge{border:1px solid #000;padding:2px 6px;font-size:10px;border-radius:3px;}.page-break{page-break-before:always;}.date-header{background-color:#f8f9fa;padding:10px;margin:20px 0 10px 0;border:1px solid #000;font-weight:bold;}</style></head><body><h1>🏪 COLLECTION SCHEDULE - ${dateStr}</h1>`;
    var dayCards = document.querySelectorAll('.card.mb-3');
    var hasCollections = false;
    dayCards.forEach(function(card, index) {
        var dateHeader = card.querySelector('.card-header h5');
        var collectionSection = card.querySelector('.col-md-6:last-child');
        if (!collectionSection) { collectionSection = card.querySelector('.col-md-12'); }
        if (collectionSection && collectionSection.querySelector('h6.text-success')) {
            hasCollections = true;
            if (index > 0) { printContent += '<div class="page-break"></div>'; }
            var dateText = dateHeader ? dateHeader.textContent.replace('📅', '').trim() : 'Unknown Date';
            printContent += `<div class="date-header">📅 ${dateText}</div>`;
            var collectionTable = collectionSection.querySelector('table');
            if (collectionTable) {
                var collectionCount = collectionSection.querySelector('h6.text-success').textContent.match(/\((\d+)\)/);
                printContent += `<h2>🏪 Collections${collectionCount ? ' (' + collectionCount[1] + ')' : ''}</h2>`;
                printContent += collectionTable.outerHTML;
            }
        }
    });
    if (!hasCollections) {
        printContent += '<p style="text-align: center; font-size: 16px; margin-top: 50px;">No collections scheduled for tomorrow.</p>';
    }
    printContent += `<div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">Generated on ${new Date().toLocaleString()} | Middleworld Farms Admin</div></body></html>`;
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() { printWindow.print(); printWindow.close(); }, 500);
}

// Attach event listeners to print buttons
$(document).ready(function() {
    $('#printScheduleBtn').on('click', printDeliveries);
    $('#printCollectionScheduleBtn').on('click', printCollections);
});
</script>

@section('styles')
<style>
/* Ensure dropdown toggles work properly */
.dropdown-toggle {
    cursor: pointer !important;
}

/* Make sure badges don't interfere with dropdown functionality */
.badge.dropdown-toggle {
    display: inline-block;
    cursor: pointer;
}

/* Fix for table cell dropdown positioning - only apply when needed */
.table-responsive .dropdown {
    position: relative;
}

.table-responsive .dropdown-menu {
    z-index: 1050;
    min-width: 160px;
}

/* Ensure dropdown menus are visible */
.dropdown-menu {
    display: none;
}

.dropdown-menu.show {
    display: block;
}
</style>
@endsection

@endsection

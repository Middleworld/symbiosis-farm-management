<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use App\Services\CsaSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    /**
     * Display the delivery schedule management page.
     */
    public function index(Request $request, CsaSubscriptionService $csaService)
    {
        try {
            // Get selected week from request, default to current week
            $selectedWeek = (int) $request->get('week', date('W'));
            
            // Test local database connection
            $apiStatus = $csaService->testConnection();
            
            // Get raw data from local Laravel database
            $rawData = [];
            try {
                $rawData = $csaService->getDeliveryScheduleData(500)->toArray();
                \Log::info('DeliveryController Raw Data (Local DB)', [
                    'count' => count($rawData),
                    'first_item' => !empty($rawData) ? array_slice($rawData, 0, 1) : null
                ]);
                \Log::info('DeliveryController Raw Data', [
                    'count' => count($rawData),
                    'first_item' => !empty($rawData) ? array_slice($rawData, 0, 1) : null
                ]);
            } catch (\Exception $e) {
                Log::error('Delivery schedule API timeout: ' . $e->getMessage());
                // Continue with empty data to show the page
                $rawData = [];
            }
            
            // Fetch collection day preferences from WordPress database before transforming
            $collectionDayPreferences = $this->fetchCollectionDayPreferences($rawData);
            
            // Transform data to match view expectations, passing in collection day preferences
            $scheduleData = $this->transformScheduleData($rawData, $selectedWeek, $collectionDayPreferences);
            
            // Add completion data to schedule data
            $scheduleData = $this->addCompletionData($scheduleData);
            
            // Calculate actual totals from transformed data (after duplicate removal)
            $totalDeliveries = 0;
            $totalCollections = 0;
            
            foreach ($scheduleData['data'] as $dateData) {
                $totalDeliveries += count($dateData['deliveries'] ?? []);
                $totalCollections += count($dateData['collections'] ?? []);
            }
            
            // Get ALL subscription statuses and split by delivery type (properly)
            try {
                // Use the same raw data that was fetched for schedule transformation
                $allSubscriptions = $rawData;
                
                // Now split all subscriptions by delivery type AND status AND week
                $deliveryStatusCounts = ['active' => 0, 'processing' => 0, 'on-hold' => 0, 'cancelled' => 0, 'pending' => 0, 'completed' => 0, 'refunded' => 0];
                $statusCounts = ['active' => 0, 'processing' => 0, 'on-hold' => 0, 'cancelled' => 0, 'pending' => 0, 'completed' => 0, 'refunded' => 0];
                
                // ALSO calculate unfiltered totals for overview badges
                $unfilteredDeliveryStatusCounts = ['active' => 0, 'processing' => 0, 'on-hold' => 0, 'cancelled' => 0, 'pending' => 0, 'completed' => 0, 'refunded' => 0];
                $unfilteredStatusCounts = ['active' => 0, 'processing' => 0, 'on-hold' => 0, 'cancelled' => 0, 'pending' => 0, 'completed' => 0, 'refunded' => 0];
                
                $currentWeek = (int) date('W');
                $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
                
                foreach ($allSubscriptions as $sub) {
                    // Determine delivery type (same logic as transform method)
                    $type = $this->determineCustomerType($sub['shipping_total'] ?? null, $sub);
                    $status = $sub['status']; // Use the normalized status from the API response
                    
                    // Always count for unfiltered totals (for overview badges)
                    if ($type === 'deliveries') {
                        if (isset($unfilteredDeliveryStatusCounts[$status])) {
                            $unfilteredDeliveryStatusCounts[$status]++;
                        }
                    } else { // collections
                        if (isset($unfilteredStatusCounts[$status])) {
                            $unfilteredStatusCounts[$status]++;
                        }
                    }
                    
                    // Determine frequency and week type (same logic as transform method)
                    $frequency = 'Weekly';
                    if (isset($sub['billing_period']) && strtolower($sub['billing_period']) === 'week') {
                        $interval = intval($sub['billing_interval'] ?? 1);
                        if ($interval === 2) {
                            $frequency = 'Fortnightly';
                        }
                    }
                    
                    // Week filtering logic (same as transform method)
                    $shouldIncludeInSelectedWeek = true;
                    if (strtolower($frequency) === 'fortnightly') {
                        $customerWeekType = ((int)$sub['id'] % 2 === 1) ? 'B' : 'A';
                        $shouldIncludeInSelectedWeek = ($customerWeekType === $currentWeekType);
                    }
                    
                    // Only count subscriptions that should appear in current week (for subtabs)
                    if ($shouldIncludeInSelectedWeek) {
                        if ($type === 'deliveries') {
                            if (isset($deliveryStatusCounts[$status])) {
                                $deliveryStatusCounts[$status]++;
                            }
                        } else { // collections
                            if (isset($statusCounts[$status])) {
                                $statusCounts[$status]++;
                            }
                        }
                    }
                }
                
                Log::info('Delivery status counts (week-filtered)', $deliveryStatusCounts);
                Log::info('Collection status counts (week-filtered)', $statusCounts);
                Log::info('Unfiltered delivery status counts', $unfilteredDeliveryStatusCounts);
                Log::info('Unfiltered collection status counts', $unfilteredStatusCounts);
                
                // Create arrays of ALL deliveries and collections (not week-filtered) for the tabs
                $allDeliveries = [];
                $allCollections = [];
                
                foreach ($allSubscriptions as $sub) {
                    $type = $this->determineCustomerType($sub['shipping_total'] ?? null, $sub);
                    $status = $sub['status'];
                    
                    // Add to appropriate array
                    if ($type === 'deliveries') {
                        $allDeliveries[] = $sub;
                    } else {
                        $allCollections[] = $sub;
                    }
                }
                
                Log::info('All deliveries count: ' . count($allDeliveries));
                Log::info('All collections count: ' . count($allCollections));
                
            } catch (\Exception $e) {
                Log::error('WooCommerce API failed: ' . $e->getMessage());
                // Simple fallback
                $statusCounts = ['active' => 0, 'processing' => 0, 'on-hold' => 0, 'cancelled' => 0, 'pending' => 0, 'completed' => 0, 'refunded' => 0];
                $deliveryStatusCounts = $statusCounts;
                $unfilteredStatusCounts = $statusCounts;
                $unfilteredDeliveryStatusCounts = $statusCounts;
                $allDeliveries = [];
                $allCollections = [];
            }            
            // Direct database connection status
            $directDbStatus = [
                'success' => true,
                'message' => 'Connected directly to WooCommerce database',
                'data_source' => 'mysql_direct'
            ];
            
            // Simple user switching status (no confusing API messaging)
            $userSwitchingAvailable = true;
            
            $error = null;
            
            return view('admin.deliveries.index', compact(
                'scheduleData', 
                'totalDeliveries', 
                'totalCollections',
                'statusCounts',
                'deliveryStatusCounts',
                'unfilteredStatusCounts',
                'unfilteredDeliveryStatusCounts',
                'allDeliveries',
                'allCollections',
                'directDbStatus',
                'userSwitchingAvailable',
                'error',
                'selectedWeek' // pass to view
            ));
            
        } catch (\Exception $e) {
            $scheduleData = ['data' => []];
            $totalDeliveries = 0;
            $totalCollections = 0;
            $statusCounts = ['active' => 0, 'processing' => 0, 'on-hold' => 0, 'cancelled' => 0, 'pending' => 0, 'completed' => 0, 'refunded' => 0, 'other' => 0];
            $deliveryStatusCounts = ['active' => 0, 'processing' => 0, 'pending' => 0, 'completed' => 0, 'on-hold' => 0, 'cancelled' => 0, 'refunded' => 0, 'other' => 0];
            $unfilteredStatusCounts = $statusCounts;
            $unfilteredDeliveryStatusCounts = $deliveryStatusCounts;
            $allDeliveries = [];
            $allCollections = [];
            $directDbStatus = ['success' => false, 'message' => 'Direct database connection failed: ' . $e->getMessage()];
            $userSwitchingAvailable = false;
            $error = $e->getMessage();
            
            return view('admin.deliveries.index', compact(
                'scheduleData', 
                'totalDeliveries', 
                'totalCollections',
                'statusCounts',
                'deliveryStatusCounts',
                'unfilteredStatusCounts',
                'unfilteredDeliveryStatusCounts',
                'allDeliveries',
                'allCollections',
                'directDbStatus',
                'userSwitchingAvailable',
                'error',
                'selectedWeek' // pass to view
            ));
        }
    }

    /**
     * Transform raw database data to match view expectations
     */
    private function transformScheduleData($rawData, $selectedWeek = null, $collectionDayPreferences = [])
    {
        // Use current week if no selectedWeek provided, ensure it's an integer
        if ($selectedWeek === null) {
            $selectedWeek = (int) date('W');
        }
        $selectedWeek = (int) $selectedWeek;
        
        // Calculate the selected week type (A or B)
        $selectedWeekType = ($selectedWeek % 2 === 0) ? 'A' : 'B';
        
        // Get delivery cut-off settings
        $settings = \App\Models\Setting::getAll();
        $deliveryCutoffTime = $settings['delivery_cutoff_time'] ?? '10:00';
        $collectionCutoffTime = $settings['collection_cutoff_time'] ?? '12:00';
        
        // If rawData is a flat API response (list of subscriptions), split into deliveries/collections
        if (isset($rawData[0]) && is_array($rawData[0])) {
            $subscriptions = $rawData;
            $rawData = ['deliveries' => [], 'collections' => []];
            foreach ($subscriptions as $sub) {
                // Use robust shipping total determination
                $type = $this->determineCustomerType($sub['shipping_total'] ?? null, $sub);
                
                // Extract frequency - use WooCommerce subscription standard fields first
                
                // Method 1: Check ALL line items meta_data FIRST (most reliable for custom frequency)
                $frequency = 'Weekly'; // Default
                if (isset($sub['line_items']) && is_array($sub['line_items'])) {
                    foreach ($sub['line_items'] as $lineItem) {
                        if (isset($lineItem['meta_data']) && is_array($lineItem['meta_data'])) {
                            foreach ($lineItem['meta_data'] as $meta) {
                                if (isset($meta['key']) && $meta['key'] === 'frequency' && !empty($meta['value'])) {
                                    $frequency = $meta['value'];
                                    break 2; // Break out of both loops
                                }
                            }
                        }
                    }
                }
                
                // Method 2: Check top-level meta_data as fallback
                if ($frequency === 'Weekly' && isset($sub['meta_data'])) {
                    foreach ($sub['meta_data'] as $meta) {
                        if ($meta['key'] === 'frequency' || $meta['key'] === '_subscription_frequency') {
                            $frequency = $meta['value'];
                            break;
                        }
                    }
                }
                
                // Method 3: Check WooCommerce subscription billing_period and billing_interval (standard approach)
                if ($frequency === 'Weekly' && isset($sub['billing_period']) && strtolower($sub['billing_period']) === 'week') {
                    $interval = intval($sub['billing_interval'] ?? 1);
                    if ($interval === 2) {
                        $frequency = 'Fortnightly';
                    } elseif ($interval === 1) {
                        $frequency = 'Weekly';
                    }
                }
                
                // Method 4: Check product name for frequency indicators (for when frequency is in product name)
                if ($frequency === 'Weekly' && isset($sub['line_items'][0]['name'])) {
                    $productName = strtolower($sub['line_items'][0]['name']);
                    if (strpos($productName, 'fortnightly') !== false) {
                        $frequency = 'Fortnightly';
                    } elseif (strpos($productName, 'weekly') !== false) {
                        $frequency = 'Weekly';
                    }
                }
                
                // Normalize frequency values - handle variations like "Fortnightly box", "Weekly box", etc.
                $frequency = trim(strtolower($frequency));
                if (strpos($frequency, 'fortnightly') !== false) {
                    $frequency = 'Fortnightly';
                } elseif (strpos($frequency, 'weekly') !== false) {
                    $frequency = 'Weekly';
                } else {
                    // Default to Weekly if we can't determine
                    $frequency = 'Weekly';
                }
                
                // Extract customer week type from meta_data if available
                $customerWeekType = 'Weekly'; // Default
                
                // First check for session-based temporary override (from failed API updates)
                // Check both subscription ID and customer ID for backwards compatibility
                if (session()->has("customer_week_type_{$sub['id']}")) {
                    $customerWeekType = session("customer_week_type_{$sub['id']}");
                } 
                elseif (session()->has("customer_week_type_{$sub['customer_id']}")) {
                    $customerWeekType = session("customer_week_type_{$sub['customer_id']}");
                } 
                // Then check meta_data from API
                elseif (isset($sub['meta_data'])) {
                    foreach ($sub['meta_data'] as $meta) {
                        if ($meta['key'] === 'customer_week_type') {
                            $customerWeekType = $meta['value'];
                            break;
                        }
                    }
                }

                // **WEEK FILTERING LOGIC** 
                $shouldIncludeInSelectedWeek = true; // Default for weekly subscriptions
                
                if (strtolower($frequency) === 'fortnightly') {
                    // For fortnightly customers, assign week type if not set
                    if ($customerWeekType === 'Weekly' || !in_array($customerWeekType, ['A', 'B'])) {
                        // Auto-assign fortnightly customers to a week type based on their subscription ID
                        // This ensures consistent assignment: odd IDs = Week A, even IDs = Week B
                        $customerWeekType = ((int)$sub['id'] % 2 === 1) ? 'B' : 'A';
                    }
                    
                    // Now check if this fortnightly customer should appear in the selected week
                    $shouldIncludeInSelectedWeek = ($customerWeekType === $selectedWeekType);
                }
                
                // Skip this subscription if it shouldn't appear in the selected week
                if (!$shouldIncludeInSelectedWeek) {
                    continue;
                }
                
                // Calculate week logic for display
                $currentWeek = (int) date('W');
                $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
                $shouldDeliverThisWeek = true;
                $weekBadge = 'primary';

                if (strtolower($frequency) === 'fortnightly') {
                    // For fortnightly customers, check if their assigned week matches current week
                    $shouldDeliverThisWeek = ($customerWeekType === $currentWeekType);
                    
                    // Set week badge color
                    if ($customerWeekType === 'A') {
                        $weekBadge = 'success'; // Green for Week A
                    } elseif ($customerWeekType === 'B') {
                        $weekBadge = 'info'; // Blue for Week B
                    }
                } else {
                    // Weekly customers get primary badge
                    $weekBadge = 'primary';
                }
                
                // Set frequency badge
                $frequencyBadge = strtolower($frequency) === 'fortnightly' ? 'warning' : 'success';

                // Get preferred collection day from WP user meta for collection subscriptions
                $preferred_collection_day = 'Friday'; // Default to Friday
                if (!empty($sub['customer_id']) && isset($collectionDayPreferences[$sub['customer_id']])) {
                    $preferred_collection_day = $collectionDayPreferences[$sub['customer_id']];
                }
                
                // Store both subscription ID and customer ID - use subscription ID for API operations
                $extractedEmail = $sub['billing']['email'] ?? '';
                $extractedFirstName = $sub['billing']['first_name'] ?? '';
                $extractedLastName = $sub['billing']['last_name'] ?? '';
                $extractedName = trim($extractedFirstName . ' ' . $extractedLastName);
                
                $rawData[$type][] = [
                    'id'                    => $sub['id'], // This is the subscription ID
                    'subscription_id'        => $sub['id'], // Keep a clear reference
                    'customer_id'           => $sub['customer_id'], // This is the WP user ID
                    'status'                => $sub['status'],
                    'date_created'          => $sub['date_created'],
                    'customer_email'        => $extractedEmail,
                    'name'                  => $extractedName, // Keep for backward compatibility
                    'customer_name'         => $extractedName, // Add this field that the templates expect
                    'address'               => array_filter([
                        $sub['shipping']['address_1'] ?? '',
                        $sub['shipping']['address_2'] ?? '',
                        $sub['shipping']['city'] ?? '',
                        $sub['shipping']['state'] ?? '',
                        $sub['shipping']['postcode'] ?? ''
                    ]),
                    // Add the address data in the format expected by the Blade templates
                    'shipping_address'      => $sub['shipping'] ?? [],
                    'billing_address'       => $sub['billing'] ?? [],
                    'products'              => array_map(fn($item) => ['quantity' => $item['quantity'], 'name' => $item['name']], $sub['line_items'] ?? []),
                    'phone'                 => $sub['billing']['phone'] ?? '',
                    'email'                 => $sub['billing']['email'] ?? '',
                    'frequency'             => $frequency,
                    'next_payment'          => $sub['next_payment_date'] ?? $sub['next_payment_date_gmt'] ?? '',
                    'total'                 => $sub['total'] ?? '0.00',
                    'customer_week_type'    => $customerWeekType,
                    'current_week_type'     => $currentWeekType,
                    'should_deliver_this_week' => $shouldDeliverThisWeek,
                    'week_badge'            => $weekBadge,
                    'frequency_badge'       => $frequencyBadge,
                    'preferred_collection_day' => $preferred_collection_day,
                ];
            }
        }

        $groupedData = [];
        $seenCustomers = [];
        
        // Process DELIVERIES - items from the service where type = 'delivery'
        if (isset($rawData['deliveries'])) {
            foreach ($rawData['deliveries'] as $delivery) {
                // Calculate delivery date for the selected week
                // All deliveries go out on THURSDAY
                $selectedWeekStart = $this->getWeekStartDate($selectedWeek);
                $date = \Carbon\Carbon::parse($selectedWeekStart)->addDays(3); // Monday + 3 days = Thursday
                $dateKey = $date->format('Y-m-d');
                $dateFormatted = $date->format('l, F j, Y') . " (Week {$selectedWeek})";
                $customerKey = $delivery['customer_email'] . '_' . $delivery['id'];
                
                if (isset($seenCustomers[$customerKey])) {
                    continue;
                }
                $seenCustomers[$customerKey] = true;
                
                if (!isset($groupedData[$dateKey])) {
                    $groupedData[$dateKey] = [
                        'date_formatted' => $dateFormatted,
                        'deliveries' => [],
                        'collections' => []
                    ];
                }
                
                // Check delivery cut-off time (Thursday)
                if ($this->shouldIncludeCustomerBasedOnCutoff($delivery['date_created'], $deliveryCutoffTime, 'Thursday', $selectedWeek)) {
                    $groupedData[$dateKey]['deliveries'][] = $delivery;
                }
            }
        }
        
        // Process COLLECTIONS - items from the service where type = 'collection'
        if (isset($rawData['collections'])) {
            foreach ($rawData['collections'] as $collection) {
                // Collections are scheduled based on their preferred collection day
                // Get preferred day from collection data (set later via addCollectionDaysToScheduleData)
                $preferredDay = $collection['preferred_collection_day'] ?? 'Friday'; // Default to Friday
                
                // Calculate the collection date for the selected week based on preferred day
                $selectedWeekStart = $this->getWeekStartDate($selectedWeek);
                $weekStart = \Carbon\Carbon::parse($selectedWeekStart);
                
                // Map day names to day of week (1=Monday, 5=Friday, 6=Saturday)
                $dayMap = [
                    'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4,
                    'Friday' => 5, 'Saturday' => 6, 'Sunday' => 6 // Sunday treated as Saturday
                ];
                
                $targetDayNumber = $dayMap[$preferredDay] ?? 5; // Default to Friday (5)
                $date = $weekStart->copy()->addDays($targetDayNumber - 1); // -1 because Monday is day 1
                
                $dateKey = $date->format('Y-m-d');
                $dateFormatted = $date->format('l, F j, Y') . " (Week {$selectedWeek})";
                $customerKey = $collection['customer_email'] . '_' . $collection['id'];
                
                if (isset($seenCustomers[$customerKey])) {
                    continue;
                }
                $seenCustomers[$customerKey] = true;
                
                // Ensure frequency is properly formatted
                $frequency = isset($collection['frequency']) ? ucfirst(strtolower($collection['frequency'])) : '';
                $collection['frequency'] = $frequency;

                // Add week logic for collections if not already present
                if (!isset($collection['customer_week_type'])) {
                    $currentWeek = (int) date('W');
                    $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';

                    if (strtolower($frequency) === 'fortnightly') {
                        // For fortnightly collections, assign to current week type by default
                        $collection['customer_week_type'] = $currentWeekType;
                        $collection['current_week_type'] = $currentWeekType;
                        $collection['should_deliver_this_week'] = true; // Default to true for collections
                        $collection['week_badge'] = $currentWeekType === 'A' ? 'success' : 'info';
                        $collection['frequency_badge'] = 'warning';
                    } else {
                        $collection['customer_week_type'] = 'Weekly';
                        $collection['current_week_type'] = $currentWeekType;
                        $collection['should_deliver_this_week'] = true;
                        $collection['week_badge'] = 'primary';
                        $collection['frequency_badge'] = 'success';
                    }
                }
                
                // Make sure week logic is properly set for fortnightly customers
                if (strtolower($frequency) === 'fortnightly' && $collection['customer_week_type'] === 'Weekly') {
                    $currentWeek = (int) date('W');
                    $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
                    $collection['customer_week_type'] = $currentWeekType;
                    $collection['current_week_type'] = $currentWeekType;
                    $collection['should_deliver_this_week'] = true;
                    $collection['week_badge'] = $currentWeekType === 'A' ? 'success' : 'info';
                    $collection['frequency_badge'] = 'warning';
                }
                
                if (!isset($groupedData[$dateKey])) {
                    $groupedData[$dateKey] = [
                        'date_formatted' => $dateFormatted,
                        'deliveries' => [],
                        'collections' => []
                    ];
                }
                
                // Check collection cut-off time (Friday)
                if ($this->shouldIncludeCustomerBasedOnCutoff($collection['date_created'], $collectionCutoffTime, 'Friday', $selectedWeek)) {
                    $groupedData[$dateKey]['collections'][] = $collection;
                }
            }
        }
        
        krsort($groupedData);
        
        // Group collections by status for the subtabs
        $collectionsByStatus = [
            'active' => [],
            'processing' => [],
            'on-hold' => [],
            'cancelled' => [],
            'pending' => [],
            'completed' => [],
            'refunded' => [],
            'other' => []
        ];
        
        foreach ($groupedData as $date => $dateData) {
            foreach ($dateData['collections'] ?? [] as $collection) {
                $status = strtolower($collection['status']);
                
                // Map WooCommerce subscription statuses to our categories
                if ($status === 'wc-active' || $status === 'active') {
                    $status = 'active';
                } elseif ($status === 'wc-on-hold' || $status === 'on-hold') {
                    $status = 'on-hold';
                } elseif ($status === 'wc-cancelled' || $status === 'cancelled') {
                    $status = 'cancelled';
                } elseif ($status === 'wc-pending' || $status === 'pending') {
                    $status = 'pending';
                } elseif ($status === 'wc-processing' || $status === 'processing') {
                    $status = 'processing';
                } elseif (!isset($collectionsByStatus[$status])) {
                    $status = 'other';
                }
                
                if (!isset($collectionsByStatus[$status][$date])) {
                    $collectionsByStatus[$status][$date] = [
                        'date_formatted' => $dateData['date_formatted'],
                        'collections' => []
                    ];
                }
                
                $collectionsByStatus[$status][$date]['collections'][] = $collection;
            }
        }
        
        // Group deliveries by status for the subtabs
        $deliveriesByStatus = [
            'active' => [],
            'processing' => [],
            'pending' => [],
            'completed' => [],
            'on-hold' => [],
            'cancelled' => [],
            'refunded' => [],
            'other' => []
        ];
        
        foreach ($groupedData as $date => $dateData) {
            foreach ($dateData['deliveries'] ?? [] as $delivery) {
                $status = strtolower($delivery['status']);
                $status = str_replace('wc-', '', $status);
                
                // Map statuses consistently - handle WooCommerce subscription statuses
                if (in_array($status, ['active'])) {
                    // Only 'active' subscriptions are considered active for deliveries
                    // Exclude 'processing', 'on-hold', 'pending' from active deliveries
                    $mappedStatus = 'active';
                } elseif ($status === 'processing') {
                    $mappedStatus = 'processing';
                } elseif ($status === 'on-hold') {
                    $mappedStatus = 'on-hold';
                } elseif ($status === 'cancelled') {
                    $mappedStatus = 'cancelled';
                } elseif ($status === 'pending') {
                    $mappedStatus = 'pending';
                } elseif ($status === 'completed') {
                    $mappedStatus = 'completed';
                } elseif ($status === 'refunded') {
                    $mappedStatus = 'refunded';
                } else {
                    $mappedStatus = 'other';
                }
                
                // Also keep separate processing count for the processing tab
                if ($status === 'processing') {
                    if (!isset($deliveriesByStatus['processing'][$date])) {
                        $deliveriesByStatus['processing'][$date] = [
                            'date_formatted' => $dateData['date_formatted'],
                            'deliveries' => []
                        ];
                    }
                    $deliveriesByStatus['processing'][$date]['deliveries'][] = $delivery;
                }
                
                if (!isset($deliveriesByStatus[$mappedStatus][$date])) {
                    $deliveriesByStatus[$mappedStatus][$date] = [
                        'date_formatted' => $dateData['date_formatted'],
                        'deliveries' => []
                    ];
                }
                
                $deliveriesByStatus[$mappedStatus][$date]['deliveries'][] = $delivery;
            }
        }
        
        // Sort each status group by date
        foreach ($collectionsByStatus as $status => $statusData) {
            krsort($collectionsByStatus[$status]);
        }
        
        foreach ($deliveriesByStatus as $status => $statusData) {
            krsort($deliveriesByStatus[$status]);
        }
        
        $totalProcessed = count($seenCustomers);
        $deliveryCount = isset($rawData['deliveries']) ? count($rawData['deliveries']) : 0;
        $collectionCount = isset($rawData['collections']) ? count($rawData['collections']) : 0;
        $duplicatesSkipped = ($deliveryCount + $collectionCount) - $totalProcessed;
        
        $data = [
            'success' => true,
            'data' => $groupedData,
            'collectionsByStatus' => $collectionsByStatus,
            'deliveriesByStatus' => $deliveriesByStatus,
            'data_source' => 'direct_database',
            'message' => "Data loaded with duplicate prevention. Processed: {$totalProcessed} unique items, Skipped: {$duplicatesSkipped} duplicates"
        ];
        
        // Add collection day preferences to the data
        // This is done as a separate step to improve performance by batching the API calls
        $data = $this->addCollectionDaysToScheduleData($data);
        
        return $data;
    }

    /**
     * API test endpoint for debugging
     */
    public function apiTest(WpApiService $wpApi)
    {
        try {
            $tests = [
                'direct_database_connection' => $wpApi->testConnection(),
                'recent_users' => $wpApi->getRecentUsers(3),
                'delivery_data' => $wpApi->getDeliveryScheduleData(5)
            ];
            
            return response()->json([
                'success' => true,
                'tests' => $tests,
                'message' => 'All tests using direct database connection only',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Update customer week type for fortnightly deliveries
     */
    public function updateCustomerWeek(WpApiService $wpApi)
    {
        try {
            $customerId = request('customer_id');
            $weekType = request('week_type');
            
            Log::info("Updating customer week type", [
                'customer_id' => $customerId,
                'week_type' => $weekType
            ]);
            
            if (!$customerId || !in_array($weekType, ['A', 'B'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer ID or week type'
                ], 400);
            }
            
            // Let's add more detailed debugging to track down the issue
            $wcApiUrl = config('services.wc_api.url');
            $wcConsumerKey = config('services.wc_api.consumer_key');
            $wcConsumerSecret = config('services.wc_api.consumer_secret');
            
            // Check if we have valid WooCommerce API credentials
            if (empty($wcConsumerKey) || empty($wcConsumerSecret)) {
                Log::error("Missing WooCommerce API credentials");
                return response()->json([
                    'success' => false,
                    'message' => 'WooCommerce API credentials not configured',
                    'debug' => [
                        'customer_id' => $customerId,
                        'week_type' => $weekType,
                        'has_consumer_key' => !empty($wcConsumerKey),
                        'has_consumer_secret' => !empty($wcConsumerSecret)
                    ]
                ], 500);
            }
            
            // First, verify that we're using the correct endpoint structure
            $subscriptionsEndpoint = "{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$customerId}";
            Log::info("Checking if subscription exists", [
                'subscription_id' => $customerId,
                'endpoint' => $subscriptionsEndpoint
            ]);
            
            // Check if the subscription exists by doing a GET request
            $checkResponse = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
                ->get($subscriptionsEndpoint);
                
            if (!$checkResponse->successful()) {
                Log::warning("Subscription not found", [
                    'subscription_id' => $customerId, 
                    'status_code' => $checkResponse->status(),
                    'response' => $checkResponse->body()
                ]);
                
                // Let's try a fallback to update using the temporary session storage
                session()->put("customer_week_type_{$customerId}", $weekType);
                
                return response()->json([
                    'success' => true,
                    'warning' => true,
                    'message' => "Subscription with ID {$customerId} not found in WooCommerce. Using temporary session storage instead.",
                    'debug' => [
                        'status_code' => $checkResponse->status(),
                        'body' => $checkResponse->body()
                    ],
                    'customer_id' => $customerId,
                    'week_type' => $weekType,
                    'method' => 'session_based'
                ]);
            }
            
            // The subscription exists, now try to update its metadata
            // We'll try several approaches in sequence until one works
            try {
                Log::info("Updating subscription metadata", [
                    'customer_id' => $customerId,
                    'week_type' => $weekType
                ]);
                
                // Approach 1: Standard WooCommerce Subscriptions REST API
                Log::info("Trying approach 1: WooCommerce Subscriptions REST API");
                $response = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
                    ->put("{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$customerId}", [
                        'meta_data' => [
                            [
                                'key' => 'customer_week_type',
                                'value' => $weekType
                            ]
                        ]
                    ]);

                if ($response->successful()) {
                    Log::info("Successfully updated via WooCommerce API");
                    return response()->json([
                        'success' => true,
                        'message' => "Customer week type updated to Week {$weekType}",
                        'customer_id' => $customerId,
                        'week_type' => $weekType,
                        'method' => 'woocommerce_api'
                    ]);
                }
                
                Log::warning("WooCommerce API update failed", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                // Approach 2: Try MWF Custom REST endpoint if available
                Log::info("Trying approach 2: MWF Custom API");
                $apiKey = config('services.wp_api.key');
                $apiSecret = config('services.wp_api.secret');
                $apiUrl = config('services.wp_api.url');
                
                $mwfResponse = Http::withBasicAuth($apiKey, $apiSecret)
                    ->post("{$apiUrl}/wp-json/mwf/v1/subscriptions/{$customerId}/meta", [
                        'key' => 'customer_week_type',
                        'value' => $weekType,
                        'integration_key' => config('services.wc_api.integration_key')
                    ]);
                    
                if ($mwfResponse->successful()) {
                    Log::info("Successfully updated via MWF plugin API");
                    return response()->json([
                        'success' => true,
                        'message' => "Customer week type updated to Week {$weekType}",
                        'customer_id' => $customerId,
                        'week_type' => $weekType,
                        'method' => 'mwf_plugin_api'
                    ]);
                }
                
                Log::warning("MWF plugin API update failed", [
                    'status' => $mwfResponse->status(),
                    'response' => $mwfResponse->body()
                ]);
                
                // Approach 3: Create a temporary flag in the session and apply it on next page load
                Log::info("Trying approach 3: Session-based temporary update");
                session()->put("customer_week_type_{$customerId}", $weekType);
                // Also save by customer ID as fallback
                session()->put("customer_week_type_" . $checkResponse->json('customer_id'), $weekType);
                
                return response()->json([
                    'success' => true,
                    'message' => "Customer week type updated to Week {$weekType} (temporary session-based update)",
                    'customer_id' => $customerId,
                    'week_type' => $weekType,
                    'method' => 'session_based',
                    'warning' => 'This change is temporary until metadata API is working'
                ]);
                
            } catch (\Exception $apiException) {
                Log::error("API exception", [
                    'message' => $apiException->getMessage(),
                    'trace' => $apiException->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'API Exception: ' . $apiException->getMessage()
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error("General exception in updateCustomerWeek", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer week: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Diagnostic method to check subscription statuses and counts
     */
    public function diagnosticSubscriptions(WpApiService $wpApi)
    {
        try {
            // Get ALL subscriptions without limit to see the full picture
            $allSubscriptions = \App\Models\WooCommerceOrder::subscriptions()
                ->with(['meta'])
                ->get();
            
            // Group by status
            $statusCounts = [];
            $statusExamples = [];
            
            foreach ($allSubscriptions as $subscription) {
                $status = $subscription->post_status;
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                
                if (!isset($statusExamples[$status])) {
                    $statusExamples[$status] = [
                        'id' => $subscription->ID,
                        'date' => $subscription->post_date,
                        'customer_email' => $subscription->getMeta('_billing_email'),
                        'total' => $subscription->getMeta('_order_total')
                    ];
                }
            }
            
            // Also check what the current service returns
            $serviceData = $wpApi->getDeliveryScheduleData(200); // Increase limit for testing
            
            return response()->json([
                'success' => true,
                'total_subscriptions_in_db' => $allSubscriptions->count(),
                'status_breakdown' => $statusCounts,
                'status_examples' => $statusExamples,
                'service_returned_deliveries' => count($serviceData['deliveries'] ?? []),
                'service_returned_collections' => count($serviceData['collections'] ?? []),
                'service_total' => (count($serviceData['deliveries'] ?? [])) + (count($serviceData['collections'] ?? [])),
                'likely_active_statuses' => ['wc-active', 'active', 'wc-pending', 'pending'],
                'message' => 'Diagnostic complete - check status_breakdown to see what statuses exist'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test the fixed filtering to see active subscriptions only
     */
    public function testActiveFilter(WpApiService $wpApi)
    {
        try {
            // Test the updated service
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            return response()->json([
                'success' => true,
                'active_deliveries_count' => count($rawData['deliveries'] ?? []),
                'active_collections_count' => count($rawData['collections'] ?? []),
                'total_active_subscriptions' => (count($rawData['deliveries'] ?? [])) + (count($rawData['collections'] ?? [])),
                'should_be_30_or_31' => 'Expected around 30-31 active subscriptions',
                'deliveries_sample' => array_slice($rawData['deliveries'] ?? [], 0, 3),
                'collections_sample' => array_slice($rawData['collections'] ?? [], 0, 3),
                'message' => 'Now filtering for wc-active and wc-pending only'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug week assignment to see what's happening
     */
    public function debugWeekAssignment(WpApiService $wpApi)
    {
        try {
            // Get a few subscriptions to debug
            $subscriptions = \App\Models\WooCommerceOrder::subscriptions()
                ->whereIn('post_status', ['wc-active', 'wc-pending'])
                ->with(['meta'])
                ->limit(10)
                ->get();
            
            $debugData = [];
            
            foreach ($subscriptions as $subscription) {
                $parentOrderId = $subscription->post_parent;
                $debug = [
                    'subscription_id' => $subscription->ID,
                    'subscription_date' => $subscription->post_date,
                    'subscription_week' => (int) \Carbon\Carbon::parse($subscription->post_date)->format('W'),
                    'parent_order_id' => $parentOrderId,
                ];
                
                if ($parentOrderId) {
                    $parentOrder = \App\Models\WooCommerceOrder::find($parentOrderId);
                    if ($parentOrder) {
                        $orderDate = \Carbon\Carbon::parse($parentOrder->post_date);
                        $debug['parent_order_date'] = $parentOrder->post_date;
                        $debug['parent_order_week'] = (int) $orderDate->format('W');
                        $debug['assigned_week'] = ($orderDate->format('W') % 2 === 1) ? 'A' : 'B';
                        $debug['used_parent'] = true;
                    } else {
                        $debug['parent_not_found'] = true;
                        $debug['used_parent'] = false;
                        $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                        $debug['assigned_week'] = ($subscriptionDate->format('W') % 2 === 1) ? 'A' : 'B';
                    }
                } else {
                    $debug['no_parent'] = true;
                    $debug['used_parent'] = false;
                    $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                    $debug['assigned_week'] = ($subscriptionDate->format('W') % 2 === 1) ? 'A' : 'B';
                }
                
                $debugData[] = $debug;
            }
            
            return response()->json([
                'success' => true,
                'current_iso_week' => (int) date('W'),
                'current_week_type' => ((int) date('W') % 2 === 1) ? 'A' : 'B',
                'debug_data' => $debugData,
                'message' => 'Check the assigned_week column to see the pattern'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug what the actual delivery schedule is returning
     */
    public function debugDeliverySchedule(WpApiService $wpApi)
    {
        try {
            // Get the same data that the main page uses
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            $weekACount = 0;
            $weekBCount = 0;
            $weeklyCount = 0;
            $allCustomers = [];
            
            // Analyze deliveries
            foreach ($rawData['deliveries'] ?? [] as $delivery) {
                $weekType = $delivery['customer_week_type'] ?? 'Unknown';
                $allCustomers[] = [
                    'type' => 'delivery',
                    'id' => $delivery['id'],
                    'customer' => $delivery['customer_name'] ?? 'Unknown',
                    'frequency' => $delivery['frequency'] ?? 'Unknown',
                    'week_type' => $weekType,
                    'should_deliver' => $delivery['should_deliver_this_week'] ?? 'Unknown'
                ];
                
                if ($weekType === 'A') $weekACount++;
                elseif ($weekType === 'B') $weekBCount++;
                elseif ($weekType === 'Weekly') $weeklyCount++;
            }
            
            // Analyze collections
            foreach ($rawData['collections'] ?? [] as $collection) {
                $weekType = $collection['customer_week_type'] ?? 'Unknown';
                $allCustomers[] = [
                    'type' => 'collection',
                    'id' => $collection['id'],
                    'customer' => $collection['customer_name'] ?? 'Unknown',
                    'frequency' => $collection['frequency'] ?? 'Unknown',
                    'week_type' => $weekType,
                    'should_deliver' => $collection['should_deliver_this_week'] ?? 'Unknown'
                ];
                
                if ($weekType === 'A') $weekACount++;
                elseif ($weekType === 'B') $weekBCount++;
                elseif ($weekType === 'Weekly') $weeklyCount++;
            }
            
            return response()->json([
                'success' => true,
                'current_week_type' => 'B',
                'total_customers' => count($allCustomers),
                'week_breakdown' => [
                    'Week_A' => $weekACount,
                    'Week_B' => $weekBCount, 
                    'Weekly' => $weeklyCount
                ],
                'deliveries_count' => count($rawData['deliveries'] ?? []),
                'collections_count' => count($rawData['collections'] ?? []),
                'all_customers' => $allCustomers,
                'raw_totals' => [
                    'deliveries' => $rawData['total_deliveries'] ?? 0,
                    'collections' => $rawData['total_collections'] ?? 0
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug specific customers to see week assignment in detail
     */
    public function debugSpecificCustomers(WpApiService $wpApi)
    {
        try {
            // Get ALL fortnightly subscriptions specifically
            $subscriptions = \App\Models\WooCommerceOrder::subscriptions()
                ->whereIn('post_status', ['wc-active', 'wc-pending'])
                ->with(['meta'])
                ->limit(100) // Increased to catch all
                ->get();
            
            $debugDetails = [];
            
            foreach ($subscriptions as $subscription) {
                // Get frequency from parent order (same logic as service)
                $parentOrderId = $subscription->post_parent;
                $frequency = null;
                $paymentOption = null;
                if ($parentOrderId) {
                    $parentOrder = \App\Models\WooCommerceOrder::find($parentOrderId);
                    if ($parentOrder) {
                        foreach ($parentOrder->items as $item) {
                            $freqMeta = strtolower(trim($item->getMeta('frequency', '')));
                            $payOptMeta = strtolower(trim($item->getMeta('payment-option', '')));
                            if ($freqMeta) $frequency = $freqMeta;
                            if ($payOptMeta) $paymentOption = $payOptMeta;
                        }
                    }
                }
                
                $freqValue = $frequency ?: $paymentOption ?: '';
                $isFortnightly = strpos($freqValue, 'fortnightly') !== false;
                
                if ($isFortnightly) {
                    $storedWeekType = $subscription->getMeta('customer_week_type');
                    
                    // Week assignment logic (same as service)
                    if ($storedWeekType && in_array($storedWeekType, ['A', 'B'])) {
                        $assignedWeek = $storedWeekType;
                        $source = 'stored_preference';
                    } else {
                        if ($parentOrderId) {
                            $parentOrder = \App\Models\WooCommerceOrder::find($parentOrderId);
                            if ($parentOrder) {
                                $orderDate = \Carbon\Carbon::parse($parentOrder->post_date);
                                $orderWeek = (int) $orderDate->format('W');
                                $assignedWeek = ($orderWeek % 2 === 1) ? 'A' : 'B';
                                $source = 'parent_order_date';
                            } else {
                                $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                                $debug['assigned_week'] = ($subscriptionDate->format('W') % 2 === 1) ? 'A' : 'B';
                                $source = 'subscription_date_fallback';
                            }
                        } else {
                            $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                            $subscriptionWeek = (int) $subscriptionDate->format('W');
                            $assignedWeek = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                            $source = 'subscription_date_no_parent';
                        }
                    }
                    
                    $debugDetails[] = [
                        'subscription_id' => $subscription->ID,
                        'customer_email' => $subscription->getMeta('_billing_email'),
                        'frequency_found' => $freqValue,
                        'is_fortnightly' => true,
                        'stored_week_type' => $storedWeekType,
                        'assigned_week' => $assignedWeek,
                        'assignment_source' => $source,
                        'parent_order_id' => $parentOrderId,
                        'parent_order_exists' => $parentOrderId ? (\App\Models\WooCommerceOrder::find($parentOrderId) ? 'yes' : 'no') : 'no_parent'
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'fortnightly_customers_found' => count($debugDetails),
                'debug_details' => $debugDetails,
                'current_week' => (int) date('W'),
                'current_week_type' => ((int) date('W') % 2 === 1) ? 'A' : 'B',
                'message' => 'Check assignment_source and assigned_week for each customer'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug what should appear on the delivery schedule page
     */
    public function debugPageDisplay(WpApiService $wpApi)
    {
        try {
            // Get the exact same data that the main delivery page uses
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            $weekACount = 0;
            $weekBCount = 0;
            $weeklyCount = 0;
            $totalFortnightly = 0;
            $allCustomers = [];
            
            // Check all customers (deliveries + collections)
            $allItems = array_merge($rawData['deliveries'] ?? [], $rawData['collections'] ?? []);
            
            foreach ($allItems as $item) {
                $frequency = $item['frequency'] ?? 'Unknown';
                $weekType = $item['customer_week_type'] ?? 'Unknown';
                
                $customer = [
                    'id' => $item['id'],
                    'email' => $item['customer_email'] ?? 'Unknown',
                    'frequency' => $frequency,
                    'week_type' => $weekType,
                    'should_deliver' => $item['should_deliver_this_week'] ?? 'Unknown',
                    'type' => $item['type'] ?? 'Unknown'
                ];
                
                if ($frequency === 'Fortnightly') {
                    $totalFortnightly++;
                    if ($weekType === 'A') $weekACount++;
                    elseif ($weekType === 'B') $weekBCount++;
                } elseif ($frequency === 'Weekly') {
                    $weeklyCount++;
                }
                
                $allCustomers[] = $customer;
            }
            
            return response()->json([
                'success' => true,
                'total_customers' => count($allCustomers),
                'frequency_breakdown' => [
                    'Weekly' => $weeklyCount,
                    'Fortnightly' => $totalFortnightly,
                    'Fortnightly_Week_A' => $weekACount,
                    'Fortnightly_Week_B' => $weekBCount
                ],
                'raw_data_counts' => [
                    'deliveries' => count($rawData['deliveries'] ?? []),
                    'collections' => count($rawData['collections'] ?? [])
                ],
                'customers_sample' => array_slice($allCustomers, 0, 10),
                'fortnightly_customers_only' => array_filter($allCustomers, function($c) { 
                    return $c['frequency'] === 'Fortnightly'; 
                }),
                'current_week_is' => 'B',
                'message' => 'This shows what should appear on the delivery schedule page'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simple accurate count - no complex logic, just count what we see
     */
    public function simpleCount(WpApiService $wpApi)
    {
        try {
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            $fortnightlyWeekA = 0;
            $fortnightlyWeekB = 0;
            $weekly = 0;
            $fortnightlyList = [];
            
            // Count collections
            foreach ($rawData['collections'] ?? [] as $collection) {
                $freq = $collection['frequency'] ?? '';
                $week = $collection['customer_week_type'] ?? '';
                
                if ($freq === 'Fortnightly') {
                    if ($week === 'A') $fortnightlyWeekA++;
                    elseif ($week === 'B') $fortnightlyWeekB++;
                    
                    $fortnightlyList[] = [
                        'id' => $collection['id'],
                        'email' => $collection['customer_email'] ?? '',
                        'week' => $week,
                        'type' => 'collection'
                    ];
                } elseif ($freq === 'Weekly') {
                    $weekly++;
                }
            }
            
            // Count deliveries
            foreach ($rawData['deliveries'] ?? [] as $delivery) {
                $freq = $delivery['frequency'] ?? '';
                $week = $delivery['customer_week_type'] ?? '';
                
                if ($freq === 'Fortnightly') {
                    if ($week === 'A') $fortnightlyWeekA++;
                    elseif ($week === 'B') $fortnightlyWeekB++;
                    
                    $fortnightlyList[] = [
                        'id' => $delivery['id'],
                        'email' => $delivery['customer_email'] ?? '',
                        'week' => $week,
                        'type' => 'delivery'
                    ];
                } elseif ($freq === 'Weekly') {
                    $weekly++;
                }
            }
            
            return response()->json([
                'success' => true,
                'fortnightly_week_a_count' => $fortnightlyWeekA,
                'fortnightly_week_b_count' => $fortnightlyWeekB,
                'weekly_count' => $weekly,
                'total_fortnightly' => $fortnightlyWeekA + $fortnightlyWeekB,
                'fortnightly_details' => $fortnightlyList,
                'message' => 'This is a simple count of what we see'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug customer statuses to see what "Other" means
     */
    public function debugCustomerStatuses(WpApiService $wpApi)
    {
        try {
            // Get the raw data
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            $statusBreakdown = [];
            $statusExamples = [];
            $allStatuses = [];
            
            // Check all customers (deliveries + collections)
            $allItems = array_merge($rawData['deliveries'] ?? [], $rawData['collections'] ?? []);
            
            foreach ($allItems as $item) {
                $status = strtolower($item['status'] ?? 'unknown');
                $cleanStatus = str_replace('wc-', '', $status);
                
                // Count each status
                $statusBreakdown[$cleanStatus] = ($statusBreakdown[$cleanStatus] ?? 0) + 1;
                $allStatuses[] = $cleanStatus;
                
                // Keep examples of each status
                if (!isset($statusExamples[$cleanStatus])) {
                    $statusExamples[$cleanStatus] = [
                        'customer_email' => $item['customer_email'] ?? 'unknown',
                        'id' => $item['id'],
                        'original_status' => $status,
                        'type' => $item['type'] ?? 'unknown'
                    ];
                }
            }
            
            // Check which statuses are considered "known" vs "other"
            $knownStatuses = ['active', 'processing', 'on-hold', 'cancelled', 'pending', 'completed', 'refunded'];
            $otherStatuses = [];
            
            foreach ($statusBreakdown as $status => $count) {
                if (!in_array($status, $knownStatuses)) {
                    $otherStatuses[$status] = $count;
                }
            }
            
            return response()->json([
                'success' => true,
                'total_customers' => count($allItems),
                'status_breakdown' => $statusBreakdown,
                'status_examples' => $statusExamples,
                'known_statuses' => $knownStatuses,
                'other_statuses' => $otherStatuses,
                'other_count' => array_sum($otherStatuses),
                'message' => 'Check other_statuses to see what the 8 "Other" customers actually are'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compare service logic with manual calculation
     */
    public function compareWeekLogic(WpApiService $wpApi)
    {
        try {
            // Test the exact same customers as in Tinker
            $testIds = [224665, 224677, 225027, 225424, 227408, 227529, 227571];
            $results = [];
            
            foreach ($testIds as $id) {
                $subscription = \App\Models\WooCommerceOrder::find($id);
                if (!$subscription) continue;
                
                // Manual calculation (like Tinker)
                $parentId = $subscription->post_parent;
                $manualWeek = null;
                if ($parentId) {
                    $parent = \App\Models\WooCommerceOrder::find($parentId);
                    if ($parent) {
                        $week = \Carbon\Carbon::parse($parent->post_date)->format('W');
                        $manualWeek = ($week % 2 === 1) ? 'A' : 'B';
                    }
                }
                
                // Service logic calculation (our actual code)
                $storedWeekType = $subscription->getMeta('customer_week_type');
                $serviceWeek = null;
                
                if ($storedWeekType && in_array($storedWeekType, ['A', 'B'])) {
                    $serviceWeek = $storedWeekType;
                    $source = 'stored';
                } else {
                    if ($parentId) {
                        $parentOrder = \App\Models\WooCommerceOrder::find($parentId);
                        if ($parentOrder) {
                            $orderDate = \Carbon\Carbon::parse($parentOrder->post_date);
                            $orderWeek = (int) $orderDate->format('W');
                            $serviceWeek = ($orderWeek % 2 === 1) ? 'A' : 'B';
                            $source = 'parent_date';
                        } else {
                            $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                            $subscriptionWeek = (int) $subscriptionDate->format('W');
                            $serviceWeek = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                            $source = 'subscription_fallback';
                        }
                    } else {
                        $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                        $subscriptionWeek = (int) $subscriptionDate->format('W');
                        $serviceWeek = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                        $source = 'subscription_no_parent';
                    }
                }
                
                $results[] = [
                    'subscription_id' => $id,
                    'manual_calculation' => $manualWeek,
                    'service_calculation' => $serviceWeek,
                    'assignment_source' => $source ?? 'unknown',
                    'match' => $manualWeek === $serviceWeek,
                    'stored_preference' => $storedWeekType
                ];
            }
            
            return response()->json([
                'success' => true,
                'comparison_results' => $results,
                'all_match' => collect($results)->every(fn($r) => $r['match']),
                'message' => 'Compare manual vs service calculations'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Fetch collection day preferences for all customers in the raw data
     * @param array $rawData The raw subscription data from WooCommerce
     * @return array Array of customer_id => preferred_day mappings
     */
    private function fetchCollectionDayPreferences($rawData)
    {
        $customerIds = [];
        
        // Extract customer IDs from raw data
        foreach ($rawData as $sub) {
            if (isset($sub['customer_id'])) {
                $customerIds[] = $sub['customer_id'];
            }
        }
        
        if (empty($customerIds)) {
            return [];
        }
        
        // Get unique IDs
        $customerIds = array_unique($customerIds);
        
        // Fetch from WordPress database
        try {
            $results = DB::connection('wordpress')
                ->table('usermeta')
                ->whereIn('user_id', $customerIds)
                ->where('meta_key', 'preferred_collection_day')
                ->select('user_id', 'meta_value')
                ->get();
            
            $preferences = [];
            foreach ($results as $result) {
                $preferences[$result->user_id] = $result->meta_value;
            }
            
            return $preferences;
        } catch (\Exception $e) {
            Log::error('Failed to fetch collection day preferences: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update transformed data with collection day preferences in batch
     * @param array $transformedData The already transformed schedule data
     * @return array The updated data with collection days
     */
    private function addCollectionDaysToScheduleData($transformedData)
    {
        // Extract all customer IDs from collections
        $customerIds = [];
        
        // Collections in main schedule
        if (isset($transformedData['data'])) {
            foreach ($transformedData['data'] as $dateKey => $dateData) {
                if (!isset($dateData['collections'])) continue;
                
                foreach ($dateData['collections'] as $collection) {
                    if (!empty($collection['customer_id'])) {
                        $customerIds[] = $collection['customer_id'];
                    }
                }
            }
        }
        
        // Collections by status
        if (isset($transformedData['collectionsByStatus'])) {
            foreach ($transformedData['collectionsByStatus'] as $status => $dates) {
                foreach ($dates as $dateKey => $dateData) {
                    if (!isset($dateData['collections'])) continue;
                    
                    foreach ($dateData['collections'] as $collection) {
                        if (!empty($collection['customer_id'])) {
                            $customerIds[] = $collection['customer_id'];
                        }
                    }
                }
            }
        }
        
        // If no customers, return unchanged data
        if (empty($customerIds)) {
            return $transformedData;
        }
        
        // Get unique customer IDs
        $customerIds = array_unique($customerIds);
        
        // Get collection days directly from the database for better performance
        $collectionDays = [];
        
        // Use direct DB query to get all collection days at once - using proper table prefix
        $results = DB::connection('wordpress')
            ->table('usermeta') // the prefix is applied automatically by Laravel
            ->whereIn('user_id', $customerIds)
            ->where('meta_key', 'preferred_collection_day')
            ->select('user_id', 'meta_value')
            ->get();
            
        // Map results to collection days array
        foreach ($results as $result) {
            $collectionDays[$result->user_id] = $result->meta_value;
        }
        
        // Now update the collection data with the preferred collection days
        // Update collections in main schedule
        if (isset($transformedData['data'])) {
            foreach ($transformedData['data'] as $dateKey => &$dateData) {
                if (!isset($dateData['collections'])) continue;
                
                foreach ($dateData['collections'] as &$collection) {
                    if (!empty($collection['customer_id']) && isset($collectionDays[$collection['customer_id']])) {
                        $collection['preferred_collection_day'] = $collectionDays[$collection['customer_id']];
                    } else {
                        $collection['preferred_collection_day'] = 'Friday'; // Default
                    }
                }
            }
        }
        
        // Update collections by status
        if (isset($transformedData['collectionsByStatus'])) {
            foreach ($transformedData['collectionsByStatus'] as $status => &$dates) {
                foreach ($dates as $dateKey => &$dateData) {
                    if (!isset($dateData['collections'])) continue;
                    
                    foreach ($dateData['collections'] as &$collection) {
                        if (!empty($collection['customer_id']) && isset($collectionDays[$collection['customer_id']])) {
                            $collection['preferred_collection_day'] = $collectionDays[$collection['customer_id']];
                        } else {
                            $collection['preferred_collection_day'] = 'Friday'; // Default
                        }
                    }
                }
            }
        }
        
        return $transformedData;
    }

    /**
     * Test the collection days functionality
     */
    public function testCollectionDays()
    {
        try {
            // Get unique customer IDs with collection day preferences
            $customerIds = DB::connection('wordpress')
                ->table('usermeta') // Let Laravel apply the prefix
                ->where('meta_key', 'preferred_collection_day')
                ->select('user_id', 'meta_value')
                ->orderBy('meta_value')
                ->limit(20)
                ->get();
                
            // Map collection days for each customer
            $collectionDays = [];
            foreach ($customerIds as $row) {
                $collectionDays[$row->user_id] = $row->meta_value;
            }
            
            // Get the distribution of collection days
            $distributionQuery = DB::connection('wordpress')
                ->table('usermeta') // Let Laravel apply the prefix
                ->where('meta_key', 'preferred_collection_day')
                ->select('meta_value', DB::raw('COUNT(*) as count'))
                ->groupBy('meta_value')
                ->orderBy('count', 'desc');
                
            $distribution = $distributionQuery->get();
            
            return response()->json([
                'success' => true,
                'sample_collection_days' => $collectionDays,
                'total_preferences_set' => count($customerIds),
                'distribution' => $distribution,
                'message' => 'Collection day preferences are now loaded efficiently via direct database access'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Debug frequencies and week types for deliveries and collections
     */
    public function debugFrequencies()
    {
        try {
            // Get data from the WpApiService
            $wpApi = app(WpApiService::class);
            $rawData = $wpApi->getDeliveryScheduleData(500); // Get all subscriptions
            
            // Process data for both deliveries and collections
            $deliveryFrequencies = [];
            $deliveryWeekTypes = [];
            $collectionFrequencies = [];
            $collectionWeekTypes = [];
            
            // Process deliveries
            if (isset($rawData['deliveries'])) {
                foreach ($rawData['deliveries'] as $delivery) {
                    // Count frequencies
                    $frequency = strtolower($delivery['frequency'] ?? 'unknown');
                    if (!isset($deliveryFrequencies[$frequency])) {
                        $deliveryFrequencies[$frequency] = 0;
                    }
                    $deliveryFrequencies[$frequency]++;
                    
                    // Count week types
                    $weekType = $delivery['customer_week_type'] ?? 'unknown';
                    if (!isset($deliveryWeekTypes[$weekType])) {
                        $deliveryWeekTypes[$weekType] = 0;
                    }
                    $deliveryWeekTypes[$weekType]++;
                }
            }
            
            // Process collections
            if (isset($rawData['collections'])) {
                foreach ($rawData['collections'] as $collection) {
                    // Count frequencies
                    $frequency = strtolower($collection['frequency'] ?? 'unknown');
                    if (!isset($collectionFrequencies[$frequency])) {
                        $collectionFrequencies[$frequency] = 0;
                    }
                    $collectionFrequencies[$frequency]++;
                    
                    // Count week types
                    $weekType = $collection['customer_week_type'] ?? 'unknown';
                    if (!isset($collectionWeekTypes[$weekType])) {
                        $collectionWeekTypes[$weekType] = 0;
                    }
                    $collectionWeekTypes[$weekType]++;
                }
            }
            
            // Gather sample data
            $deliverySamples = [];
            $collectionSamples = [];
            
            // Sample of fortnightly deliveries
            if (isset($rawData['deliveries'])) {
                foreach ($rawData['deliveries'] as $delivery) {
                    if (strtolower($delivery['frequency'] ?? '') === 'fortnightly') {
                        $deliverySamples[] = [
                            'id' => $delivery['id'],
                            'name' => $delivery['name'],
                            'frequency' => $delivery['frequency'],
                            'customer_week_type' => $delivery['customer_week_type'],
                            'line_items' => array_map(fn($item) => [
                                'name' => $item['name'],
                                'meta' => $item['meta_data'] ?? []
                            ], $delivery['products'] ?? [])
                        ];
                        
                        if (count($deliverySamples) >= 3) {
                            break;
                        }
                    }
                }
            }
            
            // Sample of fortnightly collections
            if (isset($rawData['collections'])) {
                foreach ($rawData['collections'] as $collection) {
                    if (strtolower($collection['frequency'] ?? '') === 'fortnightly') {
                        $collectionSamples[] = [
                            'id' => $collection['id'],
                            'name' => $collection['name'],
                            'frequency' => $collection['frequency'],
                            'customer_week_type' => $collection['customer_week_type'],
                            'line_items' => array_map(fn($item) => [
                                'name' => $item['name'], 
                                'meta' => $item['meta_data'] ?? []
                            ], $collection['products'] ?? [])
                        ];
                        
                        if (count($collectionSamples) >= 3) {
                            break;
                        }
                    }
                }
            }
            
            // Create a sample for raw API response
            $rawSubscriptionSample = null;
            $transformedSubscriptionSample = null;
            
            if (!empty($rawData[0]) && is_array($rawData[0])) {
                // Find a fortnightly subscription
                foreach ($rawData as $sub) {
                    $isFortnightly = false;
                    if (isset($sub['line_items'][0]['meta_data'])) {
                        foreach ($sub['line_items'][0]['meta_data'] as $meta) {
                            if ($meta['key'] === 'frequency' && strtolower($meta['value']) === 'fortnightly') {
                                $isFortnightly = true;
                                break;
                            }
                        }
                    }
                    
                    if ($isFortnightly) {
                        $rawSubscriptionSample = $sub;
                        // Convert this to what would be in our array
                        $type = $this->determineCustomerType($sub['shipping_total'] ?? null, $sub);
                        // Remove 's' from type name for this context
                        $type = str_replace(['deliveries', 'collections'], ['delivery', 'collection'], $type);
                        $transformedSubscriptionSample = [
                            'type' => $type,
                            'id' => $sub['id'],
                            'customer_id' => $sub['customer_id'],
                            'line_items' => $sub['line_items'] ?? [],
                            'meta_data' => $sub['meta_data'] ?? [],
                            'shipping_total' => $sub['shipping_total'] ?? 0,
                        ];
                        break;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'deliveries' => [
                    'total' => count($rawData['deliveries'] ?? []),
                    'frequencies' => $deliveryFrequencies,
                    'week_types' => $deliveryWeekTypes,
                    'samples' => $deliverySamples,
                ],
                'collections' => [
                    'total' => count($rawData['collections'] ?? []),
                    'frequencies' => $collectionFrequencies,
                    'week_types' => $collectionWeekTypes,
                    'samples' => $collectionSamples,
                ],
                'raw_subscription_sample' => $rawSubscriptionSample,
                'transformed_subscription_sample' => $transformedSubscriptionSample,
                'message' => 'Debug information for frequencies and week types'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * DEBUG: Analyze a specific customer's delivery/collection status
     */
    public function debugSpecificCustomer($email, WpApiService $wpApi)
    {
        try {
            $subscriptions = $wpApi->getDeliveryScheduleData(100); // Get more subscriptions to find the customer
            
            $customerFound = null;
            $allMatches = [];
            
            // Search for customer by email
            foreach ($subscriptions as $sub) {
                $customerEmail = $sub['billing']['email'] ?? '';
                if (stripos($customerEmail, $email) !== false || $customerEmail === $email) {
                    $allMatches[] = $sub;
                    if (!$customerFound) {
                        $customerFound = $sub;
                    }
                }
            }
            
            if (!$customerFound) {
                return response()->json([
                    'success' => false,
                    'error' => "Customer with email containing '{$email}' not found",
                    'searched_subscriptions' => count($subscriptions)
                ]);
            }
            
            $analysis = [
                'customer_email' => $email,
                'matches_found' => count($allMatches),
                'subscription_details' => [],
                'shipping_analysis' => [],
                'recommendations' => []
            ];
            
            foreach ($allMatches as $sub) {
                $id = $sub['id'] ?? 'N/A';
                $customerEmail = $sub['billing']['email'] ?? 'N/A';
                $customerName = trim(($sub['billing']['first_name'] ?? '') . ' ' . ($sub['billing']['last_name'] ?? ''));
                $shippingTotalRaw = $sub['shipping_total'] ?? 'NOT_SET';
                
                // Test both old and new logic
                $oldLogicFloat = (float) ($sub['shipping_total'] ?? 0);
                $oldLogicResult = $oldLogicFloat > 0 ? 'DELIVERY' : 'COLLECTION';
                $newLogicResult = strtoupper(str_replace(['deliveries', 'collections'], ['delivery', 'collection'], $this->determineCustomerType($sub['shipping_total'] ?? null, $sub)));
                
                $analysis['subscription_details'][] = [
                    'subscription_id' => $id,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'status' => $sub['status'] ?? 'unknown',
                    'shipping_total_raw' => $shippingTotalRaw,
                    'shipping_total_type' => gettype($sub['shipping_total'] ?? null),
                    'shipping_total_float' => $oldLogicFloat,
                    'old_logic_result' => $oldLogicResult,
                    'new_logic_result' => $newLogicResult,
                    'should_be_delivery' => $oldLogicFloat > 0,
                    'billing_address' => $sub['billing'] ?? [],
                    'shipping_address' => $sub['shipping'] ?? [],
                    'line_items' => $sub['line_items'] ?? []
                ];
                
                $analysis['shipping_analysis'][] = [
                    'subscription_id' => $id,
                    'raw_value' => $shippingTotalRaw,
                    'is_string' => is_string($shippingTotalRaw),
                    'is_numeric' => is_numeric($shippingTotalRaw),
                    'has_whitespace' => is_string($shippingTotalRaw) && trim($shippingTotalRaw) !== $shippingTotalRaw,
                    'float_cast' => $oldLogicFloat,
                    'classification' => $newLogicResult
                ];
            }
            
            // Generate recommendations
            $hasShippingCost = false;
            $hasInconsistentData = false;
            
            foreach ($analysis['subscription_details'] as $detail) {
                if ($detail['shipping_total_float'] > 0) {
                    $hasShippingCost = true;
                }
                if ($detail['old_logic_result'] !== $detail['new_logic_result']) {
                    $hasInconsistentData = true;
                }
            }
            
            if ($hasShippingCost) {
                $analysis['recommendations'][] = "✅ Customer HAS shipping costs - should appear as DELIVERY";
            } else {
                $analysis['recommendations'][] = "ℹ️ Customer has NO shipping costs - should appear as COLLECTION";
            }
            
            if ($hasInconsistentData) {
                $analysis['recommendations'][] = "⚠️ Logic inconsistency detected - review shipping total format";
            } else {
                $analysis['recommendations'][] = "✅ Logic is consistent between old and new methods";
            }
            
            if (count($allMatches) > 1) {
                $analysis['recommendations'][] = "🔍 Multiple subscriptions found - customer may have multiple accounts";
            }
            
            return response()->json([
                'success' => true,
                'analysis' => $analysis
           

            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Manage subscription - analyze and provide options for fixes
     */
    public function manageSubscription($email, WpApiService $wpApi)
    {
        try {
            // Get all subscriptions and look for the customer
            $subscriptions = $wpApi->getDeliveryScheduleData(200);
            
            $customerMatches = [];
            
            // Search for customer by email
            foreach ($subscriptions as $sub) {
                $customerEmail = $sub['billing']['email'] ?? '';
                if (stripos($customerEmail, $email) !== false) {
                    $customerMatches[] = $sub;
                }
            }
            
            if (empty($customerMatches)) {
                return response()->json([
                    'success' => false,
                    'error' => "Customer with email containing '{$email}' not found"
                ]);
            }
            
            // Analyze subscriptions
            $analysis = [
                'customer_email' => $email,
                'subscriptions_found' => count($customerMatches),
                'issues_detected' => [],
                'subscription_details' => [],
                'suggested_actions' => []
            ];
            
            $totalVegetableBoxes = 0;
            $totalCost = 0;
            
            foreach ($customerMatches as $sub) {
                $subscriptionId = $sub['id'] ?? 'N/A';
                $status = $sub['status'] ?? 'N/A';
                $total = floatval($sub['total'] ?? 0);
                $totalCost += $total;
                
                $subscriptionDetail = [
                    'id' => $subscriptionId,
                    'status' => $status,
                    'total' => $total,
                    'date_created' => $sub['date_created'] ?? 'N/A',
                    'line_items' => [],
                    'vegetable_boxes' => 0
                ];
                
                // Count vegetable boxes in this subscription
                foreach ($sub['line_items'] ?? [] as $item) {
                    $itemName = $item['name'] ?? '';
                    $quantity = intval($item['quantity'] ?? 0);
                    $itemTotal = floatval($item['total'] ?? 0);
                    
                    $subscriptionDetail['line_items'][] = [
                        'name' => $itemName,
                        'quantity' => $quantity,
                        'total' => $itemTotal
                    ];
                    
                    if (stripos($itemName, 'vegetable box') !== false) {
                        $subscriptionDetail['vegetable_boxes'] += $quantity;
                        $totalVegetableBoxes += $quantity;
                    }
                }
                
                $analysis['subscription_details'][] = $subscriptionDetail;
            }
            
            // Detect issues
            if (count($customerMatches) > 1) {
                $analysis['issues_detected'][] = "Multiple active subscriptions found";
                $analysis['suggested_actions'][] = "Consider consolidating subscriptions";
            }
            
            if ($totalVegetableBoxes > 1) {
                $analysis['issues_detected'][] = "Customer has {$totalVegetableBoxes} vegetable boxes total";
                $analysis['suggested_actions'][] = "Check if customer intended to have multiple boxes";
                
                // Calculate potential refund if single person should only have 1 box
                $singleBoxPrice = 20.00; // Estimated price
                $excessBoxes = $totalVegetableBoxes - 1;
                $potentialRefund = $excessBoxes * $singleBoxPrice;
                
                if ($potentialRefund > 0) {
                    $analysis['refund_calculation'] = [
                        'total_boxes' => $totalVegetableBoxes,
                        'expected_boxes' => 1,
                        'excess_boxes' => $excessBoxes,
                        'estimated_refund' => $potentialRefund
                    ];
                    $analysis['suggested_actions'][] = "Consider refunding approximately £{$potentialRefund} for excess boxes";
                }
            }
            
            // Add management options
            $analysis['management_options'] = [
                'view_in_woocommerce' => "https://middleworldfarms.org/wp-admin/edit.php?post_type=shop_subscription",
                'customer_search' => "Search for: {$email}",
                'actions_available' => [
                    'Cancel excess subscriptions',
                    'Modify subscription quantities',
                    'Process refunds',
                    'Contact customer for confirmation'
                ]
            ];
            
            return response()->json([
                'success' => true,
                'analysis' => $analysis
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Normalize shipping total value to float
     * @param mixed $shippingTotal The shipping total value
     * @return float The normalized shipping total
     */
    private function normalizeShippingTotal($shippingTotal)
    {
        if ($shippingTotal === null || $shippingTotal === '') {
            return 0.0;
        }
        
        if (is_string($shippingTotal)) {
            $shippingTotal = trim($shippingTotal);
            if (!is_numeric($shippingTotal)) {
                return 0.0;
            }
        }
        
        return (float) $shippingTotal;
    }

    /**
     * Get the start date (Monday) of a given week number
     * 
     * @param int $weekNumber The ISO week number
     * @param int|null $year The year (default: current year)
     * @return string The date in Y-m-d format
     */
    private function getWeekStartDate($weekNumber, $year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }
        
        // Create a date for January 4th (always in week 1 according to ISO standard)
        $jan4 = new \DateTime("{$year}-01-04");
        
        // Find the Monday of week 1
        $jan4WeekDay = $jan4->format('N'); // 1=Monday, 7=Sunday
        $week1Monday = clone $jan4;
        $week1Monday->modify('-' . ($jan4WeekDay - 1) . ' days');
        
        // Calculate the target week's Monday
        $targetWeekMonday = clone $week1Monday;
        $targetWeekMonday->modify('+' . ($weekNumber - 1) . ' weeks');
        
        return $targetWeekMonday->format('Y-m-d');
    }

    /**
     * Determine if a customer/order is a delivery or collection
     * Uses shipping class, method, address, and shipping total for robust classification
     * 
     * @param mixed $shippingTotal The shipping total value
     * @param array $subscription The full subscription data (optional)
     * @return string 'deliveries' or 'collections'
     */
    private function determineCustomerType($shippingTotal, $subscription = null)
    {
        // PRIORITY 1: Check line items for explicit "Collection" or "Delivery" product names
        // This takes precedence over everything else including shipping_total
        if ($subscription && isset($subscription['line_items']) && is_array($subscription['line_items'])) {
            foreach ($subscription['line_items'] as $item) {
                if (isset($item['name']) && is_string($item['name'])) {
                    $itemName = strtolower($item['name']);
                    
                    // Check for collection indicators (including typos like "colection")
                    if (strpos($itemName, 'collection') !== false || 
                        strpos($itemName, 'colection') !== false ||
                        strpos($itemName, 'collect') !== false ||
                        strpos($itemName, 'pickup') !== false) {
                        return 'collections';
                    }
                    
                    // Check for delivery indicators
                    if (strpos($itemName, 'delivery') !== false || 
                        strpos($itemName, 'shipping') !== false) {
                        return 'deliveries';
                    }
                }
            }
        }
        
        // PRIORITY 2: Check shipping total - if greater than 0, it's delivery
        // NOTE: shipping_total often contains the collection/delivery product price, not actual shipping
        // So we only use this if line items didn't give us a clear answer
        $normalizedShippingTotal = $this->normalizeShippingTotal($shippingTotal);
        if ($normalizedShippingTotal > 0) {
            return 'deliveries';
        }
        
        // PRIORITY 3: Check shipping method for explicit delivery/collection indicators
        // This takes precedence over everything else including addresses
        if ($subscription && isset($subscription['shipping_lines']) && is_array($subscription['shipping_lines'])) {
            foreach ($subscription['shipping_lines'] as $shippingLine) {
                if (isset($shippingLine['method_title']) && is_string($shippingLine['method_title'])) {
                    $methodTitle = strtolower($shippingLine['method_title']);
                    
                    // Check for collection/pickup indicators (including typos)
                    if (strpos($methodTitle, 'collect') !== false ||
                        strpos($methodTitle, 'pickup') !== false ||
                        strpos($methodTitle, 'colection') !== false) {  // Handle typo
                        return 'collections';
                    }
                    
                    // Check for explicit delivery indicators
                    if (strpos($methodTitle, 'delivery') !== false ||
                        strpos($methodTitle, 'deliver') !== false) {
                        return 'deliveries';
                    }
                }
            }
        }
        
        // PRIORITY 3: Check shipping classes in line_items for collection indicators
        if ($subscription && isset($subscription['line_items']) && is_array($subscription['line_items'])) {
            foreach ($subscription['line_items'] as $item) {
                if (isset($item['shipping_class']) && is_string($item['shipping_class'])) {
                    $shippingClass = strtolower($item['shipping_class']);
                    if (strpos($shippingClass, 'collect') !== false || strpos($shippingClass, 'pickup') !== false) {
                        return 'collections';
                    }
                }
            }
        }
        
        // PRIORITY 4: Check meta_data for shipping class collection indicators
        if ($subscription && isset($subscription['meta_data']) && is_array($subscription['meta_data'])) {
            foreach ($subscription['meta_data'] as $meta) {
                if (isset($meta['key']) && isset($meta['value']) && is_string($meta['key']) && is_string($meta['value'])) {
                    $key = strtolower($meta['key']);
                    $value = strtolower($meta['value']);

                    if (strpos($key, 'shipping') !== false && (strpos($value, 'collect') !== false || strpos($value, 'pickup') !== false)) {
                        return 'collections';
                    }
                }
            }
        }       
        
        // PRIORITY 5: Check if customer has a delivery address (only matters if no collection method found above)
        $hasDeliveryAddress = false;
        if ($subscription) {
            // Check shipping address first
            if (isset($subscription['shipping']['address_1']) && !empty(trim($subscription['shipping']['address_1']))) {
                $hasDeliveryAddress = true;
            }
            // Fallback to billing address
            elseif (isset($subscription['billing']['address_1']) && !empty(trim($subscription['billing']['address_1']))) {
                $hasDeliveryAddress = true;
            }
        }
        
        // If customer has a delivery address but no shipping cost and no collection method, 
        // it might be a delivery with free shipping or promotional delivery
        if ($hasDeliveryAddress) {
            return 'deliveries';
        }
        
        // Default to collection if no shipping cost, no delivery address, and no other indicators
        return 'collections';
    }

    /**
     * Debug classification logic for all customers
     */
    public function debugClassification(WpApiService $wpApi)
    {
        try {
            // Get all subscriptions
            $subscriptions = $wpApi->getDeliveryScheduleData(100);
            
            $oldLogicCounts = ['deliveries' => 0, 'collections' => 0];
            $newLogicCounts = ['deliveries' => 0, 'collections' => 0];
            $classifications = [];
            $mismatches = [];
            
            foreach ($subscriptions as $sub) {
                $email = $sub['billing']['email'] ?? 'N/A';
                $name = trim(($sub['billing']['first_name'] ?? '') . ' ' . ($sub['billing']['last_name'] ?? ''));
                $shippingTotal = (float) ($sub['shipping_total'] ?? 0);
                
                // Old logic
                $oldLogic = $shippingTotal > 0 ? 'deliveries' : 'collections';
                $oldLogicCounts[$oldLogic]++;
                
                // New logic
                $newLogic = $this->determineCustomerType($sub['shipping_total'] ?? null, $sub);
                $newLogicCounts[$newLogic]++;
                
                $classification = [
                    'name' => $name,
                    'email' => $email,
                    'subscription_id' => $sub['id'] ?? 'N/A',
                    'shipping_total' => $shippingTotal,
                    'old_logic' => $oldLogic,
                    'new_logic' => $newLogic,
                    'match' => $oldLogic === $newLogic,
                    'has_shipping_address' => !empty($sub['shipping']['address_1'] ?? ''),
                    'has_billing_address' => !empty($sub['billing']['address_1'] ?? '')
                ];
                
                $classifications[] = $classification;
                
                if ($oldLogic !== $newLogic) {
                    $mismatches[] = $classification;
                }
            }
            
            return response()->json([
                'success' => true,
                'summary' => [
                    'total_subscriptions' => count($subscriptions),
                    'old_logic_counts' => $oldLogicCounts,
                    'new_logic_counts' => $newLogicCounts,
                    'mismatches_count' => count($mismatches)
                ],
                'improvements' => [
                    'deliveries_gained' => $newLogicCounts['deliveries'] - $oldLogicCounts['deliveries'],
                    'collections_lost' => $oldLogicCounts['collections'] - $newLogicCounts['collections']
                ],
                'mismatches' => $mismatches,
                'all_classifications' => $classifications
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Debug Pauline Moore's duplicate order issue
     */
    public function debugPauline(WpApiService $wpApi)
    {
        try {
            // Get all subscriptions and look for Pauline
            $subscriptions = $wpApi->getDeliveryScheduleData(200);
            
            $paulineData = [];
            $allPaulineMatches = [];
            
            // Search for Pauline by email and name
            foreach ($subscriptions as $sub) {
                $email = $sub['billing']['email'] ?? '';
                $firstName = $sub['billing']['first_name'] ?? '';
                $lastName = $sub['billing']['last_name'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                
                if (stripos($email, 'pgmoore59') !== false || 
                    stripos($fullName, 'pauline') !== false || 
                    stripos($fullName, 'moore') !== false) {
                    $allPaulineMatches[] = $sub;
                }
            }
            
            if (empty($allPaulineMatches)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pauline Moore not found in subscriptions'
                ]);
            }
            
            // Analyze each subscription
            $analysis = [
                'customer_info' => [
                    'matches_found' => count($allPaulineMatches),
                    'subscriptions' => []
                ],
                'duplicate_analysis' => [
                    'total_line_items' => 0,
                    'vegetable_boxes' => 0,
                    'total_cost' => 0,
                    'suspected_duplicates' => []
                ],
                'recommendations' => []
            ];
            
            foreach ($allPaulineMatches as $index => $sub) {
                $subscriptionAnalysis = [
                    'subscription_id' => $sub['id'] ?? 'N/A',
                    'status' => $sub['status'] ?? 'N/A',
                    'total' => $sub['total'] ?? 0,
                    'date_created' => $sub['date_created'] ?? 'N/A',
                    'customer_info' => [
                        'email' => $sub['billing']['email'] ?? 'N/A',
                        'name' => trim(($sub['billing']['first_name'] ?? '') . ' ' . ($sub['billing']['last_name'] ?? '')),
                        'address' => trim(($sub['billing']['address_1'] ?? '') . ' ' . ($sub['billing']['address_2'] ?? '') . ' ' . ($sub['billing']['city'] ?? '') . ' ' . ($sub['billing']['postcode'] ?? ''))
                    ],
                    'line_items' => [],
                    'payment_info' => [
                        'payment_method' => $sub['payment_method'] ?? 'N/A',
                        'payment_method_title' => $sub['payment_method_title'] ?? 'N/A'
                    ]
                ];
                
                // Analyze line items
                foreach ($sub['line_items'] ?? [] as $item) {
                    $itemName = $item['name'] ?? 'N/A';
                    $quantity = $item['quantity'] ?? 0;
                    $total = $item['total'] ?? 0;
                    
                    $lineItem = [
                        'name' => $itemName,
                        'quantity' => $quantity,
                        'total' => $total,
                        'subtotal' => $item['subtotal'] ?? 0,
                        'product_id' => $item['product_id'] ?? 'N/A',
                        'variation_id' => $item['variation_id'] ?? 'N/A',
                        'meta_data' => $item['meta_data'] ?? []
                    ];
                    
                    $subscriptionAnalysis['line_items'][] = $lineItem;
                    $analysis['duplicate_analysis']['total_line_items']++;
                    
                    // Check for vegetable boxes
                    if (stripos($itemName, 'vegetable box') !== false) {
                        $analysis['duplicate_analysis']['vegetable_boxes'] += $quantity;
                    }
                    
                    $analysis['duplicate_analysis']['total_cost'] += floatval($total);
                }
                
                $analysis['customer_info']['subscriptions'][] = $subscriptionAnalysis;
            }
            
            // Generate recommendations
            if (count($allPaulineMatches) > 1) {
                $analysis['recommendations'][] = "⚠️ Multiple subscriptions found - check for duplicate subscriptions";
            }
            
            if ($analysis['duplicate_analysis']['vegetable_boxes'] > 1) {
                $analysis['recommendations'][] = "⚠️ Customer has {$analysis['duplicate_analysis']['vegetable_boxes']} vegetable boxes - likely duplicate";
                $analysis['recommendations'][] = "💰 Consider refunding excess charges";
            }
            
            // Calculate potential refund
            $singleBoxPrice = 20.00; // Assuming single person box is £20
            $expectedBoxes = 1;
            $actualBoxes = $analysis['duplicate_analysis']['vegetable_boxes'];
            $potentialRefund = ($actualBoxes - $expectedBoxes) * $singleBoxPrice;
            
            if ($potentialRefund > 0) {
                $analysis['refund_calculation'] = [
                    'expected_boxes' => $expectedBoxes,
                    'actual_boxes' => $actualBoxes,
                    'excess_boxes' => $actualBoxes - $expectedBoxes,
                    'estimated_single_box_price' => $singleBoxPrice,
                    'potential_refund' => $potentialRefund
                ];
            }
            
            return response()->json([
                'success' => true,
                'analysis' => $analysis
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Print packing slips for selected deliveries
     */
    public function print(Request $request, WpApiService $wpApi)
    {
        try {
            // Get the IDs from the request
            $ids = $request->get('ids');
            $type = $request->get('type', 'delivery'); // Default to delivery, but allow collections
            
            if (empty($ids)) {
                return redirect()->back()->with('error', 'No IDs provided for printing.');
            }
            
            // Convert comma-separated string to array if needed
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            
            // Get delivery data for the selected IDs
            $items = [];
            $currentWeek = date('W');
            
            // Get schedule data to find the selected items
            $rawData = $wpApi->getDeliveryScheduleData(500);
            $scheduleData = $this->transformScheduleData($rawData, $currentWeek);
            
            // Find items matching the selected IDs (search both deliveries and collections)
            foreach ($scheduleData['data'] as $dateData) {
                // Search in deliveries
                if (isset($dateData['deliveries'])) {
                    foreach ($dateData['deliveries'] as $delivery) {
                        $deliveryId = $delivery['id'] ?? $delivery['order_number'] ?? null;
                        if (in_array($deliveryId, $ids)) {
                            // Only include truly active deliveries in schedule prints
                            $status = strtolower($delivery['status'] ?? 'pending');
                            if ($status === 'active') {
                                $items[] = $delivery;
                            }
                        }
                    }
                }
                
                // Search in collections
                if (isset($dateData['collections'])) {
                    foreach ($dateData['collections'] as $collection) {
                        $collectionId = $collection['id'] ?? $collection['order_number'] ?? null;
                        if (in_array($collectionId, $ids)) {
                            // Only include truly active collections in schedule prints
                            $status = strtolower($collection['status'] ?? 'pending');
                            if ($status === 'active') {
                                $items[] = $collection;
                            }
                        }
                    }
                }
            }
            
            // Group items by date for print view
            $printData = [];
            foreach ($items as $item) {
                $date = $item['delivery_date'] ?? $item['collection_date'] ?? $item['date'] ?? date('Y-m-d');
                if (!isset($printData[$date])) {
                    $printData[$date] = [
                        'date_formatted' => date('l, F j, Y', strtotime($date)),
                        'items' => []
                    ];
                }
                $printData[$date]['items'][] = $item;
            }
            
            $title = $type === 'collection' ? 'Collection Schedule' : 'Delivery Schedule';
            $selectedWeek = $currentWeek;
            $dayInfo = $type === 'collection' ? 'Selected Collections' : 'Selected Deliveries';
            $totalItems = count($items);
            
            // Use appropriate template based on type
            if ($type === 'collection') {
                $collections = $items; // Rename for clarity in collection template
                return view('admin.deliveries.collection-schedule', compact('collections', 'title', 'selectedWeek', 'type', 'dayInfo', 'totalItems', 'printData'));
            } else {
                return view('admin.deliveries.print', compact('items', 'title', 'selectedWeek', 'type', 'dayInfo', 'totalItems', 'printData'));
            }
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating schedule: ' . $e->getMessage());
        }
    }

    /**
     * Print actual packing slips for selected deliveries (multiple per sheet)
     */
    public function printSlips(Request $request, WpApiService $wpApi)
    {
        try {
            // Get the IDs from the request
            $ids = $request->get('ids');
            $type = $request->get('type', 'delivery'); // Default to delivery, but allow collections
            
            if (empty($ids)) {
                return redirect()->back()->with('error', 'No IDs provided for printing slips.');
            }
            
            // Convert comma-separated string to array if needed
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            
            // Get settings for slips per page
            $settings = \App\Models\Setting::getAll();
            $slipsPerPage = $settings['packing_slips_per_page'] ?? 4;
            
            // Get data for the selected IDs
            $deliveries = []; // Keep same variable name for template compatibility
            $currentWeek = date('W');
            
            // Get schedule data to find the selected items
            $rawData = $wpApi->getDeliveryScheduleData(500);
            $scheduleData = $this->transformScheduleData($rawData, $currentWeek);
            
            // Find items matching the selected IDs (search both deliveries and collections)
            foreach ($scheduleData['data'] as $dateData) {
                // Search in deliveries
                if (isset($dateData['deliveries'])) {
                    foreach ($dateData['deliveries'] as $delivery) {
                        $deliveryId = $delivery['id'] ?? $delivery['order_number'] ?? null;
                        if (in_array($deliveryId, $ids)) {
                            // Only include truly active deliveries in slip prints
                            $status = strtolower($delivery['status'] ?? 'pending');
                            if ($status === 'active') {
                                $deliveries[] = $delivery;
                            }
                        }
                    }
                }
                
                // Search in collections
                if (isset($dateData['collections'])) {
                    foreach ($dateData['collections'] as $collection) {
                        $collectionId = $collection['id'] ?? $collection['order_number'] ?? null;
                        if (in_array($collectionId, $ids)) {
                            // Only include active collections in slip prints
                            $status = strtolower($collection['status'] ?? 'pending');
                            if ($status === 'active' || $status === 'processing' || $status === 'pending') {
                                $deliveries[] = $collection;
                            }
                        }
                    }
                }
            }
             // Company information for slips
            $companyInfo = [
                'name' => 'Middle World Farms',
                'address' => 'Your Farm Address', // You can update this
                'phone' => 'Your Phone Number',   // You can update this
                'logo_url' => asset('images/logo.png') // You can update this path
            ];

            // Use appropriate template based on type
            if ($type === 'collection') {
                // For collections, use the collection-slips template
                $collections = $deliveries; // Rename for clarity in collection template
                return view('admin.deliveries.collection-slips', compact('collections', 'slipsPerPage', 'companyInfo'));
            } else {
                // For deliveries, use the packing-slips template
                return view('admin.deliveries.packing-slips', compact('deliveries', 'slipsPerPage', 'companyInfo', 'type'));
            }
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating packing slips: ' . $e->getMessage());
        }
    }
    
    /**
     * Mark a delivery or collection as complete
     */
    public function markComplete(Request $request)
    {
        try {
            $request->validate([
                'delivery_id' => 'required|string',
                'type' => 'required|in:delivery,collection',
                'delivery_date' => 'required|date',
                'customer_name' => 'nullable|string',
                'customer_email' => 'nullable|email',
                'notes' => 'nullable|string|max:500'
            ]);
            
            $completion = \App\Models\DeliveryCompletion::markCompleted(
                $request->delivery_id,
                $request->type,
                $request->delivery_date,
                $request->customer_name,
                $request->customer_email,
                Auth::user()->name ?? 'Staff' // Get current user if available
            );
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($request->type) . ' marked as complete for ' . date('M j, Y', strtotime($request->delivery_date)),
                'completed_at' => $completion->completed_at->format('M j, Y g:i A'),
                'completed_by' => $completion->completed_by,
                'delivery_date' => $completion->delivery_date->format('Y-m-d')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking item as complete: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Unmark a delivery or collection as complete
     */
    public function unmarkComplete(Request $request)
    {
        try {
            $request->validate([
                'delivery_id' => 'required|string',
                'type' => 'required|in:delivery,collection',
                'delivery_date' => 'required|date'
            ]);
            
            \App\Models\DeliveryCompletion::where('external_id', $request->delivery_id)
                ->where('type', $request->type)
                ->where('delivery_date', $request->delivery_date)
                ->delete();
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($request->type) . ' unmarked as complete for ' . date('M j, Y', strtotime($request->delivery_date))
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error unmarking item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add completion data to schedule data
     */
    private function addCompletionData($scheduleData)
    {
        // Get all completion records to avoid N+1 queries
        $completions = \App\Models\DeliveryCompletion::all()->keyBy(function ($item) {
            return $item->external_id . '_' . $item->type . '_' . $item->delivery_date->format('Y-m-d');
        });

        // Add completion data to deliveries
        foreach ($scheduleData['data'] as $date => &$dateData) {
            if (isset($dateData['deliveries'])) {
                foreach ($dateData['deliveries'] as &$delivery) {
                    $deliveryId = $delivery['id'] ?? $delivery['order_number'] ?? null;
                    if ($deliveryId) {
                        $key = $deliveryId . '_delivery_' . $date;
                        if (isset($completions[$key])) {
                            $completion = $completions[$key];
                            $delivery['completion_status'] = 'completed';
                            $delivery['completed'] = true;
                            $delivery['completed_at'] = $completion->completed_at->format('M j, Y g:i A');
                            $delivery['completed_by'] = $completion->completed_by;
                        } else {
                            $delivery['completion_status'] = 'pending';
                            $delivery['completed'] = false;
                        }
                    }
                }
            }

            // Add completion data to collections
            if (isset($dateData['collections'])) {
                foreach ($dateData['collections'] as &$collection) {
                    $collectionId = $collection['id'] ?? $collection['order_number'] ?? null;
                    if ($collectionId) {
                        $key = $collectionId . '_collection_' . $date;
                        if (isset($completions[$key])) {
                            $completion = $completions[$key];
                            $collection['completion_status'] = 'completed';
                            $collection['completed'] = true;
                            $collection['completed_at'] = $completion->completed_at->format('M j, Y g:i A');
                            $collection['completed_by'] = $completion->completed_by;
                        } else {
                            $collection['completion_status'] = 'pending';
                            $collection['completed'] = false;
                        }
                    }
                }
            }
        }

        // Add completion data to collections by status
        if (isset($scheduleData['collectionsByStatus'])) {
            foreach ($scheduleData['collectionsByStatus'] as &$statusData) {
                foreach ($statusData as $date => &$dateData) {
                    if (isset($dateData['collections'])) {
                        foreach ($dateData['collections'] as &$collection) {
                            $collectionId = $collection['id'] ?? $collection['order_number'] ?? null;
                            if ($collectionId) {
                                $key = $collectionId . '_collection_' . $date;
                                if (isset($completions[$key])) {
                                    $completion = $completions[$key];
                                    $collection['completion_status'] = 'completed';
                                    $collection['completed'] = true;
                                    $collection['completed_at'] = $completion->completed_at->format('M j, Y g:i A');
                                    $collection['completed_by'] = $completion->completed_by;
                                } else {
                                    $collection['completion_status'] = 'pending';
                                    $collection['completed'] = false;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $scheduleData;
    }

    /**
     * Check if a customer should be included in the schedule based on join date and cut-off time
     * 
     * @param string $customerJoinDate Customer's join date (date_created)
     * @param string $cutoffTime Cut-off time (HH:MM format)
     * @param string $cutoffDay Day of week for cut-off (Thursday for deliveries, Friday for collections)
     * @param int $selectedWeek Selected week number
     * @return bool True if customer should be included, false if they joined after cut-off
     */
    private function shouldIncludeCustomerBasedOnCutoff($customerJoinDate, $cutoffTime, $cutoffDay, $selectedWeek)
    {
        if (empty($customerJoinDate)) {
            return true; // Include if no join date available
        }

        try {
            $joinDate = \Carbon\Carbon::parse($customerJoinDate);
            
            // Get the cut-off date/time for the selected week
            $selectedWeekStart = $this->getWeekStartDate($selectedWeek);
            $weekStart = \Carbon\Carbon::parse($selectedWeekStart);
            
            // Map day names to day numbers (1=Monday, 4=Thursday, 5=Friday)
            $dayMap = [
                'Thursday' => 4,
                'Friday' => 5
            ];
            
            $cutoffDayNumber = $dayMap[$cutoffDay] ?? 4; // Default to Thursday
            $cutoffDate = $weekStart->copy()->addDays($cutoffDayNumber - 1); // -1 because Monday is day 1
            
            // Set the cut-off time
            $cutoffDateTime = $cutoffDate->setTimeFromTimeString($cutoffTime);
            
            // Include customer if they joined BEFORE or AT the cut-off time
            return $joinDate->lessThanOrEqualTo($cutoffDateTime);
            
        } catch (\Exception $e) {
            // If there's any error parsing dates, include the customer
            return true;
        }
    }
    
    /**
     * Get deliveries data as JSON for mobile/POS interfaces
     */
    public function getDeliveriesData(Request $request, WpApiService $wpApi)
    {
        try {
            $selectedWeek = (int) $request->get('week', date('W'));
            
            // Get raw data
            $rawData = [];
            try {
                $rawData = $wpApi->getDeliveryScheduleData(500);
            } catch (\Exception $e) {
                \Log::error('Delivery schedule API timeout: ' . $e->getMessage());
            }
            
            // Fetch collection day preferences
            $collectionDayPreferences = $this->fetchCollectionDayPreferences($rawData);
            
            // Transform data
            $scheduleData = $this->transformScheduleData($rawData, $selectedWeek, $collectionDayPreferences);
            
            // Add completion data
            $scheduleData = $this->addCompletionData($scheduleData);
            
            // Format for simple display - just today's deliveries and collections
            $today = date('Y-m-d');
            $todayData = $scheduleData['data'][$today] ?? null;
            
            if (!$todayData) {
                return response()->json([
                    'success' => true,
                    'deliveries' => [],
                    'collections' => [],
                    'message' => 'No deliveries or collections scheduled for today'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'deliveries' => [$today => $todayData],
                'collections' => [$today => $todayData],
                'date' => $today
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get deliveries data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FarmOSApi;
use App\Services\FarmOSQueryService;
use App\Models\HarvestLog;
use App\Models\StockItem;
use App\Models\CropPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class FarmOSDataController extends Controller
{
    protected $farmOSApi;
    protected $farmOSQuery;

    public function __construct(FarmOSApi $farmOSApi, FarmOSQueryService $farmOSQuery)
    {
        $this->farmOSApi = $farmOSApi;
        $this->farmOSQuery = $farmOSQuery;
        
        // Debug: Verify service is properly injected
        if (!method_exists($this->farmOSApi, 'getCropPlanningData')) {
            Log::error('FarmOSApi is missing getCropPlanningData method. Service class: ' . get_class($this->farmOSApi));
            // Fallback: create a new instance of the service
            $this->farmOSApi = new FarmOSApi();
        }
    }

    /**
     * Display the main FarmOS dashboard
     */
    public function index(): View
    {
        try {
            // Debug: Check service instance and method
            Log::info('FarmOS Controller Debug - Service class: ' . get_class($this->farmOSApi));
            Log::info('FarmOS Controller Debug - getCropPlanningData method exists: ' . (method_exists($this->farmOSApi, 'getCropPlanningData') ? 'Yes' : 'No'));
            
            // Get real farmOS data (direct DB queries - fast)
            $farmOSCropPlans = $this->farmOSQuery->getPlantings()->toArray();
            $farmOSHarvests = $this->farmOSQuery->getHarvestLogs(null, null, ['with_quantities' => true])->toArray();
            
            // Use farmOS data for statistics
            $cropPlans = collect($farmOSCropPlans);
            
            $stats = [
                'recent_harvests' => 0, // farmOS harvests would need date filtering
                'unsynced_harvests' => 0, // Not applicable for farmOS direct integration
                'total_stock_items' => 0, // Would need to fetch stock from farmOS
                'low_stock_items' => 0, // Would need stock levels from farmOS
                'active_crop_plans' => $cropPlans->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ];

            // Use farmOS crop plans for upcoming harvests
            $upcomingHarvests = $cropPlans->filter(function($plan) {
                return !empty($plan['planned_harvest_start']) && 
                       !in_array($plan['status'], ['completed', 'cancelled']) &&
                       strtotime($plan['planned_harvest_start']) >= time() &&
                       strtotime($plan['planned_harvest_start']) <= strtotime('+14 days');
            })->take(10);

            // Use farmOS data but format it for the dashboard
            $recentHarvests = collect($farmOSHarvests)->map(function($harvest) {
                // Convert farmOS harvest data to expected format
                if (is_array($harvest)) {
                    return (object) [
                        'crop_name' => $harvest['crop_name'] ?? $harvest['name'] ?? 'Unknown Crop',
                        'crop_type' => $harvest['crop_type'] ?? null,
                        'formatted_quantity' => $harvest['formatted_quantity'] ?? $harvest['quantity'] ?? 'N/A',
                        'harvest_date' => isset($harvest['harvest_date']) ? 
                            Carbon::parse($harvest['harvest_date']) : Carbon::now(),
                        'is_today' => isset($harvest['harvest_date']) ? 
                            Carbon::parse($harvest['harvest_date'])->isToday() : false,
                        'synced_to_stock' => $harvest['synced_to_stock'] ?? false
                    ];
                }
                return $harvest; // If already an object, return as-is
            })->take(10);
            $lowStockItems = collect([]);

            // Check if we have live farmOS data
            $hasRealData = !empty($farmOSCropPlans) || !empty($farmOSHarvests);
            $hasTestData = false; // No test data - we want live farmOS data
            $usingFarmOSData = $hasRealData;

            return view('admin.farmos.dashboard', compact(
                'stats',
                'recentHarvests', 
                'lowStockItems',
                'upcomingHarvests',
                'hasTestData',
                'usingFarmOSData'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to fetch farmOS dashboard data: ' . $e->getMessage());
            
            // Fallback to local database if farmOS fails
            $hasTestData = HarvestLog::where('crop_name', 'LIKE', 'TEST -%')->exists() ||
                          StockItem::where('crop_name', 'LIKE', 'TEST -%')->exists() ||
                          CropPlan::where('crop_name', 'LIKE', 'TEST -%')->exists();

            $stats = [
                'recent_harvests' => HarvestLog::where('harvest_date', '>=', now()->subDays(7))->count(),
                'unsynced_harvests' => HarvestLog::where('synced_to_stock', false)->count(),
                'total_stock_items' => StockItem::count(),
                'low_stock_items' => StockItem::whereRaw('current_stock <= minimum_stock')->count(),
                'active_crop_plans' => CropPlan::whereNotIn('status', ['completed', 'cancelled'])->count(),
            ];

            $recentHarvests = HarvestLog::orderBy('harvest_date', 'desc')
                ->limit(10)
                ->get();

            $lowStockItems = StockItem::whereRaw('current_stock <= minimum_stock')
                ->orderBy('current_stock', 'asc')
                ->limit(10)
                ->get();

            $upcomingHarvests = CropPlan::whereNotIn('status', ['completed', 'cancelled'])
                ->where('planned_harvest_start', '>=', now())
                ->where('planned_harvest_start', '<=', now()->addDays(14))
                ->orderBy('planned_harvest_start', 'asc')
                ->limit(10)
                ->get();

            $usingFarmOSData = false;

            return view('admin.farmos.dashboard', compact(
                'stats',
                'recentHarvests', 
                'lowStockItems',
                'upcomingHarvests',
                'hasTestData',
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Display harvest logs
     */
    public function harvests(Request $request): View
    {
        try {
            // Get real farmOS harvest logs (direct DB query - fast)
            $farmOSHarvests = $this->farmOSQuery->getHarvestLogs(null, null, ['with_quantities' => true]);
            
            // Convert to collection for filtering
            $harvestLogs = $farmOSHarvests->map(function($harvest) {
                return (object)[
                    'id' => $harvest->id,
                    'farmos_id' => $harvest->id,
                    'crop_name' => $harvest->name ?? 'Unknown',
                    'crop_type' => 'harvest', // Type from log table
                    'quantity' => $harvest->quantity_value ?? 0,
                    'units' => $harvest->quantity_measure ?? 'kg',
                    'harvest_date' => Carbon::createFromTimestamp($harvest->timestamp),
                    'location' => 'Unknown', // Would need join to asset table
                    'notes' => $harvest->notes ?? '',
                    'status' => $harvest->status ?? 'done',
                    'synced_to_stock' => false, // farmOS logs aren't synced to local stock
                    'formatted_quantity' => ($harvest->quantity_value ?? 0) . ' ' . ($harvest->quantity_measure ?? 'kg'),
                    'is_today' => Carbon::createFromTimestamp($harvest->timestamp)->isToday()
                ];
            });

            // Apply filters
            if ($request->filled('crop_type')) {
                $harvestLogs = $harvestLogs->where('crop_type', $request->crop_type);
            }

            if ($request->filled('date_from')) {
                $harvestLogs = $harvestLogs->filter(function($harvest) use ($request) {
                    return $harvest->harvest_date->gte(\Carbon\Carbon::parse($request->date_from));
                });
            }

            if ($request->filled('date_to')) {
                $harvestLogs = $harvestLogs->filter(function($harvest) use ($request) {
                    return $harvest->harvest_date->lte(\Carbon\Carbon::parse($request->date_to));
                });
            }

            // Sort by harvest date descending
            $harvestLogs = $harvestLogs->sortByDesc('harvest_date');

            // Get unique crop types for filter dropdown (direct DB query - fast)
            $cropTypes = $this->farmOSQuery->getPlantVarieties(['active_only' => true]);
            $cropTypes = ['types' => $cropTypes->map(fn($v) => ['id' => $v->tid, 'name' => $v->name])->toArray()];

            $farmosBaseUrl = config('farmos.url', 'https://farmos.example-farm.com');
            $usingFarmOSData = true;

            // Convert to paginated-like structure for view compatibility
            // Since it's a collection, we'll slice it manually for pagination
            $perPage = 50;
            $currentPage = $request->get('page', 1);
            $total = $harvestLogs->count();
            $offset = ($currentPage - 1) * $perPage;
            $paginatedHarvests = $harvestLogs->slice($offset, $perPage)->values();

            // Create a mock paginator-like object
            $harvestLogs = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedHarvests,
                $total,
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'pageName' => 'page']
            );

            return view('admin.farmos.harvests', compact(
                'harvestLogs', 
                'cropTypes', 
                'farmosBaseUrl', 
                'usingFarmOSData'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to fetch farmOS harvest logs: ' . $e->getMessage());
            
            // Fallback to local database
            $query = HarvestLog::with('stockItem');

            if ($request->filled('crop_type')) {
                $query->where('crop_type', $request->crop_type);
            }

            if ($request->filled('date_from')) {
                $query->where('harvest_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('harvest_date', '<=', $request->date_to);
            }

            $harvestLogs = $query->orderBy('harvest_date', 'desc')
                ->paginate(50);

            $cropTypes = HarvestLog::distinct()
                ->pluck('crop_type')
                ->filter()
                ->sort()
                ->values();

            $farmosBaseUrl = config('farmos.url', 'https://farmos.example-farm.com');
            $usingFarmOSData = false;

            return view('admin.farmos.harvests', compact(
                'harvestLogs', 
                'cropTypes', 
                'farmosBaseUrl', 
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Display stock management
     */
    public function stock(Request $request): View
    {
        try {
            // For now, stock management still uses local database since farmOS doesn't have a direct stock API
            // But we'll get crop types and locations from farmOS for consistency (direct DB - fast)
            $varieties = $this->farmOSQuery->getPlantVarieties(['active_only' => true]);
            $farmOSCropTypes = ['types' => $varieties->map(fn($v) => ['id' => $v->tid, 'name' => $v->name])->toArray()];
            $beds = $this->farmOSQuery->getBeds();
            $farmOSLocations = $beds->map(fn($b) => ['id' => $b->id, 'name' => $b->name])->toArray();

            $query = StockItem::query();

            if ($request->filled('crop_type')) {
                $query->where('crop_type', $request->crop_type);
            }

            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'in_stock':
                        $query->where('current_stock', '>', 0);
                        break;
                    case 'low_stock':
                        $query->whereRaw('current_stock <= minimum_stock AND current_stock > 0');
                        break;
                    case 'out_of_stock':
                        $query->where('current_stock', '<=', 0);
                        break;
                }
            }

            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('crop_type', 'like', "%{$search}%")
                      ->orWhere('variety', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $stockItems = $query->orderBy('crop_type')
                ->paginate(50);

            // Use farmOS data for dropdowns, fallback to local if needed
            $cropTypes = !empty($farmOSCropTypes) ? $farmOSCropTypes : 
                StockItem::distinct()->pluck('crop_type')->filter()->sort()->values();
            
            $locations = !empty($farmOSLocations) ? $farmOSLocations :
                StockItem::distinct()->pluck('storage_location')->filter()->sort()->values();

            // Calculate stock statistics
            $stockStats = [
                'total_items' => StockItem::count(),
                'items_in_stock' => StockItem::where('current_stock', '>', 0)->count(),
                'low_stock_items' => StockItem::whereRaw('current_stock <= minimum_stock AND current_stock > 0')->count(),
                'out_of_stock_items' => StockItem::where('current_stock', '<=', 0)->count(),
            ];

            $usingFarmOSData = !empty($farmOSCropTypes) && !empty($farmOSLocations);

            return view('admin.farmos.stock', compact(
                'stockItems', 
                'cropTypes', 
                'locations', 
                'stockStats',
                'usingFarmOSData'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to fetch farmOS data for stock management: ' . $e->getMessage());
            
            // Complete fallback to local database
            $query = StockItem::query();

            if ($request->filled('crop_type')) {
                $query->where('crop_type', $request->crop_type);
            }

            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'in_stock':
                        $query->where('current_stock', '>', 0);
                        break;
                    case 'low_stock':
                        $query->whereRaw('current_stock <= minimum_stock AND current_stock > 0');
                        break;
                    case 'out_of_stock':
                        $query->where('current_stock', '<=', 0);
                        break;
                }
            }

            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('crop_type', 'like', "%{$search}%")
                      ->orWhere('variety', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $stockItems = $query->orderBy('crop_type')
                ->paginate(50);

            $cropTypes = StockItem::distinct()
                ->pluck('crop_type')
                ->filter()
                ->sort()
                ->values();

            $locations = StockItem::distinct()
                ->pluck('storage_location')
                ->filter()
                ->sort()
                ->values();

            // Calculate stock statistics
            $stockStats = [
                'total_items' => StockItem::count(),
                'items_in_stock' => StockItem::where('current_stock', '>', 0)->count(),
                'low_stock_items' => StockItem::whereRaw('current_stock <= minimum_stock AND current_stock > 0')->count(),
                'out_of_stock_items' => StockItem::where('current_stock', '<=', 0)->count(),
            ];

            $usingFarmOSData = false;

            return view('admin.farmos.stock', compact(
                'stockItems', 
                'cropTypes', 
                'locations', 
                'stockStats', 
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Store a new stock item
     */
    public function storeStock(Request $request): JsonResponse
    {
        $request->validate([
            'crop_type' => 'required|string|max:255',
            'current_stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'quality_grade' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $stockItem = StockItem::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stock item created successfully',
            'data' => $stockItem
        ]);
    }

    /**
     * Display crop planning
     */
    public function cropPlans(Request $request): View
    {
        try {
            // Get real farmOS data (direct DB queries - fast)
            $farmOSCropPlans = $this->farmOSQuery->getPlantings()->toArray();
            $varieties = $this->farmOSQuery->getPlantVarieties(['active_only' => true]);
            $farmOSCropTypes = ['types' => $varieties->map(fn($v) => ['id' => $v->tid, 'name' => $v->name])->toArray()];
            $beds = $this->farmOSQuery->getBeds();
            $farmOSLocations = $beds->map(fn($b) => ['id' => $b->id, 'name' => $b->name])->toArray();

            // Convert farmOS data to collection for easier filtering
            $cropPlans = collect($farmOSCropPlans);

            // Apply filters
            if ($request->filled('crop_type')) {
                $cropPlans = $cropPlans->where('crop_type', $request->crop_type);
            }

            if ($request->filled('status')) {
                $cropPlans = $cropPlans->where('status', $request->status);
            }

            if ($request->filled('location')) {
                $cropPlans = $cropPlans->where('location', $request->location);
            }

            // Handle calendar format request
            if ($request->format === 'calendar') {
                $events = [];
                
                foreach ($cropPlans as $plan) {
                    if (!empty($plan['planned_harvest_start'])) {
                        $events[] = [
                            'title' => "Harvest Start: {$plan['crop_type']}",
                            'start' => $plan['planned_harvest_start'],
                            'color' => '#007bff',
                            'extendedProps' => ['type' => 'harvest_start', 'plan_id' => $plan['farmos_asset_id']]
                        ];
                    }
                    
                    if (!empty($plan['planned_transplant_date'])) {
                        $events[] = [
                            'title' => "Transplant: {$plan['crop_type']}",
                            'start' => $plan['planned_transplant_date'],
                            'color' => '#28a745',
                            'extendedProps' => ['type' => 'transplant', 'plan_id' => $plan['farmos_asset_id']]
                        ];
                    }
                    
                    if (!empty($plan['planned_harvest_end'])) {
                        $events[] = [
                            'title' => "Harvest: {$plan['crop_type']}",
                            'start' => $plan['planned_harvest_end'],
                            'color' => '#ffc107',
                            'extendedProps' => ['type' => 'harvest', 'plan_id' => $plan['farmos_asset_id']]
                        ];
                    }
                }
                
                return response()->json($events);
            }

            // Convert back to paginated collection-like structure for view compatibility
            // Create a proper paginator for the view
            $perPage = 50;
            $currentPage = $request->get('page', 1);
            $total = $cropPlans->count();
            $offset = ($currentPage - 1) * $perPage;
            $paginatedPlans = $cropPlans->slice($offset, $perPage)->values();

            // Create a mock paginator-like object
            $cropPlans = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedPlans,
                $total,
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'pageName' => 'page']
            );

            // Use farmOS data for dropdowns
            $cropTypes = $farmOSCropTypes;
            $locations = $farmOSLocations;

            // Calculate planning statistics from farmOS data (use original collection before pagination)
            $allPlans = collect($farmOSCropPlans);
            $planningStats = [
                'total_plans' => $allPlans->count(),
                'planned' => $allPlans->where('status', 'planned')->count(),
                'in_progress' => $allPlans->whereIn('status', ['seeded', 'transplanted', 'growing'])->count(),
                'completed' => $allPlans->where('status', 'completed')->count(),
                'cancelled' => $allPlans->where('status', 'cancelled')->count(),
                'overdue' => 0, // Would need date logic for farmOS data
            ];

            // Add farmOS data source indicator
            $hasTestData = false; // We're now using real farmOS data
            $usingFarmOSData = true;

            return view('admin.farmos.crop-plans', compact(
                'cropPlans', 
                'cropTypes', 
                'locations', 
                'planningStats',
                'hasTestData',
                'usingFarmOSData'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to fetch farmOS crop planning data: ' . $e->getMessage());
            
            // Fallback to local database if farmOS fails
            $query = CropPlan::query();

            if ($request->filled('season')) {
                $query->where('season', $request->season);
            }

            if ($request->filled('crop_type')) {
                $query->where('crop_type', $request->crop_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            if ($request->filled('sort')) {
                switch ($request->sort) {
                    case 'planned_harvest_start':
                        $query->orderBy('planned_harvest_start', 'asc');
                        break;
                    case 'expected_harvest_date':
                        $query->orderBy('expected_harvest_date', 'asc');
                        break;
                    default:
                        $query->orderBy('planned_harvest_start', 'asc');
                }
            } else {
                $query->orderBy('planned_harvest_start', 'asc');
            }

            // Handle calendar format request (fallback)
            if ($request->format === 'calendar') {
                $events = [];
                $plans = $query->get();
                
                foreach ($plans as $plan) {
                    if ($plan->planned_harvest_start) {
                        $events[] = [
                            'title' => "Harvest Start: {$plan->crop_type}",
                            'start' => $plan->planned_harvest_start->toDateString(),
                            'color' => '#007bff',
                            'extendedProps' => ['type' => 'harvest_start', 'plan_id' => $plan->id]
                        ];
                    }
                }
                
                return response()->json($events);
            }

            $cropPlans = $query->paginate(50);

            $cropTypes = CropPlan::distinct()->pluck('crop_type')->filter()->sort()->values();
            $locations = CropPlan::distinct()->pluck('location')->filter()->sort()->values();

            // Calculate planning statistics (fallback)
            $planningStats = [
                'total_plans' => CropPlan::count(),
                'planned' => CropPlan::where('status', 'planned')->count(),
                'in_progress' => CropPlan::whereIn('status', ['seeded', 'transplanted', 'growing'])->count(),
                'completed' => CropPlan::where('status', 'completed')->count(),
                'cancelled' => CropPlan::where('status', 'cancelled')->count(),
                'overdue' => CropPlan::where('planned_harvest_start', '<', now())
                                     ->whereNotIn('status', ['completed', 'cancelled'])
                                     ->count(),
            ];

            $hasTestData = true; // Using fallback local database
            $usingFarmOSData = false;

            return view('admin.farmos.crop-plans', compact(
                'cropPlans', 
                'cropTypes', 
                'locations', 
                'planningStats',
                'hasTestData',
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Store a new crop plan
     */
    public function storeCropPlan(Request $request): JsonResponse
    {
        $request->validate([
            'crop_type' => 'required|string|max:255',
            'season' => 'required|in:spring,summer,fall,winter',
            'year' => 'required|integer|min:' . date('Y') . '|max:' . (date('Y') + 2),
            'planned_seed_date' => 'nullable|date',
            'planned_transplant_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date',
            'expected_yield' => 'nullable|numeric|min:0',
            'yield_unit' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $cropPlan = CropPlan::create(array_merge($request->all(), [
            'status' => 'planned'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Crop plan created successfully',
            'data' => $cropPlan
        ]);
    }

    /**
     * Sync harvest logs from FarmOS
     */
    public function syncHarvests(): JsonResponse
    {
        try {
            $since = HarvestLog::max('updated_at') ?? Carbon::now()->subDays(30);
            // Direct DB query - fast
            $farmOSHarvests = $this->farmOSQuery->getHarvestLogs($since->format('Y-m-d'), null, ['with_quantities' => true]);

            $synced = 0;
            foreach ($farmOSHarvests as $harvestData) {
                $this->processHarvestLog($harvestData);
                $synced++;
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$synced} harvest logs from FarmOS",
                'synced_count' => $synced
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync harvest logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync harvest logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync plant varieties from FarmOS
     */
    public function syncVarieties(): JsonResponse
    {
        try {
            // Run the artisan command to sync varieties
            \Artisan::call('farmos:sync-varieties', ['--force' => true]);
            
            $output = \Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Plant varieties synced successfully from FarmOS',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync varieties: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync varieties: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a single harvest log from FarmOS
     */
    private function processHarvestLog($harvestData)
    {
        $attributes = $harvestData['attributes'] ?? [];
        $relationships = $harvestData['relationships'] ?? [];

        // Extract basic harvest info
        $harvestLog = HarvestLog::updateOrCreate(
            ['farmos_id' => $harvestData['id']],
            [
                'crop_name' => $this->extractCropName($harvestData),
                'crop_type' => $this->extractCropType($harvestData),
                'quantity' => $this->extractQuantity($harvestData),
                'units' => $this->extractUnits($harvestData),
                'harvest_date' => Carbon::parse($attributes['timestamp'] ?? now()),
                'location' => $this->extractLocation($harvestData),
                'notes' => $attributes['notes']['value'] ?? '',
                'status' => $attributes['status'] ?? 'done',
                'farmos_data' => $harvestData
            ]
        );

        // Auto-sync to stock if enabled
        if (!$harvestLog->synced_to_stock) {
            $this->syncHarvestToStock($harvestLog);
        }

        return $harvestLog;
    }

    /**
     * Sync harvest to stock items
     */
    public function syncHarvestToStock(HarvestLog $harvestLog): JsonResponse
    {
        try {
            // Find or create stock item
            $stockItem = StockItem::firstOrCreate(
                ['name' => $harvestLog->crop_name],
                [
                    'crop_type' => $harvestLog->crop_type,
                    'units' => $harvestLog->units,
                    'current_stock' => 0,
                    'reserved_stock' => 0,
                    'available_stock' => 0,
                    'is_active' => true,
                    'track_stock' => true,
                    'description' => "Auto-created from harvest log"
                ]
            );

            // Add harvest quantity to stock
            $stockItem->addHarvestStock($harvestLog->quantity, $harvestLog->harvest_date);

            // Mark harvest as synced
            $harvestLog->markAsSynced();

            return response()->json([
                'success' => true,
                'message' => "Added {$harvestLog->formatted_quantity} to {$stockItem->name} stock"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync harvest to stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync to stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract crop name from FarmOS data
     */
    private function extractCropName($harvestData)
    {
        $attributes = $harvestData['attributes'] ?? [];
        return $attributes['name'] ?? 'Unknown Crop';
    }

    /**
     * Extract crop type from FarmOS data
     */
    private function extractCropType($harvestData)
    {
        // This would need to be extracted from plant asset relationships
        return 'vegetable'; // Default for now
    }

    /**
     * Extract quantity from FarmOS data
     */
    private function extractQuantity($harvestData)
    {
        $relationships = $harvestData['relationships'] ?? [];
        $quantity = $relationships['quantity']['data'][0] ?? null;
        return $quantity['attributes']['value']['decimal'] ?? 0;
    }

    /**
     * Extract units from FarmOS data
     */
    private function extractUnits($harvestData)
    {
        $relationships = $harvestData['relationships'] ?? [];
        $units = $relationships['quantity']['data'][0]['relationships']['units']['data'] ?? null;
        return $units['attributes']['name'] ?? 'kg';
    }

    /**
     * Extract location from FarmOS data
     */
    private function extractLocation($harvestData)
    {
        $relationships = $harvestData['relationships'] ?? [];
        $location = $relationships['location']['data'][0] ?? null;
        return $location['attributes']['name'] ?? 'Unknown';
    }

    /**
     * Display the planting chart
     */
    public function plantingChart(Request $request)
    {
        try {
            // Get farmOS land assets using direct database query (much faster)
            $geometryAssets = $this->farmOSQuery->getGeometryAssets();
            
            // Get plant assets using direct database query (much faster)
            $cropPlans = $this->farmOSQuery->getPlantAssets();
            
            // Get crop types using direct database query (already optimized)
            $cropTypesData = $this->farmOSQuery->getPlantVarieties(['active_only' => true]);
            $cropTypes = $cropTypesData->pluck('name')->toArray();
            
            // Debug: Log the data we're getting
            Log::info('Planting Chart Debug - Geometry Assets Count: ' . $geometryAssets->count());
            Log::info('Planting Chart Debug - Crop Plans Count: ' . $cropPlans->count());
            Log::info('Planting Chart Debug - Crop Types: ', $cropTypes);
            
            // Transform land assets into chart data showing your actual blocks and beds
            $chartData = $this->transformGeometryAssetsToChart($geometryAssets, $cropPlans);
            $locations = $this->extractLocationsFromGeometryAssets($geometryAssets);
            
            // Debug: Log sample of chart data
            foreach (array_slice($chartData, 0, 3) as $location => $activities) {
                Log::info("Planting Chart - {$location}: " . count($activities) . " activities", [
                    'sample' => array_slice($activities, 0, 2)
                ]);
            }
            
            $usingFarmOSData = true;
            
            // Check if we have actual planting data (not just empty locations)
            $hasPlantingData = false;
            foreach ($chartData as $location => $plantings) {
                if (!empty($plantings)) {
                    $hasPlantingData = true;
                    break;
                }
            }
            
            // If we don't have good data or no planting data, use fallback
            if (empty($chartData) || $geometryAssets->count() < 5 || !$hasPlantingData) {
                throw new \Exception('Insufficient farmOS planting data, using fallback');
            }
            
            $usingFarmOSData = true;
            
            return view('admin.farmos.planting-chart', compact(
                'chartData',
                'locations',
                'cropTypes', 
                'usingFarmOSData'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load planting chart from controller: ' . $e->getMessage());
            
            // Don't use test data - pass empty data and let frontend fetch from API
            $locations = [];
            $cropTypes = [];
            $chartData = [];
            $usingFarmOSData = false;
            
            return view('admin.farmos.planting-chart', compact(
                'chartData',
                'locations',
                'cropTypes',
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Transform farmOS crop plans into planting chart format
     */
    private function transformPlantingChartData($cropPlans)
    {
        $chartData = [];
        
        foreach ($cropPlans as $plan) {
            $item = [
                'id' => $plan['farmos_asset_id'] ?? uniqid(),
                'crop_type' => $plan['crop_type'] ?? 'Unknown',
                'variety' => $plan['variety'] ?? '',
                'location' => $plan['location'] ?? 'Unknown',
                'status' => $plan['status'] ?? 'planned',
                'planned_seeding_date' => $plan['planned_seeding_date'] ?? null,
                'planned_transplant_date' => $plan['planned_transplant_date'] ?? null,
                'planned_harvest_start' => $plan['planned_harvest_start'] ?? null,
                'planned_harvest_end' => $plan['planned_harvest_end'] ?? null,
                'notes' => $plan['notes'] ?? '',
                'source' => 'farmOS'
            ];
            
            $chartData[] = $item;
        }
        
        return $chartData;
    }

    /**
     * Get fallback planting data for testing - returns realistic test data with 16 beds per block
     */
    private function getFallbackPlantingData()
    {
        $chartData = [];
        $crops = ['lettuce', 'tomato', 'carrot', 'cabbage', 'potato', 'spinach', 'kale', 'broccoli'];
        $varieties = [
            'lettuce' => ['Butter Lettuce', 'Romaine', 'Iceberg', 'Red Leaf'],
            'tomato' => ['Cherry Tomato', 'Beefsteak', 'Roma', 'Heirloom'],
            'carrot' => ['Nantes', 'Chantenay', 'Purple Haze', 'Baby Carrot'],
            'cabbage' => ['Green Cabbage', 'Red Cabbage', 'Savoy', 'Napa'],
            'potato' => ['Russet', 'Red Potato', 'Yukon Gold', 'Fingerling'],
            'spinach' => ['Baby Spinach', 'Giant Noble', 'Space', 'Bloomsdale'],
            'kale' => ['Curly Kale', 'Lacinato', 'Red Russian', 'Winterbor'],
            'broccoli' => ['Calabrese', 'Purple Sprouting', 'Romanesco', 'Broccolini']
        ];
        
        // Create data for 10 blocks, each with 16 beds
        for ($blockNum = 1; $blockNum <= 10; $blockNum++) {
            // Add 16 beds per block as separate entries
            for ($bedNum = 1; $bedNum <= 16; $bedNum++) {
                $bedName = "$blockNum/$bedNum";
                $chartData[$bedName] = []; // Create separate entry for each bed
                
                // Deterministic chance based on bed position (about 60% of beds have activities)
                if (($blockNum * $bedNum * 7) % 100 <= 60) {
                    $cropIndex = ($blockNum + $bedNum) % count($crops);
                    $crop = $crops[$cropIndex];
                    $varietyIndex = ($blockNum * $bedNum) % count($varieties[$crop]);
                    $variety = $varieties[$crop][$varietyIndex];
                    
                    // Deterministic timing based on bed position
                    $seedingOffset = (($blockNum * $bedNum * 17) % 360) - 180; // +/- 6 months from now
                    $transplantOffset = $seedingOffset + 14 + (($blockNum + $bedNum) % 14); // 14-28 days after seeding
                    $harvestStartOffset = $transplantOffset + 28 + (($blockNum * $bedNum) % 56); // 28-84 days after transplant
                    $harvestEndOffset = $harvestStartOffset + 7 + (($blockNum + $bedNum * 3) % 21); // 7-28 days harvest period
                    
                    $seedingStart = date('Y-m-d', strtotime("$seedingOffset days"));
                    $seedingEnd = date('Y-m-d', strtotime("$transplantOffset days"));
                    $growingStart = $seedingEnd;
                    $growingEnd = date('Y-m-d', strtotime("$harvestStartOffset days"));
                    $harvestStart = $growingEnd;
                    $harvestEnd = date('Y-m-d', strtotime("$harvestEndOffset days"));
                    
                    // Create seeding activity
                    $chartData[$bedName][] = [
                        'id' => "seeding_{$crop}_{$blockNum}_{$bedNum}",
                        'type' => 'seeding',
                        'crop' => $crop,
                        'variety' => $variety,
                        'location' => $bedName,
                        'start' => $seedingStart,
                        'end' => $seedingEnd,
                        'status' => 'completed',
                        'notes' => "Demo seeding: $variety in $bedName",
                        'source' => 'fallback'
                    ];
                    
                    // Create growing activity
                    $chartData[$bedName][] = [
                        'id' => "growing_{$crop}_{$blockNum}_{$bedNum}",
                        'type' => 'growing',
                        'crop' => $crop,
                        'variety' => $variety,
                        'location' => $bedName,
                        'start' => $growingStart,
                        'end' => $growingEnd,
                        'status' => ($blockNum * $bedNum * 11) % 100 <= 70 ? 'active' : 'planned',
                        'notes' => "Demo growing: $variety in $bedName",
                        'source' => 'fallback'
                    ];
                    
                    // Create harvest activity
                    $chartData[$bedName][] = [
                        'id' => "harvest_{$crop}_{$blockNum}_{$bedNum}",
                        'type' => 'harvest',
                        'crop' => $crop,
                        'variety' => $variety,
                        'location' => $bedName,
                        'start' => $harvestStart,
                        'end' => $harvestEnd,
                        'status' => 'planned',
                        'notes' => "Demo harvest: $variety in $bedName",
                        'source' => 'fallback'
                    ];
                }
            }
        }
        
        return $chartData;
    }
    
    /**
     * Transform land assets (blocks/beds) into planting chart timeline format
     */
    private function transformGeometryAssetsToChart($geometryAssets, $cropPlans = [])
    {
        $chartData = [];
        
        // Group assets by location (blocks and beds)
        $locationGroups = [];
        
        foreach ($geometryAssets as $asset) {
            $name = $asset['name'] ?? 'Unnamed';
            $landType = $asset['land_type'] ?? 'field';
            
            // Skip property-level assets, focus on blocks and beds
            if ($landType === 'property') {
                continue;
            }
            
            // Create timeline activities for this asset
            $activities = $this->generateActivitiesForGeometryAsset($asset, $cropPlans);
            
            if (!empty($activities)) {
                $chartData[$name] = $activities;
            } else {
                // No activities for this asset - will show as empty on timeline
                $chartData[$name] = [];
            }
        }
        
        return $chartData;
    }
    
    /**
     * Extract location names from geometry assets
     */
    private function extractLocationsFromGeometryAssets($geometryAssets)
    {
        $locations = [];
        
        foreach ($geometryAssets as $asset) {
            $name = $asset['name'] ?? null;
            $landType = $asset['land_type'] ?? 'field';
            
            // Skip property-level assets
            if ($landType === 'property' || !$name) {
                continue;
            }
            
            if (!in_array($name, $locations)) {
                $locations[] = $name;
            }
        }
        
        // Sort naturally (Block 1, Block 2, etc.)
        usort($locations, function($a, $b) {
            return strnatcmp($a, $b);
        });
        
        return $locations;
    }
    
    /**
     * Generate timeline activities for a specific geometry asset by reading farmOS logs
     */
    private function generateActivitiesForGeometryAsset($asset, $cropPlans)
    {
        $activities = [];
        $assetId = $asset['id'] ?? null;
        $assetName = $asset['name'] ?? 'Unnamed';
        
        if (!$assetId) {
            return $activities;
        }
        
        try {
            // Query farmOS database directly for logs related to this location
            // Get seeding, transplanting, and harvest logs for plantings in this bed
            $hasLogStatus = Schema::connection('farmos')->hasTable('log__status');
            $hasMaturityDays = Schema::connection('farmos')->hasTable('taxonomy_term__maturity_days');
            $hasHarvestWindowDays = Schema::connection('farmos')->hasTable('taxonomy_term__harvest_window_days');
            $hasHarvestDays = Schema::connection('farmos')->hasTable('taxonomy_term__harvest_days');

            $query = DB::connection('farmos')
                ->table('log_field_data as l')
                ->join('log__location as ll', 'l.id', '=', 'll.entity_id')
                ->leftJoin('log__asset as la', 'l.id', '=', 'la.entity_id')
                ->leftJoin('asset_field_data as a', 'la.asset_target_id', '=', 'a.id')
                ->leftJoin('asset__plant_type as apt', 'a.id', '=', 'apt.entity_id')
                ->leftJoin('taxonomy_term_field_data as t', 'apt.plant_type_target_id', '=', 't.tid');

            if ($hasLogStatus) {
                $query->leftJoin('log__status as ls', 'l.id', '=', 'ls.entity_id');
            }

            if ($hasMaturityDays) {
                $query->leftJoin('taxonomy_term__maturity_days as md', 't.tid', '=', 'md.entity_id');
            }

            if ($hasHarvestWindowDays) {
                $query->leftJoin('taxonomy_term__harvest_window_days as hw', 't.tid', '=', 'hw.entity_id');
            } elseif ($hasHarvestDays) {
                $query->leftJoin('taxonomy_term__harvest_days as hd', 't.tid', '=', 'hd.entity_id');
            }

            $selects = [
                'l.id as log_id',
                'l.type as log_type',
                'l.timestamp',
                'a.id as plant_id',
                'a.name as plant_name',
                't.name as variety',
            ];

            $selects[] = $hasMaturityDays
                ? 'md.maturity_days_value as maturity_days'
                : DB::raw('NULL as maturity_days');

            if ($hasHarvestWindowDays) {
                $selects[] = 'hw.harvest_window_days_value as harvest_window_days';
            } elseif ($hasHarvestDays) {
                $selects[] = 'hd.harvest_days_value as harvest_window_days';
            } else {
                $selects[] = DB::raw('NULL as harvest_window_days');
            }

            if ($hasLogStatus) {
                $selects[] = 'ls.status_value as log_status';
            }

            $query
                ->where('ll.location_target_id', $assetId)
                ->where('l.status', 1)
                ->whereIn('l.type', ['seeding', 'transplanting', 'harvest']);

            if ($hasLogStatus) {
                $query->whereIn('ls.status_value', ['done', 'planned', 'in_progress', 'active']);
            }

            $logs = $query
                ->select($selects)
                ->orderBy('l.timestamp', 'asc')
                ->get();
            
            Log::info("Activity generation for {$assetName}", [
                'asset_id' => $assetId,
                'logs_found' => $logs->count()
            ]);
            
            // Group logs by plant asset to create full timeline
            $plantTimelines = [];
            foreach ($logs as $log) {
                $plantId = $log->plant_id ?? 'unknown';
                if (!isset($plantTimelines[$plantId])) {
                    $plantTimelines[$plantId] = [
                        'name' => $log->plant_name ?? 'Unknown Plant',
                        'variety' => $log->variety ?? 'Unknown',
                        'maturity_days' => $log->maturity_days ?? 60,
                        'harvest_window_days' => $log->harvest_window_days ?? 14,
                        'seeding' => null,
                        'transplanting' => null,
                        'harvest' => null,
                    ];
                }
                
                $plantTimelines[$plantId][$log->log_type] = $log->timestamp;
            }
            
            // Convert plant timelines to activities
            foreach ($plantTimelines as $plantId => $timeline) {
                $cropName = $timeline['variety'] ?? $timeline['name'];
                
                if ($timeline['seeding']) {
                    $seedingDate = date('Y-m-d', $timeline['seeding']);
                    
                    // Calculate full timeline from seeding date
                    $transplantDate = $timeline['transplanting'] 
                        ? date('Y-m-d', $timeline['transplanting'])
                        : date('Y-m-d', strtotime($seedingDate . ' +21 days')); // Default 3 weeks
                    
                    $harvestStartDate = $timeline['harvest'] 
                        ? date('Y-m-d', $timeline['harvest'])
                        : date('Y-m-d', strtotime($seedingDate . ' +' . $timeline['maturity_days'] . ' days'));
                    
                    $harvestEndDate = date('Y-m-d', strtotime($harvestStartDate . ' +' . $timeline['harvest_window_days'] . ' days'));
                    
                    Log::info("Creating timeline for {$cropName}", [
                        'seeding' => $seedingDate,
                        'transplant' => $transplantDate,
                        'harvest_start' => $harvestStartDate,
                        'harvest_end' => $harvestEndDate,
                        'maturity_days' => $timeline['maturity_days']
                    ]);
                    
                    // Create seeding activity (green)
                    $activities[] = [
                        'id' => 'seeding_' . $plantId,
                        'type' => 'seeding',
                        'crop' => $cropName,
                        'variety' => $timeline['variety'],
                        'start' => $seedingDate,
                        'end' => $transplantDate,
                        'status' => 'done'
                    ];
                    
                    // Create growing activity (blue)
                    $activities[] = [
                        'id' => 'growing_' . $plantId,
                        'type' => 'growing',
                        'crop' => $cropName,
                        'variety' => $timeline['variety'],
                        'start' => $transplantDate,
                        'end' => $harvestStartDate,
                        'status' => 'active'
                    ];
                    
                    // Create harvest activity (yellow)
                    $activities[] = [
                        'id' => 'harvest_' . $plantId,
                        'type' => 'harvest',
                        'crop' => $cropName,
                        'variety' => $timeline['variety'],
                        'start' => $harvestStartDate,
                        'end' => $harvestEndDate,
                        'status' => 'planned'
                    ];
                }
            }

            // Also check for planned crop plans (plant assets) that don't have logs yet
            // These are 2026+ plans that exist as assets but haven't been planted
            Log::info("About to check crop plans for {$assetName}", ['count' => count($cropPlans)]);
            foreach ($cropPlans as $plan) {
                $planName = $plan['variety'] ?? '';
                Log::info("Checking plan: {$planName} against bed: {$assetName}");
                
                // Check if this plan's name contains the current bed name (e.g., "B1/1" in "2026 Season B1/1 Carrot")
                if (strpos($planName, $assetName) !== false) {
                    Log::info("MATCH FOUND: {$planName} contains {$assetName}");
                    // This plan is for this bed - create a planned activity
                    $plannedSeedingDate = date('Y-m-d'); // Default to today if no date specified
                    $maturityDays = 60; // Default maturity period
                    $harvestWindowDays = 14; // Default harvest window
                    
                    // Try to extract dates from the plan name or use defaults
                    $bedOffset = 0; // Initialize bed offset
                    if (preg_match('/(\d{4})/', $planName, $yearMatch)) {
                        $year = $yearMatch[1];
                        
                        // Extract bed number to create staggered planting (succession planting)
                        if (preg_match('/B\d+\/(\d+)/', $assetName, $bedMatch)) {
                            $bedOffset = intval($bedMatch[1]) - 1; // 0-based offset
                            Log::info("Bed match for {$assetName}: captured '{$bedMatch[1]}', offset = {$bedOffset}");
                        } else {
                            Log::info("No bed match for {$assetName}");
                        }
                        
                        // Stagger planting by bed number (every 3 days for succession)
                        $staggerDays = $bedOffset * 3;
                        Log::info("Stagger calculation for {$assetName}: offset={$bedOffset}, staggerDays={$staggerDays}");
                        $plannedSeedingDate = date('Y-m-d', strtotime($year . '-04-01 +' . $staggerDays . ' days'));
                        Log::info("Calculated date for {$assetName}: {$plannedSeedingDate}");
                    }
                    
                    $harvestStartDate = date('Y-m-d', strtotime($plannedSeedingDate . ' +' . $maturityDays . ' days'));
                    $harvestEndDate = date('Y-m-d', strtotime($harvestStartDate . ' +' . $harvestWindowDays . ' days'));
                    
                    Log::info("Creating staggered planned timeline for {$planName} in {$assetName}", [
                        'seeding' => $plannedSeedingDate,
                        'harvest_start' => $harvestStartDate,
                        'harvest_end' => $harvestEndDate,
                        'bed_offset' => $bedOffset
                    ]);
                    
                    // Create planned seeding activity (light green)
                    $activities[] = [
                        'id' => 'planned_seeding_' . $plan['farmos_asset_id'],
                        'type' => 'seeding',
                        'crop' => $planName,
                        'variety' => $planName,
                        'start' => $plannedSeedingDate,
                        'end' => date('Y-m-d', strtotime($plannedSeedingDate . ' +21 days')),
                        'status' => 'planned'
                    ];
                    
                    // Create planned growing activity (light blue)
                    $activities[] = [
                        'id' => 'planned_growing_' . $plan['farmos_asset_id'],
                        'type' => 'growing',
                        'crop' => $planName,
                        'variety' => $planName,
                        'start' => date('Y-m-d', strtotime($plannedSeedingDate . ' +21 days')),
                        'end' => $harvestStartDate,
                        'status' => 'planned'
                    ];
                    
                    // Create planned harvest activity (light yellow)
                    $activities[] = [
                        'id' => 'planned_harvest_' . $plan['farmos_asset_id'],
                        'type' => 'harvest',
                        'crop' => $planName,
                        'variety' => $planName,
                        'start' => $harvestStartDate,
                        'end' => $harvestEndDate,
                        'status' => 'planned'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate activities for asset: ' . $e->getMessage(), [
                'asset_id' => $assetId,
                'asset_name' => $assetName
            ]);
        }
        
        return $activities;
    }

    /**
     * Proxy variety images from FarmOS with authentication
     */
    public function proxyVarietyImage(string $fileId)
    {
        try {
            Log::info('📸 Proxying variety image', ['file_id' => $fileId]);
            
            // Get the image from FarmOS API
            $imageResponse = $this->farmOSApi->getFileById($fileId);
            
            if (!$imageResponse) {
                Log::warning('⚠️ No image data returned from FarmOS', ['file_id' => $fileId]);
                return response()->file(public_path('images/no-variety-image.png'));
            }
            
            // Return the image with proper headers
            return response($imageResponse['content'])
                ->header('Content-Type', $imageResponse['mime_type'] ?? 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=86400'); // Cache for 24 hours
                
        } catch (\Exception $e) {
            Log::error('Failed to proxy variety image: ' . $e->getMessage(), [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            
            // Return a placeholder image
            return response()->file(public_path('images/no-variety-image.png'));
        }
    }
}
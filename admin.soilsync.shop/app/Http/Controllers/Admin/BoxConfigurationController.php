<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoxConfiguration;
use App\Models\BoxConfigurationItem;
use App\Models\VegboxPlan;
use App\Models\PlantVariety;
use App\Services\FarmOSApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BoxConfigurationController extends Controller
{
    protected FarmOSApi $farmOSApi;

    public function __construct(FarmOSApi $farmOSApi)
    {
        $this->farmOSApi = $farmOSApi;
    }

    /**
     * List all box configurations
     */
    public function index(Request $request)
    {
        $configurations = BoxConfiguration::with(['plan', 'items.plantVariety'])
            ->upcoming()
            ->get()
            ->groupBy(function ($config) {
                return Carbon::parse($config->week_starting)->format('W - Y'); // Week number and year
            });

        $plans = VegboxPlan::active()->get();

        return view('admin.box-configurations.index', compact('configurations', 'plans'));
    }

    /**
     * Show configuration for a specific week/plan
     */
    public function show($id)
    {
        $configuration = BoxConfiguration::with(['plan', 'items.plantVariety', 'items.product'])
            ->findOrFail($id);

        $allocationSummary = $configuration->getAllocationSummary();

        return view('admin.box-configurations.show', compact('configuration', 'allocationSummary'));
    }

    /**
     * Create new week configuration
     */
    public function create(Request $request)
    {
        $weekStart = $request->week 
            ? Carbon::parse($request->week)->startOfWeek()
            : Carbon::now()->startOfWeek();

        // Get all active vegbox plans to create tabs
        $plans = VegboxPlan::active()->orderBy('default_tokens')->get();
        
        // Get active products with prices (excluding vegbox subscription products themselves)
        $products = \App\Models\Product::where('is_active', true)
            ->whereNotIn('id', [1, 2, 3, 4]) // Exclude the 4 vegbox products themselves
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return view('admin.box-configurations.create', compact('weekStart', 'plans', 'products'));
    }

    /**
     * Store new configuration
     */
    public function store(Request $request)
    {
        \Log::info('BoxConfiguration store attempt', [
            'all_data' => $request->all(),
            'has_items' => $request->has('items'),
            'items_count' => $request->has('items') ? count($request->input('items')) : 0
        ]);

        $validated = $request->validate([
            'week_starting' => 'required|date',
            'plan_id' => 'required|exists:vegbox_plans,id',
            'admin_notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        \Log::info('BoxConfiguration validation passed', [
            'validated' => $validated
        ]);

        DB::beginTransaction();
        try {
            \Log::info('Starting transaction');
            
            $plan = VegboxPlan::findOrFail($validated['plan_id']);
            \Log::info('Found plan', ['plan_id' => $plan->id, 'plan_name' => $plan->name]);
            
            // Check if configuration already exists for this week and plan
            $existingConfig = BoxConfiguration::where('week_starting', Carbon::parse($validated['week_starting'])->startOfWeek())
                ->where('plan_id', $validated['plan_id'])
                ->first();
            
            if ($existingConfig) {
                \Log::info('Configuration already exists, updating instead', ['existing_id' => $existingConfig->id]);
                $configuration = $existingConfig;
                $configuration->update([
                    'admin_notes' => $validated['admin_notes'] ?? null,
                    'is_active' => true,
                ]);
                // Delete existing items to replace with new ones
                $configuration->items()->delete();
            } else {
                $configuration = BoxConfiguration::create([
                    'week_starting' => Carbon::parse($validated['week_starting'])->startOfWeek(),
                    'plan_id' => $validated['plan_id'],
                    'admin_notes' => $validated['admin_notes'] ?? null,
                    'is_active' => true,
                ]);
            }
            
            \Log::info('Configuration ready', ['config_id' => $configuration->id]);

            // Add products to configuration
            if (!empty($validated['items'])) {
                \Log::info('Adding items', ['items_count' => count($validated['items'])]);
                
                foreach ($validated['items'] as $index => $itemData) {
                    $product = \App\Models\Product::find($itemData['product_id']);
                    \Log::info('Adding product', ['product_id' => $product->id, 'name' => $product->name]);
                    
                    BoxConfigurationItem::create([
                        'box_configuration_id' => $configuration->id,
                        'product_id' => $itemData['product_id'],
                        'item_name' => $product->name,
                        'quantity' => $itemData['quantity'],
                        'price_at_time' => $itemData['price'],
                        'unit' => $product->unit ?? 'item',
                        'sort_order' => $index,
                    ]);
                }
                \Log::info('All items added');
            }

            DB::commit();
            \Log::info('Transaction committed');

            \Log::info('BoxConfiguration created successfully', [
                'configuration_id' => $configuration->id,
                'plan_id' => $plan->id,
                'items_count' => count($validated['items'] ?? [])
            ]);

            $planName = is_array($plan->name) ? ($plan->name['en'] ?? 'Box') : $plan->name;

            $action = $existingConfig ? 'updated' : 'created';

            return redirect()->route('admin.box-configurations.index')
                           ->with('success', "Box configuration for {$planName} {$action} successfully for week starting " . 
                                  Carbon::parse($validated['week_starting'])->format('M d, Y'));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('BoxConfiguration creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Failed to create configuration: ' . $e->getMessage());
        }
    }

    /**
     * Update configuration
     */

    /**
     * Edit configuration
     */
    public function edit($id)
    {
        $configuration = BoxConfiguration::with(['plan', 'items'])
            ->findOrFail($id);

        $plans = VegboxPlan::active()->get();
        
        // Get products grouped by category (same as create method)
        $products = \App\Models\Product::where('is_active', true)
            ->whereNotIn('id', [1, 2, 3, 4]) // Exclude vegbox products
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return view('admin.box-configurations.edit', compact('configuration', 'plans', 'products'));
    }

    /**
     * Update configuration
     */
    public function update(Request $request, $id)
    {
        \Log::info('BoxConfiguration update attempt', [
            'id' => $id,
            'all_data' => $request->all(),
            'has_items' => $request->has('items'),
            'items_count' => $request->has('items') ? count($request->input('items')) : 0
        ]);

        $configuration = BoxConfiguration::findOrFail($id);

        $validated = $request->validate([
            'is_active' => 'boolean',
            'admin_notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:box_configuration_items,id',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.quantity_available' => 'nullable|integer|min:0',
            'items.*.unit' => 'nullable|string',
            'items.*.plant_variety_id' => 'nullable|exists:plant_varieties,id',
            'items.*.is_featured' => 'boolean',
        ]);

        \Log::info('BoxConfiguration update validation passed', [
            'validated' => $validated
        ]);

        DB::beginTransaction();
        try {
            $configuration->update([
                'is_active' => $request->has('is_active'),
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            // Handle items
            if (isset($validated['items'])) {
                \Log::info('Processing items', ['count' => count($validated['items'])]);
                $itemIds = [];
                foreach ($validated['items'] as $index => $itemData) {
                    \Log::info('Processing item', ['index' => $index, 'has_id' => isset($itemData['id']), 'data' => $itemData]);
                    if (isset($itemData['id'])) {
                        // Update existing
                        $item = BoxConfigurationItem::find($itemData['id']);
                        \Log::info('Updating existing item', ['id' => $itemData['id'], 'item_exists' => $item ? true : false]);
                        $item->update([
                            'item_name' => $itemData['item_name'],
                            'quantity' => $itemData['quantity'],
                            'quantity_available' => $itemData['quantity_available'] ?? null,
                            'unit' => $itemData['unit'] ?? 'item',
                            'plant_variety_id' => $itemData['plant_variety_id'] ?? null,
                            'is_featured' => $itemData['is_featured'] ?? false,
                            'sort_order' => $index,
                        ]);
                        $itemIds[] = $item->id;
                    } else {
                        // Create new
                        \Log::info('Creating new item', ['product_id' => $itemData['product_id'] ?? null]);
                        $item = $configuration->items()->create([
                            'product_id' => $itemData['product_id'] ?? null,
                            'item_name' => $itemData['item_name'],
                            'quantity' => $itemData['quantity'],
                            'quantity_available' => $itemData['quantity_available'] ?? null,
                            'unit' => $itemData['unit'] ?? 'item',
                            'plant_variety_id' => $itemData['plant_variety_id'] ?? null,
                            'is_featured' => $itemData['is_featured'] ?? false,
                            'sort_order' => $index,
                        ]);
                        $itemIds[] = $item->id;
                    }
                }

                // Delete removed items
                $configuration->items()->whereNotIn('id', $itemIds)->delete();
            }

            DB::commit();

            return redirect()
                ->route('admin.box-configurations.show', $configuration)
                ->with('success', 'Box configuration updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update configuration: ' . $e->getMessage());
        }
    }

    /**
     * Delete configuration
     */
    public function destroy($id)
    {
        try {
            $configuration = BoxConfiguration::findOrFail($id);
            $planName = is_array($configuration->plan->name) 
                ? ($configuration->plan->name['en'] ?? 'Box') 
                : $configuration->plan->name;
            
            // Delete the configuration (items will cascade delete)
            $configuration->delete();
            
            return redirect()
                ->route('admin.box-configurations.index')
                ->with('success', "Box configuration for {$planName} deleted successfully");
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete configuration: ' . $e->getMessage());
        }
    }

    /**
     * Import harvest data from FarmOS
     */
    public function importHarvests(Request $request, $id)
    {
        $configuration = BoxConfiguration::findOrFail($id);

        try {
            // Get recent harvest logs from FarmOS
            $harvests = $this->farmOSApi->getHarvestLogs([
                'timestamp' => [
                    'start' => $configuration->week_starting->subDays(7)->timestamp,
                    'end' => $configuration->week_starting->addDays(7)->timestamp,
                ],
            ]);

            $imported = 0;
            foreach ($harvests as $harvest) {
                // Try to match to plant variety
                $variety = PlantVariety::where('farmos_id', $harvest['plant_id'] ?? null)->first();

                // Create item if doesn't exist
                $exists = $configuration->items()
                    ->where('farmos_harvest_id', $harvest['id'])
                    ->exists();

                if (!$exists) {
                    $configuration->items()->create([
                        'item_name' => $harvest['crop_name'] ?? 'Unknown',
                        'description' => $harvest['notes'] ?? null,
                        'quantity_available' => $harvest['quantity'] ?? null,
                        'unit' => $harvest['unit'] ?? 'item',
                        'plant_variety_id' => $variety->id ?? null,
                        'farmos_harvest_id' => $harvest['id'],
                    ]);
                    $imported++;
                }
            }

            return back()->with('success', "Imported {$imported} harvest items from FarmOS");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to import harvests: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate configuration to another week
     */
    public function duplicate(Request $request, $id)
    {
        $source = BoxConfiguration::with('items')->findOrFail($id);

        $validated = $request->validate([
            'week_starting' => 'required|date|after:today',
        ]);

        DB::beginTransaction();
        try {
            $newConfig = $source->replicate();
            $newConfig->week_starting = Carbon::parse($validated['week_starting'])->startOfWeek();
            $newConfig->save();

            foreach ($source->items as $item) {
                $newItem = $item->replicate();
                $newItem->box_configuration_id = $newConfig->id;
                $newItem->quantity_allocated = 0; // Reset allocations
                $newItem->save();
            }

            DB::commit();

            return redirect()
                ->route('admin.box-configurations.show', $newConfig)
                ->with('success', 'Configuration duplicated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to duplicate: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate all configurations from a week to the next week
     */
    public function duplicateWeek($week)
    {
        // Parse the week key (format: "W - Y")
        [$weekNum, $year] = explode(' - ', $week);
        
        // Get the start date of the current week
        $currentWeekStart = Carbon::now()->setISODate($year, $weekNum)->startOfWeek();
        
        // Next week start
        $nextWeekStart = $currentWeekStart->copy()->addWeek();
        
        // Check if next week already has configurations
        $existingNextWeek = BoxConfiguration::where('week_starting', $nextWeekStart)->exists();
        if ($existingNextWeek) {
            return response()->json(['error' => 'Next week already has configurations'], 400);
        }
        
        // Get all configurations for the current week
        $currentConfigs = BoxConfiguration::with('items')
            ->where('week_starting', $currentWeekStart)
            ->get();
        
        if ($currentConfigs->isEmpty()) {
            return response()->json(['error' => 'No configurations found for this week'], 404);
        }
        
        DB::beginTransaction();
        try {
            foreach ($currentConfigs as $config) {
                $newConfig = $config->replicate();
                $newConfig->week_starting = $nextWeekStart;
                $newConfig->save();
                
                foreach ($config->items as $item) {
                    $newItem = $item->replicate();
                    $newItem->box_configuration_id = $newConfig->id;
                    $newItem->quantity_allocated = 0; // Reset allocations
                    $newItem->save();
                }
            }
            
            DB::commit();
            
            return response()->json(['success' => 'Week duplicated successfully']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to duplicate week: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete all configurations for a specific week
     */
    public function deleteWeek($week)
    {
        try {
            // Parse the week string (format: "W - Y")
            $parts = explode(' - ', $week);
            if (count($parts) !== 2) {
                return response()->json(['error' => 'Invalid week format'], 400);
            }
            
            $weekNumber = (int) $parts[0];
            $year = (int) $parts[1];
            
            // Find the start date of the week
            $weekStart = Carbon::now()->setISODate($year, $weekNumber)->startOfWeek();
            
            // Get all configurations for this week
            $configs = BoxConfiguration::where('week_starting', $weekStart)->get();
            
            if ($configs->isEmpty()) {
                return response()->json(['error' => 'No configurations found for this week'], 404);
            }
            
            DB::beginTransaction();
            try {
                foreach ($configs as $config) {
                    // Delete associated items first
                    $config->items()->delete();
                    // Delete the configuration
                    $config->delete();
                }
                
                DB::commit();
                
                return response()->json(['success' => 'Week deleted successfully']);
                
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to delete week: ' . $e->getMessage()], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid week parameter'], 400);
        }
    }
}

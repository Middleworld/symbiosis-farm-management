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
        $configurations = BoxConfiguration::with(['plan', 'items'])
            ->when($request->week, function ($query, $week) {
                $query->where('week_starting', Carbon::parse($week)->startOfWeek());
            })
            ->upcoming()
            ->paginate(20);

        $plans = VegboxPlan::active()->get();

        return view('admin.box-configurations.index', compact('configurations', 'plans'));
    }

    /**
     * Show configuration for a specific week/plan
     */
    public function show($id)
    {
        $configuration = BoxConfiguration::with(['plan', 'items.plantVariety'])
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

        $plans = VegboxPlan::active()->get();
        
        // Get active products (excluding vegbox subscription products)
        $products = \App\Models\Product::where('is_active', true)
            ->whereNotIn('id', [226084, 226083, 226081, 226082]) // Exclude vegbox subscription products
            ->orderBy('name')
            ->get();

        return view('admin.box-configurations.create', compact('weekStart', 'plans', 'products'));
    }

    /**
     * Store new configuration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'week_starting' => 'required|date',
            'plan_id' => 'required|exists:vegbox_plans,id',
            'default_tokens' => 'required|integer|min:1|max:50',
            'admin_notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required|string',
            'items.*.token_value' => 'required|integer|min:1|max:10',
            'items.*.quantity_available' => 'nullable|integer|min:0',
            'items.*.unit' => 'nullable|string',
            'items.*.plant_variety_id' => 'nullable|exists:plant_varieties,id',
        ]);

        DB::beginTransaction();
        try {
            $configuration = BoxConfiguration::create([
                'week_starting' => Carbon::parse($validated['week_starting'])->startOfWeek(),
                'plan_id' => $validated['plan_id'],
                'default_tokens' => $validated['default_tokens'],
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $index => $itemData) {
                    $configuration->items()->create([
                        'item_name' => $itemData['item_name'],
                        'token_value' => $itemData['token_value'],
                        'quantity_available' => $itemData['quantity_available'] ?? null,
                        'unit' => $itemData['unit'] ?? 'item',
                        'plant_variety_id' => $itemData['plant_variety_id'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.box-configurations.show', $configuration)
                ->with('success', 'Box configuration created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create configuration: ' . $e->getMessage());
        }
    }

    /**
     * Edit configuration
     */
    public function edit($id)
    {
        $configuration = BoxConfiguration::with(['plan', 'items'])
            ->findOrFail($id);

        $plans = VegboxPlan::active()->get();
        $varieties = PlantVariety::orderBy('common_name')->get();

        return view('admin.box-configurations.edit', compact('configuration', 'plans', 'varieties'));
    }

    /**
     * Update configuration
     */
    public function update(Request $request, $id)
    {
        $configuration = BoxConfiguration::findOrFail($id);

        $validated = $request->validate([
            'default_tokens' => 'required|integer|min:1|max:50',
            'is_active' => 'boolean',
            'admin_notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:box_configuration_items,id',
            'items.*.item_name' => 'required|string',
            'items.*.token_value' => 'required|integer|min:1|max:10',
            'items.*.quantity_available' => 'nullable|integer|min:0',
            'items.*.unit' => 'nullable|string',
            'items.*.plant_variety_id' => 'nullable|exists:plant_varieties,id',
            'items.*.is_featured' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $configuration->update([
                'default_tokens' => $validated['default_tokens'],
                'is_active' => $request->has('is_active'),
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            // Handle items
            if (isset($validated['items'])) {
                $itemIds = [];
                foreach ($validated['items'] as $index => $itemData) {
                    if (isset($itemData['id'])) {
                        // Update existing
                        $item = BoxConfigurationItem::find($itemData['id']);
                        $item->update([
                            'item_name' => $itemData['item_name'],
                            'token_value' => $itemData['token_value'],
                            'quantity_available' => $itemData['quantity_available'] ?? null,
                            'unit' => $itemData['unit'] ?? 'item',
                            'plant_variety_id' => $itemData['plant_variety_id'] ?? null,
                            'is_featured' => $itemData['is_featured'] ?? false,
                            'sort_order' => $index,
                        ]);
                        $itemIds[] = $item->id;
                    } else {
                        // Create new
                        $item = $configuration->items()->create([
                            'item_name' => $itemData['item_name'],
                            'token_value' => $itemData['token_value'],
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
                        'token_value' => 2, // Default value
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
}

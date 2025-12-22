<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingClass;
use App\Services\WooCommerceApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShippingClassController extends Controller
{
    protected WooCommerceApiService $wooCommerceApi;

    public function __construct(WooCommerceApiService $wooCommerceApi)
    {
        $this->wooCommerceApi = $wooCommerceApi;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shippingClasses = ShippingClass::orderBy('name')->get();

        return view('admin.shipping-classes.index', compact('shippingClasses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.shipping-classes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Set cost to 0 for free shipping or farm collection
        if ($request->has('is_free') || $request->has('is_farm_collection')) {
            $request->merge(['cost' => 0]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:shipping_classes,slug',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'is_farm_collection' => 'boolean',
            'delivery_zones' => 'nullable|array',
            'delivery_zones.*' => 'string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $shippingClass = ShippingClass::create([
            'name' => $request->name,
            'slug' => $request->slug ?: \Str::slug($request->name),
            'description' => $request->description,
            'cost' => $request->cost,
            'is_free' => $request->has('is_free'),
            'is_farm_collection' => $request->has('is_farm_collection'),
            'delivery_zones' => $request->delivery_zones ?? [],
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        // Sync to WooCommerce
        $this->syncShippingClassToWooCommerce($shippingClass);

        return redirect()->route('admin.shipping-classes.index')
            ->with('success', 'Shipping class created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ShippingClass $shippingClass)
    {
        return view('admin.shipping-classes.show', compact('shippingClass'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShippingClass $shippingClass)
    {
        return view('admin.shipping-classes.edit', compact('shippingClass'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShippingClass $shippingClass)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:shipping_classes,slug,' . $shippingClass->id,
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'is_farm_collection' => 'boolean',
            'delivery_zones' => 'nullable|array',
            'delivery_zones.*' => 'string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $shippingClass->update([
            'name' => $request->name,
            'slug' => $request->slug ?: \Str::slug($request->name),
            'description' => $request->description,
            'cost' => $request->cost,
            'is_free' => $request->has('is_free'),
            'is_farm_collection' => $request->has('is_farm_collection'),
            'delivery_zones' => $request->delivery_zones ?? [],
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        // Sync to WooCommerce
        $this->syncShippingClassToWooCommerce($shippingClass);

        return redirect()->route('admin.shipping-classes.index')
            ->with('success', 'Shipping class updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShippingClass $shippingClass)
    {
        // Check if shipping class is being used by any products
        if ($shippingClass->products()->count() > 0) {
            return redirect()->route('admin.shipping-classes.index')
                ->with('error', 'Cannot delete shipping class that is assigned to products.');
        }

        $shippingClass->delete();

        return redirect()->route('admin.shipping-classes.index')
            ->with('success', 'Shipping class deleted successfully.');
    }

    /**
     * Sync shipping class to WooCommerce database
     */
    private function syncShippingClassToWooCommerce($shippingClass)
    {
        try {
            if ($shippingClass->woo_id) {
                // Update existing
                $existing = DB::connection('wordpress')->select(
                    'SELECT t.term_id FROM demo_wp_terms t JOIN demo_wp_term_taxonomy tt ON t.term_id = tt.term_id WHERE tt.taxonomy = ? AND t.term_id = ?',
                    ['product_shipping_class', $shippingClass->woo_id]
                );

                if (count($existing) > 0) {
                    // Update existing
                    DB::connection('wordpress')->update(
                        'UPDATE demo_wp_terms SET name = ?, slug = ? WHERE term_id = ?',
                        [$shippingClass->name, $shippingClass->slug, $shippingClass->woo_id]
                    );
                    DB::connection('wordpress')->update(
                        'UPDATE demo_wp_term_taxonomy SET description = ? WHERE term_id = ? AND taxonomy = ?',
                        [$shippingClass->description ?: '', $shippingClass->woo_id, 'product_shipping_class']
                    );
                } else {
                    // WooCommerce record missing, recreate
                    $this->createShippingClassInDatabase($shippingClass);
                }
            } else {
                // Create new
                $this->createShippingClassInDatabase($shippingClass);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync shipping class to WooCommerce: ' . $e->getMessage());
        }
    }

    /**
     * Create shipping class directly in WooCommerce database
     */
    private function createShippingClassInDatabase($shippingClass)
    {
        // Insert into WordPress terms
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_terms (name, slug, term_group) VALUES (?, ?, 0)',
            [$shippingClass->name, $shippingClass->slug]
        );

        // Get the inserted term ID
        $termId = DB::connection('wordpress')->select('SELECT LAST_INSERT_ID() as id')[0]->id;

        // Insert into term_taxonomy
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (?, ?, ?, 0, 0)',
            [$termId, 'product_shipping_class', $shippingClass->description ?: '']
        );

        // Update Laravel record with WooCommerce ID
        $shippingClass->update(['woo_id' => $termId]);
    }
}

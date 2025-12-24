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
                // Check if WooCommerce record exists
                $existing = DB::connection('wordpress')
                    ->table('terms')
                    ->join('term_taxonomy', 'terms.term_id', '=', 'term_taxonomy.term_id')
                    ->where('term_taxonomy.taxonomy', 'product_shipping_class')
                    ->where('terms.term_id', $shippingClass->woo_id)
                    ->first();

                if ($existing) {
                    // Update existing WooCommerce record
                    DB::connection('wordpress')
                        ->table('terms')
                        ->where('term_id', $shippingClass->woo_id)
                        ->update([
                            'name' => $shippingClass->name,
                            'slug' => $shippingClass->slug
                        ]);

                    DB::connection('wordpress')
                        ->table('term_taxonomy')
                        ->where('term_id', $shippingClass->woo_id)
                        ->where('taxonomy', 'product_shipping_class')
                        ->update([
                            'description' => $shippingClass->description ?: ''
                        ]);

                    \Log::info('Shipping class updated in WooCommerce', [
                        'shipping_class_id' => $shippingClass->id,
                        'woo_term_id' => $shippingClass->woo_id
                    ]);
                } else {
                    // WooCommerce record missing, recreate
                    $this->createShippingClassInDatabase($shippingClass);
                }
            } else {
                // Create new WooCommerce record
                $this->createShippingClassInDatabase($shippingClass);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync shipping class to WooCommerce', [
                'shipping_class_id' => $shippingClass->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create shipping class directly in WooCommerce database
     */
    private function createShippingClassInDatabase($shippingClass)
    {
        try {
            // Insert into WordPress terms table
            $termId = DB::connection('wordpress')->table('terms')->insertGetId([
                'name' => $shippingClass->name,
                'slug' => $shippingClass->slug,
                'term_group' => 0
            ]);

            // Insert into term_taxonomy table
            DB::connection('wordpress')->table('term_taxonomy')->insert([
                'term_id' => $termId,
                'taxonomy' => 'product_shipping_class',
                'description' => $shippingClass->description ?: '',
                'parent' => 0,
                'count' => 0
            ]);

            // Update Laravel record with WooCommerce ID
            $shippingClass->update(['woo_id' => $termId]);

            \Log::info('Shipping class created in WooCommerce', [
                'shipping_class_id' => $shippingClass->id,
                'woo_term_id' => $termId
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create shipping class in WooCommerce', [
                'shipping_class_id' => $shippingClass->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

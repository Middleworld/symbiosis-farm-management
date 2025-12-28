<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Services\WooCommerceApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductAttributeController extends Controller
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
        $attributes = ProductAttribute::orderBy('name')->get();

        return view('admin.product-attributes.index', compact('attributes'));
    }

    /**
     * API endpoint to get all attributes with their options
     */
    public function apiList()
    {
        $attributes = ProductAttribute::where('is_variation', true)
            ->orderBy('name')
            ->get()
            ->map(function ($attr) {
                return [
                    'id' => $attr->id,
                    'name' => $attr->slug ?: strtolower(str_replace(' ', '-', $attr->name)),
                    'label' => $attr->name,
                    'slug' => $attr->slug,
                    'type' => $attr->is_taxonomy ? 'taxonomy' : 'custom',
                    'variation' => $attr->is_variation,
                    'visible' => $attr->is_visible,
                    'options' => is_array($attr->options) ? $attr->options : (json_decode($attr->options, true) ?: [])
                ];
            });

        return response()->json([
            'success' => true,
            'attributes' => $attributes
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.product-attributes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_attributes,slug',
            'type' => 'required|in:select,text',
            'is_visible' => 'boolean',
            'is_variation' => 'boolean',
            'is_taxonomy' => 'boolean',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $attribute = ProductAttribute::create([
            'name' => $request->name,
            'slug' => $request->slug ?: \Str::slug($request->name),
            'type' => $request->type,
            'is_visible' => $request->has('is_visible'),
            'is_variation' => $request->has('is_variation'),
            'is_taxonomy' => $request->has('is_taxonomy'),
            'options' => $request->options ?? [],
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        // Sync to WooCommerce
        $this->syncAttributeToWooCommerce($attribute);

        return redirect()->route('admin.product-attributes.index')
            ->with('success', 'Product attribute created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductAttribute $attribute)
    {
        return view('admin.product-attributes.show', compact('attribute'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductAttribute $attribute)
    {
        return view('admin.product-attributes.edit', compact('attribute'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductAttribute $attribute)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_attributes,slug,' . $attribute->id,
            'type' => 'required|in:select,text',
            'is_visible' => 'boolean',
            'is_variation' => 'boolean',
            'is_taxonomy' => 'boolean',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $attribute->update([
            'name' => $request->name,
            'slug' => $request->slug ?: \Str::slug($request->name),
            'type' => $request->type,
            'is_visible' => $request->has('is_visible'),
            'is_variation' => $request->has('is_variation'),
            'is_taxonomy' => $request->has('is_taxonomy'),
            'options' => $request->options ?? [],
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        // Sync to WooCommerce
        $this->syncAttributeToWooCommerce($attribute);

        return redirect()->route('admin.product-attributes.index')
            ->with('success', 'Product attribute updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductAttribute $attribute)
    {
        // Check if attribute is being used by any variations
        if ($attribute->variations()->count() > 0) {
            return redirect()->route('admin.product-attributes.index')
                ->with('error', 'Cannot delete attribute that is assigned to product variations.');
        }

        $attribute->delete();

        return redirect()->route('admin.product-attributes.index')
            ->with('success', 'Product attribute deleted successfully.');
    }

    /**
     * Sync attribute to WooCommerce database
     */
    private function syncAttributeToWooCommerce($attribute)
    {
        try {
            if ($attribute->woo_id) {
                // Update existing
                $existing = DB::connection('wordpress')->select(
                    'SELECT t.term_id FROM demo_wp_terms t JOIN demo_wp_term_taxonomy tt ON t.term_id = tt.term_id WHERE tt.taxonomy = ? AND t.term_id = ?',
                    ['pa_' . $attribute->slug, $attribute->woo_id]
                );

                if (count($existing) > 0) {
                    // Update existing
                    DB::connection('wordpress')->update(
                        'UPDATE demo_wp_terms SET name = ?, slug = ? WHERE term_id = ?',
                        [$attribute->name, $attribute->slug, $attribute->woo_id]
                    );
                } else {
                    // WooCommerce record missing, recreate
                    $this->createAttributeInDatabase($attribute);
                }
            } else {
                // Create new
                $this->createAttributeInDatabase($attribute);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync product attribute to WooCommerce: ' . $e->getMessage());
        }
    }

    /**
     * Create attribute directly in WooCommerce database
     */
    private function createAttributeInDatabase($attribute)
    {
        // Insert into WordPress terms
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_terms (name, slug, term_group) VALUES (?, ?, 0)',
            [$attribute->name, $attribute->slug]
        );

        // Get the inserted term ID
        $termId = DB::connection('wordpress')->select('SELECT LAST_INSERT_ID() as id')[0]->id;

        // Insert into term_taxonomy
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (?, ?, ?, 0, 0)',
            [$termId, 'pa_' . $attribute->slug, '', 0]
        );

        // Update Laravel record with WooCommerce ID
        $attribute->update(['woo_id' => $termId]);
    }
}

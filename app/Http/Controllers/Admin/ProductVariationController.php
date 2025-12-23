<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductVariationController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Product $product)
    {
        return view('admin.products.variations.create', compact('product'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:product_variations,sku',
            'price' => 'required|numeric|min:0',
            'regular_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'attributes' => 'nullable|array',
            'stock_quantity' => 'nullable|integer|min:0',
            'manage_stock' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $variation = $product->variations()->create([
            'name' => $request->name,
            'sku' => $request->sku,
            'price' => $request->price,
            'regular_price' => $request->regular_price,
            'sale_price' => $request->sale_price,
            'description' => $request->description,
            'attributes' => $request->attributes ?? [],
            'stock_quantity' => $request->stock_quantity,
            'stock_status' => ($request->manage_stock && $request->stock_quantity > 0) ? 'instock' : 'outofstock',
            'manage_stock' => $request->has('manage_stock'),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Variation created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product, ProductVariation $variation)
    {
        return view('admin.products.variations.edit', compact('product', 'variation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product, ProductVariation $variation)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:product_variations,sku,' . $variation->id,
            'price' => 'required|numeric|min:0',
            'regular_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'attributes' => 'nullable|array',
            'stock_quantity' => 'nullable|integer|min:0',
            'manage_stock' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $variation->update([
            'name' => $request->name,
            'sku' => $request->sku,
            'price' => $request->price,
            'regular_price' => $request->regular_price,
            'sale_price' => $request->sale_price,
            'description' => $request->description,
            'attributes' => $request->attributes ?? [],
            'stock_quantity' => $request->stock_quantity,
            'stock_status' => ($request->manage_stock && $request->stock_quantity > 0) ? 'instock' : 'outofstock',
            'manage_stock' => $request->has('manage_stock'),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Variation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product, ProductVariation $variation)
    {
        $variation->delete();

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Variation deleted successfully.');
    }
}

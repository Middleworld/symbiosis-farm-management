<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosInventoryController extends Controller
{
    /**
     * Display POS inventory management page
     */
    public function index()
    {
        return view('pos.inventory');
    }

    /**
     * Get all products with their POS availability status
     */
    public function getProducts(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $availability = $request->get('availability'); // 'all', 'available', 'unavailable'

        $query = Product::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($availability === 'available') {
            $query->where('pos_available', true);
        } elseif ($availability === 'unavailable') {
            $query->where('pos_available', false);
        }

        $products = $query->orderBy('category')->orderBy('name')->get();

        return response()->json($products);
    }

    /**
     * Toggle POS availability for a single product
     */
    public function toggleAvailability($id)
    {
        $product = Product::findOrFail($id);
        $product->pos_available = !$product->pos_available;
        $product->save();

        return response()->json([
            'success' => true,
            'product_id' => $product->id,
            'pos_available' => $product->pos_available,
            'message' => $product->pos_available 
                ? "'{$product->name}' is now available in POS" 
                : "'{$product->name}' is now hidden from POS"
        ]);
    }

    /**
     * Bulk update POS availability
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'action' => 'required|in:enable,disable'
        ]);

        $available = $request->action === 'enable';
        
        Product::whereIn('id', $request->product_ids)
            ->update(['pos_available' => $available]);

        $count = count($request->product_ids);
        $message = $available 
            ? "{$count} product(s) are now available in POS"
            : "{$count} product(s) are now hidden from POS";

        return response()->json([
            'success' => true,
            'message' => $message,
            'count' => $count
        ]);
    }

    /**
     * Get category counts with availability
     */
    public function getCategoryCounts()
    {
        $counts = Product::select('category')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN pos_available = 1 THEN 1 ELSE 0 END) as available')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        return response()->json($counts);
    }
}

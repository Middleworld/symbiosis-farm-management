<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\WooCommerceApiService;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Sort functionality
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $products = $query->paginate(25);

        // Get unique categories for filter dropdown
        $categories = Product::whereNotNull('category')
                            ->distinct()
                            ->pluck('category')
                            ->sort();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $categories = Product::whereNotNull('category')
                            ->distinct()
                            ->pluck('category')
                            ->sort();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_taxable' => 'boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'shipping_class_id' => 'nullable|exists:shipping_classes,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $data = $request->all();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $data['image_url'] = $imagePath;
        }

        // Set default values
        $data['is_active'] = $request->has('is_active');
        $data['is_taxable'] = $request->has('is_taxable');
        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['min_stock_level'] = $data['min_stock_level'] ?? 0;
        $data['max_stock_level'] = $data['max_stock_level'] ?? 0;

        Product::create($data);

        return redirect()->route('admin.products.index')
                        ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the product
     */
    public function edit(Product $product)
    {
        $categories = Product::whereNotNull('category')
                            ->distinct()
                            ->pluck('category')
                            ->sort();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_taxable' => 'boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'shipping_class_id' => 'nullable|exists:shipping_classes,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $data = $request->all();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }

            $imagePath = $request->file('image')->store('products', 'public');
            $data['image_url'] = $imagePath;
        }

        // Set boolean values
        $data['is_active'] = $request->has('is_active');
        $data['is_taxable'] = $request->has('is_taxable');

        $product->update($data);

        return redirect()->route('admin.products.index')
                        ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        // Delete image if exists
        if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
            Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
                        ->with('success', 'Product deleted successfully.');
    }

    /**
     * Toggle product active status
     */
    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Product {$status} successfully.",
            'is_active' => $product->is_active
        ]);
    }

    /**
     * Sync product with WooCommerce
     */
    public function syncWithWooCommerce(Product $product)
    {
        try {
            $wooCommerceService = new WooCommerceApiService();
            $result = $wooCommerceService->syncProduct($product);

            if ($result['success']) {
                $action = $product->woo_product_id ? 'updated' : 'created';
                $wooProductId = isset($result['data']) && is_object($result['data']) 
                    ? $result['data']->id 
                    : $product->woo_product_id;
                    
                return response()->json([
                    'success' => true,
                    'message' => "Product successfully {$action} in WooCommerce.",
                    'woo_product_id' => $wooProductId
                ]);
            } else {
                $errorMessage = $result['message'];
                
                // Add helpful hint for common REST API issue
                if (strpos($errorMessage, 'rest_no_route') !== false || strpos($errorMessage, 'No route was found') !== false) {
                    $errorMessage .= ' - The WooCommerce REST API routes may not be registered. Please go to WordPress Admin > Settings > Permalinks and click "Save Changes" to flush rewrite rules.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync with WooCommerce: ' . $errorMessage
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while syncing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WooCommerce product details
     */
    public function getWooCommerceProduct(Product $product)
    {
        if (!$product->woo_product_id) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not linked to WooCommerce'
            ], 404);
        }

        try {
            $wooCommerceService = new WooCommerceApiService();
            $result = $wooCommerceService->getProduct($product->woo_product_id);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get WooCommerce product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlink product from WooCommerce
     */
    public function unlinkFromWooCommerce(Product $product)
    {
        try {
            $product->update(['woo_product_id' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Product unlinked from WooCommerce successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlink product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show WooCommerce Dashboard in iframe
     */
    public function iframeDashboard()
    {
        $wooCommerceUrl = config('services.woocommerce.base_url');
        $dashboardUrl = rtrim($wooCommerceUrl, '/') . '/wp-admin/edit.php?post_type=product';

        return view('admin.products.iframe-dashboard', compact('dashboardUrl'));
    }

    /**
     * Show WooCommerce Product Edit page in iframe
     */
    public function iframeEdit(Product $product)
    {
        if (!$product->woo_product_id) {
            return redirect()->route('admin.products.show', $product)
                ->with('error', 'Product must be linked to WooCommerce first.');
        }

        $wooCommerceUrl = config('services.woocommerce.base_url');
        $editUrl = rtrim($wooCommerceUrl, '/') . "/wp-admin/post.php?post={$product->woo_product_id}&action=edit";

        return view('admin.products.iframe-edit', compact('product', 'editUrl'));
    }

    /**
     * Show WooCommerce Product Edit page using MWF API integration
     */
    public function apiEdit(Product $product)
    {
        if (!$product->woo_product_id) {
            return redirect()->route('admin.products.show', $product)
                ->with('error', 'Product must be linked to WooCommerce first.');
        }

        return view('admin.products.api-edit', compact('product'));
    }

    /**
     * Get WooCommerce product ID for a product
     */
    public function getWooProductId(Product $product)
    {
        return response()->json([
            'woo_product_id' => $product->woo_product_id
        ]);
    }

    /**
     * Test WooCommerce API connection
     */
    public function testWooCommerceConnection()
    {
        try {
            $wooCommerceService = new WooCommerceApiService();
            $result = $wooCommerceService->testConnection();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'WooCommerce connection successful'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Connection test failed'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sync all active products with WooCommerce
     */
    public function syncAllWithWooCommerce(Request $request)
    {
        try {
            $wooCommerceService = new WooCommerceApiService();
            $result = $wooCommerceService->syncAllProducts();

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Bulk sync completed successfully',
                'synced_count' => $result['synced_count'] ?? 0,
                'errors' => $result['errors'] ?? []
            ]);
        } catch (\Exception $e) {
            \Log::error('Bulk WooCommerce sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch all products from WooCommerce
     */
    public function fetchAllFromWooCommerce(Request $request)
    {
        try {
            $wooCommerceService = new WooCommerceApiService();
            $result = $wooCommerceService->getProducts();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch products from WooCommerce: ' . $result['message']
                ], 500);
            }

            $fetched = 0;
            $updated = 0;
            $errors = [];

            foreach ($result['data'] as $wooProduct) {
                try {
                    // Check if product already exists by WooCommerce ID
                    $existingProduct = Product::where('woo_product_id', $wooProduct->id)->first();

                    $productData = [
                        'name' => $wooProduct->name,
                        'sku' => $wooProduct->sku ?: 'WC-' . $wooProduct->id,
                        'description' => $wooProduct->description ?: $wooProduct->short_description,
                        'price' => $wooProduct->price ?: $wooProduct->regular_price ?: 0,
                        'category' => !empty($wooProduct->categories) ? $wooProduct->categories[0]->name : null,
                        'is_active' => $wooProduct->status === 'publish',
                        'is_taxable' => $wooProduct->tax_status === 'taxable',
                        'stock_quantity' => $wooProduct->stock_quantity ?? 0,
                        'weight' => $wooProduct->weight ? floatval($wooProduct->weight) : null,
                        'woo_product_id' => $wooProduct->id,
                        'image_url' => !empty($wooProduct->images) ? $wooProduct->images[0]->src : null,
                    ];

                    if ($existingProduct) {
                        $existingProduct->update($productData);
                        $updated++;
                    } else {
                        Product::create($productData);
                        $fetched++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Product {$wooProduct->name} (ID: {$wooProduct->id}): " . $e->getMessage();
                }
            }

            $message = "Fetch completed. Fetched: {$fetched}, Updated: {$updated}";
            if (!empty($errors)) {
                $message .= ". Errors: " . count($errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'fetched_count' => $fetched,
                'updated_count' => $updated,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            \Log::error('Bulk WooCommerce fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk fetch failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch a specific product from WooCommerce
     */
    public function fetchFromWooCommerce(Request $request)
    {
        $request->validate([
            'woo_product_id' => 'required|integer'
        ]);

        try {
            $wooCommerceService = new WooCommerceApiService();
            $result = $wooCommerceService->getProduct($request->woo_product_id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch product from WooCommerce: ' . $result['message']
                ], 500);
            }

            $wooProduct = $result['data'];

            // Check if product already exists
            $existingProduct = Product::where('woo_product_id', $wooProduct->id)->first();

            $productData = [
                'name' => $wooProduct->name,
                'sku' => $wooProduct->sku ?: 'WC-' . $wooProduct->id,
                'description' => $wooProduct->description ?: $wooProduct->short_description,
                'price' => $wooProduct->price ?: $wooProduct->regular_price ?: 0,
                'category' => !empty($wooProduct->categories) ? $wooProduct->categories[0]->name : null,
                'is_active' => $wooProduct->status === 'publish',
                'is_taxable' => $wooProduct->tax_status === 'taxable',
                'stock_quantity' => $wooProduct->stock_quantity ?? 0,
                'weight' => $wooProduct->weight ? floatval($wooProduct->weight) : null,
                'woo_product_id' => $wooProduct->id,
                'image_url' => !empty($wooProduct->images) ? $wooProduct->images[0]->src : null,
            ];

            if ($existingProduct) {
                $existingProduct->update($productData);
                $action = 'updated';
                $product = $existingProduct;
            } else {
                $product = Product::create($productData);
                $action = 'created';
            }

            return response()->json([
                'success' => true,
                'message' => "Product successfully {$action} from WooCommerce.",
                'product_id' => $product->id,
                'woo_product_id' => $wooProduct->id
            ]);
        } catch (\Exception $e) {
            \Log::error('WooCommerce fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Fetch failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk fetch products from WooCommerce
     */
    public function bulkFetchFromWooCommerce(Request $request)
    {
        $request->validate([
            'woo_product_ids' => 'required|array',
            'woo_product_ids.*' => 'integer'
        ]);

        try {
            $wooCommerceService = new WooCommerceApiService();
            $fetched = 0;
            $updated = 0;
            $errors = [];

            foreach ($request->woo_product_ids as $wooProductId) {
                try {
                    $result = $wooCommerceService->getProduct($wooProductId);

                    if (!$result['success']) {
                        $errors[] = "WooCommerce Product ID {$wooProductId}: " . $result['message'];
                        continue;
                    }

                    $wooProduct = $result['data'];

                    // Check if product already exists
                    $existingProduct = Product::where('woo_product_id', $wooProduct->id)->first();

                    $productData = [
                        'name' => $wooProduct->name,
                        'sku' => $wooProduct->sku ?: 'WC-' . $wooProduct->id,
                        'description' => $wooProduct->description ?: $wooProduct->short_description,
                        'price' => $wooProduct->price ?: $wooProduct->regular_price ?: 0,
                        'category' => !empty($wooProduct->categories) ? $wooProduct->categories[0]->name : null,
                        'is_active' => $wooProduct->status === 'publish',
                        'is_taxable' => $wooProduct->tax_status === 'taxable',
                        'stock_quantity' => $wooProduct->stock_quantity ?? 0,
                        'weight' => $wooProduct->weight ? floatval($wooProduct->weight) : null,
                        'woo_product_id' => $wooProduct->id,
                        'image_url' => !empty($wooProduct->images) ? $wooProduct->images[0]->src : null,
                    ];

                    if ($existingProduct) {
                        $existingProduct->update($productData);
                        $updated++;
                    } else {
                        Product::create($productData);
                        $fetched++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "WooCommerce Product ID {$wooProductId}: " . $e->getMessage();
                }
            }

            $message = "Bulk fetch completed. Fetched: {$fetched}, Updated: {$updated}";
            if (!empty($errors)) {
                $message .= ". Errors: " . count($errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'fetched_count' => $fetched,
                'updated_count' => $updated,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            \Log::error('Bulk WooCommerce fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk fetch failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export products to CSV
     */
    public function exportCsv()
    {
        $products = Product::all();
        
        $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Name', 'SKU', 'Description', 'Price', 'Category', 
                'Is Active', 'Is Taxable', 'WooCommerce ID', 'Created At', 'Updated At'
            ]);
            
            // CSV data
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $product->description,
                    $product->price,
                    $product->category,
                    $product->is_active ? 'Yes' : 'No',
                    $product->is_taxable ? 'Yes' : 'No',
                    $product->woo_product_id,
                    $product->created_at,
                    $product->updated_at,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import products from CSV
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $data = array_map('str_getcsv', file($path));
        $header = array_shift($data);
        
        $imported = 0;
        $errors = [];
        
        foreach ($data as $row) {
            try {
                $productData = array_combine($header, $row);
                
                // Validate required fields
                if (empty($productData['Name']) || empty($productData['SKU'])) {
                    $errors[] = "Row " . ($imported + 2) . ": Missing required fields (Name or SKU)";
                    continue;
                }
                
                Product::create([
                    'name' => $productData['Name'],
                    'sku' => $productData['SKU'],
                    'description' => $productData['Description'] ?? null,
                    'price' => $productData['Price'] ?? 0,
                    'category' => $productData['Category'] ?? null,
                    'is_active' => strtolower($productData['Is Active'] ?? 'yes') === 'yes',
                    'is_taxable' => strtolower($productData['Is Taxable'] ?? 'yes') === 'yes',
                    'woo_product_id' => $productData['WooCommerce ID'] ?? null,
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($imported + 2) . ": " . $e->getMessage();
            }
        }

        $message = "Import completed. Imported: {$imported}";
        if (!empty($errors)) {
            $message .= ". Errors: " . count($errors);
        }

        return redirect()->route('admin.products.index')
                        ->with('success', $message);
    }

    /**
     * Display product variations management page
     */
    public function variations(Product $product)
    {
        // Check if product has WooCommerce ID
        if (!$product->woo_product_id) {
            return redirect()->route('admin.products.index')
                            ->with('error', 'This product is not linked to WooCommerce. Please link it first.');
        }

        return view('admin.products.variations', compact('product'));
    }

    /**
     * Get variations as JSON
     */
    public function getVariations(Product $product)
    {
        try {
            $variations = $product->variations()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'variations' => $variations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

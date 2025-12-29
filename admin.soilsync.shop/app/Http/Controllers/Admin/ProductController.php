<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\WooCommerceApiService;
use App\Services\AI\SymbiosisAIService;

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8192',
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

        // Note: Shipping classes are determined by subscription plan at checkout, not per-product

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        // LOG THE UPDATE REQUEST
        Log::info('Product update attempt', [
            'product_id' => $product->id,
            'old_name' => $product->name,
            'new_name' => $request->input('name'),
            'old_description' => substr($product->description ?? '', 0, 50),
            'new_description' => substr($request->input('description') ?? '', 0, 50),
            'has_image_file' => $request->hasFile('image'),
            'has_gallery_files' => $request->hasFile('gallery_images'),
            'image_file_valid' => $request->hasFile('image') && $request->file('image')->isValid(),
            'all_inputs' => array_keys($request->all()),
            'all_files' => array_keys($request->allFiles())
        ]);
        
        // For variable products, price is set per variation, not on the product itself
        $priceRule = $request->input('product_type') === 'variable' 
            ? 'nullable|numeric|min:0' 
            : 'required|numeric|min:0';
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'price' => $priceRule, // Dynamic validation based on product type
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8192',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8192',
        ]);

        if ($validator->fails()) {
            Log::warning('Product update validation failed', [
                'product_id' => $product->id,
                'errors' => $validator->errors()->toArray()
            ]);
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $data = $request->only([
            'name', 'sku', 'product_type', 'description', 'price', 'cost_price',
            'category', 'subcategory', 'stock_quantity', 'min_stock_level', 
            'max_stock_level', 'tax_rate', 'weight', 'unit',
            'brand', 'metadata', 'reviews_enabled', 'upsell_ids', 'crosssell_ids'
        ]);

        // Handle short_description in metadata
        if ($request->has('short_description')) {
            $metadata = $data['metadata'] ?? [];
            $metadata['short_description'] = $request->input('short_description');
            $data['metadata'] = $metadata;
        }

        // Handle WooCommerce categories (checkboxes to metadata)
        if ($request->has('woo_categories')) {
            $metadata = $data['metadata'] ?? [];
            $metadata['woo_categories'] = $request->input('woo_categories', []);
            $data['metadata'] = $metadata;
        }

        // Handle product tags (comma-separated or array)
        if ($request->has('tags')) {
            $tagsInput = $request->input('tags');
            if (is_string($tagsInput)) {
                $data['tags'] = array_filter(array_map('trim', explode(',', $tagsInput)));
            } else {
                $data['tags'] = $tagsInput;
            }
        }

        // Handle product categories (checkboxes to array)
        $data['product_categories'] = $request->input('product_categories', []);
        
        // Handle product tags (comma-separated string to array)
        if ($request->has('product_tags_input')) {
            $tagsString = $request->input('product_tags_input');
            $data['product_tags'] = array_filter(array_map('trim', explode(',', $tagsString)));
        } else {
            $data['product_tags'] = [];
        }

        // Handle main product image upload
        if ($request->hasFile('image')) {
            Log::info('Processing main image upload', [
                'product_id' => $product->id,
                'old_image' => $product->image_url,
                'file_name' => $request->file('image')->getClientOriginalName(),
                'file_size' => $request->file('image')->getSize(),
                'mime_type' => $request->file('image')->getMimeType()
            ]);
            
            try {
                // Delete old image if exists
                if ($product->image_url && $product->image_url !== '0' && Storage::disk('public')->exists($product->image_url)) {
                    Storage::disk('public')->delete($product->image_url);
                }

                $imagePath = $request->file('image')->store('products', 'public');
                
                if ($imagePath === false) {
                    Log::error('Image store() returned false', [
                        'product_id' => $product->id,
                        'file_name' => $request->file('image')->getClientOriginalName()
                    ]);
                } else {
                    $data['image_url'] = $imagePath;
                    
                    Log::info('Main image uploaded successfully', [
                        'product_id' => $product->id,
                        'new_image_path' => $imagePath,
                        'full_path' => Storage::disk('public')->path($imagePath)
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Image upload exception', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            // Don't include image_url in update if no new image uploaded
            // This prevents overwriting existing image with empty value
            Log::info('No new image uploaded, keeping existing', [
                'product_id' => $product->id,
                'current_image' => $product->image_url
            ]);
        }

        // Handle main image removal
        if ($request->input('remove_main_image') == '1' && $product->image_url) {
            if (Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }
            $data['image_url'] = null;
        }

        // Handle gallery images
        $galleryImages = [];
        
        // Keep existing gallery images (not marked for removal)
        if ($request->has('existing_gallery_images')) {
            $galleryImages = $request->input('existing_gallery_images');
        }
        
        // Add new gallery images
        if ($request->hasFile('gallery_images')) {
            $newImages = $request->file('gallery_images');
            
            Log::info('Processing gallery images', [
                'product_id' => $product->id,
                'new_images_count' => count($newImages),
                'existing_images_count' => count($galleryImages)
            ]);
            
            // Limit to 5 total images in gallery
            $remainingSlots = 5 - count($galleryImages);
            $imagesToUpload = array_slice($newImages, 0, $remainingSlots);
            
            foreach ($imagesToUpload as $galleryImage) {
                $galleryPath = $galleryImage->store('products/gallery', 'public');
                $galleryImages[] = $galleryPath;
            }
            
            Log::info('Gallery images uploaded', [
                'product_id' => $product->id,
                'total_gallery_images' => count($galleryImages)
            ]);
        }
        
        $data['gallery_images'] = $galleryImages;

        // Set boolean values
        $data['is_active'] = $request->has('is_active');
        $data['is_taxable'] = $request->has('is_taxable');
        $data['reviews_enabled'] = $request->has('reviews_enabled');

        Log::info('About to update product', [
            'product_id' => $product->id,
            'data_keys' => array_keys($data),
            'name_in_data' => $data['name'] ?? 'MISSING',
            'description_in_data' => substr($data['description'] ?? 'MISSING', 0, 50),
            'categories_count' => count($data['product_categories'] ?? []),
            'tags_count' => count($data['product_tags'] ?? []),
            'has_image_url' => isset($data['image_url']),
            'gallery_images_count' => count($galleryImages),
            'image_url' => $data['image_url'] ?? 'none'
        ]);

        $product->update($data);
        
        // Reload product from database to verify what was saved
        $product = $product->fresh();
        
        Log::info('Product saved - verifying data', [
            'product_id' => $product->id,
            'image_url' => $product->image_url,
            'gallery_images' => $product->gallery_images,
            'product_categories' => $product->product_categories,
            'product_tags' => $product->product_tags,
            'short_description' => $product->metadata['short_description'] ?? 'EMPTY',
            'metadata_keys' => array_keys($product->metadata ?? [])
        ]);
        
        // Track what was updated
        $updates = [];
        $updates[] = "Product '{$product->name}' updated";
        
        if (isset($data['image_url'])) {
            $updates[] = "Image uploaded";
        }
        
        if (count($galleryImages) > 0) {
            $updates[] = count($galleryImages) . " gallery images";
        }
        
        if (!empty($data['product_categories'])) {
            $updates[] = count($data['product_categories']) . " categories";
        }
        
        if (!empty($data['product_tags'])) {
            $updates[] = count($data['product_tags']) . " tags";
        }
        
        // Sync to WooCommerce if linked
        $wooSyncSuccess = false;
        $wooSyncErrors = [];
        
        if ($product->woo_product_id) {
            try {
                $this->syncImagesToWooCommerce($product);
                $this->syncShortDescriptionToWooCommerce($product);
                // Note: Shipping classes are determined by subscription plan at checkout, not per-product
                $this->syncReviewsToWooCommerce($product);
                $this->syncUpsellsToWooCommerce($product);
                $this->syncCrosssellsToWooCommerce($product);
                
                $wooSyncSuccess = true;
                $updates[] = "Synced to WooCommerce (ID: {$product->woo_product_id})";
            } catch (\Exception $e) {
                $wooSyncErrors[] = "WooCommerce sync failed: " . $e->getMessage();
                Log::error('WooCommerce sync error', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Product updated successfully', [
            'product_id' => $product->id,
            'new_name' => $product->fresh()->name,
            'new_description_length' => strlen($product->fresh()->description ?? ''),
            'woo_sync' => $wooSyncSuccess ? 'yes' : 'no'
        ]);
        
        // Build success message with details
        $message = implode(', ', $updates);
        
        $redirect = redirect()->route('admin.products.index')
                        ->with('success', $message);
        
        // Add warnings if WooCommerce sync had issues
        if (!empty($wooSyncErrors)) {
            $redirect->with('warning', implode(', ', $wooSyncErrors));
        }
        
        return $redirect;
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        // LOG WHO IS DELETING
        Log::warning('Product deletion triggered', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);
        
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
            
            // Check if product has a woo_product_id that doesn't exist in current WooCommerce
            if ($product->woo_product_id) {
                try {
                    $existingProduct = $wooCommerceService->getProduct($product->woo_product_id);
                    // If product doesn't exist, clear the ID and create new
                    if (!$existingProduct || (isset($existingProduct['code']) && $existingProduct['code'] === 'woocommerce_rest_product_invalid_id')) {
                        \Log::info("Product ID {$product->woo_product_id} doesn't exist in WooCommerce, creating new product instead");
                        $product->woo_product_id = null;
                        $product->save();
                    }
                } catch (\Exception $e) {
                    // If we can't check, clear the ID to be safe
                    if (strpos($e->getMessage(), 'Invalid ID') !== false || strpos($e->getMessage(), 'invalid_id') !== false) {
                        \Log::info("Clearing invalid WooCommerce product ID {$product->woo_product_id} for product: {$product->name}");
                        $product->woo_product_id = null;
                        $product->save();
                    }
                }
            }
            
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

    /**
     * Generate AI-powered SEO suggestions
     */
    public function generateSeoSuggestions(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            // Generate SEO using RAG data directly (faster and more accurate than AI)
            $suggestions = $this->generateRagBasedSeo($product);

            // Validate length constraints
            if (strlen($suggestions['title']) > 70) {
                $suggestions['title'] = substr($suggestions['title'], 0, 67) . '...';
            }
            if (strlen($suggestions['description']) > 160) {
                $suggestions['description'] = substr($suggestions['description'], 0, 157) . '...';
            }

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate SEO suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build enriched product context using RAG knowledge base
     */
    private function buildProductContextWithRAG($product): string
    {
        $context = "Product: {$product->name}\n";
        $context .= "SKU: {$product->sku}\n";
        
        if ($product->description) {
            $context .= "Description: " . strip_tags($product->description) . "\n";
        }
        
        if ($product->category) {
            $context .= "Category: {$product->category}\n";
        }
        
        $context .= "Price: £" . number_format($product->price, 2) . "\n";

        // Query agricultural knowledge from RAG database directly
        try {
            $searchTerm = strtolower($product->name);
            $knowledge = [];

            // Get companion planting info
            $companions = DB::connection('pgsql_rag')
                ->table('companion_planting_knowledge')
                ->where('primary_crop', 'ilike', "%{$searchTerm}%")
                ->orWhere('companion_plant', 'ilike', "%{$searchTerm}%")
                ->limit(3)
                ->get();

            foreach ($companions as $comp) {
                $knowledge[] = "Companion planting: {$comp->primary_crop} grows well with {$comp->companion_plant}. {$comp->benefits}";
            }

            // Get seasonal planting info
            $calendar = DB::connection('pgsql_rag')
                ->table('uk_planting_calendar')
                ->where('crop_name', 'ilike', "%{$searchTerm}%")
                ->first();

            if ($calendar) {
                $sowInfo = $calendar->indoor_seed_months ?: $calendar->outdoor_seed_months;
                $knowledge[] = "UK Growing Season: {$calendar->crop_name} - Sow: {$sowInfo}. Harvest: {$calendar->harvest_months}. {$calendar->uk_specific_advice}";
            }

            // Get crop rotation info
            $rotation = DB::connection('pgsql_rag')
                ->table('crop_rotation_knowledge')
                ->where('previous_crop', 'ilike', "%{$searchTerm}%")
                ->orWhere('following_crop', 'ilike', "%{$searchTerm}%")
                ->first();

            if ($rotation) {
                $knowledge[] = "Crop rotation: After {$rotation->previous_crop}, plant {$rotation->following_crop}. Benefits: {$rotation->benefits}";
            }

            if (!empty($knowledge)) {
                $context .= "\nRelevant Agricultural Knowledge:\n";
                foreach ($knowledge as $item) {
                    $context .= "- {$item}\n";
                }
            }
        } catch (\Exception $e) {
            // RAG database not available, continue with basic context
            \Log::info('RAG database not available for SEO enhancement: ' . $e->getMessage());
        }

        return $context;
    }

    /**
     * Generate SEO using RAG data directly (fast fallback when AI unavailable)
     * Enhanced with full Laravel model data - often BETTER than AI!
     */
    private function generateRagBasedSeo($product): array
    {
        $name = $product->name;
        $category = $product->category ?? 'vegetables';
        $isSolidarityPricing = !empty($product->metadata['solidarity_pricing']) ?? false;
        $description = $product->description ? strip_tags($product->description) : '';
        
        // Get customer's farm name from WordPress
        $farmName = 'our UK farm';
        $useDefaultFarm = true;
        try {
            $siteName = DB::connection('wordpress')
                ->table('options')
                ->where('option_name', 'blogname')
                ->value('option_value');
            // Only use if it's a real farm name (not single letter, not admin domain)
            if ($siteName && strlen($siteName) > 2 && 
                $siteName !== 'admin.middleworldfarms.org' &&
                !in_array(strtolower($siteName), ['g', 'test', 'demo'])) {
                $farmName = $siteName;
                $useDefaultFarm = false;
            }
        } catch (\Exception $e) {
            // Use default
        }
        
        // Fallback for staging/demo environments
        if ($useDefaultFarm) {
            $farmName = 'our UK organic farm';
        }
        
        // Smart keyword building
        $keywords = [];
        $uniqueDetails = [];
        
        try {
            // Category-specific keywords
            if (stripos($name, 'box') !== false || stripos($category, 'box') !== false) {
                $keywords = ['organic veg box', 'seasonal vegetables', 'local produce', 'farm box delivery'];
                if ($isSolidarityPricing) {
                    $keywords[] = 'solidarity pricing';
                    $keywords[] = 'pay what you can';
                }
            } else {
                $keywords = ['organic ' . strtolower($category), 'seasonal vegetables', 'uk grown'];
            }
            
            // Get actual seasonal crops from RAG for this time of year
            $currentMonth = date('F');
            $seasonalCrops = DB::connection('pgsql_rag')
                ->table('uk_planting_calendar')
                ->where('harvest_months', 'like', "%{$currentMonth}%")
                ->limit(3)
                ->get();
            
            foreach ($seasonalCrops as $crop) {
                if (!empty($crop->crop_name)) {
                    $cropName = strtolower($crop->crop_name);
                    $keywords[] = $cropName;
                    $uniqueDetails[] = $cropName;
                }
            }
            
            // Get companion planting benefits for credibility
            $companion = DB::connection('pgsql_rag')
                ->table('companion_planting_knowledge')
                ->inRandomOrder()
                ->first();
            
            if ($companion && !empty($companion->benefits)) {
                $keywords[] = 'companion planting';
                $keywords[] = 'sustainable farming';
            }
            
            // Add CSA/local food movement terms
            $keywords[] = 'csa box';
            $keywords[] = 'farm direct';
            
        } catch (\Exception $e) {
            \Log::info('RAG enhanced fallback failed, using defaults: ' . $e->getMessage());
            $keywords = ['organic', 'seasonal', 'local', 'vegetables', 'farm fresh'];
        }
        
        // Build enhanced title (50-60 chars)
        $title = "Organic {$name} - Seasonal UK Farm Delivery";
        if (strlen($title) > 60) {
            $title = "Organic {$name} - UK Farm Fresh";
        }
        if (strlen($title) > 60) {
            $title = str_replace(' - Seasonal UK Farm Delivery', ' Box', $title);
        }
        
        // Build rich description (150-160 chars) - MUST be full length for SEO
        $descParts = [];
        
        if (!empty($uniqueDetails)) {
            $cropList = implode(', ', array_slice($uniqueDetails, 0, 2));
            $descParts[] = "Weekly {$name} from {$farmName} with seasonal {$cropList}";
        } else {
            $descParts[] = "Weekly {$name} delivered from {$farmName}";
        }
        
        // Add more detail to reach 150-160 chars
        $descParts[] = "Fresh seasonal organic vegetables";
        
        if ($isSolidarityPricing) {
            $descParts[] = "Solidarity pricing available";
        } else {
            $descParts[] = "Supporting local, sustainable farming";
        }
        
        $description = implode('. ', $descParts) . '. Order today!';
        
        // Ensure minimum 150 chars
        if (strlen($description) < 150) {
            // Add UK farming context
            $description = str_replace('. Order today!', '. Grown with care in the UK. Order your box today!', $description);
        }
        
        // Ensure exactly 160 chars or less, cutting at last complete word
        if (strlen($description) > 160) {
            $description = substr($description, 0, 160);
            // Cut at last space to avoid mid-word truncation
            $lastSpace = strrpos($description, ' ');
            if ($lastSpace !== false && $lastSpace > 140) {
                $description = substr($description, 0, $lastSpace);
            }
        }
        
        // Ensure unique keywords, limit to 10
        $keywords = array_unique($keywords);
        $keywords = array_slice($keywords, 0, 10);
        
        return [
            'title' => $title,
            'description' => $description,
            'keywords' => implode(', ', $keywords),
            'source' => 'rag_enhanced'
        ];
    }

    /**
     * Generate AI-powered product description
     */
    public function generateDescription(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $productName = $request->input('product_name', $product->name);
            $category = $request->input('category', $product->category);
            $currentDescription = $request->input('current_description', '');
            $type = $request->input('type', 'main'); // 'main' or 'short'

            // Build context from RAG and product data
            $context = $this->buildProductContextWithRAG($product);
            
            // Get seasonal information
            $currentMonth = now()->format('F');
            $seasonalCrops = DB::connection('pgsql_rag')
                ->table('uk_planting_calendar')
                ->where('harvest_months', 'like', "%{$currentMonth}%")
                ->limit(5)
                ->pluck('crop_name')
                ->toArray();
            
            // Build AI prompt for description generation
            $prompt = $this->buildDescriptionPrompt($productName, $category, $context, $seasonalCrops, $currentDescription, $type);
            
            // Call AI service with 90s timeout
            $response = Http::timeout(90)->post('http://localhost:8005/ask-ollama', [
                'question' => $prompt,
                'crop' => $category,
                'season' => $currentMonth
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success'] && isset($data['answer'])) {
                    $aiDescription = $data['answer'];
                    
                    // Clean up AI response (remove "🤖" prefixes, etc.)
                    $aiDescription = preg_replace('/^🤖\s*\*\*[^:]+:\*\*\s*/u', '', $aiDescription);
                    $aiDescription = trim($aiDescription);
                    
                    // Format description with proper paragraphs for better SEO and readability
                    $aiDescription = $this->formatDescriptionWithParagraphs($aiDescription);
                    
                    $response = [
                        'success' => true,
                        'source' => 'ai_phi3'
                    ];
                    
                    // Return appropriate field based on type
                    if ($type === 'short') {
                        $response['short_description'] = $aiDescription;
                    } else {
                        $response['description'] = $aiDescription;
                    }
                    
                    return response()->json($response);
                }
            }
            
            // Fallback to RAG-only description if AI fails
            return $this->generateRagFallbackDescription($product, $context, $seasonalCrops);

        } catch (\Exception $e) {
            Log::error('Description generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build AI prompt for product description
     */
    private function buildDescriptionPrompt($productName, $category, $context, $seasonalCrops, $currentDescription, $type = 'main')
    {
        $seasonal = count($seasonalCrops) > 0 ? implode(', ', $seasonalCrops) : 'seasonal produce';
        
        $prompt = "Write an engaging product description for '{$productName}' - a {$category} product for a UK organic farm CSA box scheme. ";
        
        if (!empty($currentDescription)) {
            $prompt .= "Current description: {$currentDescription}. Please improve and expand it. ";
        }
        
        $prompt .= "Context: This is currently harvest season for {$seasonal}. ";
        
        if (!empty($context)) {
            // $context is a string, not an array
            $prompt .= "Additional context: " . substr($context, 0, 200) . ". ";
        }
        
        if ($type === 'short') {
            $prompt .= "\n\nIMPORTANT: Write a SHORT description - ONLY 1-2 sentences (maximum 50 words). ";
            $prompt .= "Make it punchy and compelling. Focus on the key benefit and what makes this product special. ";
            $prompt .= "Use warm, inviting language. No paragraphs, just a brief hook.";
        } else {
            $prompt .= "\n\nIMPORTANT: Write EXACTLY 3 paragraphs separated by double line breaks. Each paragraph should be 2-3 sentences. Structure:\n";
            $prompt .= "Paragraph 1: Opening hook about freshness and what makes this product special\n";
            $prompt .= "Paragraph 2: Growing methods, organic practices, and seasonal benefits\n";
            $prompt .= "Paragraph 3: Community impact and call to action\n\n";
            $prompt .= "Use warm, inviting language. Avoid technical jargon. Make it customer-focused and SEO-friendly.";
        }
        
        return $prompt;
    }

    /**
     * Format AI-generated description with proper HTML paragraphs
     */
    private function formatDescriptionWithParagraphs($text)
    {
        // Remove any existing HTML tags
        $text = strip_tags($text);
        
        // Trim whitespace
        $text = trim($text);
        
        // Split by double newlines or sentence breaks to create paragraphs
        // First, try to split by double newlines
        $paragraphs = preg_split('/\n\n+/', $text);
        
        // If we don't have at least 2 paragraphs, try splitting by sentences
        if (count($paragraphs) < 2) {
            // Split into sentences (ending with . ! or ?)
            $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            
            // Group sentences into paragraphs (about 2-3 sentences each)
            $paragraphs = [];
            $currentParagraph = [];
            $sentencesPerParagraph = ceil(count($sentences) / 3);
            
            foreach ($sentences as $i => $sentence) {
                $currentParagraph[] = $sentence;
                
                if (count($currentParagraph) >= $sentencesPerParagraph || $i == count($sentences) - 1) {
                    $paragraphs[] = implode(' ', $currentParagraph);
                    $currentParagraph = [];
                }
            }
        }
        
        // Wrap each paragraph in <p> tags
        $html = '';
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (!empty($para)) {
                $html .= '<p>' . htmlspecialchars($para, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }
        
        return $html;
    }

    /**
     * Generate RAG-based fallback description
     */
    private function generateRagFallbackDescription($product, $context, $seasonalCrops)
    {
        $seasonal = count($seasonalCrops) > 0 ? implode(', ', $seasonalCrops) : 'seasonal produce';
        
        $description = "Our {$product->name} is grown with care on our UK organic farm. ";
        $description .= "Currently in season alongside {$seasonal}, this product represents the best of sustainable, local agriculture. ";
        
        if (!empty($context)) {
            $description .= $context[0] . " ";
        }
        
        $description .= "Delivered fresh as part of your CSA veg box, supporting local farming and reducing food miles. ";
        $description .= "Grown without synthetic pesticides or fertilizers, using companion planting and crop rotation for natural pest management.";
        
        return response()->json([
            'success' => true,
            'description' => $description,
            'source' => 'rag_fallback'
        ]);
    }

    /**
     * Generate short description from main description using AI summarization
     */
    public function generateShortDescription(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $productName = $request->input('product_name', $product->name);
            $mainDescription = $request->input('main_description', '');

            if (empty($mainDescription)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Main description is required'
                ], 400);
            }

            // Build AI prompt for short description summarization
            $prompt = "Summarize the following product description into a concise 1-2 sentence summary (150-300 characters) suitable for product listings and previews. Keep the tone warm and customer-focused, highlighting the key benefits:\n\n{$mainDescription}";
            
            // Call AI service with 90s timeout
            $response = Http::timeout(90)->post('http://localhost:8005/ask-ollama', [
                'question' => $prompt,
                'crop' => $productName,
                'season' => now()->format('F')
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success'] && isset($data['answer'])) {
                    $shortDescription = $data['answer'];
                    
                    // Clean up AI response (remove prefixes, extra whitespace)
                    $shortDescription = preg_replace('/^🤖\s*\*\*[^:]+:\*\*\s*/u', '', $shortDescription);
                    $shortDescription = trim($shortDescription);
                    
                    // If too long, truncate to 300 chars
                    if (strlen($shortDescription) > 300) {
                        $shortDescription = substr($shortDescription, 0, 297) . '...';
                    }
                    
                    Log::info('Short description generated via AI', [
                        'product_id' => $product->id,
                        'length' => strlen($shortDescription)
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'short_description' => $shortDescription,
                        'source' => 'ai_phi3'
                    ]);
                }
            }
            
            // Fallback: Simple truncation if AI fails
            $shortDescription = substr(strip_tags($mainDescription), 0, 250) . '...';
            
            return response()->json([
                'success' => true,
                'short_description' => $shortDescription,
                'source' => 'truncation_fallback'
            ]);

        } catch (\Exception $e) {
            Log::error('Short description generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate short description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate product tags using AI based on name, description, and categories
     */
    public function generateTags(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $productName = $request->input('product_name', $product->name);
            $description = $request->input('description', '');
            $categories = $request->input('categories', []);

            if (empty($productName)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product name is required'
                ], 400);
            }

            // Build AI prompt for tag generation
            $contextParts = [];
            $contextParts[] = "Product: {$productName}";
            
            if (!empty($categories)) {
                $contextParts[] = "Categories: " . implode(', ', $categories);
            }
            
            if (!empty($description)) {
                $contextParts[] = "Description: {$description}";
            }
            
            $context = implode("\n", $contextParts);
            
            $prompt = "Based on this product information, suggest 5-8 relevant tags that would help customers find this product. Tags should be short (1-2 words), descriptive, and commonly used in organic/local food contexts.\n\n{$context}\n\nProvide ONLY the tags as a comma-separated list, nothing else. Examples: organic, seasonal, local, fresh, sustainable, farm-fresh, artisan, handmade, heritage, heirloom, pesticide-free, chemical-free, biodynamic";
            
            // Call AI service with 90s timeout
            $response = Http::timeout(90)->post('http://localhost:8005/ask-ollama', [
                'question' => $prompt,
                'crop' => $productName,
                'season' => now()->format('F')
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success'] && isset($data['answer'])) {
                    $aiResponse = $data['answer'];
                    
                    // Clean up AI response
                    $aiResponse = preg_replace('/^🤖\s*\*\*[^:]+:\*\*\s*/u', '', $aiResponse);
                    $aiResponse = trim($aiResponse);
                    
                    // Extract tags from response (handle various formats)
                    $aiResponse = str_replace(['"', "'", '  '], ['', '', ' '], $aiResponse);
                    $tags = array_map('trim', explode(',', $aiResponse));
                    
                    // Clean up tags (remove numbers, bullets, extra text)
                    $tags = array_map(function($tag) {
                        $tag = preg_replace('/^\d+[\.)]\s*/', '', $tag); // Remove "1. " or "1) "
                        $tag = preg_replace('/^[-•*]\s*/', '', $tag);    // Remove bullets
                        $tag = strtolower(trim($tag));
                        return $tag;
                    }, $tags);
                    
                    // Filter out empty or invalid tags
                    $tags = array_filter($tags, function($tag) {
                        return !empty($tag) && strlen($tag) <= 30 && !str_contains($tag, ':');
                    });
                    
                    // Limit to 8 tags
                    $tags = array_slice(array_values($tags), 0, 8);
                    
                    Log::info('Tags generated via AI', [
                        'product_id' => $product->id,
                        'tag_count' => count($tags),
                        'tags' => $tags
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'tags' => $tags,
                        'source' => 'ai_phi3'
                    ]);
                }
            }
            
            // Fallback: Generate basic tags from product name and categories
            $fallbackTags = [];
            
            // Add category-based tags
            foreach ($categories as $category) {
                $fallbackTags[] = strtolower($category);
            }
            
            // Add basic tags based on product name keywords
            $keywords = ['organic', 'fresh', 'local', 'seasonal', 'farm', 'natural'];
            foreach ($keywords as $keyword) {
                if (stripos($productName, $keyword) !== false) {
                    $fallbackTags[] = $keyword;
                }
            }
            
            // Default tags for farm products
            $fallbackTags = array_merge($fallbackTags, ['sustainable', 'quality']);
            $fallbackTags = array_unique(array_slice($fallbackTags, 0, 6));
            
            return response()->json([
                'success' => true,
                'tags' => array_values($fallbackTags),
                'source' => 'fallback'
            ]);

        } catch (\Exception $e) {
            Log::error('Tag generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate tags: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync product images to WooCommerce
     * Main image → _thumbnail_id
     * Gallery → _product_image_gallery
     */
    private function syncImagesToWooCommerce($product)
    {
        try {
            if (!$product->woo_product_id) {
                return;
            }

            $wooProductId = $product->woo_product_id;

            // Sync main image (featured image)
            if ($product->image_url) {
                // In real implementation, you would:
                // 1. Upload image to WordPress media library
                // 2. Get the attachment ID
                // 3. Set as _thumbnail_id meta
                
                // For now, store the URL in a custom meta field
                DB::connection('wordpress')->table('postmeta')->updateOrInsert(
                    ['post_id' => $wooProductId, 'meta_key' => '_product_image_url'],
                    ['meta_value' => route('product.image', ['path' => $product->image_url])]
                );
            } else {
                // Remove featured image
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->where('post_id', $wooProductId)
                    ->where('meta_key', '_thumbnail_id')
                    ->delete();
            }

            // Sync gallery images
            if ($product->gallery_images && count($product->gallery_images) > 0) {
                // In real implementation, upload to WordPress and get attachment IDs
                // Store as comma-separated attachment IDs in _product_image_gallery
                
                // For now, store URLs in custom meta
                $galleryUrls = array_map(function($img) {
                    return route('product.image', ['path' => $img]);
                }, $product->gallery_images);
                
                DB::connection('wordpress')->table('postmeta')->updateOrInsert(
                    ['post_id' => $wooProductId, 'meta_key' => '_product_gallery_urls'],
                    ['meta_value' => json_encode($galleryUrls)]
                );
            } else {
                // Clear gallery
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->where('post_id', $wooProductId)
                    ->where('meta_key', '_product_image_gallery')
                    ->delete();
            }

            Log::info('Product images synced to WooCommerce', [
                'product_id' => $product->id,
                'woo_product_id' => $wooProductId,
                'has_main_image' => !empty($product->image_url),
                'gallery_count' => count($product->gallery_images ?? [])
            ]);

            // Sync categories to WooCommerce taxonomy
            if ($product->product_categories && count($product->product_categories) > 0) {
                $this->syncCategoriesToWooCommerce($product, $wooProductId);
            }

            // Sync tags to WooCommerce taxonomy
            if ($product->product_tags && count($product->product_tags) > 0) {
                $this->syncTagsToWooCommerce($product, $wooProductId);
            }

            // Sync brand as custom meta
            if ($product->brand) {
                DB::connection('wordpress')->table('postmeta')->updateOrInsert(
                    ['post_id' => $wooProductId, 'meta_key' => '_product_brand'],
                    ['meta_value' => $product->brand]
                );
            }

        } catch (\Exception $e) {
            Log::error('Failed to sync images to WooCommerce', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync short description to WooCommerce post_excerpt
     */
    private function syncShortDescriptionToWooCommerce($product)
    {
        try {
            if (!$product->woo_product_id) {
                return;
            }

            $shortDescription = $product->metadata['short_description'] ?? '';

            // Update WooCommerce post_excerpt
            DB::connection('wordpress')
                ->table('posts')
                ->where('ID', $product->woo_product_id)
                ->update([
                    'post_excerpt' => $shortDescription
                ]);

            Log::info('Short description synced to WooCommerce', [
                'product_id' => $product->id,
                'woo_product_id' => $product->woo_product_id,
                'short_desc_length' => strlen($shortDescription)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync short description to WooCommerce', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync product shipping class to WooCommerce
     */
    private function syncShippingClassToWooCommerce($product)
    {
        try {
            if (!$product->woo_product_id) {
                return;
            }

            // Get the shipping class slug if one is assigned
            $shippingClassSlug = '';
            
            if ($product->shipping_class_id) {
                $shippingClass = \App\Models\ShippingClass::find($product->shipping_class_id);
                
                if ($shippingClass && $shippingClass->woo_id) {
                    // Get the WooCommerce term slug
                    $wooTerm = DB::connection('wordpress')
                        ->table('terms')
                        ->where('term_id', $shippingClass->woo_id)
                        ->first();
                    
                    if ($wooTerm) {
                        $shippingClassSlug = $wooTerm->slug;
                    }
                }
            }

            // Update WooCommerce _shipping_class meta
            // Check if meta exists
            $existingMeta = DB::connection('wordpress')
                ->table('postmeta')
                ->where('post_id', $product->woo_product_id)
                ->where('meta_key', '_shipping_class')
                ->first();

            if ($existingMeta) {
                // Update existing meta
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->where('post_id', $product->woo_product_id)
                    ->where('meta_key', '_shipping_class')
                    ->update(['meta_value' => $shippingClassSlug]);
            } else {
                // Insert new meta
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->insert([
                        'post_id' => $product->woo_product_id,
                        'meta_key' => '_shipping_class',
                        'meta_value' => $shippingClassSlug
                    ]);
            }

            Log::info('Shipping class synced to WooCommerce', [
                'product_id' => $product->id,
                'woo_product_id' => $product->woo_product_id,
                'shipping_class_slug' => $shippingClassSlug ?: 'none (default)'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync shipping class to WooCommerce', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync product categories to WooCommerce taxonomy (product_cat)
     */
    private function syncCategoriesToWooCommerce($product, $wooProductId)
    {
        try {
            // Clear existing category relationships
            DB::connection('wordpress')
                ->table('term_relationships')
                ->where('object_id', $wooProductId)
                ->whereIn('term_taxonomy_id', function($query) {
                    $query->select('term_taxonomy_id')
                        ->from('term_taxonomy')
                        ->where('taxonomy', 'product_cat');
                })
                ->delete();

            // Add new categories
            foreach ($product->product_categories as $categoryName) {
                // Check if term exists
                $term = DB::connection('wordpress')
                    ->table('terms')
                    ->where('name', $categoryName)
                    ->first();

                if (!$term) {
                    // Create new term
                    $termId = DB::connection('wordpress')->table('terms')->insertGetId([
                        'name' => $categoryName,
                        'slug' => \Str::slug($categoryName),
                        'term_group' => 0
                    ]);

                    // Create taxonomy entry
                    $termTaxonomyId = DB::connection('wordpress')->table('term_taxonomy')->insertGetId([
                        'term_id' => $termId,
                        'taxonomy' => 'product_cat',
                        'description' => '',
                        'parent' => 0,
                        'count' => 0
                    ]);
                } else {
                    // Get existing taxonomy ID
                    $termTaxonomyId = DB::connection('wordpress')
                        ->table('term_taxonomy')
                        ->where('term_id', $term->term_id)
                        ->where('taxonomy', 'product_cat')
                        ->value('term_taxonomy_id');
                }

                // Link product to category
                if ($termTaxonomyId) {
                    DB::connection('wordpress')->table('term_relationships')->insert([
                        'object_id' => $wooProductId,
                        'term_taxonomy_id' => $termTaxonomyId,
                        'term_order' => 0
                    ]);

                    // Update term count
                    DB::connection('wordpress')
                        ->table('term_taxonomy')
                        ->where('term_taxonomy_id', $termTaxonomyId)
                        ->increment('count');
                }
            }

            Log::info('Categories synced to WooCommerce', [
                'product_id' => $product->id,
                'categories' => $product->product_categories
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync categories to WooCommerce', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync product tags to WooCommerce taxonomy (product_tag)
     */
    private function syncTagsToWooCommerce($product, $wooProductId)
    {
        try {
            // Clear existing tag relationships
            DB::connection('wordpress')
                ->table('term_relationships')
                ->where('object_id', $wooProductId)
                ->whereIn('term_taxonomy_id', function($query) {
                    $query->select('term_taxonomy_id')
                        ->from('term_taxonomy')
                        ->where('taxonomy', 'product_tag');
                })
                ->delete();

            // Add new tags
            foreach ($product->product_tags as $tagName) {
                if (empty($tagName)) continue;

                // Check if term exists
                $term = DB::connection('wordpress')
                    ->table('terms')
                    ->where('name', $tagName)
                    ->first();

                if (!$term) {
                    // Create new term
                    $termId = DB::connection('wordpress')->table('terms')->insertGetId([
                        'name' => $tagName,
                        'slug' => \Str::slug($tagName),
                        'term_group' => 0
                    ]);

                    // Create taxonomy entry
                    $termTaxonomyId = DB::connection('wordpress')->table('term_taxonomy')->insertGetId([
                        'term_id' => $termId,
                        'taxonomy' => 'product_tag',
                        'description' => '',
                        'parent' => 0,
                        'count' => 0
                    ]);
                } else {
                    // Get existing taxonomy ID
                    $termTaxonomyId = DB::connection('wordpress')
                        ->table('term_taxonomy')
                        ->where('term_id', $term->term_id)
                        ->where('taxonomy', 'product_tag')
                        ->value('term_taxonomy_id');
                }

                // Link product to tag
                if ($termTaxonomyId) {
                    DB::connection('wordpress')->table('term_relationships')->insert([
                        'object_id' => $wooProductId,
                        'term_taxonomy_id' => $termTaxonomyId,
                        'term_order' => 0
                    ]);

                    // Update term count
                    DB::connection('wordpress')
                        ->table('term_taxonomy')
                        ->where('term_taxonomy_id', $termTaxonomyId)
                        ->increment('count');
                }
            }

            Log::info('Tags synced to WooCommerce', [
                'product_id' => $product->id,
                'tags' => $product->product_tags
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync tags to WooCommerce', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync product reviews enabled status to WooCommerce
     */
    private function syncReviewsToWooCommerce($product)
    {
        try {
            if (!$product->woo_product_id) {
                return;
            }

            // Update comment_status in posts table
            $commentStatus = $product->reviews_enabled ? 'open' : 'closed';
            
            DB::connection('wordpress')
                ->table('posts')
                ->where('ID', $product->woo_product_id)
                ->update(['comment_status' => $commentStatus]);

            Log::info('Reviews status synced to WooCommerce', [
                'product_id' => $product->id,
                'woo_product_id' => $product->woo_product_id,
                'reviews_enabled' => $product->reviews_enabled
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync reviews status to WooCommerce', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync upsell products to WooCommerce
     */
    private function syncUpsellsToWooCommerce($product)
    {
        try {
            if (!$product->woo_product_id) {
                return;
            }

            // Get WooCommerce product IDs from our product IDs
            $wooUpsellIds = [];
            if (!empty($product->upsell_ids)) {
                $upsellProducts = Product::whereIn('id', $product->upsell_ids)
                    ->whereNotNull('woo_product_id')
                    ->pluck('woo_product_id')
                    ->toArray();
                $wooUpsellIds = $upsellProducts;
            }

            // Serialize for WooCommerce meta storage
            $metaValue = !empty($wooUpsellIds) ? serialize($wooUpsellIds) : '';

            // Check if meta exists
            $existingMeta = DB::connection('wordpress')
                ->table('postmeta')
                ->where('post_id', $product->woo_product_id)
                ->where('meta_key', '_upsell_ids')
                ->first();

            if ($existingMeta) {
                // Update existing meta
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->where('post_id', $product->woo_product_id)
                    ->where('meta_key', '_upsell_ids')
                    ->update(['meta_value' => $metaValue]);
            } else {
                // Insert new meta
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->insert([
                        'post_id' => $product->woo_product_id,
                        'meta_key' => '_upsell_ids',
                        'meta_value' => $metaValue
                    ]);
            }

            Log::info('Upsells synced to WooCommerce', [
                'product_id' => $product->id,
                'woo_product_id' => $product->woo_product_id,
                'upsell_count' => count($wooUpsellIds)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync upsells to WooCommerce', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync cross-sell products to WooCommerce
     */
    private function syncCrosssellsToWooCommerce($product)
    {
        try {
            if (!$product->woo_product_id) {
                return;
            }

            // Get WooCommerce product IDs from our product IDs
            $wooCrosssellIds = [];
            if (!empty($product->crosssell_ids)) {
                $crosssellProducts = Product::whereIn('id', $product->crosssell_ids)
                    ->whereNotNull('woo_product_id')
                    ->pluck('woo_product_id')
                    ->toArray();
                $wooCrosssellIds = $crosssellProducts;
            }

            // Serialize for WooCommerce meta storage
            $metaValue = !empty($wooCrosssellIds) ? serialize($wooCrosssellIds) : '';

            // Check if meta exists
            $existingMeta = DB::connection('wordpress')
                ->table('postmeta')
                ->where('post_id', $product->woo_product_id)
                ->where('meta_key', '_crosssell_ids')
                ->first();

            if ($existingMeta) {
                // Update existing meta
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->where('post_id', $product->woo_product_id)
                    ->where('meta_key', '_crosssell_ids')
                    ->update(['meta_value' => $metaValue]);
            } else {
                // Insert new meta
                DB::connection('wordpress')
                    ->table('postmeta')
                    ->insert([
                        'post_id' => $product->woo_product_id,
                        'meta_key' => '_crosssell_ids',
                        'meta_value' => $metaValue
                    ]);
            }

            Log::info('Cross-sells synced to WooCommerce', [
                'product_id' => $product->id,
                'woo_product_id' => $product->woo_product_id,
                'crosssell_count' => count($wooCrosssellIds)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync cross-sells to WooCommerce', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

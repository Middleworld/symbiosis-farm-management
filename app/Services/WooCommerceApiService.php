<?php

namespace App\Services;

use Automattic\WooCommerce\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;

class WooCommerceApiService
{
    protected $woocommerce;
    protected $cacheTtl = 3600; // 1 hour

    public function __construct()
    {
        $baseUrl = config('services.woocommerce.base_url');
        $consumerKey = config('services.woocommerce.consumer_key');
        $consumerSecret = config('services.woocommerce.consumer_secret');
        
        // Validate configuration
        if (empty($baseUrl) || empty($consumerKey) || empty($consumerSecret)) {
            throw new \Exception(
                'WooCommerce API credentials not configured. Please set WOOCOMMERCE_URL, WOOCOMMERCE_CONSUMER_KEY, and WOOCOMMERCE_CONSUMER_SECRET in .env'
            );
        }
        
        // Ensure URL has trailing slash for WooCommerce API
        $baseUrl = rtrim($baseUrl, '/') . '/';
        
        Log::info('Initializing WooCommerce API', [
            'base_url' => $baseUrl,
            'has_key' => !empty($consumerKey),
            'has_secret' => !empty($consumerSecret)
        ]);
        
        $this->woocommerce = new Client(
            $baseUrl,
            $consumerKey,
            $consumerSecret,
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'timeout' => 30,
                'verify_ssl' => true,
                'query_string_auth' => true // Use query string auth for better compatibility
            ]
        );
    }

    /**
     * Test WooCommerce API connection
     */
    public function testConnection()
    {
        try {
            $baseUrl = config('services.woocommerce.base_url');
            
            // Try to get store info to test connection
            $response = $this->woocommerce->get('');
            
            Log::info('WooCommerce API connection test successful', [
                'url' => $baseUrl,
                'store_name' => $response->name ?? 'unknown'
            ]);
            
            return [
                'success' => true,
                'message' => 'WooCommerce API connection successful',
                'data' => [
                    'base_url' => $baseUrl,
                    'store_name' => $response->name ?? 'unknown',
                    'woocommerce_version' => $response->woocommerce_version ?? 'unknown',
                    'wordpress_version' => $response->wordpress_version ?? 'unknown'
                ]
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Provide helpful error messages
            if (strpos($errorMessage, 'rest_no_route') !== false) {
                $errorMessage = "REST API routes not registered. Go to WordPress Admin > Settings > Permalinks > Save Changes. Current URL: " . config('services.woocommerce.base_url');
            } elseif (strpos($errorMessage, 'Could not resolve host') !== false) {
                $errorMessage = "Cannot reach WooCommerce URL. Check WOOCOMMERCE_URL in .env: " . config('services.woocommerce.base_url');
            }
            
            Log::error('WooCommerce API connection test failed', [
                'error' => $errorMessage,
                'url' => config('services.woocommerce.base_url')
            ]);
            
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $errorMessage,
                'data' => [
                    'base_url' => config('services.woocommerce.base_url')
                ]
            ];
        }
    }

    /**
     * Get WooCommerce shipping zones
     */
    public function getShippingZones($params = [])
    {
        $cacheKey = 'woocommerce_shipping_zones_' . md5(serialize($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($params) {
            try {
                $response = $this->woocommerce->get('shipping/zones', $params);
                return ['success' => true, 'data' => $response];
            } catch (\Exception $e) {
                Log::error('Failed to fetch WooCommerce shipping zones: ' . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        });
    }

    /**
     * Get shipping methods for a specific zone
     */
    public function getShippingMethods($zoneId, $params = [])
    {
        $cacheKey = 'woocommerce_shipping_methods_' . $zoneId . '_' . md5(serialize($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($zoneId, $params) {
            try {
                $response = $this->woocommerce->get("shipping/zones/{$zoneId}/methods", $params);
                return ['success' => true, 'data' => $response];
            } catch (\Exception $e) {
                Log::error('Failed to fetch WooCommerce shipping methods: ' . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        });
    }

    /**
     * Get all shipping classes
     */
    public function getShippingClasses($params = [])
    {
        $cacheKey = 'woocommerce_shipping_classes_' . md5(serialize($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($params) {
            try {
                $response = $this->woocommerce->get('products/shipping_classes', $params);
                return ['success' => true, 'data' => $response];
            } catch (\Exception $e) {
                Log::error('Failed to fetch WooCommerce shipping classes: ' . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        });
    }

    /**
     * Create a shipping class in WooCommerce
     */
    public function createShippingClass($shippingClassData)
    {
        try {
            $response = $this->woocommerce->post('products/shipping_classes', $shippingClassData);
            
            // Clear shipping classes cache
            Cache::forget('woocommerce_shipping_classes_' . md5(serialize([])));
            
            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            Log::error('Failed to create WooCommerce shipping class: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update a shipping class in WooCommerce
     */
    public function updateShippingClass($shippingClassId, $shippingClassData)
    {
        try {
            $response = $this->woocommerce->put("products/shipping_classes/{$shippingClassId}", $shippingClassData);
            
            // Clear shipping classes cache
            Cache::forget('woocommerce_shipping_classes_' . md5(serialize([])));
            
            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            Log::error('Failed to update WooCommerce shipping class: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all WooCommerce products
     */
    public function getProducts($params = [])
    {
        $cacheKey = 'woocommerce_products_' . md5(serialize($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($params) {
            try {
                $defaultParams = [
                    'per_page' => 100,
                    'page' => 1,
                    'status' => 'any'
                ];

                $params = array_merge($defaultParams, $params);
                $products = $this->woocommerce->get('products', $params);

                return ['success' => true, 'data' => $products];
            } catch (\Exception $e) {
                Log::error('Failed to get WooCommerce products: ' . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        });
    }

    /**
     * Get a specific WooCommerce product by ID
     */
    public function getProduct($wooProductId)
    {
        $cacheKey = 'woocommerce_product_' . $wooProductId;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($wooProductId) {
            try {
                $product = $this->woocommerce->get("products/{$wooProductId}");
                return ['success' => true, 'data' => $product];
            } catch (\Exception $e) {
                Log::error("Failed to get WooCommerce product {$wooProductId}: " . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        });
    }

    /**
     * Create a product in WooCommerce
     */
    public function createProduct($wooProductData)
    {
        try {
            $response = $this->woocommerce->post('products', $wooProductData);

            // Clear cache
            Cache::forget('woocommerce_products_' . md5(serialize([])));

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            Log::error('Failed to create WooCommerce product: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if a product exists in WooCommerce
     */
    public function productExists($wooProductId)
    {
        try {
            $this->woocommerce->get("products/{$wooProductId}");
            return true;
        } catch (\Exception $e) {
            // If we get a 404 or invalid ID error, product doesn't exist
            if (strpos($e->getMessage(), 'Invalid ID') !== false || strpos($e->getMessage(), '404') !== false) {
                return false;
            }
            // Re-throw other exceptions
            throw $e;
        }
    }

    /**
     * Update a product in WooCommerce
     */
    public function updateProduct($wooProductId, $wooProductData)
    {
        try {
            $response = $this->woocommerce->put("products/{$wooProductId}", $wooProductData);

            // Clear cache
            Cache::forget('woocommerce_product_' . $wooProductId);
            Cache::forget('woocommerce_products_' . md5(serialize([])));

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            Log::error("Failed to update WooCommerce product {$wooProductId}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete a product from WooCommerce
     */
    public function deleteProduct($wooProductId, $force = false)
    {
        try {
            $params = $force ? ['force' => true] : [];
            $response = $this->woocommerce->delete("products/{$wooProductId}", $params);

            // Clear cache
            Cache::forget('woocommerce_product_' . $wooProductId);
            Cache::forget('woocommerce_products_' . md5(serialize([])));

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            Log::error("Failed to delete WooCommerce product {$wooProductId}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync a Laravel product with WooCommerce
     */
    public function syncProduct(Product $product)
    {
        try {
            $productData = $product->toArray();
            Log::info('Syncing product', ['product_id' => $product->id, 'type' => $product->product_type, 'has_price' => isset($productData['price']), 'price' => $productData['price'] ?? 'NULL']);

            $wooProductData = $this->formatProductForWooCommerce($productData, $product);

            if ($product->woo_product_id) {
                // Check if product exists in WooCommerce before updating
                $exists = $this->productExists($product->woo_product_id);
                
                if ($exists) {
                    // Update existing WooCommerce product
                    $result = $this->updateProduct($product->woo_product_id, $wooProductData);
                    if ($result['success']) {
                        // Sync variations if variable product
                        if ($product->product_type === 'variable') {
                            $this->syncProductVariations($product);
                        }
                        
                        // Sync solidarity pricing meta
                        $this->syncSolidarityPricingMeta($product);
                        
                        Log::info("Updated WooCommerce product {$product->woo_product_id} for Laravel product {$product->id}");
                    }
                } else {
                    // Product doesn't exist in WooCommerce, create it and update the ID
                    Log::warning("WooCommerce product {$product->woo_product_id} doesn't exist, creating new product");
                    $result = $this->createProduct($wooProductData);
                    if ($result['success']) {
                        $wooProduct = $result['data'];
                        $product->update(['woo_product_id' => $wooProduct->id]);
                        
                        // Sync variations if variable product
                        if ($product->product_type === 'variable') {
                            $this->syncProductVariations($product);
                        }
                        
                        // Sync solidarity pricing meta
                        $this->syncSolidarityPricingMeta($product);
                        
                        Log::info("Created WooCommerce product {$wooProduct->id} for Laravel product {$product->id}");
                    }
                }
            } else {
                // Create new WooCommerce product
                $result = $this->createProduct($wooProductData);
                if ($result['success']) {
                    $wooProduct = $result['data']; // This is an object, not array
                    $product->update(['woo_product_id' => $wooProduct->id]);
                    
                    // Sync variations if variable product
                    if ($product->product_type === 'variable') {
                        $this->syncProductVariations($product);
                    }
                    
                    // Sync solidarity pricing meta
                    $this->syncSolidarityPricingMeta($product);
                    
                    Log::info("Created WooCommerce product {$wooProduct->id} for Laravel product {$product->id}");
                }
            }

            return $result;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Detect common WooCommerce API errors
            if (strpos($errorMessage, 'rest_no_route') !== false) {
                $errorMessage = "WooCommerce REST API routes not found. Please:\n" .
                               "1. Go to WordPress Admin > Settings > Permalinks\n" .
                               "2. Click 'Save Changes' to flush rewrite rules\n" .
                               "3. Verify WOOCOMMERCE_URL in .env points to correct WordPress installation\n" .
                               "Current URL: " . config('services.woocommerce.base_url');
            } elseif (strpos($errorMessage, 'woocommerce_rest_authentication') !== false) {
                $errorMessage = "WooCommerce REST API authentication failed. Check consumer key/secret in .env";
            } elseif (strpos($errorMessage, 'Could not resolve host') !== false) {
                $errorMessage = "Cannot connect to WooCommerce URL. Check WOOCOMMERCE_URL in .env: " . config('services.woocommerce.base_url');
            }
            
            Log::error("Failed to sync product {$product->id}", [
                'error' => $errorMessage,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => $errorMessage];
        }
    }

    /**
     * Sync all active Laravel products with WooCommerce
     */
    public function syncAllProducts()
    {
        $products = Product::where('is_active', true)->get();
        $results = [
            'total' => $products->count(),
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($products as $product) {
            $result = $this->syncProduct($product);
            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'error' => $result['message']
                ];
            }
        }

        Log::info("Product sync completed: {$results['successful']} successful, {$results['failed']} failed");

        return $results;
    }

    /**
     * Get WooCommerce product variations
     */
    public function getProductVariations($wooProductId)
    {
        $cacheKey = 'woocommerce_product_variations_' . $wooProductId;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($wooProductId) {
            try {
                $variations = $this->woocommerce->get("products/{$wooProductId}/variations");
                return ['success' => true, 'data' => $variations];
            } catch (\Exception $e) {
                Log::error("Failed to get variations for product {$wooProductId}: " . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        });
    }

    /**
     * Create product variation in WooCommerce
     */
    public function createProductVariation($wooProductId, $variationData)
    {
        try {
            $response = $this->woocommerce->post("products/{$wooProductId}/variations", $variationData);

            // Clear cache
            Cache::forget('woocommerce_product_variations_' . $wooProductId);

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            Log::error("Failed to create variation for product {$wooProductId}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get WooCommerce categories
     */
    public function getCategories($params = [])
    {
        $cacheKey = 'woocommerce_categories_' . md5(serialize($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($params) {
            try {
                $defaultParams = ['per_page' => 100, 'page' => 1];
                $params = array_merge($defaultParams, $params);
                $categories = $this->woocommerce->get('products/categories', $params);

                return ['success' => true, 'data' => $categories];
            } catch (\Exception $e) {
                Log::error('Failed to get WooCommerce categories: ' . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        });
    }

    /**
     * Format Laravel product data for WooCommerce API
     */
    protected function formatProductForWooCommerce($productData, Product $product = null)
    {
        // Validate required fields
        if (!isset($productData['name']) || empty($productData['name'])) {
            throw new \InvalidArgumentException('Product name is required for WooCommerce sync');
        }

        if (!isset($productData['sku']) || empty($productData['sku'])) {
            throw new \InvalidArgumentException('Product SKU is required for WooCommerce sync');
        }

        // Determine product type
        $productType = $productData['product_type'] ?? 'simple';
        
        // For simple products, price is required
        if ($productType === 'simple' && (!isset($productData['price']) || $productData['price'] === null)) {
            throw new \InvalidArgumentException('Product price is required for simple WooCommerce products');
        }

        $wooProduct = [
            'name' => $productData['name'],
            'type' => $productType, // 'simple' or 'variable'
            'description' => $productData['description'] ?? '',
            'short_description' => substr($productData['description'] ?? '', 0, 150),
            'sku' => $productData['sku'],
            'status' => ($productData['is_active'] ?? true) ? 'publish' : 'draft',
            'weight' => (string) ($productData['weight'] ?? ''),
            'dimensions' => [
                'length' => '',
                'width' => '',
                'height' => ''
            ]
        ];
        
        // Only add price/stock for simple products
        if ($productType === 'simple') {
            $wooProduct['regular_price'] = (string) $productData['price'];
            $wooProduct['manage_stock'] = true;
            $wooProduct['stock_quantity'] = $productData['stock_quantity'] ?? 0;
            // Explicitly set stock status - instock if quantity > 0, otherwise outofstock
            $wooProduct['stock_status'] = ($productData['stock_quantity'] ?? 0) > 0 ? 'instock' : 'outofstock';
        } else {
            // Variable products manage stock at variation level
            $wooProduct['manage_stock'] = false;
            $wooProduct['stock_status'] = 'instock'; // Variable products typically show as in stock
        }

        // Add categories if available
        if (!empty($productData['category'])) {
            $wooProduct['categories'] = [
                ['name' => $productData['category']]
            ];
        }

        // Skip image syncing for now - images are stored in Laravel and displayed there
        // WooCommerce can't access images via URL due to cross-site restrictions
        // Images will still display correctly in the Laravel admin and box customization interface
        /*
        if (!empty($productData['local_image_path'])) {
            $wooProduct['images'] = [
                [
                    'src' => url('storage/' . $productData['local_image_path']),
                    'name' => $productData['name'],
                    'alt' => $productData['name']
                ]
            ];
        } elseif (!empty($productData['image_url']) && !str_starts_with($productData['image_url'], 'http')) {
            // Only sync local images (legacy format)
            $wooProduct['images'] = [
                [
                    'src' => route('product.image', ['path' => $productData['image_url']]),
                    'name' => $productData['name'],
                    'alt' => $productData['name']
                ]
            ];
        }
        */

        // Add tax settings
        if (isset($productData['is_taxable']) && $productData['is_taxable']) {
            $wooProduct['tax_status'] = 'taxable';
            if (!empty($productData['tax_rate'])) {
                $wooProduct['tax_class'] = 'standard'; // Can be extended for custom tax classes
            }
        } else {
            $wooProduct['tax_status'] = 'none';
        }

        // For variable products, collect unique attributes from variations
        if ($productType === 'variable' && $product) {
            $wooProduct['attributes'] = $this->collectProductAttributes($product);
        }

        return $wooProduct;
    }

    /**
     * Collect unique attributes from product variations
     */
    protected function collectProductAttributes(Product $product)
    {
        $attributesMap = [];
        
        foreach ($product->variations as $variation) {
            if ($variation->attributes) {
                foreach ($variation->attributes as $key => $value) {
                    if (!isset($attributesMap[$key])) {
                        $attributesMap[$key] = [];
                    }
                    if (!in_array($value, $attributesMap[$key])) {
                        $attributesMap[$key][] = $value;
                    }
                }
            }
        }
        
        $attributes = [];
        foreach ($attributesMap as $name => $options) {
            $attributes[] = [
                'name' => $name,
                'visible' => true,
                'variation' => true,
                'options' => $options
            ];
        }
        
        return $attributes;
    }

    /**
     * Sync product variations to WooCommerce
     */
    protected function syncProductVariations(Product $product)
    {
        if (!$product->woo_product_id || $product->product_type !== 'variable') {
            return;
        }
        
        try {
            $variations = $product->variations;
            
            foreach ($variations as $variation) {
                $variationData = [
                    'regular_price' => (string) $variation->price,
                    'sku' => $variation->sku,
                    'manage_stock' => true,
                    'stock_quantity' => $variation->stock_quantity ?? 0,
                    'attributes' => []
                ];
                
                // Format attributes
                if ($variation->attributes) {
                    foreach ($variation->attributes as $key => $value) {
                        $variationData['attributes'][] = [
                            'name' => $key,
                            'option' => $value
                        ];
                    }
                }
                
                if ($variation->woo_variation_id) {
                    // Update existing variation
                    $this->woocommerce->put(
                        "products/{$product->woo_product_id}/variations/{$variation->woo_variation_id}",
                        $variationData
                    );
                } else {
                    // Create new variation
                    $result = $this->woocommerce->post(
                        "products/{$product->woo_product_id}/variations",
                        $variationData
                    );
                    $variation->update(['woo_variation_id' => $result->id]);
                }
            }
            
            Log::info("Synced {$variations->count()} variations for product {$product->id}");
        } catch (\Exception $e) {
            Log::error("Failed to sync variations for product {$product->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Sync solidarity pricing meta to WooCommerce
     */
    protected function syncSolidarityPricingMeta(Product $product)
    {
        if (!$product->woo_product_id) {
            return;
        }
        
        try {
            // Check if product has solidarity pricing in metadata
            $metadata = $product->metadata ?? [];
            
            if (isset($metadata['solidarity_pricing_enabled']) && $metadata['solidarity_pricing_enabled']) {
                $metaUpdates = [
                    ['key' => '_mwf_solidarity_pricing', 'value' => 'yes'],
                    ['key' => '_mwf_min_price', 'value' => (string) ($metadata['solidarity_min_price'] ?? '')],
                    ['key' => '_mwf_recommended_price', 'value' => (string) ($metadata['solidarity_recommended_price'] ?? '')],
                    ['key' => '_mwf_max_price', 'value' => (string) ($metadata['solidarity_max_price'] ?? '')]
                ];
                
                // Update via WordPress database connection
                foreach ($metaUpdates as $meta) {
                    \DB::connection('wordpress')
                        ->table('postmeta')
                        ->updateOrInsert(
                            ['post_id' => $product->woo_product_id, 'meta_key' => $meta['key']],
                            ['meta_value' => $meta['value']]
                        );
                }
                
                Log::info("Synced solidarity pricing meta for product {$product->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync solidarity pricing meta for product {$product->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Clear all WooCommerce-related cache
     */
    public function clearCache()
    {
        Cache::forget('woocommerce_products_' . md5(serialize([])));
        Cache::forget('woocommerce_categories_' . md5(serialize([])));
        Cache::forget('woocommerce_shipping_classes_' . md5(serialize([])));
        Cache::forget('woocommerce_shipping_zones_' . md5(serialize([])));

        // Clear product-specific caches
        $products = Product::whereNotNull('woo_product_id')->pluck('woo_product_id');
        foreach ($products as $wooId) {
            Cache::forget('woocommerce_product_' . $wooId);
            Cache::forget('woocommerce_product_variations_' . $wooId);
        }

        return ['success' => true, 'message' => 'Cache cleared successfully'];
    }
}

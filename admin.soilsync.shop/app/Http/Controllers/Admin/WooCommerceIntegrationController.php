<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WooCommerceIntegrationController extends Controller
{
    protected $mwfApiBaseUrl;
    protected $mwfApiKey;

    public function __construct()
    {
        $this->mwfApiBaseUrl = env('MWF_API_BASE_URL', 'https://middleworldfarms.org/wp-json/mwf/v1');
        $this->mwfApiKey = env('MWF_API_KEY', 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h');
    }

    /**
     * Get enhanced product data for editing
     */
    public function getProductForEdit($wooProductId)
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
            ])->get("{$this->mwfApiBaseUrl}/products/edit/{$wooProductId}");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            Log::error('MWF Integration API error', [
                'endpoint' => 'getProductForEdit',
                'product_id' => $wooProductId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'error' => 'Failed to fetch product data from MWF Integration API'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration API exception', [
                'endpoint' => 'getProductForEdit',
                'product_id' => $wooProductId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    /**
     * Update product via MWF integration
     */
    public function updateProduct(Request $request, $wooProductId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'regular_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'manage_stock' => 'boolean',
            'stock_status' => 'nullable|string|in:instock,outofstock,onbackorder',
            'categories' => 'nullable|array',
            'images' => 'nullable|array',
            'attributes' => 'nullable|array',
            'variations' => 'nullable|array',
            'meta_data' => 'nullable|array',
            'tax_status' => 'nullable|string|in:taxable,shipping,none',
            'nyp_enabled' => 'boolean',
            'nyp_min_price' => 'nullable|numeric|min:0',
            'nyp_max_price' => 'nullable|numeric|min:0',
            'nyp_suggested_price' => 'nullable|numeric|min:0',
            'nyp_hide_regular_price' => 'boolean',
        ]);

        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/products/update/{$wooProductId}", $validated);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            Log::error('MWF Integration API update error', [
                'endpoint' => 'updateProduct',
                'product_id' => $wooProductId,
                'status' => $response->status(),
                'body' => $response->body(),
                'data' => $validated
            ]);

            return response()->json([
                'error' => 'Failed to update product via MWF Integration API'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration API update exception', [
                'endpoint' => 'updateProduct',
                'product_id' => $wooProductId,
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    /**
     * Get WooCommerce admin capabilities
     */
    public function getCapabilities()
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
            ])->get("{$this->mwfApiBaseUrl}/products/capabilities");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to fetch capabilities'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration capabilities error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    /**
     * Execute WooCommerce admin action
     */
    public function executeAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string',
            'params' => 'nullable|array',
        ]);

        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/products/actions", $validated);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to execute action'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration action error', [
                'action' => $validated['action'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:publish,draft,trash,bulk_field_update',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
            'field' => 'required_if:action,bulk_field_update|string',
            'value' => 'required_if:action,bulk_field_update'
        ]);

        if ($validated['action'] === 'bulk_field_update') {
            // Handle field-specific bulk updates
            $updates = [$validated['field'] => $validated['value']];
        } else {
            // Map action to WooCommerce status
            $statusMap = [
                'publish' => 'publish',
                'draft' => 'draft',
                'trash' => 'trash'
            ];
            $updates = ['status' => $statusMap[$validated['action']]];
        }

        $payload = [
            'product_ids' => $validated['product_ids'],
            'updates' => $updates
        ];

        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/products/bulk-update", $payload);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to perform bulk update'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration bulk update error', [
                'error' => $e->getMessage(),
                'product_count' => count($validated['product_ids'])
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    public function getProductVariations(Request $request, $wooProductId)
    {
        $validated = $request->validate([
            'woo_product_id' => 'required|integer',
        ]);

        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
            ])->get("{$this->mwfApiBaseUrl}/products/{$wooProductId}/variations");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to get product variations'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration get variations error', [
                'error' => $e->getMessage(),
                'woo_product_id' => $wooProductId
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    public function createVariation(Request $request, $wooProductId)
    {
        $validated = $request->validate([
            'attributes' => 'sometimes|array',
            'attributes.*' => 'string',
            'regular_price' => 'sometimes|numeric|min:0',
            'sale_price' => 'sometimes|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'stock_status' => 'sometimes|in:instock,outofstock,onbackorder',
            'sku' => 'sometimes|string|max:255',
            'image_id' => 'sometimes|integer',
            'weight' => 'sometimes|numeric|min:0',
            'length' => 'sometimes|numeric|min:0',
            'width' => 'sometimes|numeric|min:0',
            'height' => 'sometimes|numeric|min:0',
        ]);

        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/products/{$wooProductId}/variations", $validated);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to create variation'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration create variation error', [
                'error' => $e->getMessage(),
                'woo_product_id' => $wooProductId,
                'data' => $validated
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    public function updateVariation(Request $request, $variationId)
    {
        $validated = $request->validate([
            'attributes' => 'sometimes|array',
            'attributes.*' => 'string',
            'regular_price' => 'sometimes|numeric|min:0',
            'sale_price' => 'sometimes|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'stock_status' => 'sometimes|in:instock,outofstock,onbackorder',
            'sku' => 'sometimes|string|max:255',
            'image_id' => 'sometimes|integer',
            'weight' => 'sometimes|numeric|min:0',
            'length' => 'sometimes|numeric|min:0',
            'width' => 'sometimes|numeric|min:0',
            'height' => 'sometimes|numeric|min:0',
        ]);

        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->put("{$this->mwfApiBaseUrl}/products/variations/{$variationId}", $validated);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to update variation'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration update variation error', [
                'error' => $e->getMessage(),
                'variation_id' => $variationId,
                'data' => $validated
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    public function deleteVariation(Request $request, $variationId)
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
            ])->delete("{$this->mwfApiBaseUrl}/products/variations/{$variationId}");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to delete variation'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration delete variation error', [
                'error' => $e->getMessage(),
                'variation_id' => $variationId
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    public function getProductAttributes(Request $request, $wooProductId)
    {
        try {
            // Use WooCommerce REST API to get product data (includes attributes)
            $wooService = new \App\Services\WooCommerceApiService();
            $result = $wooService->getProduct($wooProductId);

            if ($result['success']) {
                $product = $result['data'];
                
                // Extract attributes from product
                $attributes = isset($product->attributes) ? $product->attributes : [];
                
                return response()->json([
                    'success' => true,
                    'attributes' => $attributes
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to get product attributes'
            ], 500);

        } catch (\Exception $e) {
            Log::error('WooCommerce get attributes error', [
                'error' => $e->getMessage(),
                'woo_product_id' => $wooProductId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Connection failed to WooCommerce API: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateProductAttributes(Request $request, $wooProductId)
    {
        $validated = $request->validate([
            'attributes' => 'required|array',
            'attributes.*.name' => 'required|string',
            'attributes.*.type' => 'required|in:taxonomy,custom',
            'attributes.*.options' => 'sometimes|array',
            'attributes.*.variation' => 'sometimes|boolean',
            'attributes.*.visible' => 'sometimes|boolean',
        ]);

        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/products/{$wooProductId}/attributes", $validated);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to update product attributes'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration update attributes error', [
                'error' => $e->getMessage(),
                'woo_product_id' => $wooProductId,
                'data' => $validated
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }

    public function bulkUpdateVariations(Request $request)
    {
        $validated = $request->validate([
            'variation_ids' => 'required|array',
            'variation_ids.*' => 'integer',
            'updates' => 'required|array',
            'updates.regular_price' => 'sometimes|numeric|min:0',
            'updates.sale_price' => 'sometimes|numeric|min:0',
            'updates.stock_quantity' => 'sometimes|integer|min:0',
            'updates.stock_status' => 'sometimes|in:instock,outofstock,onbackorder',
            'updates.sku' => 'sometimes|string|max:255',
            'updates.weight' => 'sometimes|numeric|min:0',
            'updates.status' => 'sometimes|in:publish,draft,trash',
        ]);

        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->mwfApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->mwfApiBaseUrl}/products/variations/bulk-update", $validated);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Failed to perform bulk variation update'
            ], 500);

        } catch (\Exception $e) {
            Log::error('MWF Integration bulk update variations error', [
                'error' => $e->getMessage(),
                'variation_count' => count($validated['variation_ids'])
            ]);

            return response()->json([
                'error' => 'Connection failed to MWF Integration API'
            ], 500);
        }
    }
}

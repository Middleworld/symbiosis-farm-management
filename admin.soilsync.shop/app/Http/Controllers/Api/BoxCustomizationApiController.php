<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoxConfiguration;
use App\Models\CustomerBoxSelection;
use App\Models\CustomerBoxItem;
use App\Models\CsaSubscription;
use App\Models\WooCommerceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BoxCustomizationApiController extends Controller
{
    /**
     * Get available items for current/upcoming week by subscription
     * 
     * @param int $subscriptionId WooCommerce subscription ID or Laravel subscription ID
     */
    public function getAvailableItems(Request $request, $subscriptionId)
    {
        try {
            // Find subscription (try both Laravel and WooCommerce ID)
            $subscription = CsaSubscription::where('id', $subscriptionId)
                ->orWhere('woo_subscription_id', $subscriptionId)
                ->with('plan')
                ->firstOrFail();

            // Get week parameter or default to next delivery
            $weekStart = $request->week 
                ? Carbon::parse($request->week)->startOfWeek()
                : Carbon::now()->startOfWeek();

            // Find box configuration for this plan and week
            $configuration = BoxConfiguration::where('plan_id', $subscription->plan_id)
                ->where('week_starting', $weekStart)
                ->where('is_active', true)
                ->with(['items' => function ($query) {
                    $query->available()->ordered();
                }])
                ->first();

            if (!$configuration) {
                return response()->json([
                    'success' => false,
                    'message' => 'No box configuration available for this week',
                    'data' => [
                        'week' => $weekStart->format('Y-m-d'),
                        'plan_id' => $subscription->plan_id,
                    ]
                ], 404);
            }

            // Get or create customer box selection
            $selection = CustomerBoxSelection::firstOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'box_configuration_id' => $configuration->id,
                    'delivery_date' => $subscription->next_delivery_date ?? $weekStart->addDays(3), // Default to Thursday
                ],
                [
                    'tokens_allocated' => $configuration->default_tokens,
                    'tokens_used' => 0,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'configuration' => [
                        'id' => $configuration->id,
                        'week_starting' => $configuration->week_starting->format('Y-m-d'),
                        'week_display' => $configuration->week_display,
                    ],
                    'selection' => [
                        'id' => $selection->id,
                        'tokens_allocated' => $selection->tokens_allocated,
                        'tokens_used' => $selection->tokens_used,
                        'tokens_remaining' => $selection->remaining_tokens,
                        'is_customized' => $selection->is_customized,
                        'is_locked' => $selection->is_locked,
                        'is_editable' => $selection->is_editable,
                        'delivery_date' => $selection->delivery_date->format('Y-m-d'),
                    ],
                    'items' => $configuration->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->item_name,
                            'description' => $item->description,
                            'token_value' => $item->token_value,
                            'unit' => $item->unit,
                            'is_featured' => $item->is_featured,
                            'is_available' => $item->is_available,
                            'quantity_available' => $item->quantity_available,
                            'quantity_allocated' => $item->quantity_allocated,
                            'remaining_quantity' => $item->remaining_quantity,
                            'allocation_percent' => $item->allocation_percent,
                            'plant_variety_id' => $item->plant_variety_id,
                        ];
                    }),
                    'plan' => [
                        'id' => $subscription->plan->id,
                        'name' => $subscription->plan->name,
                        'box_size' => $subscription->plan->box_size,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Box customization API error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load box items: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer's current box selection
     */
    public function getCustomerBox($subscriptionId, $selectionId = null)
    {
        try {
            $subscription = CsaSubscription::where('id', $subscriptionId)
                ->orWhere('woo_subscription_id', $subscriptionId)
                ->firstOrFail();

            if ($selectionId) {
                $selection = CustomerBoxSelection::with(['items.configurationItem'])
                    ->where('subscription_id', $subscription->id)
                    ->findOrFail($selectionId);
            } else {
                // Get latest upcoming selection
                $selection = CustomerBoxSelection::with(['items.configurationItem'])
                    ->where('subscription_id', $subscription->id)
                    ->upcoming()
                    ->unlocked()
                    ->latest('delivery_date')
                    ->firstOrFail();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'selection' => [
                        'id' => $selection->id,
                        'delivery_date' => $selection->delivery_date->format('Y-m-d'),
                        'tokens_allocated' => $selection->tokens_allocated,
                        'tokens_used' => $selection->tokens_used,
                        'tokens_remaining' => $selection->remaining_tokens,
                        'is_customized' => $selection->is_customized,
                        'is_locked' => $selection->is_locked,
                    ],
                    'items' => $selection->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'configuration_item_id' => $item->box_configuration_item_id,
                            'name' => $item->configurationItem->item_name,
                            'quantity' => $item->quantity,
                            'token_value' => $item->configurationItem->token_value,
                            'tokens_used' => $item->tokens_used,
                            'unit' => $item->configurationItem->unit,
                        ];
                    }),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Box selection not found',
            ], 404);
        }
    }

    /**
     * Update customer's box selection (drag & drop save)
     */
    public function updateCustomerBox(Request $request, $subscriptionId)
    {
        $validated = $request->validate([
            'selection_id' => 'required|exists:customer_box_selections,id',
            'items' => 'required|array',
            'items.*.configuration_item_id' => 'required|exists:box_configuration_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:10',
        ]);

        DB::beginTransaction();
        try {
            $subscription = CsaSubscription::where('id', $subscriptionId)
                ->orWhere('woo_subscription_id', $subscriptionId)
                ->firstOrFail();

            $selection = CustomerBoxSelection::where('subscription_id', $subscription->id)
                ->findOrFail($validated['selection_id']);

            // Check if locked
            if ($selection->is_locked) {
                return response()->json([
                    'success' => false,
                    'message' => 'This box is locked and cannot be edited',
                ], 403);
            }

            // Delete existing items
            $selection->items()->delete();

            $totalTokens = 0;

            // Create new items and calculate tokens
            foreach ($validated['items'] as $itemData) {
                $configItem = \App\Models\BoxConfigurationItem::findOrFail($itemData['configuration_item_id']);
                
                $tokensForItem = $configItem->token_value * $itemData['quantity'];
                
                $selection->items()->create([
                    'box_configuration_item_id' => $itemData['configuration_item_id'],
                    'quantity' => $itemData['quantity'],
                    'tokens_used' => $configItem->token_value,
                ]);

                $totalTokens += $tokensForItem;
            }

            // Update selection
            $selection->update([
                'tokens_used' => $totalTokens,
                'is_customized' => true,
                'customized_at' => now(),
            ]);

            // Update allocation counts on configuration items
            $this->updateAllocationCounts($selection->box_configuration_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Box updated successfully',
                'data' => [
                    'tokens_used' => $totalTokens,
                    'tokens_remaining' => $selection->tokens_allocated - $totalTokens,
                    'is_over_budget' => $totalTokens > $selection->tokens_allocated,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update customer box', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update box: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get token balance for a subscription
     */
    public function getTokenBalance($subscriptionId)
    {
        try {
            $subscription = CsaSubscription::where('id', $subscriptionId)
                ->orWhere('woo_subscription_id', $subscriptionId)
                ->with('plan')
                ->firstOrFail();

            $selection = CustomerBoxSelection::where('subscription_id', $subscription->id)
                ->upcoming()
                ->unlocked()
                ->latest('delivery_date')
                ->first();

            if (!$selection) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active box selection found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tokens_allocated' => $selection->tokens_allocated,
                    'tokens_used' => $selection->tokens_used,
                    'tokens_remaining' => $selection->remaining_tokens,
                    'plan_name' => $subscription->plan->name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get token balance',
            ], 404);
        }
    }

    /**
     * Update allocation counts for configuration items
     */
    protected function updateAllocationCounts($configurationId)
    {
        $items = \App\Models\BoxConfigurationItem::where('box_configuration_id', $configurationId)->get();

        foreach ($items as $item) {
            $allocated = CustomerBoxItem::whereHas('customerBoxSelection', function ($query) use ($configurationId) {
                $query->where('box_configuration_id', $configurationId);
            })
            ->where('box_configuration_item_id', $item->id)
            ->sum('quantity');

            $item->update(['quantity_allocated' => $allocated]);
        }
    }
    
    /**
     * Reset customer's box to default (remove all customizations)
     */
    public function resetToDefault(Request $request, $subscriptionId)
    {
        $validated = $request->validate([
            'selection_id' => 'required|exists:customer_box_selections,id',
        ]);

        DB::beginTransaction();
        try {
            $subscription = CsaSubscription::where('id', $subscriptionId)
                ->orWhere('woo_subscription_id', $subscriptionId)
                ->firstOrFail();

            $selection = CustomerBoxSelection::where('subscription_id', $subscription->id)
                ->findOrFail($validated['selection_id']);

            // Check if locked
            if ($selection->is_locked) {
                return response()->json([
                    'success' => false,
                    'message' => 'This box is locked and cannot be edited',
                ], 403);
            }

            // Delete all items
            $selection->items()->delete();

            // Reset selection
            $selection->update([
                'tokens_used' => 0,
                'is_customized' => false,
                'customized_at' => null,
            ]);

            // Update allocation counts
            $this->updateAllocationCounts($selection->box_configuration_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Box reset to default selections',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset box to default', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset box: ' . $e->getMessage(),
            ], 500);
        }
    }
}

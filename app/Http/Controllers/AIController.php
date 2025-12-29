<?php

namespace App\Http\Controllers;

use App\Services\AI\SymbiosisAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIController extends Controller
{
    protected $aiService;

    public function __construct(SymbiosisAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * General chat endpoint
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array',
            'options' => 'sometimes|array'
        ]);

        try {
            $response = $this->aiService->chat(
                $request->input('messages'),
                $request->input('options', [])
            );

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Farming insights endpoint
     */
    public function farmingInsights(Request $request): JsonResponse
    {
        $request->validate([
            'farm_data' => 'required|array'
        ]);

        try {
            $insights = $this->aiService->generateFarmingInsights($request->input('farm_data'));

            return response()->json([
                'success' => true,
                'insights' => $insights
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crop planning endpoint
     */
    public function cropPlanning(Request $request): JsonResponse
    {
        $request->validate([
            'conditions' => 'required|array'
        ]);

        try {
            $suggestions = $this->aiService->suggestCropPlanning($request->input('conditions'));

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Contextual help endpoint for admin pages
     */
    public function contextualHelp(Request $request): JsonResponse
    {
        $request->validate([
            'page_context' => 'required|string',
            'question' => 'required|string',
            'current_section' => 'sometimes|string'
        ]);

        try {
            $pageContext = $request->input('page_context');
            $question = $request->input('question');
            $currentSection = $request->input('current_section', '');

            // Handle different page contexts
            $response = $this->getContextualHelp($pageContext, $question, $currentSection);

            return response()->json([
                'success' => true,
                'response' => $response,
                'sources' => ['admin_help'],
                'context_found' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'response' => 'Sorry, I encountered an error. Please try again.',
                'sources' => [],
                'context_found' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contextual help based on page and question
     */
    private function getContextualHelp(string $pageContext, string $question, string $currentSection): string
    {
        // Handle shipping classes context
        if ($pageContext === 'shipping-classes') {
            if (str_contains(strtolower($question), 'explain') || str_contains(strtolower($question), 'what')) {
                return "📦 **Shipping Classes Create Page**

This page allows you to create new shipping classes for your WooCommerce store. Shipping classes help you organize products by delivery requirements and costs.

**Key Fields:**
- **Name**: Display name for the shipping class (e.g., \"Fragile Items\", \"Heavy Equipment\")
- **Description**: Optional details about this shipping class
- **Cost**: Base shipping cost for this class
- **Is Farm Collection**: Check if this class allows farm pickup instead of delivery

**Usage:**
- Assign shipping classes to products in WooCommerce
- Different classes can have different shipping rates
- Farm collection bypasses delivery costs
- Helps organize your delivery schedule and logistics

**Tips:**
- Use descriptive names that help customers understand shipping options
- Consider weight, size, and fragility when creating classes
- Farm collection is great for local customers who prefer pickup";
            } else {
                return "For shipping classes, you asked: '{$question}'. This page manages WooCommerce shipping classifications for organizing delivery costs and methods.";
            }
        }

        // Handle other contexts
        elseif ($pageContext === 'succession-planning') {
            return "Succession planning helps you schedule crop plantings in sequence for continuous harvest throughout the season.";
        }
        elseif ($pageContext === 'user-management') {
            return "User management handles customer accounts, permissions, and access control for the admin system.";
        }
        elseif ($pageContext === 'delivery-management') {
            return "Delivery management organizes and tracks customer deliveries, schedules, and logistics.";
        }
        elseif ($pageContext === 'subscription-management') {
            return "Subscription management handles recurring customer orders, billing cycles, and subscription lifecycle.";
        }
        elseif ($pageContext === 'farmos-integration') {
            return "farmOS integration connects your farm management data with the admin system for crop planning and tracking.";
        }
        else {
            return "This is the {$pageContext} section. {$question} - I'm here to help with admin tasks!";
        }
    }
}

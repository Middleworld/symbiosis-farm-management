<?php

namespace App\Http\Controllers;

use App\Services\AI\SymbiosisAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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

    public function contextualHelp(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'response' => 'Test response from AI helper',
            'sources' => ['admin_help'],
            'context_found' => true
        ]);
    }

    /**
     * Get contextual help based on page and question
     */
    private function getContextualHelp(string $pageContext, string $question, string $currentSection): string
    {
        // For contextual help, use static responses for faster response times
        // The AI service is CPU-based and can take 60+ seconds, which is too slow for UI
        return $this->getStaticContextualHelp($pageContext, $question);
    }

    /**
     * Build context prompt for AI
     */
    private function buildContextPrompt(string $pageContext, string $currentSection): string
    {
        $basePrompt = "You are a helpful AI assistant for the Middle World Farms CSA admin system. ";

        $contextDescriptions = [
            'shipping-classes' => "You are on the shipping classes management page. This page manages WooCommerce shipping classifications for organizing delivery costs and methods. Shipping classes determine how products are delivered and their associated costs.",
            'succession-planning' => "You are on the succession planning page. This page helps schedule crop plantings in sequence for continuous harvest throughout the season using farmOS integration.",
            'user-management' => "You are on the user management page. This page handles customer accounts, permissions, and access control for the admin system.",
            'delivery-management' => "You are on the delivery management page. This page organizes and tracks customer deliveries, schedules, and logistics.",
            'subscription-management' => "You are on the subscription management page. This page handles recurring customer orders, billing cycles, and subscription lifecycle.",
            'farmos-integration' => "You are on the farmOS integration page. This page connects farm management data with the admin system for crop planning and tracking.",
            'admin-general' => "You are in the general admin area. This system manages a Community Supported Agriculture (CSA) program with WooCommerce integration, farmOS farm management, and subscription handling."
        ];

        $context = $contextDescriptions[$pageContext] ?? $contextDescriptions['admin-general'];

        return $basePrompt . $context . " Provide helpful, accurate answers about this page and its functionality. Keep responses concise but informative.";
    }

    /**
     * Get static contextual help (fallback)
     */
    private function getStaticContextualHelp(string $pageContext, string $question): string
    {
        // Handle specific questions
        if ($pageContext === 'subscription-management' && str_contains(strtolower($question), 'how many')) {
            try {
                $count = \App\Models\VegboxSubscription::count();
                return "You currently have {$count} active subscriptions in the system.";
            } catch (\Exception $e) {
                return "I couldn't retrieve the subscription count. Please check the admin dashboard.";
            }
        }

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
            return "Test response for succession planner.";
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

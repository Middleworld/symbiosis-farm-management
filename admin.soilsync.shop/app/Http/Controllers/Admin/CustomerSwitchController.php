<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WordPressUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerSwitchController extends Controller
{
    private $wpUserService;
    
    public function __construct(WordPressUserService $wpUserService)
    {
        $this->wpUserService = $wpUserService;
    }
    
    /**
     * Show the customer switching interface
     */
    public function index()
    {
        $recentUsers = $this->wpUserService->getRecentUsers(20, 'customer');
        
        return view('admin.customer-switch.index', [
            'recentUsers' => $recentUsers,
            'page_title' => 'Customer Account Switching'
        ]);
    }
    
    /**
     * Search for customers via AJAX
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);
        
        $results = $this->wpUserService->searchUsers(
            $request->get('q'),
            $request->get('limit', 20),
            'customer'
        );
        
        return response()->json($results);
    }
    
    /**
     * Get detailed customer information
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        $userDetails = $this->wpUserService->getUserDetails($userId);
        
        if (!$userDetails['success']) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        return response()->json($userDetails);
    }
    
    /**
     * Switch to customer account
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'redirect_to' => 'nullable|string',
            'context' => 'nullable|string'
        ]);
        
        $result = $this->wpUserService->switchToUser(
            $request->get('user_id'),
            $request->get('redirect_to', '/my-account/'),
            $request->get('context', 'delivery_schedule')
        );
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'preview_url' => $result['preview_url'],
                'message' => 'Successfully switched to customer account'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to switch user'
        ], 400);
    }
    
    /**
     * Test API connection
     */
    public function testApi(): JsonResponse
    {
        $result = $this->wpUserService->testConnection();
        return response()->json($result);
    }
}

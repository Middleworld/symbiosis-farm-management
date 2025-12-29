<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    /**
     * Verify admin authentication for all methods
     */
    private function verifyAdminAuth()
    {
        if (!Session::get('admin_authenticated', false)) {
            abort(401, 'Admin authentication required');
        }
    }

    /**
     * Store a new conversation message (ADMIN ONLY)
     */
    public function store(Request $request): JsonResponse
    {
        $this->verifyAdminAuth();

        $validated = $request->validate([
            'user_id' => 'nullable|integer',
            'conversation_id' => 'nullable|string',
            'message' => 'required|string',
            'type' => 'nullable|string|in:chat,training,feedback,note',
            'metadata' => 'nullable|array'
        ]);

        $conversation = Conversation::create([
            'user_id' => $validated['user_id'] ?? null,
            'conversation_id' => $validated['conversation_id'] ?? uniqid('conv_'),
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'chat',
            'metadata' => array_merge($validated['metadata'] ?? [], [
                'admin_created' => true,
                'admin_session' => Session::getId(),
                'created_by_admin' => true
            ]),
        ]);

        // Log admin creation for audit
        Log::info('Conversation created by admin', [
            'conversation_id' => $conversation->id,
            'type' => $conversation->type,
            'admin_session' => Session::getId()
        ]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }

    /**
     * Get conversations by conversation_id (ADMIN ONLY)
     */
    public function show(string $conversationId): JsonResponse
    {
        $this->verifyAdminAuth();

        $conversations = Conversation::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations
        ]);
    }

    /**
     * Get all conversations for a user (ADMIN ONLY)
     */
    public function userConversations(int $userId): JsonResponse
    {
        $this->verifyAdminAuth();

        $conversations = Conversation::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations
        ]);
    }

    /**
     * Get conversations by type (ADMIN ONLY - for training data)
     */
    public function byType(string $type): JsonResponse
    {
        $this->verifyAdminAuth();

        $conversations = Conversation::where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations
        ]);
    }

    /**
     * Search conversations by message content (ADMIN ONLY)
     */
    public function search(Request $request): JsonResponse
    {
        $this->verifyAdminAuth();

        $query = $request->get('q');
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Query parameter "q" is required'
            ], 400);
        }

        $conversations = Conversation::where('message', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations
        ]);
    }
}

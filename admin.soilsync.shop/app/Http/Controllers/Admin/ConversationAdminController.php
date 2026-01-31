<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\SecureConversationLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversationAdminController extends Controller
{
    public function __construct()
    {
        // Middleware will be applied via routes
    }

    /**
     * Display the conversation management dashboard
     */
    public function index()
    {
        // Verify admin authentication
        if (!Session::get('admin_authenticated', false)) {
            return redirect()->route('admin.login')->with('error', 'Admin authentication required');
        }

        $stats = [
            'total_conversations' => Conversation::count(),
            'chat_messages' => Conversation::where('type', 'chat')->count(),
            'training_entries' => Conversation::where('type', 'training')->count(),
            'feedback_entries' => Conversation::where('type', 'feedback')->count(),
            'recent_conversations' => Conversation::orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return view('admin.conversations.index', compact('stats'));
    }

    /**
     * Show conversation details (admin only)
     */
    public function show($id)
    {
        if (!Session::get('admin_authenticated', false)) {
            return redirect()->route('admin.login');
        }

        $conversation = Conversation::findOrFail($id);
        
        // Decrypt if encrypted (admin privilege)
        if ($conversation->metadata['encrypted'] ?? false) {
            $conversation = SecureConversationLogger::getDecrypted($id);
        }

        return view('admin.conversations.show', compact('conversation'));
    }

    /**
     * Export training data (admin only)
     */
    public function exportTraining()
    {
        if (!Session::get('admin_authenticated', false)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $export = SecureConversationLogger::exportForTraining('training');
        
        return response($export)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="training_data_' . date('Y-m-d_H-i-s') . '.txt"');
    }

    /**
     * Purge old conversations (admin only)
     */
    public function purgeOld(Request $request)
    {
        if (!Session::get('admin_authenticated', false)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $retentionDays = $request->input('retention_days', 365);
        $deletedCount = SecureConversationLogger::purgeOldConversations($retentionDays);

        return response()->json([
            'success' => true,
            'deleted_count' => $deletedCount,
            'retention_days' => $retentionDays
        ]);
    }

    /**
     * Get conversation statistics (admin only)
     */
    public function statistics()
    {
        if (!Session::get('admin_authenticated', false)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $stats = [
            'total_conversations' => Conversation::count(),
            'by_type' => [
                'chat' => Conversation::where('type', 'chat')->count(),
                'training' => Conversation::where('type', 'training')->count(),
                'feedback' => Conversation::where('type', 'feedback')->count(),
                'note' => Conversation::where('type', 'note')->count(),
            ],
            'encrypted_count' => Conversation::whereJsonContains('metadata->encrypted', true)->count(),
            'recent_activity' => [
                'today' => Conversation::whereDate('created_at', today())->count(),
                'this_week' => Conversation::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => Conversation::whereMonth('created_at', now()->month)->count(),
            ],
            'storage_size' => [
                'total_characters' => Conversation::sum(DB::raw('CHAR_LENGTH(message)')),
                'average_message_length' => Conversation::avg(DB::raw('CHAR_LENGTH(message)')),
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Search conversations (admin only)
     */
    public function search(Request $request)
    {
        if (!Session::get('admin_authenticated', false)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = $request->get('q');
        $type = $request->get('type');
        
        $conversations = Conversation::query();
        
        if ($query) {
            $conversations->where('message', 'LIKE', "%{$query}%");
        }
        
        if ($type) {
            $conversations->where('type', $type);
        }
        
        $results = $conversations->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($results);
    }

    /**
     * Delete conversation (admin only)
     */
    public function destroy($id)
    {
        if (!Session::get('admin_authenticated', false)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $conversation = Conversation::findOrFail($id);
        $conversation->delete();

        // Log admin deletion for audit
        Log::warning('Conversation deleted by admin', [
            'conversation_id' => $id,
            'admin_session' => Session::getId(),
            'timestamp' => now()
        ]);

        return response()->json(['success' => true]);
    }
}

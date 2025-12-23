<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SecureConversationLogger
{
    /**
     * Log a conversation with encryption and audit trail
     */
    public static function logSecure(
        string $message, 
        string $type = 'chat', 
        ?int $userId = null, 
        ?string $conversationId = null, 
        array $metadata = [],
        bool $encrypt = true
    ): Conversation {
        
        // Encrypt sensitive message content if requested
        $processedMessage = $encrypt ? Crypt::encryptString($message) : $message;
        
        // Add security metadata
        $securityMetadata = array_merge($metadata, [
            'encrypted' => $encrypt,
            'logged_at' => now()->toISOString(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        $conversation = Conversation::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId ?? uniqid($type . '_'),
            'message' => $processedMessage,
            'type' => $type,
            'metadata' => $securityMetadata,
        ]);

        // Audit log for security compliance
        Log::channel('single')->info('Conversation logged', [
            'conversation_id' => $conversation->id,
            'type' => $type,
            'user_id' => $userId,
            'encrypted' => $encrypt,
            'timestamp' => now()
        ]);

        return $conversation;
    }

    /**
     * Retrieve and decrypt conversation if encrypted
     */
    public static function getDecrypted(int $conversationId): ?Conversation
    {
        $conversation = Conversation::find($conversationId);
        
        if (!$conversation) {
            return null;
        }

        // Decrypt message if it was encrypted
        if ($conversation->metadata['encrypted'] ?? false) {
            try {
                $conversation->message = Crypt::decryptString($conversation->message);
            } catch (\Exception $e) {
                Log::error('Failed to decrypt conversation', [
                    'conversation_id' => $conversationId,
                    'error' => $e->getMessage()
                ]);
                // Return null or throw exception based on your security policy
                return null;
            }
        }

        return $conversation;
    }

    /**
     * Export conversations for AI training (as plain text) - ADMIN ONLY
     */
    public static function exportForTraining(string $type = 'training'): string
    {
        // Verify admin authentication for export operations
        if (!request() || !\Illuminate\Support\Facades\Session::get('admin_authenticated', false)) {
            throw new \Exception('Admin authentication required for data export');
        }

        $conversations = Conversation::where('type', $type)
            ->orderBy('created_at', 'asc')
            ->get();

        $output = "";
        foreach ($conversations as $conversation) {
            $output .= "=== " . $conversation->type . " [" . $conversation->created_at . "] ===" . PHP_EOL;
            $output .= $conversation->message . PHP_EOL . PHP_EOL;
        }

        // Log the export for audit purposes
        Log::channel('single')->warning('Training data exported by admin', [
            'record_count' => $conversations->count(),
            'type' => $type,
            'admin_session' => \Illuminate\Support\Facades\Session::getId(),
            'timestamp' => now()
        ]);

        return $output;
    }

    /**
     * Purge old conversations based on retention policy - ADMIN ONLY
     */
    public static function purgeOldConversations(int $retentionDays = 365): int
    {
        // Verify admin authentication for purge operations
        if (!request() || !\Illuminate\Support\Facades\Session::get('admin_authenticated', false)) {
            throw new \Exception('Admin authentication required for data purge operations');
        }

        $cutoffDate = now()->subDays($retentionDays);
        
        $deletedCount = Conversation::where('created_at', '<', $cutoffDate)
            ->where('type', '!=', 'training') // Keep training data longer
            ->delete();

        Log::channel('single')->warning('Old conversations purged by admin', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate,
            'retention_days' => $retentionDays,
            'admin_session' => \Illuminate\Support\Facades\Session::getId(),
            'timestamp' => now()
        ]);

        return $deletedCount;
    }
}

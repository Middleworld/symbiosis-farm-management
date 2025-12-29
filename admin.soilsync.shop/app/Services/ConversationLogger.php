<?php

namespace App\Services;

use App\Models\Conversation;

class ConversationLogger
{
    /**
     * Log a chat message
     */
    public static function logChat(string $message, ?int $userId = null, ?string $conversationId = null, array $metadata = []): Conversation
    {
        return Conversation::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId ?? uniqid('conv_'),
            'message' => $message,
            'type' => 'chat',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log training material
     */
    public static function logTraining(string $content, ?int $userId = null, array $metadata = []): Conversation
    {
        return Conversation::create([
            'user_id' => $userId,
            'conversation_id' => uniqid('training_'),
            'message' => $content,
            'type' => 'training',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log user feedback
     */
    public static function logFeedback(string $feedback, ?int $userId = null, ?string $conversationId = null, array $metadata = []): Conversation
    {
        return Conversation::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId ?? uniqid('feedback_'),
            'message' => $feedback,
            'type' => 'feedback',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a note or documentation
     */
    public static function logNote(string $note, ?int $userId = null, array $metadata = []): Conversation
    {
        return Conversation::create([
            'user_id' => $userId,
            'conversation_id' => uniqid('note_'),
            'message' => $note,
            'type' => 'note',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get all training materials for AI training
     */
    public static function getTrainingData(): \Illuminate\Database\Eloquent\Collection
    {
        return Conversation::where('type', 'training')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get conversation thread by conversation_id
     */
    public static function getConversationThread(string $conversationId): \Illuminate\Database\Eloquent\Collection
    {
        return Conversation::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Export conversations for AI training (as plain text)
     */
    public static function exportForTraining(string $type = 'training'): string
    {
        $conversations = Conversation::where('type', $type)
            ->orderBy('created_at', 'asc')
            ->get();

        $output = "";
        foreach ($conversations as $conversation) {
            $output .= "=== " . $conversation->type . " [" . $conversation->created_at . "] ===" . PHP_EOL;
            $output .= $conversation->message . PHP_EOL . PHP_EOL;
        }

        return $output;
    }
}

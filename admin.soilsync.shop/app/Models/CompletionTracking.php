<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletionTracking extends Model
{
    protected $table = 'completion_tracking';
    
    protected $fillable = [
        'item_id',
        'item_type',
        'customer_name',
        'customer_email',
        'completed_at',
        'completed_by',
        'notes'
    ];
    
    protected $casts = [
        'completed_at' => 'datetime'
    ];
    
    /**
     * Check if an item is completed
     */
    public static function isCompleted(string $itemId, string $itemType): bool
    {
        return self::where('item_id', $itemId)
                  ->where('item_type', $itemType)
                  ->exists();
    }
    
    /**
     * Mark an item as completed
     */
    public static function markCompleted(string $itemId, string $itemType, array $data = []): self
    {
        return self::updateOrCreate(
            [
                'item_id' => $itemId,
                'item_type' => $itemType
            ],
            array_merge($data, [
                'completed_at' => now(),
                'completed_by' => auth()->user()->name ?? 'Admin'
            ])
        );
    }
    
    /**
     * Get completion data for an item
     */
    public static function getCompletionData(string $itemId, string $itemType): ?self
    {
        return self::where('item_id', $itemId)
                  ->where('item_type', $itemType)
                  ->first();
    }
}

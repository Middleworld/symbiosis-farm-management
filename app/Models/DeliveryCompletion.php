<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DeliveryCompletion extends Model
{
    protected $fillable = [
        'external_id',
        'type',
        'delivery_date',
        'customer_name',
        'customer_email',
        'completed_at',
        'completed_by',
        'notes'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'delivery_date' => 'date',
    ];

    /**
     * Check if a delivery/collection is completed for a specific date
     */
    public static function isCompleted($externalId, $type, $deliveryDate = null)
    {
        $query = self::where('external_id', $externalId)
                    ->where('type', $type);
        
        if ($deliveryDate) {
            $query->where('delivery_date', $deliveryDate);
        }
        
        return $query->exists();
    }

    /**
     * Get completion record for a delivery/collection on a specific date
     */
    public static function getCompletion($externalId, $type, $deliveryDate = null)
    {
        $query = self::where('external_id', $externalId)
                    ->where('type', $type);
        
        if ($deliveryDate) {
            $query->where('delivery_date', $deliveryDate);
        }
        
        return $query->first();
    }

    /**
     * Mark as completed with timestamp and delivery date
     */
    public static function markCompleted($externalId, $type, $deliveryDate, $customerName = null, $customerEmail = null, $completedBy = null)
    {
        return self::updateOrCreate(
            [
                'external_id' => $externalId,
                'type' => $type,
                'delivery_date' => $deliveryDate,
            ],
            [
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'completed_at' => Carbon::now(),
                'completed_by' => $completedBy,
            ]
        );
    }
}

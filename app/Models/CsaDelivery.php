<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsaDelivery extends Model
{
    protected $table = 'csa_deliveries';

    protected $fillable = [
        'subscription_id',
        'scheduled_date',
        'delivered_date',
        'status',
        'contents',
        'notes',
        'packed_by',
        'delivered_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'delivered_date' => 'date',
    ];

    /**
     * Get the subscription for this delivery.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CsaSubscription::class, 'subscription_id');
    }

    /**
     * Scope to get deliveries by date.
     */
    public function scopeByDate($query, $date)
    {
        return $query->where('scheduled_date', $date);
    }

    /**
     * Scope to get deliveries by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get scheduled deliveries.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to get packed deliveries.
     */
    public function scopePacked($query)
    {
        return $query->where('status', 'packed');
    }

    /**
     * Scope to get delivered deliveries.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Mark as packed.
     */
    public function markAsPacked($packedBy = null): void
    {
        $this->status = 'packed';
        if ($packedBy) {
            $this->packed_by = $packedBy;
        }
        $this->save();
    }

    /**
     * Mark as delivered.
     */
    public function markAsDelivered($deliveredBy = null, $date = null): void
    {
        $this->status = 'delivered';
        $this->delivered_date = $date ?? now();
        if ($deliveredBy) {
            $this->delivered_by = $deliveredBy;
        }
        $this->save();
    }

    /**
     * Get a human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'scheduled' => 'Scheduled',
            'packed' => 'Packed',
            'out-for-delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'skipped' => 'Skipped',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }
}

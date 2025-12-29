<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DeliverySchedule extends Model
{
    protected $fillable = [
        'vegbox_subscription_id',
        'scheduled_date',
        'delivery_status', // pending, delivered, failed, canceled
        'delivery_notes',
        'delivered_at',
        'delivered_by',
        'recipient_name',
        'recipient_signature',
        'delivery_address',
        'special_instructions',
        'box_contents', // JSON of what's in the box
        'quality_rating',
        'customer_feedback',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'delivered_at' => 'datetime',
        'box_contents' => 'json',
        'quality_rating' => 'integer',
    ];

    /**
     * Get the vegbox subscription for this delivery.
     */
    public function vegboxSubscription(): BelongsTo
    {
        return $this->belongsTo(VegboxSubscription::class);
    }

    /**
     * Scope for pending deliveries.
     */
    public function scopePending($query)
    {
        return $query->where('delivery_status', 'pending');
    }

    /**
     * Scope for deliveries in a date range.
     */
    public function scopeInDateRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('scheduled_date', [$start, $end]);
    }

    /**
     * Scope for today's deliveries.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    /**
     * Scope for upcoming deliveries.
     */
    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('scheduled_date', '>=', today())
                    ->where('scheduled_date', '<=', today()->addDays($days))
                    ->where('delivery_status', 'pending');
    }

    /**
     * Mark delivery as completed.
     */
    public function markAsDelivered(?string $deliveredBy = null, ?string $notes = null): void
    {
        $this->update([
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
            'delivered_by' => $deliveredBy,
            'delivery_notes' => $notes,
        ]);

        // Update the subscription's next delivery date
        $this->vegboxSubscription->recordDelivery($this->scheduled_date);
    }

    /**
     * Mark delivery as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'delivery_status' => 'failed',
            'delivery_notes' => $reason,
        ]);
    }

    /**
     * Check if delivery is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->delivery_status === 'pending' &&
               $this->scheduled_date->isPast();
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->delivery_status) {
            'pending' => 'Pending',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'canceled' => 'Canceled',
            default => ucfirst($this->delivery_status ?? 'Unknown')
        };
    }

    /**
     * Get the formatted scheduled date.
     */
    public function getFormattedScheduledDateAttribute(): string
    {
        return $this->scheduled_date?->format('l, F j, Y') ?? 'Not scheduled';
    }
}

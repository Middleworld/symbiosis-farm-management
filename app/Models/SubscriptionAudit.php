<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionAudit extends Model
{
    protected $fillable = [
        'subscription_id',
        'action',
        'user_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the subscription this audit belongs to
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CsaSubscription::class, 'subscription_id');
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a subscription audit event
     * Accepts either VegboxSubscription or CsaSubscription
     */
    public static function log(
        VegboxSubscription|CsaSubscription $subscription,
        string $action,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?int $userId = null
    ): self {
        return self::create([
            'subscription_id' => $subscription->id,
            'action' => $action,
            'user_id' => $userId ?? auth()->id(),
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

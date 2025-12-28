<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerBoxSelection extends Model
{
    protected $fillable = [
        'subscription_id',
        'box_configuration_id',
        'delivery_date',
        'tokens_allocated',
        'tokens_used',
        'is_customized',
        'is_locked',
        'customized_at',
        'locked_at',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'tokens_allocated' => 'integer',
        'tokens_used' => 'integer',
        'is_customized' => 'boolean',
        'is_locked' => 'boolean',
        'customized_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    /**
     * Get the subscription for this selection.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(VegboxSubscription::class, 'subscription_id');
    }

    /**
     * Get the box configuration.
     */
    public function boxConfiguration(): BelongsTo
    {
        return $this->belongsTo(BoxConfiguration::class);
    }

    /**
     * Get the items in this box.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CustomerBoxItem::class);
    }

    /**
     * Get tokens remaining.
     */
    public function getRemainingTokensAttribute(): int
    {
        return max(0, $this->tokens_allocated - $this->tokens_used);
    }

    /**
     * Check if box is editable.
     */
    public function getIsEditableAttribute(): bool
    {
        return !$this->is_locked && $this->delivery_date->isFuture();
    }

    /**
     * Scope for upcoming selections.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('delivery_date', '>=', now()->toDateString());
    }

    /**
     * Scope for unlocked selections.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }
}

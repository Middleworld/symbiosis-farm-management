<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBoxItem extends Model
{
    protected $fillable = [
        'customer_box_selection_id',
        'box_configuration_item_id',
        'quantity',
        'tokens_used',
        'is_substitution',
        'substitution_note',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'tokens_used' => 'integer',
        'is_substitution' => 'boolean',
    ];

    /**
     * Get the customer box selection.
     */
    public function customerBoxSelection(): BelongsTo
    {
        return $this->belongsTo(CustomerBoxSelection::class);
    }

    /**
     * Get the configuration item.
     */
    public function configurationItem(): BelongsTo
    {
        return $this->belongsTo(BoxConfigurationItem::class, 'box_configuration_item_id');
    }

    /**
     * Get total tokens for this line item.
     */
    public function getTotalTokensAttribute(): int
    {
        return $this->quantity * $this->tokens_used;
    }
}

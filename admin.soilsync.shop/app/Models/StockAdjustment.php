<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'reason',
        'user_id',
        'reference',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByReason($query, $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getQuantityFormattedAttribute()
    {
        $sign = $this->quantity > 0 ? '+' : '';
        return $sign . $this->quantity;
    }

    public function getReasonNameAttribute()
    {
        return match($this->reason) {
            'manual_adjustment' => 'Manual Adjustment',
            'purchase_order' => 'Purchase Order',
            'return' => 'Customer Return',
            'damage' => 'Damaged/Lost',
            'transfer' => 'Stock Transfer',
            default => $this->reason ? ucwords(str_replace('_', ' ', $this->reason)) : 'Unknown'
        };
    }

    public function getTypeAttribute()
    {
        return $this->quantity > 0 ? 'increase' : 'decrease';
    }

    // Methods
    public static function logAdjustment(Product $product, $quantity, $reason, $userId = null, $reference = null, $notes = null)
    {
        return static::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reason' => $reason,
            'user_id' => $userId ?? auth()->id(),
            'reference' => $reference,
            'notes' => $notes
        ]);
    }
}
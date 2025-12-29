<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'pos_session_id',
        'amount',
        'method', // 'cash', 'card', 'bank_transfer', 'other'
        'reference',
        'status', // 'pending', 'completed', 'failed', 'refunded'
        'processed_at',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function posSession()
    {
        return $this->belongsTo(PosSession::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return '£' . number_format($this->amount, 2);
    }

    public function getMethodNameAttribute()
    {
        return match($this->method) {
            'cash' => 'Cash',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'other' => 'Other',
            default => ucfirst($this->method)
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'refunded' => 'orange',
            default => 'gray'
        };
    }

    // Methods
    public function markCompleted($reference = null)
    {
        $this->status = 'completed';
        $this->processed_at = now();
        $this->reference = $reference;
        $this->save();
    }

    public function markFailed($reason = null)
    {
        $this->status = 'failed';
        $this->notes = $reason;
        $this->save();
    }

    public function refund($amount = null, $reason = null)
    {
        $refundAmount = $amount ?? $this->amount;

        $refund = static::create([
            'order_id' => $this->order_id,
            'pos_session_id' => $this->pos_session_id,
            'amount' => -$refundAmount, // Negative amount for refund
            'method' => $this->method,
            'reference' => 'Refund of ' . $this->reference,
            'status' => 'completed',
            'processed_at' => now(),
            'notes' => $reason ?? 'Refund processed'
        ]);

        $this->status = 'refunded';
        $this->save();

        return $refund;
    }
}
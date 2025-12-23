<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_reference',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_customer_id',
        'stripe_metadata',
        'order_status',
        'order_type', // 'pos', 'online', 'phone'
        'staff_id',
        'notes',
        'metadata',
        'completed_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'stripe_metadata' => 'array',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Alias for orderItems (for compatibility)
    public function items()
    {
        return $this->orderItems();
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('order_status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', '!=', 'paid');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('order_type', $type);
    }

    // Accessors & Mutators
    public function getFormattedTotalAttribute()
    {
        return '£' . number_format($this->total_amount, 2);
    }

    public function getStatusColorAttribute()
    {
        return match($this->order_status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'cancelled' => 'red',
            'refunded' => 'orange',
            default => 'gray'
        };
    }

    public function getPaymentStatusColorAttribute()
    {
        return match($this->payment_status) {
            'paid' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            'refunded' => 'orange',
            default => 'gray'
        };
    }

    // Methods
    public function addItem(Product $product, $quantity, $unitPrice = null)
    {
        $unitPrice = $unitPrice ?? $product->price;

        $orderItem = $this->orderItems()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'tax_amount' => $product->calculateTax($quantity)
        ]);

        $this->recalculateTotals();
        return $orderItem;
    }

    public function removeItem($orderItemId)
    {
        $item = $this->orderItems()->find($orderItemId);
        if ($item) {
            $item->delete();
            $this->recalculateTotals();
        }
    }

    public function applyDiscount($amount, $type = 'fixed')
    {
        if ($type === 'percentage') {
            $this->discount_amount = $this->subtotal * ($amount / 100);
        } else {
            $this->discount_amount = $amount;
        }

        $this->recalculateTotals();
        $this->save();
    }

    public function recalculateTotals()
    {
        $this->subtotal = $this->orderItems->sum('total_price');
        $this->tax_amount = $this->orderItems->sum('tax_amount');
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }

    public function complete()
    {
        $this->order_status = 'completed';
        $this->completed_at = now();
        $this->save();

        // Reduce stock for each item
        foreach ($this->orderItems as $item) {
            $item->product->adjustStock(-$item->quantity, 'Order #' . $this->order_number);
        }
    }

    public function cancel()
    {
        $this->order_status = 'cancelled';
        $this->save();

        // Restore stock if order was completed
        if ($this->completed_at) {
            foreach ($this->orderItems as $item) {
                $item->product->adjustStock($item->quantity, 'Order cancellation #' . $this->order_number);
            }
        }
    }

    public function processPayment($method, $reference = null)
    {
        $this->payment_method = $method;
        $this->payment_status = 'paid';
        $this->payment_reference = $reference;
        $this->save();

        Payment::create([
            'order_id' => $this->id,
            'amount' => $this->total_amount,
            'method' => $method,
            'reference' => $reference,
            'status' => 'completed'
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . date('Ymd') . '-' . str_pad(static::count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'tax_amount',
        'discount_amount',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getFormattedUnitPriceAttribute()
    {
        return '£' . number_format($this->unit_price, 2);
    }

    public function getFormattedTotalAttribute()
    {
        return '£' . number_format($this->total_price, 2);
    }

    public function getFormattedTaxAttribute()
    {
        return '£' . number_format($this->tax_amount, 2);
    }

    // Methods
    public function updateQuantity($newQuantity)
    {
        $this->quantity = $newQuantity;
        $this->total_price = $this->unit_price * $newQuantity;
        $this->tax_amount = $this->product ? $this->product->calculateTax($newQuantity) : 0;
        $this->save();

        $this->order->recalculateTotals();
    }

    public function applyDiscount($amount, $type = 'fixed')
    {
        if ($type === 'percentage') {
            $this->discount_amount = $this->total_price * ($amount / 100);
        } else {
            $this->discount_amount = $amount;
        }

        $this->total_price = ($this->unit_price * $this->quantity) - $this->discount_amount;
        $this->save();

        $this->order->recalculateTotals();
    }
}
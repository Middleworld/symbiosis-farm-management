<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'woocommerce_subscription_id',
        'customer_id',
        'customer_email',
        'customer_name',
        'status',
        'amount',
        'billing_period',
        'billing_interval',
        'next_payment_date',
        'last_payment_date',
        'payment_method',
        'notes'
    ];

    protected $casts = [
        'next_payment_date' => 'datetime',
        'last_payment_date' => 'datetime',
        'amount' => 'decimal:2'
    ];

    /**
     * Get the WooCommerce subscription data
     */
    public function getWooCommerceData()
    {
        // This would fetch data from the WordPress database
        // For now, return cached/synced data
        return [
            'id' => $this->woocommerce_subscription_id,
            'status' => $this->status,
            'customer' => $this->customer_name,
            'amount' => $this->amount,
            'next_payment' => $this->next_payment_date
        ];
    }

    /**
     * Check if subscription needs renewal
     */
    public function needsRenewal()
    {
        return $this->status === 'active' &&
               $this->next_payment_date &&
               $this->next_payment_date->isPast();
    }

    /**
     * Calculate next payment date
     */
    public function calculateNextPaymentDate()
    {
        if (!$this->last_payment_date) {
            return now();
        }

        $interval = $this->billing_interval;

        switch ($this->billing_period) {
            case 'week':
                return $this->last_payment_date->addWeeks($interval);
            case 'month':
                return $this->last_payment_date->addMonths($interval);
            case 'year':
                return $this->last_payment_date->addYears($interval);
            default:
                return $this->last_payment_date->addMonths(1);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WooCommerceOrder extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'posts'; // WooCommerce orders are stored as posts
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $casts = [
        'post_date' => 'datetime',
        'post_date_gmt' => 'datetime',
        'post_modified' => 'datetime',
        'post_modified_gmt' => 'datetime',
    ];

    /**
     * Only get orders (not other post types)
     */
    protected static function boot()
    {
        parent::boot();
        
        static::addGlobalScope('orders', function ($builder) {
            $builder->whereIn('post_type', ['shop_order', 'shop_subscription']);
        });
    }

    /**
     * Get order meta data
     */
    public function meta()
    {
        return $this->hasMany(WooCommerceOrderMeta::class, 'post_id', 'ID');
    }

    /**
     * Get specific meta value
     */
    public function getMeta($key, $default = null)
    {
        $meta = $this->meta()->where('meta_key', $key)->first();
        return $meta ? $meta->meta_value : $default;
    }

    /**
     * Get the customer for this order
     */
    public function customer()
    {
        $customer_id = $this->getMeta('_customer_user');
        return $customer_id ? WordPressUser::find($customer_id) : null;
    }

    /**
     * Get order items
     */
    public function items()
    {
        return $this->hasMany(WooCommerceOrderItem::class, 'order_id', 'ID');
    }

    /**
     * Scope for orders only
     */
    public function scopeOrders($query)
    {
        return $query->where('post_type', 'shop_order');
    }

    /**
     * Scope for subscriptions only
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('post_type', 'shop_subscription');
    }

    /**
     * Scope for active orders/subscriptions (deprecated, no longer used)
     */
    public function scopeActive($query)
    {
        // Deprecated: No status filtering
        return $query;
    }

    /**
     * Get formatted order data
     */
    public function getFormattedData()
    {
        $customer = $this->customer();
        
        return [
            'id' => $this->ID,
            'order_number' => $this->getMeta('_order_number') ?: $this->ID,
            'status' => str_replace('wc-', '', $this->post_status),
            'date_created' => $this->post_date,
            'total' => (float) $this->getMeta('_order_total'),
            'currency' => $this->getMeta('_order_currency', 'GBP'),
            'customer_id' => $this->getMeta('_customer_user'),
            'customer_name' => $customer ? $customer->display_name : 'Guest',
            'customer_email' => $this->getMeta('_billing_email'),
            'billing_address' => [
                'first_name' => $this->getMeta('_billing_first_name'),
                'last_name' => $this->getMeta('_billing_last_name'),
                'company' => $this->getMeta('_billing_company'),
                'address_1' => $this->getMeta('_billing_address_1'),
                'address_2' => $this->getMeta('_billing_address_2'),
                'city' => $this->getMeta('_billing_city'),
                'state' => $this->getMeta('_billing_state'),
                'postcode' => $this->getMeta('_billing_postcode'),
                'country' => $this->getMeta('_billing_country'),
                'phone' => $this->getMeta('_billing_phone'),
            ],
            'shipping_address' => [
                'first_name' => $this->getMeta('_shipping_first_name'),
                'last_name' => $this->getMeta('_shipping_last_name'),
                'company' => $this->getMeta('_shipping_company'),
                'address_1' => $this->getMeta('_shipping_address_1'),
                'address_2' => $this->getMeta('_shipping_address_2'),
                'city' => $this->getMeta('_shipping_city'),
                'state' => $this->getMeta('_shipping_state'),
                'postcode' => $this->getMeta('_shipping_postcode'),
                'country' => $this->getMeta('_shipping_country'),
            ],
            'payment_method' => $this->getMeta('_payment_method'),
            'payment_method_title' => $this->getMeta('_payment_method_title'),
            'transaction_id' => $this->getMeta('_transaction_id'),
            'type' => $this->post_type === 'shop_subscription' ? 'subscription' : 'order',
        ];
    }

    /**
     * Get recent orders for delivery schedule (no status filtering)
     */
    public static function getForDeliverySchedule($limit = 50)
    {
        return static::orders()
            ->with(['meta'])
            ->orderBy('post_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $data = $order->getFormattedData();
                
                // Add delivery-specific data
                $data['delivery_date'] = $order->getMeta('_delivery_date');
                $data['delivery_slot'] = $order->getMeta('_delivery_slot');
                $data['delivery_notes'] = $order->getMeta('_delivery_notes');
                $data['special_instructions'] = $order->getMeta('_customer_notes') ?: $order->post_excerpt;
                
                return $data;
            });
    }

    /**
     * Get all subscriptions for delivery schedule (no status filtering)
     */
    public static function getActiveSubscriptions($limit = 50)
    {
        return static::subscriptions()
            ->with(['meta'])
            ->orderBy('post_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($subscription) {
                $data = $subscription->getFormattedData();
                
                // Add subscription-specific data
                $data['next_payment'] = $subscription->getMeta('_schedule_next_payment');
                $data['billing_period'] = $subscription->getMeta('_billing_period');
                $data['billing_interval'] = $subscription->getMeta('_billing_interval');
                $data['subscription_status'] = str_replace('wc-', '', $subscription->post_status);
                
                return $data;
            });
    }
}

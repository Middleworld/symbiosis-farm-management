<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WooCommerceOrderItemMeta extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'woocommerce_order_itemmeta';
    protected $primaryKey = 'meta_id';
    public $timestamps = false;
    
    protected $fillable = [
        'order_item_id',
        'meta_key',
        'meta_value'
    ];

    /**
     * Get the order item that owns this meta
     */
    public function orderItem()
    {
        return $this->belongsTo(WooCommerceOrderItem::class, 'order_item_id', 'order_item_id');
    }
}

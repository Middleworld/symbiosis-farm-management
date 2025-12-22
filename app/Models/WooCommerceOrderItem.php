<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WooCommerceOrderItem extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'woocommerce_order_items';
    protected $primaryKey = 'order_item_id';
    public $timestamps = false;
    
    protected $fillable = [
        'order_item_name',
        'order_item_type',
        'order_id'
    ];

    /**
     * Get the order that owns this item
     */
    public function order()
    {
        return $this->belongsTo(WooCommerceOrder::class, 'order_id', 'ID');
    }

    /**
     * Get item meta data
     */
    public function meta()
    {
        return $this->hasMany(WooCommerceOrderItemMeta::class, 'order_item_id', 'order_item_id');
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
     * Get formatted item data
     */
    public function getFormattedData()
    {
        return [
            'id' => $this->order_item_id,
            'name' => $this->order_item_name,
            'type' => $this->order_item_type,
            'quantity' => (int) $this->getMeta('_qty', 1),
            'total' => (float) $this->getMeta('_line_total', 0),
            'product_id' => $this->getMeta('_product_id'),
            'variation_id' => $this->getMeta('_variation_id'),
        ];
    }
}

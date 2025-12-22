<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WooCommerceOrderMeta extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'postmeta'; // WooCommerce order meta is stored in postmeta
    protected $primaryKey = 'meta_id';
    public $timestamps = false;
    
    protected $fillable = [
        'post_id',
        'meta_key',
        'meta_value'
    ];

    /**
     * Get the order that owns this meta
     */
    public function order()
    {
        return $this->belongsTo(WooCommerceOrder::class, 'post_id', 'ID');
    }
}

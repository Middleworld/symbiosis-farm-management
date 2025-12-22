<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WordPressUser extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'users'; // Will be prefixed with D6sPMX_ automatically
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    protected $fillable = [
        'user_login',
        'user_email',
        'user_nicename',
        'display_name',
        'user_registered',
        'user_status'
    ];

    protected $casts = [
        'user_registered' => 'datetime',
        'user_status' => 'integer'
    ];

    /**
     * Get user meta data
     */
    public function meta()
    {
        return $this->hasMany(WordPressUserMeta::class, 'user_id', 'ID');
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
     * Get user capabilities
     */
    public function getCapabilities()
    {
        $capabilities = $this->getMeta(config('database.connections.wordpress.prefix') . 'capabilities');
        return $capabilities ? unserialize($capabilities) : [];
    }

    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        $capabilities = $this->getCapabilities();
        return isset($capabilities[$role]) && $capabilities[$role];
    }

    /**
     * Get user's primary role
     */
    public function getPrimaryRole()
    {
        $capabilities = $this->getCapabilities();
        
        // Check for common WordPress and WooCommerce roles in order of priority
        $roles = [
            'administrator', 
            'shop_manager', 
            'customer', 
            'editor', 
            'author', 
            'contributor', 
            'subscriber',
            'delicious_recipes_subscriber' // Your custom role
        ];
        
        foreach ($roles as $role) {
            if (isset($capabilities[$role]) && $capabilities[$role]) {
                return $role;
            }
        }
        
        // If no standard role found, return the first capability found
        if (!empty($capabilities)) {
            $firstRole = array_keys($capabilities)[0];
            return $firstRole;
        }
        
        return 'subscriber';
    }

    /**
     * Get user's WooCommerce data
     */
    public function getWooCommerceData()
    {
        return [
            'billing_first_name' => $this->getMeta('billing_first_name'),
            'billing_last_name' => $this->getMeta('billing_last_name'),
            'billing_email' => $this->getMeta('billing_email'),
            'billing_phone' => $this->getMeta('billing_phone'),
            'billing_address_1' => $this->getMeta('billing_address_1'),
            'billing_city' => $this->getMeta('billing_city'),
            'billing_postcode' => $this->getMeta('billing_postcode'),
            'billing_country' => $this->getMeta('billing_country'),
            'shipping_first_name' => $this->getMeta('shipping_first_name'),
            'shipping_last_name' => $this->getMeta('shipping_last_name'),
            'shipping_address_1' => $this->getMeta('shipping_address_1'),
            'shipping_city' => $this->getMeta('shipping_city'),
            'shipping_postcode' => $this->getMeta('shipping_postcode'),
            'shipping_country' => $this->getMeta('shipping_country'),
        ];
    }

    /**
     * Get formatted user data for display
     */
    public function getFormattedData()
    {
        $wc_data = $this->getWooCommerceData();
        
        return [
            'id' => $this->ID,
            'username' => $this->user_login,
            'email' => $this->user_email,
            'display_name' => $this->display_name,
            'first_name' => $wc_data['billing_first_name'] ?: $this->getMeta('first_name'),
            'last_name' => $wc_data['billing_last_name'] ?: $this->getMeta('last_name'),
            'role' => $this->getPrimaryRole(),
            'registered' => $this->user_registered,
            'phone' => $wc_data['billing_phone'],
            'address' => [
                'billing' => [
                    'address_1' => $wc_data['billing_address_1'],
                    'city' => $wc_data['billing_city'],
                    'postcode' => $wc_data['billing_postcode'],
                    'country' => $wc_data['billing_country'],
                ],
                'shipping' => [
                    'address_1' => $wc_data['shipping_address_1'],
                    'city' => $wc_data['shipping_city'],
                    'postcode' => $wc_data['shipping_postcode'],
                    'country' => $wc_data['shipping_country'],
                ]
            ]
        ];
    }

    /**
     * Search users by various criteria
     */
    public static function search($query, $limit = 20)
    {
        return static::where('user_login', 'LIKE', "%{$query}%")
            ->orWhere('user_email', 'LIKE', "%{$query}%")
            ->orWhere('display_name', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Get WooCommerce orders for this user
     */
    public function orders()
    {
        return $this->hasMany(WooCommerceOrder::class, 'ID', 'meta_value')
                    ->whereHas('meta', function($query) {
                        $query->where('meta_key', '_customer_user');
                    });
    }
}

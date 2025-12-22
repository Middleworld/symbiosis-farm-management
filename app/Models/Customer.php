<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'users';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    /**
     * Search for customers by name, email, or phone
     */
    public static function search($query)
    {
        $connection = \DB::connection('wordpress');
        $prefix = $connection->getTablePrefix();
        
        // Search in users table for name and email
        $users = $connection->table('users')
            ->select([
                'ID',
                'display_name as name',
                'user_email as email'
            ])
            ->where('display_name', 'like', "%{$query}%")
            ->orWhere('user_email', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        // Enhance with metadata (phone and address)
        $results = [];
        foreach ($users as $user) {
            $meta = $connection->table('usermeta')
                ->where('user_id', $user->ID)
                ->whereIn('meta_key', [
                    'billing_phone', 
                    'billing_address_1',
                    'billing_address_2',
                    'billing_city',
                    'billing_state',
                    'billing_postcode',
                    'shipping_address_1',
                    'shipping_address_2',
                    'shipping_city',
                    'shipping_state',
                    'shipping_postcode'
                ])
                ->get()
                ->keyBy('meta_key');

            // Build full address from components
            $addressParts = array_filter([
                $meta['billing_address_1']->meta_value ?? $meta['shipping_address_1']->meta_value ?? '',
                $meta['billing_address_2']->meta_value ?? $meta['shipping_address_2']->meta_value ?? '',
                $meta['billing_city']->meta_value ?? $meta['shipping_city']->meta_value ?? '',
                $meta['billing_state']->meta_value ?? $meta['shipping_state']->meta_value ?? ''
            ]);
            
            $fullAddress = implode(', ', $addressParts);

            $results[] = [
                'id' => $user->ID,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $meta['billing_phone']->meta_value ?? '',
                'address' => $fullAddress,
                'postcode' => $meta['billing_postcode']->meta_value ?? $meta['shipping_postcode']->meta_value ?? ''
            ];
        }

        // Also search by phone in usermeta
        $phoneUsers = $connection->table('usermeta as um')
            ->join('users as u', 'um.user_id', '=', 'u.ID')
            ->select('u.ID', 'u.display_name as name', 'u.user_email as email')
            ->where('um.meta_key', 'billing_phone')
            ->where('um.meta_value', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        foreach ($phoneUsers as $user) {
            // Skip if already in results
            if (collect($results)->contains('id', $user->ID)) {
                continue;
            }

            $meta = $connection->table('usermeta')
                ->where('user_id', $user->ID)
                ->whereIn('meta_key', [
                    'billing_phone', 
                    'billing_address_1',
                    'billing_address_2',
                    'billing_city',
                    'billing_state',
                    'billing_postcode',
                    'shipping_address_1',
                    'shipping_address_2',
                    'shipping_city',
                    'shipping_state',
                    'shipping_postcode'
                ])
                ->get()
                ->keyBy('meta_key');

            // Build full address from components
            $addressParts = array_filter([
                $meta['billing_address_1']->meta_value ?? $meta['shipping_address_1']->meta_value ?? '',
                $meta['billing_address_2']->meta_value ?? $meta['shipping_address_2']->meta_value ?? '',
                $meta['billing_city']->meta_value ?? $meta['shipping_city']->meta_value ?? '',
                $meta['billing_state']->meta_value ?? $meta['shipping_state']->meta_value ?? ''
            ]);
            
            $fullAddress = implode(', ', $addressParts);

            $results[] = [
                'id' => $user->ID,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $meta['billing_phone']->meta_value ?? '',
                'address' => $fullAddress,
                'postcode' => $meta['billing_postcode']->meta_value ?? $meta['shipping_postcode']->meta_value ?? ''
            ];
        }

        return collect($results)->take(10);
    }

    /**
     * Get user metadata
     */
    public function getMeta($key, $default = null)
    {
        $meta = \DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $this->ID)
            ->where('meta_key', $key)
            ->first();

        return $meta ? $meta->meta_value : $default;
    }

    /**
     * Get full customer details including all meta
     */
    public function getFullDetails()
    {
        $meta = \DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $this->ID)
            ->whereIn('meta_key', [
                'billing_first_name',
                'billing_last_name',
                'billing_phone',
                'billing_email',
                'billing_address_1',
                'billing_address_2',
                'billing_city',
                'billing_postcode',
                'billing_state',
                'billing_country',
                'shipping_first_name',
                'shipping_last_name',
                'shipping_phone',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_postcode',
                'shipping_state',
                'shipping_country',
            ])
            ->get()
            ->keyBy('meta_key');

        return [
            'id' => $this->ID,
            'name' => $this->display_name,
            'email' => $this->user_email,
            'phone' => $meta['billing_phone']->meta_value ?? '',
            'address' => $meta['billing_address_1']->meta_value ?? '',
            'postcode' => $meta['billing_postcode']->meta_value ?? '',
            'billing_address' => $meta['billing_address_1']->meta_value ?? '',
            'billing_postcode' => $meta['billing_postcode']->meta_value ?? '',
            'shipping_address' => $meta['shipping_address_1']->meta_value ?? '',
            'shipping_postcode' => $meta['shipping_postcode']->meta_value ?? '',
        ];
    }
}

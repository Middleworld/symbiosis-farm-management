<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateTracking extends Model
{
    protected $table = 'update_tracking';
    
    protected $fillable = [
        'version',
        'title',
        'description',
        'files_changed',
        'changes',
        'customer_id',
        'environment',
        'applied_at',
        'applied_by'
    ];

    protected $casts = [
        'files_changed' => 'array',
        'changes' => 'array',
        'applied_at' => 'datetime'
    ];

    /**
     * Scope for filtering by customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for filtering by environment
     */
    public function scopeInEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Get the latest version for a customer/environment
     */
    public static function getLatestVersion($customerId = null, $environment = 'production')
    {
        $query = self::inEnvironment($environment);
        
        if ($customerId) {
            $query->forCustomer($customerId);
        }
        
        return $query->orderBy('version', 'desc')->first();
    }
}

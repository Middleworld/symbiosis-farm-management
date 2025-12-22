<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_customer_id',
        'provider_payment_method_id',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'funding',
        'is_default',
        'ready_for_off_session',
        'status',
        'meta',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'ready_for_off_session' => 'boolean',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'woo_customer_id',
        'stripe_customer_id',
        'stripe_default_payment_method_id',
        'stripe_metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'stripe_metadata' => 'array',
        ];
    }

    public function paymentMethods()
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    public function defaultPaymentMethod()
    {
        return $this->paymentMethods()->where('is_default', true)->first();
    }
    
    /**
     * Check if user is an admin (for now, all Laravel users are admins)
     * TODO: Implement proper role-based access control if needed
     */
    public function isAdmin(): bool
    {
        // For now, all users in the Laravel admin system are admins
        // This is because customers use WordPress, not Laravel
        return true;
    }
}

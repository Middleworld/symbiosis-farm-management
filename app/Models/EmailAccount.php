<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailAccount extends Model
{
    protected $fillable = [
        'name',
        'email',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'username',
        'password',
        'is_active',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
        'imap_port' => 'integer',
        'smtp_port' => 'integer',
    ];

    /**
     * Get the emails for this account
     */
    public function emails(): HasMany
    {
        return $this->hasMany(AdminEmail::class, 'account_id');
    }

    /**
     * Get the active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default account
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get display name for the account
     */
    public function getDisplayNameAttribute()
    {
        return $this->name ?: $this->email;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpenBankingConnection extends Model
{
    protected $table = 'openbanking_connections';

    protected $fillable = [
        'bank_id',
        'bank_name',
        'bank_client_id',
        'bank_client_secret',
        'registration_endpoint',
        'auth_endpoint',
        'token_endpoint',
        'api_base',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'bank_client_secret',
        'access_token',
        'refresh_token',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(OpenBankingAccount::class, 'connection_id');
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function isAuthorized(): bool
    {
        return $this->status === 'authorized' && !$this->isTokenExpired();
    }
}

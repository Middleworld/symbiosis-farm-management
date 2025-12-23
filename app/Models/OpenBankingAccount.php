<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpenBankingAccount extends Model
{
    protected $table = 'openbanking_accounts';

    protected $fillable = [
        'connection_id',
        'account_id',
        'account_type',
        'account_subtype',
        'currency',
        'nickname',
        'account_number',
        'sort_code',
        'balance',
        'balance_type',
        'balance_updated_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'balance_updated_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(OpenBankingConnection::class, 'connection_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(OpenBankingTransaction::class, 'account_id');
    }

    public function getFormattedAccountNumberAttribute(): string
    {
        if ($this->sort_code && $this->account_number) {
            return $this->sort_code . ' ' . $this->account_number;
        }
        return $this->account_number ?? 'N/A';
    }
}

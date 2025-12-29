<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenBankingTransaction extends Model
{
    protected $table = 'openbanking_transactions';

    protected $fillable = [
        'account_id',
        'transaction_id',
        'type',
        'status',
        'booking_datetime',
        'value_datetime',
        'amount',
        'currency',
        'merchant_name',
        'merchant_category',
        'description',
        'reference',
        'balance_after',
        'metadata',
    ];

    protected $casts = [
        'booking_datetime' => 'datetime',
        'value_datetime' => 'datetime',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(OpenBankingAccount::class, 'account_id');
    }

    public function isCredit(): bool
    {
        return $this->type === 'Credit';
    }

    public function isDebit(): bool
    {
        return $this->type === 'Debit';
    }
}

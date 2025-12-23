<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\BankTransactionCategorizationService;

class BankTransaction extends Model
{
    protected $fillable = [
        'transaction_date',
        'description',
        'amount',
        'type',
        'reference',
        'balance',
        'category',
        'notes',
        'matched_order_id',
        'matched_subscription_id',
        'stripe_payout_id',
        'stripe_charges',
        'import_filename',
        'imported_at',
        'imported_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'stripe_charges' => 'array',
        'balance' => 'decimal:2',
        'imported_at' => 'datetime',
    ];

    /**
     * Scope for income transactions
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope for expense transactions
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        $service = new BankTransactionCategorizationService();
        return $service->getCategoryLabel($this->category ?? 'uncategorized');
    }

    /**
     * Check if transaction is categorized
     */
    public function isCategorized(): bool
    {
        return !empty($this->category) && $this->category !== 'uncategorized';
    }

    /**
     * Auto-categorize this transaction
     */
    public function autoCategorize(): void
    {
        $service = new BankTransactionCategorizationService();
        $this->category = $service->categorize($this);
        $this->save();
    }

    /**
     * Imported by user
     */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}


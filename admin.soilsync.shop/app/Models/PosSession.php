<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'status', // 'open', 'closed'
        'notes'
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }

    // Accessors
    public function getIsOpenAttribute()
    {
        return $this->status === 'open';
    }

    public function getDurationAttribute()
    {
        if (!$this->closed_at) {
            return now()->diff($this->opened_at);
        }

        return $this->closed_at->diff($this->opened_at);
    }

    public function getTotalSalesAttribute()
    {
        return $this->orders()->completed()->sum('total_amount');
    }

    public function getTotalPaymentsAttribute()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getBalanceDifferenceAttribute()
    {
        if (!$this->closing_balance) {
            return 0;
        }

        return $this->closing_balance - $this->expected_balance;
    }

    // Methods
    public static function startSession($userId, $openingBalance = 0)
    {
        // Close any existing open sessions for this user
        static::where('user_id', $userId)->where('status', 'open')->update([
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => 'Auto-closed due to new session start'
        ]);

        return static::create([
            'user_id' => $userId,
            'session_id' => 'POS-' . date('Ymd-His') . '-' . $userId,
            'opened_at' => now(),
            'opening_balance' => $openingBalance,
            'status' => 'open'
        ]);
    }

    public function closeSession($closingBalance, $notes = null)
    {
        $this->closing_balance = $closingBalance;
        $this->expected_balance = $this->opening_balance + $this->total_sales;
        $this->closed_at = now();
        $this->status = 'closed';
        $this->notes = $notes;
        $this->save();
    }

    public function addOrder(Order $order)
    {
        // Orders are automatically associated via the session relationship
        // This method can be used for additional session-specific logic
        return $order;
    }
}
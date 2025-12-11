<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'locked_amount' => 'decimal:8',
    ];

    /**
     * Get the user that owns this asset
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get total amount (available + locked)
     */
    public function getTotalAmountAttribute(): string
    {
        return bcadd($this->amount, $this->locked_amount, 8);
    }

    /**
     * Lock amount for sell order
     */
    public function lockAmount(float $amount): void
    {
        $this->decrement('amount', $amount);
        $this->increment('locked_amount', $amount);
    }

    /**
     * Unlock amount (e.g., when canceling order)
     */
    public function unlockAmount(float $amount): void
    {
        $this->decrement('locked_amount', $amount);
        $this->increment('amount', $amount);
    }

    /**
     * Release locked amount after trade
     */
    public function releaseLockedAmount(float $amount): void
    {
        $this->decrement('locked_amount', $amount);
    }

    /**
     * Add amount to available
     */
    public function addAmount(float $amount): void
    {
        $this->increment('amount', $amount);
    }
}

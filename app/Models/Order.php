<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const STATUS_OPEN = 1;
    const STATUS_FILLED = 2;
    const STATUS_CANCELLED = 3;

    const SIDE_BUY = 'buy';
    const SIDE_SELL = 'sell';

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'amount' => 'decimal:8',
        'status' => 'integer',
    ];

    /**
     * Get the user that owns this order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get trades where this order was the buy order
     */
    public function buyTrades()
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    /**
     * Get trades where this order was the sell order
     */
    public function sellTrades()
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

    /**
     * Calculate total volume (price * amount)
     */
    public function getVolumeAttribute(): string
    {
        return bcmul($this->price, $this->amount, 8);
    }

    /**
     * Check if order is open
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if order is filled
     */
    public function isFilled(): bool
    {
        return $this->status === self::STATUS_FILLED;
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Mark order as filled
     */
    public function markAsFilled(): void
    {
        $this->update(['status' => self::STATUS_FILLED]);
    }

    /**
     * Mark order as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Check if this is a buy order
     */
    public function isBuy(): bool
    {
        return $this->side === self::SIDE_BUY;
    }

    /**
     * Check if this is a sell order
     */
    public function isSell(): bool
    {
        return $this->side === self::SIDE_SELL;
    }

    /**
     * Scope to get only open orders
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get buy orders
     */
    public function scopeBuy($query)
    {
        return $query->where('side', self::SIDE_BUY);
    }

    /**
     * Scope to get sell orders
     */
    public function scopeSell($query)
    {
        return $query->where('side', self::SIDE_SELL);
    }

    /**
     * Scope to filter by symbol
     */
    public function scopeSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }
}

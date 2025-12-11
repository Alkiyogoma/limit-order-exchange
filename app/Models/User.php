<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:8',
    ];

    /**
     * Get all assets for this user
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get all orders for this user
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get trades where user was buyer
     */
    public function buyTrades()
    {
        return $this->hasMany(Trade::class, 'buyer_id');
    }

    /**
     * Get trades where user was seller
     */
    public function sellTrades()
    {
        return $this->hasMany(Trade::class, 'seller_id');
    }

    /**
     * Get or create asset for a symbol
     */
    public function getAsset(string $symbol): Asset
    {
        return $this->assets()->firstOrCreate(
            ['symbol' => $symbol],
            ['amount' => 0, 'locked_amount' => 0]
        );
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Check if user has sufficient asset amount
     */
    public function hasSufficientAsset(string $symbol, float $amount): bool
    {
        $asset = $this->assets()->where('symbol', $symbol)->first();
        return $asset && $asset->amount >= $amount;
    }
}

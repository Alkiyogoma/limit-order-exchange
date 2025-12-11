<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Exceptions\InsufficientAssetException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidOrderStatusException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\UnauthorizedOrderCancellationException;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    const COMMISSION_RATE = 0.015; // 1.5%

    /**
     * Create a new order and attempt immediate matching
     */
    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock user for update to prevent race conditions
            $user = User::lockForUpdate()->find($user->id);

            $symbol = strtoupper($data['symbol']);
            $side = $data['side'];
            $price = $data['price'];
            $amount = $data['amount'];

            // Validate and lock resources
            if ($side === Order::SIDE_BUY) {
                $this->validateAndLockBuyOrder($user, $price, $amount);
            } else {
                $this->validateAndLockSellOrder($user, $symbol, $amount);
            }

            // Create the order
            $order = $user->orders()->create([
                'symbol' => $symbol,
                'side' => $side,
                'price' => $price,
                'amount' => $amount,
                'status' => Order::STATUS_OPEN,
            ]);

            // Try to match immediately within same transaction
            $counterOrder = $this->findMatchingOrder($order);
            if ($counterOrder) {
                $this->executeTrade($order, $counterOrder);
            }

            return $order->fresh();
        });
    }

    /**
     * Validate and lock funds for buy order
     */
    private function validateAndLockBuyOrder(User $user, string $price, string $amount): void
    {
        $totalCost = bcmul($price, $amount, 8);

        if (bccomp($user->balance, $totalCost, 8) < 0) {
            throw new InsufficientBalanceException(
                "Insufficient balance. Required: $totalCost USD, Available: {$user->balance} USD"
            );
        }

        // Lock USD by deducting from balance
        $user->decrement('balance', $totalCost);
    }

    /**
     * Validate and lock assets for sell order
     */
    private function validateAndLockSellOrder(User $user, string $symbol, string $amount): void
    {
        // Get or create asset record
        $asset = Asset::firstOrCreate(
            ['user_id' => $user->id, 'symbol' => $symbol],
            ['amount' => '0', 'locked_amount' => '0']
        );

        // Re-fetch with lock to ensure consistency
        $asset = Asset::lockForUpdate()->find($asset->id);

        // Check if user has sufficient asset
        if (bccomp($asset->amount, $amount, 8) < 0) {
            throw new InsufficientAssetException(
                "Insufficient $symbol. Required: $amount, Available: {$asset->amount}"
            );
        }

        // Lock asset amount
        $asset->lockAmount($amount);
    }

    /**
     * Find a matching counter order (full match only)
     */
    private function findMatchingOrder(Order $order): ?Order
    {
        if ($order->isBuy()) {
            // Find cheapest sell order that matches price and amount
            return Order::open()
                ->sell()
                ->symbol($order->symbol)
                ->where('price', '<=', $order->price)
                ->where('amount', '=', $order->amount) // Full match only
                ->orderBy('price', 'asc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();
        } else {
            // Find highest buy order that matches price and amount
            return Order::open()
                ->buy()
                ->symbol($order->symbol)
                ->where('price', '>=', $order->price)
                ->where('amount', '=', $order->amount) // Full match only
                ->orderBy('price', 'desc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();
        }
    }

    /**
     * Execute trade between two orders
     */
    private function executeTrade(Order $newOrder, Order $counterOrder): void
    {
        $buyOrder = $newOrder->isBuy() ? $newOrder : $counterOrder;
        $sellOrder = $newOrder->isSell() ? $newOrder : $counterOrder;

        // Use the counter order price (maker price)
        $executionPrice = $counterOrder->price;
        $amount = $newOrder->amount;

        // Calculate volume and commission
        $volume = bcmul($executionPrice, $amount, 8);
        $commission = bcmul($volume, self::COMMISSION_RATE, 8);

        // Lock users for update
        $buyer = User::lockForUpdate()->find($buyOrder->user_id);
        $seller = User::lockForUpdate()->find($sellOrder->user_id);

        // Get or create buyer's asset record
        $buyerAsset = Asset::firstOrCreate(
            ['user_id' => $buyer->id, 'symbol' => $buyOrder->symbol],
            ['amount' => '0', 'locked_amount' => '0']
        );
        $buyerAsset = Asset::lockForUpdate()->find($buyerAsset->id);

        // Get seller's asset record
        $sellerAsset = Asset::lockForUpdate()
            ->where('user_id', $seller->id)
            ->where('symbol', $sellOrder->symbol)
            ->first();

        // Release locked amount from seller
        $sellerAsset->releaseLockedAmount($amount);

        // Transfer asset to buyer
        $buyerAsset->addAmount($amount);

        // Handle USD settlement
        // Buyer paid at their order price, but execution is at maker price
        $buyerPaid = bcmul($buyOrder->price, $amount, 8);
        $actualCost = $volume;
        $refund = bcsub($buyerPaid, $actualCost, 8);

        // Refund excess to buyer if they paid more
        if (bccomp($refund, '0', 8) > 0) {
            $buyer->increment('balance', $refund);
        }

        // Seller receives execution price minus commission
        // Commission is deducted from seller's proceeds
        $sellerReceives = bcsub($volume, $commission, 8);
        $seller->increment('balance', $sellerReceives);

        // Mark orders as filled
        $buyOrder->markAsFilled();
        $sellOrder->markAsFilled();

        // Create trade record
        $trade = Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => $buyOrder->symbol,
            'price' => $executionPrice,
            'amount' => $amount,
            'volume' => $volume,
            'commission' => $commission,
        ]);

        // Broadcast event to BOTH users (remove toOthers() to ensure delivery)
        event(new OrderMatched($trade));

        Log::info('Trade executed', [
            'trade_id' => $trade->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => $trade->symbol,
            'price' => $trade->price,
            'amount' => $trade->amount,
            'volume' => $trade->volume,
            'commission' => $trade->commission,
        ]);
    }

    /**
     * Cancel an order and release locked resources
     */
    public function cancelOrder(User $user, int $orderId): Order
    {
        return DB::transaction(function () use ($user, $orderId) {
            $order = Order::lockForUpdate()->find($orderId);

            if (!$order) {
                throw new OrderNotFoundException();
            }

            if ($order->user_id !== $user->id) {
                throw new UnauthorizedOrderCancellationException();
            }

            if (!$order->isOpen()) {
                throw new InvalidOrderStatusException(
                    'Only open orders can be cancelled'
                );
            }

            // Release locked resources
            if ($order->isBuy()) {
                // Return locked USD to balance
                $lockedAmount = bcmul($order->price, $order->amount, 8);
                $user->increment('balance', $lockedAmount);
            } else {
                // Return locked assets
                $asset = Asset::lockForUpdate()
                    ->where('user_id', $user->id)
                    ->where('symbol', $order->symbol)
                    ->first();

                if ($asset) {
                    $asset->unlockAmount($order->amount);
                }
            }

            // Mark order as cancelled
            $order->markAsCancelled();

            return $order;
        });
    }

    /**
     * Get orderbook for a symbol
     */
    public function getOrderbook(string $symbol): array
    {
        $symbol = strtoupper($symbol);

        $buyOrders = Order::open()
            ->buy()
            ->symbol($symbol)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        $sellOrders = Order::open()
            ->sell()
            ->symbol($symbol)
            ->orderBy('price', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'symbol' => $symbol,
            'buys' => $buyOrders,
            'sells' => $sellOrders,
        ];
    }
}

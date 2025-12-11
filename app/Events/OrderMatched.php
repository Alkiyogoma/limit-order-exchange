<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Trade $trade) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->trade->buyer_id),
            new PrivateChannel('user.' . $this->trade->seller_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'trade_id' => $this->trade->id,
            'buyer_id' => $this->trade->buyer_id,
            'seller_id' => $this->trade->seller_id,
            'symbol' => $this->trade->symbol,
            'price' => $this->trade->price,
            'amount' => $this->trade->amount,
            'volume' => $this->trade->volume,
            'commission' => $this->trade->commission,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    /**
     * Get authenticated user's trade history
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all trades where user was either buyer or seller
        $trades = \App\Models\Trade::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->with(['buyOrder', 'sellOrder', 'buyer:id,name', 'seller:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($trades);
    }
}

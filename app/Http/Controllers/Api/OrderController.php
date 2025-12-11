<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Get orderbook for a symbol
     */
    public function index(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string|in:BTC,ETH',
        ]);

        $orderbook = $this->orderService->getOrderbook($request->symbol);

        return response()->json($orderbook);
    }

    /**
     * Get authenticated user's orders
     */
    public function myOrders(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Create a new order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string|in:BTC,ETH',
            'side' => ['required', Rule::in(['buy', 'sell'])],
            'price' => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        try {
            $order = $this->orderService->createOrder($request->user(), $validated);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, int $id)
    {
        try {
            $order = $this->orderService->cancelOrder($request->user(), $id);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * Manual match trigger (for testing)
     */
    public function match(Request $request)
    {
        // This endpoint is for testing/admin purposes
        // In production, matching would happen automatically

        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = \App\Models\Order::find($request->order_id);

        if (!$order->isOpen()) {
            return response()->json([
                'message' => 'Order is not open',
            ], 400);
        }

        $this->orderService->matchOrder($order);

        return response()->json([
            'message' => 'Matching attempted',
            'order' => $order->fresh(),
        ]);
    }
}

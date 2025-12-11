<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile with balance and assets
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $user->load('assets');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'balance' => $user->balance,
            'assets' => $user->assets->map(function ($asset) {
                return [
                    'symbol' => $asset->symbol,
                    'amount' => $asset->amount,
                    'locked_amount' => $asset->locked_amount,
                    'total_amount' => $asset->total_amount,
                ];
            }),
        ]);
    }
}

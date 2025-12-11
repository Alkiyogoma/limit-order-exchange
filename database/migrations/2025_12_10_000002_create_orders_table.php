<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 10); // BTC, ETH, etc.
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 20, 8); // Price per unit in USD
            $table->decimal('amount', 20, 8); // Amount of asset
            $table->tinyInteger('status')->default(1); // 1=open, 2=filled, 3=cancelled
            $table->timestamps();

            // Critical indexes for matching performance
            $table->index(['symbol', 'status', 'side', 'price', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

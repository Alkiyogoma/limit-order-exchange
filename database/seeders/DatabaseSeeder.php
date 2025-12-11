<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Asset;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        $user1 = User::create([
            'name' => 'Alice Buyer',
            'email' => 'trader1@example.com',
            'password' => Hash::make('password'),
            'balance' => 100000.00, // $100k USD
        ]);

        $user2 = User::create([
            'name' => 'Bob Seller',
            'email' => 'trader2@example.com',
            'password' => Hash::make('password'),
            'balance' => 50000.00, // $50k USD
        ]);

        // Give Bob some BTC and ETH to sell
        Asset::create([
            'user_id' => $user2->id,
            'symbol' => 'BTC',
            'amount' => 5.00000000,
            'locked_amount' => 0,
        ]);

        Asset::create([
            'user_id' => $user2->id,
            'symbol' => 'ETH',
            'amount' => 100.00000000,
            'locked_amount' => 0,
        ]);

        $this->command->info('Test users created:');
        $this->command->info('Alice (Buyer): trader1@example.com / password - Balance: $100,000');
        $this->command->info('Bob (Seller): trader2@example.com / password - Balance: $50,000 + 5 BTC + 100 ETH');
    }
}

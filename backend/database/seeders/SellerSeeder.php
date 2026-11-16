<?php

namespace Database\Seeders;

use App\Models\Seller;
use Illuminate\Database\Seeder;

/** Invented marketplace sellers. Ratings differ so the offers table has something to compare. */
class SellerSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = [
            'PixelVault' => 4.9,
            'KeyNest' => 4.8,
            'NordKeys' => 4.7,
            'GameDrop' => 4.6,
            'Arcade Harbor' => 4.5,
            'Loot Lagoon' => 4.3,
            'QuickCode' => 4.1,
            'BudgetBytes' => 3.8,
        ];

        foreach ($sellers as $name => $rating) {
            Seller::create(['name' => $name, 'rating' => $rating]);
        }
    }
}

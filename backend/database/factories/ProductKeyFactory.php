<?php

namespace Database\Factories;

use App\Enums\KeyStatus;
use App\Models\Offer;
use App\Models\ProductKey;
use Database\Seeders\ProductSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductKey>
 */
class ProductKeyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'code' => ProductSeeder::randomKeyCode(),
            'status' => KeyStatus::Available,
            'order_item_id' => null,
        ];
    }
}

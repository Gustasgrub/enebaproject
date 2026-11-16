<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the full demo catalog: `php artisan migrate:fresh --seed` locally; in production the container
 * runs it on its first start through `catalog:seed-if-empty` (docs/DECISIONS.md #11).
 *
 * The seeders use no Faker: production installs no dev dependencies. Prices, stock and order history
 * come from a random generator with a fixed seed, so every run produces the same catalog
 * (docs/DECISIONS.md #18).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // One transaction: if seeding fails halfway, nothing is kept, so the catalog is still empty and
        // the next container start simply tries again.
        DB::transaction(function () {
            $this->call([
                SellerSeeder::class,
                ProductSeeder::class,
                OrderHistorySeeder::class,
                BannerSeeder::class,
                HomepageBlockSeeder::class,
            ]);
        });
    }
}

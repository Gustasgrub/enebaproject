<?php

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;

// Run by docker/start.sh on every container start. Render's free plan has no shell or pre-deploy
// command, so this is how production gets its catalog: seeded on the first start, skipped afterwards.
// Seeding runs inside the app so the key codes are encrypted with production's own APP_KEY
// (docs/DECISIONS.md #11).
Artisan::command('catalog:seed-if-empty', function () {
    if (Product::query()->exists()) {
        $this->info('The catalog already has products; not seeding.');

        return;
    }

    $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
})->purpose('Seed the database, but only if the catalog is empty');

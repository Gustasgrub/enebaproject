<?php

use App\Checkout\ExpiredReservations;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

// Keys reserved by a checkout that never finished go back on sale (App\Checkout\ExpiredReservations).
Artisan::command('checkout:release-expired', function (ExpiredReservations $expiredReservations) {
    $count = $expiredReservations->release();
    if ($count > 0) {
        $this->info("Released the keys of {$count} unfinished orders.");
    }
})->purpose('Release keys of pending orders whose checkout never finished');

// Run by `php artisan schedule:work`, which docker/start.sh starts next to the web server.
Schedule::command('checkout:release-expired')->everyMinute();

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A public demo account for reviewers (credentials in the README). Safe to run repeatedly: it only
 * creates the account when it is missing, so docker/start.sh runs it on every start, which also covers
 * a production database that was seeded before this account existed.
 */
class DemoAccountSeeder extends Seeder
{
    public const EMAIL = 'demo@example.com';

    public const PASSWORD = 'anabe-demo';

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => self::EMAIL],
            ['name' => 'Demo Buyer', 'password' => self::PASSWORD],
        );

        // A local admin for development (listed in .env.example's ADMIN_EMAILS). Never in production,
        // where the password would be public.
        if (app()->environment('local')) {
            User::firstOrCreate(
                ['email' => 'admin@example.com'],
                ['name' => 'Local Admin', 'password' => 'anabe-admin'],
            );
        }
    }
}

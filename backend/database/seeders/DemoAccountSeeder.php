<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Public demo accounts for reviewers, a buyer and an admin (credentials in the README). Safe to run repeatedly: it only
 * creates the account when it is missing, so docker/start.sh runs it on every start, which also covers
 * a production database that was seeded before this account existed.
 */
class DemoAccountSeeder extends Seeder
{
    public const EMAIL = 'demo@example.com';

    public const PASSWORD = 'anabe-demo';

    public const ADMIN_PASSWORD = 'anabe-admin';

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => self::EMAIL],
            ['name' => 'Demo Buyer', 'password' => self::PASSWORD],
        );

        // The public demo admin (config('anabe.demo_admin_email')), so reviewers can try /admin.
        User::firstOrCreate(
            ['email' => config('anabe.demo_admin_email')],
            ['name' => 'Demo Admin', 'password' => self::ADMIN_PASSWORD],
        );
    }
}

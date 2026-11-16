<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabaseState;

/**
 * For read-only tests against the full seeded catalog. Seeding takes a few seconds, so it runs once per
 * test class instead of once per test. Tests using this trait must not change any data.
 */
trait UsesSeededCatalog
{
    private static bool $catalogSeeded = false;

    // Laravel calls setUp<TraitName>() automatically for traits used by a test case.
    protected function setUpUsesSeededCatalog(): void
    {
        if (! self::$catalogSeeded) {
            $this->artisan('migrate:fresh', ['--seed' => true]);
            self::$catalogSeeded = true;
        }

        // The seeded rows are committed, not rolled back, so the next test class that uses
        // RefreshDatabase must rebuild an empty database instead of assuming it already has one.
        RefreshDatabaseState::$migrated = false;
    }
}

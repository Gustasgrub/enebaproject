<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedIfEmptyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_an_empty_database_once_and_then_leaves_it_alone(): void
    {
        $this->artisan('catalog:seed-if-empty')->assertSuccessful();
        $productCount = Product::count();
        $this->assertGreaterThan(0, $productCount);

        $this->artisan('catalog:seed-if-empty')
            ->expectsOutputToContain('not seeding')
            ->assertSuccessful();
        $this->assertSame($productCount, Product::count());
    }
}

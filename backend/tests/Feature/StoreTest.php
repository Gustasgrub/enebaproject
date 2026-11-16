<?php

namespace Tests\Feature;

use App\Enums\Platform;
use App\Enums\Region;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** A product with one offer per given price; each offer gets $stock available keys. */
    private function productWithOffers(array $attributes, array $pricesCents, int $stock = 1): Product
    {
        $product = Product::factory()->create($attributes);

        foreach ($pricesCents as $priceCents) {
            $offer = Offer::factory()->for($product)->create(['price_cents' => $priceCents]);
            ProductKey::factory()->count($stock)->for($offer)->create();
        }

        return $product;
    }

    public function test_a_product_is_priced_by_its_cheapest_offer_that_has_stock(): void
    {
        $product = $this->productWithOffers(['name' => 'Priced Game'], [3000, 2500]);
        // Cheaper, but out of stock: must not count.
        Offer::factory()->for($product)->create(['price_cents' => 1000]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Priced Game')
            ->assertJsonPath('data.0.price_cents', 2500);
    }

    public function test_a_deal_shows_its_original_price_and_discount(): void
    {
        $product = Product::factory()->create();
        $offer = Offer::factory()->for($product)->create(['price_cents' => 2700, 'original_price_cents' => 5999]);
        ProductKey::factory()->for($offer)->create();

        $this->getJson('/api/products')
            ->assertJsonPath('data.0.original_price_cents', 5999)
            ->assertJsonPath('data.0.discount_percent', 55);
    }

    public function test_products_without_stock_have_no_price_and_sort_last_by_price(): void
    {
        $this->productWithOffers(['name' => 'Sold Out'], [500], stock: 0);
        $this->productWithOffers(['name' => 'Expensive'], [6000]);
        $this->productWithOffers(['name' => 'Cheap'], [1000]);

        $ascending = $this->getJson('/api/products?sort=price_asc')->json('data.*.name');
        $descending = $this->getJson('/api/products?sort=price_desc')->json('data.*.name');

        $this->assertSame(['Cheap', 'Expensive', 'Sold Out'], $ascending);
        $this->assertSame(['Expensive', 'Cheap', 'Sold Out'], $descending);
        $this->assertNull($this->getJson('/api/products?sort=name')->json('data.2.price_cents'));
    }

    public function test_filters_by_platform_region_and_genre(): void
    {
        $this->productWithOffers(['name' => 'Steam Global RPG', 'platform' => Platform::Steam, 'region' => Region::Global, 'genre' => 'RPG'], [1000]);
        $this->productWithOffers(['name' => 'Xbox Global RPG', 'platform' => Platform::Xbox, 'region' => Region::Global, 'genre' => 'RPG'], [1000]);
        $this->productWithOffers(['name' => 'Steam Europe RPG', 'platform' => Platform::Steam, 'region' => Region::Europe, 'genre' => 'RPG'], [1000]);
        $this->productWithOffers(['name' => 'Steam Global Racing', 'platform' => Platform::Steam, 'region' => Region::Global, 'genre' => 'Racing'], [1000]);

        $query = http_build_query(['platform' => ['Steam'], 'region' => ['Global'], 'genre' => ['RPG']]);
        $this->assertSame(['Steam Global RPG'], $this->getJson("/api/products?{$query}")->json('data.*.name'));

        // Several values of one filter mean "any of them".
        $query = http_build_query(['platform' => ['Steam', 'Xbox'], 'region' => ['Global'], 'genre' => ['RPG'], 'sort' => 'name']);
        $this->assertSame(['Steam Global RPG', 'Xbox Global RPG'], $this->getJson("/api/products?{$query}")->json('data.*.name'));
    }

    public function test_filters_by_price_range_in_euros(): void
    {
        $this->productWithOffers(['name' => 'Nine'], [999]);
        $this->productWithOffers(['name' => 'Fifteen'], [1500]);
        $this->productWithOffers(['name' => 'Thirty'], [3000]);

        $names = $this->getJson('/api/products?min_price=9.99&max_price=15&sort=price_asc')->json('data.*.name');

        $this->assertSame(['Nine', 'Fifteen'], $names);
    }

    public function test_sorts_by_newest_and_name(): void
    {
        $this->productWithOffers(['name' => 'Beta', 'release_year' => 2015], [1000]);
        $this->productWithOffers(['name' => 'Alpha', 'release_year' => 2024], [1000]);
        $this->productWithOffers(['name' => 'Gamma', 'release_year' => 2020], [1000]);

        $this->assertSame(['Alpha', 'Gamma', 'Beta'], $this->getJson('/api/products')->json('data.*.name'));
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $this->getJson('/api/products?sort=name')->json('data.*.name'));
    }

    public function test_pages_hold_24_products(): void
    {
        Product::factory()->count(30)->create();

        $this->getJson('/api/products?page=2')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 30)
            ->assertJsonCount(6, 'data');
    }

    public function test_rejects_unknown_filter_values(): void
    {
        $this->getJson('/api/products?platform[]=Dreamcast&sort=cheapest')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['platform.0', 'sort']);
    }

    public function test_the_card_links_to_a_full_image_url(): void
    {
        Product::factory()->create(['image_path' => 'covers/elden-ring.svg']);

        $this->assertSame(url('covers/elden-ring.svg'), $this->getJson('/api/products')->json('data.0.image_url'));
    }

    public function test_filter_options_list_every_value_with_its_count(): void
    {
        $this->productWithOffers(['platform' => Platform::Steam, 'region' => Region::Global, 'genre' => 'RPG'], [1000]);
        $this->productWithOffers(['platform' => Platform::Steam, 'region' => Region::Europe, 'genre' => 'Action'], [1000]);

        $data = $this->getJson('/api/filters')->assertOk()->json('data');

        $platformCounts = collect($data['platforms'])->pluck('count', 'value');
        $this->assertSame(2, $platformCounts['Steam']);
        $this->assertSame(0, $platformCounts['Xbox']);
        $this->assertCount(count(Region::cases()), $data['regions']);
        $this->assertSame(['Action', 'RPG'], array_column($data['genres'], 'value'));
    }
}

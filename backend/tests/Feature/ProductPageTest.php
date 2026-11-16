<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    private function offer(Product $product, string $sellerName, int $priceCents, int $stock): Offer
    {
        $offer = Offer::factory()
            ->for($product)
            ->for(Seller::factory()->create(['name' => $sellerName, 'rating' => 4.5]))
            ->create(['price_cents' => $priceCents]);
        ProductKey::factory()->count($stock)->for($offer)->create();

        return $offer;
    }

    public function test_the_product_page_lists_offers_in_stock_cheapest_first_then_sold_out(): void
    {
        $product = Product::factory()->create(['slug' => 'elden-ring-steam-global']);
        $this->offer($product, 'Pricey', 3000, stock: 2);
        $this->offer($product, 'Sold Out', 1000, stock: 0);
        $this->offer($product, 'Cheapest', 2000, stock: 5);

        $response = $this->getJson('/api/products/elden-ring-steam-global');

        $response->assertOk()
            ->assertJsonPath('data.offers.*.seller.name', ['Cheapest', 'Pricey', 'Sold Out'])
            ->assertJsonPath('data.offers.*.stock', [5, 2, 0])
            ->assertJsonPath('data.offers.0.seller.rating', 4.5);
    }

    public function test_an_unknown_product_is_not_found(): void
    {
        $this->getJson('/api/products/half-life-3')->assertNotFound();
    }

    public function test_the_cart_gets_current_prices_and_stock_for_its_offers(): void
    {
        $product = Product::factory()->create(['name' => 'Hades']);
        $inCart = $this->offer($product, 'A', 1299, stock: 3);
        $this->offer($product, 'B', 999, stock: 3);

        $this->getJson('/api/offers?ids[]='.$inCart->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price_cents', 1299)
            ->assertJsonPath('data.0.stock', 3)
            ->assertJsonPath('data.0.product.name', 'Hades');
    }
}

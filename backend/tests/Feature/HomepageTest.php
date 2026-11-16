<?php

namespace Tests\Feature;

use App\Enums\KeyStatus;
use App\Enums\OrderStatus;
use App\Enums\Platform;
use App\Homepage\Blocks\BlockProvider;
use App\Homepage\Blocks\DealsBlock;
use App\Models\Banner;
use App\Models\HomepageBlock;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    private function block(string $type, array $config = [], int $position = 1, bool $enabled = true): HomepageBlock
    {
        return HomepageBlock::create(['type' => $type, 'title' => ucfirst($type), 'position' => $position, 'enabled' => $enabled, 'config' => $config]);
    }

    private function productInStock(array $attributes = [], int $priceCents = 2000, ?int $originalPriceCents = null): Product
    {
        $product = Product::factory()->create($attributes);
        $offer = Offer::factory()->for($product)->create(['price_cents' => $priceCents, 'original_price_cents' => $originalPriceCents]);
        ProductKey::factory()->for($offer)->create();

        return $product;
    }

    private function sell(Product $product, int $quantity, OrderStatus $status = OrderStatus::Paid, int $daysAgo = 1): void
    {
        $offer = $product->offers()->first();
        $order = Order::forceCreate([
            'user_id' => User::factory()->create()->id,
            'status' => $status,
            'total_cents' => $offer->price_cents * $quantity,
            'created_at' => now()->subDays($daysAgo),
        ]);
        $item = $order->items()->create(['offer_id' => $offer->id, 'quantity' => $quantity, 'unit_price_cents' => $offer->price_cents]);
        $keyStatus = $status === OrderStatus::Paid ? KeyStatus::Sold : KeyStatus::Reserved;
        ProductKey::factory()->count($quantity)->for($offer)->create(['status' => $keyStatus, 'order_item_id' => $item->id]);
    }

    public function test_the_layout_lists_enabled_blocks_in_order(): void
    {
        $this->block('deals', position: 2);
        $this->block('banner', position: 1);
        $this->block('new_releases', position: 3, enabled: false);

        $this->getJson('/api/homepage')
            ->assertOk()
            ->assertJsonPath('data.*.type', ['banner', 'deals']);
    }

    public function test_the_banner_block_returns_active_banners_in_order(): void
    {
        Banner::create(['title' => 'Second', 'subtitle' => '', 'image_path' => 'banners/b.svg', 'link_url' => '/store', 'position' => 2]);
        Banner::create(['title' => 'First', 'subtitle' => '', 'image_path' => 'banners/a.svg', 'link_url' => '/store', 'position' => 1]);
        Banner::create(['title' => 'Off', 'subtitle' => '', 'image_path' => 'banners/c.svg', 'link_url' => '/store', 'position' => 0, 'active' => false]);

        $this->getJson('/api/homepage/blocks/'.$this->block('banner')->id)
            ->assertOk()
            ->assertJsonPath('data.*.title', ['First', 'Second']);
    }

    public function test_bestsellers_rank_units_sold_in_paid_orders_of_the_last_30_days(): void
    {
        $popular = $this->productInStock(['name' => 'Popular']);
        $steady = $this->productInStock(['name' => 'Steady']);
        $old = $this->productInStock(['name' => 'Sold Long Ago']);
        $unpaid = $this->productInStock(['name' => 'Never Paid']);
        $this->sell($popular, 3);
        $this->sell($steady, 1);
        $this->sell($old, 5, daysAgo: 45);
        $this->sell($unpaid, 4, status: OrderStatus::Pending);

        $names = $this->getJson('/api/homepage/blocks/'.$this->block('bestsellers')->id)->json('data.*.name');

        // Ranked sales first; the rest of the row is filled with other products.
        $this->assertSame(['Popular', 'Steady'], array_slice($names, 0, 2));
    }

    public function test_bestsellers_fall_back_to_the_newest_products_without_sales(): void
    {
        $this->productInStock(['name' => 'Older', 'release_year' => 2015]);
        $this->productInStock(['name' => 'Newer', 'release_year' => 2024]);

        $this->getJson('/api/homepage/blocks/'.$this->block('bestsellers')->id)
            ->assertJsonPath('data.*.name', ['Newer', 'Older']);
    }

    public function test_deals_show_the_biggest_discount_first_and_skip_full_price(): void
    {
        $this->productInStock(['name' => 'Half Off'], 2000, 4000);
        $this->productInStock(['name' => 'Ten Off'], 900, 1000);
        $this->productInStock(['name' => 'Full Price'], 1000);

        $this->getJson('/api/homepage/blocks/'.$this->block('deals')->id)
            ->assertJsonPath('data.*.name', ['Half Off', 'Ten Off']);
    }

    public function test_a_platform_row_shows_its_configured_platform_only(): void
    {
        $this->productInStock(['name' => 'On Steam', 'platform' => Platform::Steam]);
        $this->productInStock(['name' => 'On Xbox', 'platform' => Platform::Xbox]);

        $this->getJson('/api/homepage/blocks/'.$this->block('platform_row', ['platform' => 'Xbox'])->id)
            ->assertJsonPath('data.*.name', ['On Xbox']);
    }

    public function test_a_failing_provider_breaks_only_its_own_block(): void
    {
        $this->productInStock(['name' => 'Still Here']);
        $deals = $this->block('deals', position: 1);
        $newReleases = $this->block('new_releases', position: 2);

        // The deals provider's data source fails.
        $this->app->bind(DealsBlock::class, fn () => new class implements BlockProvider
        {
            public function getData(array $config): array
            {
                throw new RuntimeException('Deals source is down');
            }
        });

        $this->getJson("/api/homepage/blocks/{$deals->id}")->assertServerError();
        $this->getJson('/api/homepage')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/homepage/blocks/{$newReleases->id}")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Still Here');
    }

    public function test_disabled_and_unknown_blocks_are_not_found(): void
    {
        $this->getJson('/api/homepage/blocks/'.$this->block('deals', enabled: false)->id)->assertNotFound();
        $this->getJson('/api/homepage/blocks/'.$this->block('carousel_3d')->id)->assertNotFound();
    }
}

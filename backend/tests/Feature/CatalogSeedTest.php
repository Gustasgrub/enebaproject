<?php

namespace Tests\Feature;

use App\Enums\KeyStatus;
use App\Enums\OrderStatus;
use App\Models\Banner;
use App\Models\HomepageBlock;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The seeded catalog is what production runs on, so its shape and consistency are tested. */
class CatalogSeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seeded inside each test's transaction. (RefreshDatabase's $seed property only seeds when the
        // schema is first migrated in a test run, which another test class may already have done.)
        $this->seed();
    }

    public function test_the_catalog_has_the_planned_size(): void
    {
        $this->assertGreaterThanOrEqual(75, Product::count());
        $this->assertGreaterThanOrEqual(6, Seller::count());

        $offersPerProduct = Product::withCount('offers')->pluck('offers_count');
        $this->assertGreaterThanOrEqual(2, $offersPerProduct->min());
        $this->assertLessThanOrEqual(5, $offersPerProduct->max());
    }

    public function test_deals_are_real_discounts(): void
    {
        $deals = Offer::whereNotNull('original_price_cents')->get();

        $this->assertNotEmpty($deals);
        foreach ($deals as $deal) {
            $this->assertGreaterThan($deal->price_cents, $deal->original_price_cents);
        }
    }

    public function test_every_paid_order_adds_up_and_has_one_sold_key_per_unit(): void
    {
        $orders = Order::with('items.keys')->get();

        $this->assertNotEmpty($orders);
        foreach ($orders as $order) {
            $this->assertSame(OrderStatus::Paid, $order->status);
            $this->assertSame(
                $order->total_cents,
                $order->items->sum(fn ($item) => $item->quantity * $item->unit_price_cents),
            );

            foreach ($order->items as $item) {
                $this->assertCount($item->quantity, $item->keys);
                $this->assertTrue($item->keys->every(fn ($key) => $key->status === KeyStatus::Sold));
            }
        }

        $this->assertTrue(
            Order::where('created_at', '>=', now()->subDays(30))->exists(),
            'The bestsellers block needs orders from the last 30 days.',
        );
    }

    public function test_every_image_the_catalog_points_to_exists(): void
    {
        $paths = Product::pluck('image_path')->merge(Banner::pluck('image_path'))->unique();

        foreach ($paths as $path) {
            $this->assertFileExists(public_path($path), 'Run `php artisan catalog:generate-art` and commit the images.');
        }
    }

    public function test_banner_links_point_to_existing_products(): void
    {
        $productLinks = Banner::where('link_url', 'like', '/product/%')->pluck('link_url');

        $this->assertNotEmpty($productLinks);
        foreach ($productLinks as $link) {
            $this->assertTrue(Product::where('slug', basename($link))->exists(), "No product for {$link}");
        }
    }

    public function test_the_homepage_layout_is_seeded_in_order(): void
    {
        $types = HomepageBlock::where('enabled', true)->orderBy('position')->pluck('type')->all();

        $this->assertSame('banner', $types[0]);
        $this->assertContains('bestsellers', $types);
        $this->assertContains('platform_row', $types);
    }
}

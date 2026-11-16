<?php

namespace Tests\Feature;

use App\Enums\KeyStatus;
use App\Enums\OrderStatus;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buyer = User::factory()->create();
        Sanctum::actingAs($this->buyer);
    }

    private function offerWithKeys(int $priceCents, int $keyCount, string $productName = 'Hades'): Offer
    {
        $offer = Offer::factory()
            ->for(Product::factory()->create(['name' => $productName]))
            ->create(['price_cents' => $priceCents]);
        ProductKey::factory()->count($keyCount)->for($offer)->create();

        return $offer;
    }

    private function checkout(array $items, string $paymentToken = 'tok_success')
    {
        return $this->postJson('/api/checkout', ['items' => $items, 'payment_token' => $paymentToken]);
    }

    public function test_a_successful_payment_sells_the_keys_and_reveals_them(): void
    {
        $hades = $this->offerWithKeys(1299, keyCount: 3);
        $celeste = $this->offerWithKeys(999, keyCount: 1, productName: 'Celeste');

        $response = $this->checkout([
            ['offer_id' => $hades->id, 'quantity' => 2],
            ['offer_id' => $celeste->id, 'quantity' => 1],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'paid')
            // Computed from database prices: 2 × 12.99 + 9.99.
            ->assertJsonPath('data.total_cents', 3597)
            ->assertJsonCount(2, 'data.items.0.keys');

        $order = Order::first();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertStringStartsWith('fake_ch_', $order->payment_reference);
        $this->assertSame(2, $hades->keys()->where('status', KeyStatus::Sold)->count());
        $this->assertSame(1, $hades->availableKeys()->count());

        $soldCodes = ProductKey::where('status', KeyStatus::Sold)->get()->pluck('code')->sort()->values()->all();
        $revealedCodes = collect($response->json('data.items.*.keys'))->flatten()->sort()->values()->all();
        $this->assertSame($soldCodes, $revealedCodes);
    }

    public function test_a_declined_payment_fails_the_order_and_puts_the_keys_back_on_sale(): void
    {
        $offer = $this->offerWithKeys(1299, keyCount: 2);

        $this->checkout([['offer_id' => $offer->id, 'quantity' => 2]], 'tok_declined')
            ->assertStatus(402)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.items.0.keys', []);

        $this->assertSame(OrderStatus::Failed, Order::first()->status);
        $this->assertSame(2, $offer->availableKeys()->count());
        $this->assertSame(0, ProductKey::whereNotNull('order_item_id')->count());
    }

    public function test_not_enough_keys_rejects_the_whole_checkout_without_side_effects(): void
    {
        $enough = $this->offerWithKeys(1299, keyCount: 5);
        $scarce = $this->offerWithKeys(999, keyCount: 1, productName: 'Celeste');

        $this->checkout([
            ['offer_id' => $enough->id, 'quantity' => 1],
            ['offer_id' => $scarce->id, 'quantity' => 2],
        ])
            ->assertStatus(409)
            ->assertJsonPath('offer_id', $scarce->id)
            ->assertJsonFragment(['message' => $this->outOfStockMessageFor($scarce)]);

        $this->assertSame(0, Order::count());
        $this->assertSame(6, ProductKey::where('status', KeyStatus::Available)->count());
    }

    public function test_the_request_is_validated(): void
    {
        $offer = $this->offerWithKeys(1299, keyCount: 1);

        $this->postJson('/api/checkout', ['items' => [], 'payment_token' => 'tok_success'])
            ->assertJsonValidationErrors(['items']);
        $this->checkout([['offer_id' => $offer->id, 'quantity' => 11], ['offer_id' => 999999, 'quantity' => 1]])
            ->assertJsonValidationErrors(['items.0.quantity', 'items.1.offer_id']);
        $this->postJson('/api/checkout', ['items' => [['offer_id' => $offer->id, 'quantity' => 1]]])
            ->assertJsonValidationErrors(['payment_token']);
    }

    public function test_checkout_and_orders_require_a_login(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/checkout', [])->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_buyers_see_only_their_own_orders(): void
    {
        $offer = $this->offerWithKeys(1299, keyCount: 2);
        $this->checkout([['offer_id' => $offer->id, 'quantity' => 1]]);
        $myOrder = Order::first();

        $this->getJson('/api/orders')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/orders/{$myOrder->id}")->assertOk()->assertJsonPath('data.id', $myOrder->id);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/orders')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/orders/{$myOrder->id}")->assertNotFound();
    }

    private function outOfStockMessageFor(Offer $offer): string
    {
        $offer->load('product', 'seller');

        return "Only 1 key is left of Celeste ({$offer->product->platform->value}, {$offer->product->region->value}) from {$offer->seller->name}. Lower the quantity or choose another seller.";
    }
}

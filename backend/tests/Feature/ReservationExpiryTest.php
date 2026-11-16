<?php

namespace Tests\Feature;

use App\Checkout\ExpiredReservations;
use App\Enums\KeyStatus;
use App\Enums\OrderStatus;
use App\Models\Offer;
use App\Models\Order;
use App\Models\ProductKey;
use App\Models\User;
use App\Payments\PaymentProvider;
use App\Payments\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservationExpiryTest extends TestCase
{
    use RefreshDatabase;

    /** A pending order placed $minutesAgo with one reserved key. */
    private function pendingOrder(int $minutesAgo): Order
    {
        $offer = Offer::factory()->create();
        $order = Order::forceCreate([
            'user_id' => User::factory()->create()->id,
            'status' => OrderStatus::Pending,
            'total_cents' => $offer->price_cents,
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
        $item = $order->items()->create(['offer_id' => $offer->id, 'quantity' => 1, 'unit_price_cents' => $offer->price_cents]);
        ProductKey::factory()->for($offer)->create(['status' => KeyStatus::Reserved, 'order_item_id' => $item->id]);

        return $order;
    }

    public function test_unfinished_checkouts_older_than_the_limit_give_their_keys_back(): void
    {
        $abandoned = $this->pendingOrder(minutesAgo: 20);
        $inProgress = $this->pendingOrder(minutesAgo: 2);

        $this->artisan('checkout:release-expired')->assertSuccessful();

        $this->assertSame(OrderStatus::Failed, $abandoned->fresh()->status);
        $this->assertSame(KeyStatus::Available, ProductKey::where('offer_id', $abandoned->items()->first()->offer_id)->first()->status);

        $this->assertSame(OrderStatus::Pending, $inProgress->fresh()->status);
        $this->assertSame(1, ProductKey::where('status', KeyStatus::Reserved)->count());
    }

    public function test_a_payment_that_finishes_after_its_reservation_expired_does_not_sell_the_keys(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $offer = Offer::factory()->create();
        $key = ProductKey::factory()->for($offer)->create();

        // A payment so slow that the reservation expires (and is released) while it runs.
        $this->app->bind(PaymentProvider::class, fn () => new class($this) implements PaymentProvider
        {
            public function __construct(private TestCase $test) {}

            public function charge(int $amountCents, string $paymentToken): PaymentResult
            {
                $this->test->travel(ExpiredReservations::EXPIRE_AFTER_MINUTES + 1)->minutes();
                app(ExpiredReservations::class)->release();

                return PaymentResult::succeeded('fake_ch_late');
            }
        });

        $this->postJson('/api/checkout', ['items' => [['offer_id' => $offer->id, 'quantity' => 1]], 'payment_token' => 'tok_success'])
            ->assertStatus(402);

        $this->assertSame(OrderStatus::Failed, Order::first()->status);
        $this->assertSame(KeyStatus::Available, $key->fresh()->status);
    }
}

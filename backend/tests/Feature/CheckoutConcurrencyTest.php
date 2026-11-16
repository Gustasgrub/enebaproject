<?php

namespace Tests\Feature;

use App\Enums\KeyStatus;
use App\Models\Offer;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Two buyers at the same moment, simulated with a second database connection that holds a row lock on
 * a key, exactly as another checkout does between "reserve" and the end of its transaction.
 *
 * DatabaseTruncation instead of RefreshDatabase: RefreshDatabase wraps each test in a transaction, so
 * rows created by the test would be invisible to the second connection.
 */
class CheckoutConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    private ConnectionInterface $otherBuyer;

    protected function setUp(): void
    {
        parent::setUp();

        // A second, independent session to the same database: "the other buyer's checkout".
        config(['database.connections.other_buyer' => config('database.connections.pgsql')]);
        $this->otherBuyer = DB::connection('other_buyer');

        // If checkout ever waited on a locked key instead of skipping it, fail after 2 s instead of hanging.
        DB::statement("SET lock_timeout = '2s'");

        Sanctum::actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        $this->otherBuyer->rollBack();
        DB::purge('other_buyer');
        // These tests commit their rows, so the next RefreshDatabase test class must start from a fresh schema.
        RefreshDatabaseState::$migrated = false;

        parent::tearDown();
    }

    /** The other buyer's checkout has locked these keys and has not committed yet. */
    private function otherBuyerLocks(ProductKey ...$keys): void
    {
        $this->otherBuyer->beginTransaction();
        $this->otherBuyer->select(
            'SELECT id FROM product_keys WHERE id = ANY(?::bigint[]) FOR UPDATE',
            ['{'.implode(',', array_map(fn ($key) => $key->id, $keys)).'}'],
        );
    }

    public function test_a_key_locked_by_another_checkout_counts_as_out_of_stock_without_waiting(): void
    {
        $offer = Offer::factory()->create();
        $onlyKey = ProductKey::factory()->for($offer)->create();
        $this->otherBuyerLocks($onlyKey);

        $startedAt = microtime(true);
        $response = $this->postJson('/api/checkout', [
            'items' => [['offer_id' => $offer->id, 'quantity' => 1]],
            'payment_token' => 'tok_success',
        ]);

        $response->assertStatus(409);
        $this->assertLessThan(1.5, microtime(true) - $startedAt, 'Checkout waited for the lock instead of skipping it.');
        $this->assertSame(KeyStatus::Available, $onlyKey->fresh()->status);
    }

    public function test_two_buyers_of_the_last_two_keys_get_different_keys(): void
    {
        $offer = Offer::factory()->create();
        [$firstKey, $secondKey] = ProductKey::factory()->count(2)->for($offer)->create()->all();
        // The other buyer holds the first key (the one an ORDER BY id would pick first).
        $this->otherBuyerLocks($firstKey);

        $this->postJson('/api/checkout', [
            'items' => [['offer_id' => $offer->id, 'quantity' => 1]],
            'payment_token' => 'tok_success',
        ])->assertCreated();

        // This buyer got the second key, untouched by the other checkout's lock.
        $this->assertSame(KeyStatus::Sold, $secondKey->fresh()->status);
        $this->assertSame(KeyStatus::Available, $firstKey->fresh()->status);
    }
}

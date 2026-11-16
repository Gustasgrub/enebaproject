<?php

namespace Database\Seeders;

use App\Enums\KeyStatus;
use App\Enums\OrderStatus;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Past paid orders from the last 60 days, so the bestsellers block (most sold in the last 30 days)
 * has real sales to rank. Each order is complete: its items have exactly `quantity` sold keys each.
 */
class OrderHistorySeeder extends Seeder
{
    private const ORDER_COUNT = 60;

    public function run(): void
    {
        $random = new Randomizer(new Mt19937(2028));
        $customers = $this->createCustomers();

        // Shuffled once; buyers then favour the start of this list (see pickPopularProduct), so some
        // products sell much more than others, as real bestsellers do.
        $products = $random->shuffleArray(Product::with('offers')->get()->all());

        for ($orderNumber = 1; $orderNumber <= self::ORDER_COUNT; $orderNumber++) {
            $placedAt = Carbon::now()
                ->subDays($random->getInt(0, 59))
                ->subMinutes($random->getInt(0, 24 * 60 - 1));

            $this->createPaidOrder(
                customer: $customers[$random->getInt(0, count($customers) - 1)],
                offers: $this->pickOffers($products, $random),
                random: $random,
                reference: sprintf('seed_%04d', $orderNumber),
                placedAt: $placedAt,
            );
        }
    }

    /** @return list<User> */
    private function createCustomers(): array
    {
        // These accounts only exist to own past orders; nobody knows the password. Hashed once and
        // reused, because hashing is deliberately slow and the free server has a tenth of a CPU.
        $unusablePassword = Hash::make(Str::random(40));

        $customers = [];
        for ($n = 1; $n <= 8; $n++) {
            $customers[] = User::create([
                'name' => "Customer {$n}",
                'email' => "customer{$n}@example.com",
                'password' => $unusablePassword,
            ]);
        }

        return $customers;
    }

    /**
     * One or two different products per order. Buyers usually take the cheapest offer.
     *
     * @param  list<Product>  $products
     * @return list<Offer>
     */
    private function pickOffers(array $products, Randomizer $random): array
    {
        $offers = [];
        $itemCount = $random->getInt(1, 2);

        while (count($offers) < $itemCount) {
            $cheapestOffer = $this->pickPopularProduct($products, $random)->offers->sortBy('price_cents')->first();
            $offers[$cheapestOffer->id] = $cheapestOffer;
        }

        return array_values($offers);
    }

    /** @param  list<Product>  $products */
    private function pickPopularProduct(array $products, Randomizer $random): Product
    {
        // Squaring a number between 0 and 1 pushes it towards 0, so products near the start of the list
        // are picked far more often than those at the end.
        $position = (int) floor(count($products) * $random->nextFloat() ** 2);

        return $products[$position];
    }

    /** @param  list<Offer>  $offers */
    private function createPaidOrder(User $customer, array $offers, Randomizer $random, string $reference, Carbon $placedAt): void
    {
        $timestamps = ['created_at' => $placedAt, 'updated_at' => $placedAt];

        $lines = array_map(fn (Offer $offer) => [
            'offer' => $offer,
            'quantity' => $random->getInt(1, 5) === 1 ? 2 : 1,
        ], $offers);

        // forceCreate: the past timestamps are not fillable attributes, but the history needs them.
        $order = $customer->orders()->forceCreate([
            'status' => OrderStatus::Paid,
            'total_cents' => array_sum(array_map(fn ($line) => $line['offer']->price_cents * $line['quantity'], $lines)),
            'payment_reference' => $reference,
            ...$timestamps,
        ]);

        foreach ($lines as $line) {
            $orderItem = $order->items()->forceCreate([
                'offer_id' => $line['offer']->id,
                'quantity' => $line['quantity'],
                'unit_price_cents' => $line['offer']->price_cents,
                ...$timestamps,
            ]);

            // The keys that were delivered: sold, and linked to this order item.
            for ($i = 0; $i < $line['quantity']; $i++) {
                $orderItem->keys()->create([
                    'offer_id' => $line['offer']->id,
                    'code' => ProductSeeder::randomKeyCode(),
                    'status' => KeyStatus::Sold,
                ]);
            }
        }
    }
}

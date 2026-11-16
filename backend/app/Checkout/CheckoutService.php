<?php

namespace App\Checkout;

use App\Enums\KeyStatus;
use App\Enums\OrderStatus;
use App\Models\Offer;
use App\Models\Order;
use App\Models\ProductKey;
use App\Models\User;
use App\Payments\PaymentProvider;
use App\Payments\PaymentResult;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Buys the items of a cart in three steps, so no row lock is held while the payment runs
 * (PLAN.md, Section 8; docs/DECISIONS.md #7 and #8):
 *
 *   1. reserve  (transaction): read prices from the database, take keys, create a pending order
 *   2. pay      (no transaction): charge the payment token
 *   3. confirm  (transaction): mark the keys sold and the order paid, or release the keys
 */
class CheckoutService
{
    public function __construct(private readonly PaymentProvider $payments) {}

    /**
     * @param  list<array{offer_id: int, quantity: int}>  $items
     *
     * @throws OutOfStockException when an offer has fewer available keys than requested
     */
    public function checkout(User $buyer, array $items, string $paymentToken): Order
    {
        $order = $this->reserve($buyer, $items);

        try {
            $payment = $this->payments->charge($order->total_cents, $paymentToken);
        } catch (Throwable $exception) {
            // The provider failed (e.g. timed out): treat it as not paid, give the keys back, and log it.
            report($exception);
            $payment = PaymentResult::declined('The payment could not be processed. Please try again.');
        }

        return $this->confirm($order, $payment);
    }

    /** @param  list<array{offer_id: int, quantity: int}>  $items */
    private function reserve(User $buyer, array $items): Order
    {
        return DB::transaction(function () use ($buyer, $items) {
            // Prices come from the database, never from the request: the browser's cart is not trusted.
            $offers = Offer::with(['product', 'seller'])
                ->whereIn('id', array_column($items, 'offer_id'))
                ->get()
                ->keyBy('id');

            $order = $buyer->orders()->create([
                'status' => OrderStatus::Pending,
                'total_cents' => 0,
            ]);
            $totalCents = 0;

            foreach ($items as $item) {
                $offer = $offers[$item['offer_id']];

                // FOR UPDATE locks the chosen keys until this transaction ends; SKIP LOCKED passes over
                // keys another buyer's checkout has locked instead of waiting for them. So concurrent
                // buyers get different keys, and nobody sees a false "out of stock" while waiting
                // on a key that someone else then takes (docs/DECISIONS.md #7).
                $keyIds = ProductKey::query()
                    ->where('offer_id', $offer->id)
                    ->where('status', KeyStatus::Available)
                    ->orderBy('id')
                    ->limit($item['quantity'])
                    ->lock('FOR UPDATE SKIP LOCKED')
                    ->pluck('id');

                if ($keyIds->count() < $item['quantity']) {
                    // Thrown inside the transaction: the order created above is rolled back too.
                    throw new OutOfStockException($offer, $keyIds->count());
                }

                $orderItem = $order->items()->create([
                    'offer_id' => $offer->id,
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $offer->price_cents,
                ]);

                ProductKey::whereIn('id', $keyIds)->update([
                    'status' => KeyStatus::Reserved,
                    'order_item_id' => $orderItem->id,
                ]);

                $totalCents += $offer->price_cents * $item['quantity'];
            }

            $order->update(['total_cents' => $totalCents]);

            return $order;
        });
    }

    private function confirm(Order $order, PaymentResult $payment): Order
    {
        DB::transaction(function () use ($order, $payment) {
            $reservedKeys = ProductKey::whereIn('order_item_id', $order->items()->select('id'))
                ->where('status', KeyStatus::Reserved);

            if ($payment->succeeded) {
                $reservedKeys->update(['status' => KeyStatus::Sold]);
                $order->update(['status' => OrderStatus::Paid, 'payment_reference' => $payment->reference]);
            } else {
                // Back in stock, linked to nothing (the database's check constraint insists on both).
                $reservedKeys->update(['status' => KeyStatus::Available, 'order_item_id' => null]);
                $order->update(['status' => OrderStatus::Failed]);
            }
        });

        return $order;
    }
}

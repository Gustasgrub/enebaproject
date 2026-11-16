<?php

namespace App\Checkout;

use App\Enums\KeyStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductKey;
use Illuminate\Support\Facades\DB;

/**
 * Gives back keys that a checkout reserved but never confirmed, for example because the server
 * stopped between "reserve" and "confirm". Runs every minute (routes/console.php).
 */
class ExpiredReservations
{
    /** Far longer than a payment takes, so a checkout that is still running is never released. */
    public const EXPIRE_AFTER_MINUTES = 15;

    /** @return int how many orders were released */
    public function release(): int
    {
        return DB::transaction(function () {
            // SKIP LOCKED: an order whose checkout is confirming right now is locked by it; leave it be.
            $orderIds = Order::query()
                ->where('status', OrderStatus::Pending)
                ->where('created_at', '<', now()->subMinutes(self::EXPIRE_AFTER_MINUTES))
                ->lock('FOR UPDATE SKIP LOCKED')
                ->pluck('id');

            if ($orderIds->isEmpty()) {
                return 0;
            }

            ProductKey::query()
                ->whereIn('order_item_id', OrderItem::whereIn('order_id', $orderIds)->select('id'))
                ->where('status', KeyStatus::Reserved)
                ->update(['status' => KeyStatus::Available, 'order_item_id' => null]);

            Order::whereIn('id', $orderIds)->update(['status' => OrderStatus::Failed]);

            return $orderIds->count();
        });
    }
}

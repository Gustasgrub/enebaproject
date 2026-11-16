<?php

namespace App\Homepage\Blocks;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * `bestsellers`: the products with the most units sold in paid orders of the last 30 days. When fewer
 * than a full row sold, the row is filled up with the newest products, so it never looks empty.
 */
class BestsellersBlock implements BlockProvider
{
    public const PERIOD_DAYS = 30;

    public function getData(array $config): array
    {
        $unitsSoldPerProduct = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('offers', 'offers.id', '=', 'order_items.offer_id')
            ->where('orders.status', OrderStatus::Paid)
            ->where('orders.created_at', '>=', now()->subDays(self::PERIOD_DAYS))
            ->groupBy('offers.product_id')
            ->select('offers.product_id', DB::raw('sum(order_items.quantity) as units_sold'));

        $bestsellers = ProductRow::productsInStock()
            ->joinSub($unitsSoldPerProduct, 'sales', 'sales.product_id', '=', 'products.id')
            ->orderByDesc('sales.units_sold')
            ->orderBy('products.id')
            ->limit(ProductRow::LIMIT)
            ->get();

        $missing = ProductRow::LIMIT - $bestsellers->count();
        if ($missing > 0) {
            $newest = ProductRow::productsInStock()
                ->whereNotIn('products.id', $bestsellers->pluck('id'))
                ->orderByDesc('release_year')
                ->orderBy('products.id')
                ->limit($missing)
                ->get();

            $bestsellers = $bestsellers->concat($newest);
        }

        return ProductRow::toItems($bestsellers);
    }
}

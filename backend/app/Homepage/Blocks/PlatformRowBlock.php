<?php

namespace App\Homepage\Blocks;

use App\Enums\Platform;

/** `platform_row`: the newest products of one platform, set in the block's config: {"platform": "Steam"}. */
class PlatformRowBlock implements BlockProvider
{
    public function getData(array $config): array
    {
        // A misconfigured block (missing or unknown platform) throws here, and only this block fails.
        $platform = Platform::from($config['platform'] ?? '');

        $products = ProductRow::productsInStock()
            ->where('platform', $platform)
            ->orderByDesc('release_year')
            ->orderBy('products.id')
            ->limit(ProductRow::LIMIT)
            ->get();

        return ProductRow::toItems($products);
    }
}

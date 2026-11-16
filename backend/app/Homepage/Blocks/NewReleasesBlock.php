<?php

namespace App\Homepage\Blocks;

/** `new_releases`: the most recently released products, newest added first within a year. */
class NewReleasesBlock implements BlockProvider
{
    public function getData(array $config): array
    {
        $products = ProductRow::productsInStock()
            ->orderByDesc('release_year')
            ->orderByDesc('products.created_at')
            ->orderBy('products.id')
            ->limit(ProductRow::LIMIT)
            ->get();

        return ProductRow::toItems($products);
    }
}

<?php

namespace App\Homepage\Blocks;

/**
 * Supplies the data of one homepage block type. Each provider reads only its own source (the banners
 * table, order history, offers, products), so one failing source affects only its own block.
 */
interface BlockProvider
{
    /**
     * @param  array<string, mixed>  $config  the block's settings from homepage_blocks.config
     * @return list<array<string, mixed>> the block's items, ready to be sent as JSON
     */
    public function getData(array $config): array;
}

<?php

namespace App\Homepage;

use App\Homepage\Blocks\BannerBlock;
use App\Homepage\Blocks\BestsellersBlock;
use App\Homepage\Blocks\BlockProvider;
use App\Homepage\Blocks\DealsBlock;
use App\Homepage\Blocks\NewReleasesBlock;
use App\Homepage\Blocks\PlatformRowBlock;
use Illuminate\Contracts\Container\Container;

/**
 * Which provider supplies each homepage block type. A new block of an existing type needs only a
 * homepage_blocks row; a new type needs a provider here and a component in the frontend's map.
 */
class BlockRegistry
{
    /** @var array<string, class-string<BlockProvider>> */
    private const PROVIDERS = [
        'banner' => BannerBlock::class,
        'bestsellers' => BestsellersBlock::class,
        'deals' => DealsBlock::class,
        'new_releases' => NewReleasesBlock::class,
        'platform_row' => PlatformRowBlock::class,
    ];

    public function __construct(private readonly Container $container) {}

    /** @return list<string> every block type that has a provider */
    public function types(): array
    {
        return array_keys(self::PROVIDERS);
    }

    /** Null for a type without a provider. Resolved through the container, so tests can swap one. */
    public function providerFor(string $type): ?BlockProvider
    {
        $providerClass = self::PROVIDERS[$type] ?? null;

        return $providerClass === null ? null : $this->container->make($providerClass);
    }
}

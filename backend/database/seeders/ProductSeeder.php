<?php

namespace Database\Seeders;

use App\Catalog\PlaceholderArt;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/** Products from data/games.php, each with 2–5 sellers' offers and a stock of keys per offer. */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // A fixed seed gives the same prices and stock on every run, locally and in production.
        $random = new Randomizer(new Mt19937(2027));
        $sellers = Seller::all();

        foreach (require __DIR__.'/data/games.php' as $game) {
            foreach ($game['editions'] as [$platform, $region]) {
                $product = Product::create([
                    'slug' => Str::slug("{$game['name']} {$platform->value} {$region->value}"),
                    'name' => $game['name'],
                    'description' => $game['description'],
                    'image_path' => PlaceholderArt::coverPath($game['name']),
                    'platform' => $platform,
                    'region' => $region,
                    'genre' => $game['genre'],
                    'release_year' => $game['release_year'],
                    'search_keywords' => $game['keywords'],
                ]);

                $this->createOffers($product, $game['price_cents'], $sellers, $random);
            }
        }
    }

    /** A key code in the usual XXXXX-XXXXX-XXXXX format. Shared with ProductKeyFactory. */
    public static function randomKeyCode(): string
    {
        return Str::upper(Str::random(5).'-'.Str::random(5).'-'.Str::random(5));
    }

    /** @param  Collection<int, Seller>  $sellers */
    private function createOffers(Product $product, int $retailPriceCents, Collection $sellers, Randomizer $random): void
    {
        $sellerIndexes = $random->pickArrayKeys($sellers->all(), $random->getInt(2, 5));

        foreach ($sellerIndexes as $sellerIndex) {
            // Key resellers sell below the retail price: here between 45% and 100% of it.
            $priceCents = (int) round($retailPriceCents * $random->getInt(45, 100) / 100);

            // About a third of the offers that are at least 20% cheaper are advertised as deals, with the
            // retail price shown struck through.
            $isDeal = $priceCents <= $retailPriceCents * 0.8 && $random->getInt(1, 3) === 1;

            $offer = $product->offers()->create([
                'seller_id' => $sellers[$sellerIndex]->id,
                'price_cents' => $priceCents,
                'original_price_cents' => $isDeal ? $retailPriceCents : null,
            ]);

            // 0–9 keys, so roughly one offer in ten is out of stock.
            $this->createAvailableKeys($offer, $random->getInt(0, 9));
        }
    }

    private function createAvailableKeys(Offer $offer, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            // Created through the model so the `encrypted` cast encrypts the code.
            $offer->keys()->create(['code' => self::randomKeyCode()]);
        }
    }
}

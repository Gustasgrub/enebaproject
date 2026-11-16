<?php

namespace Tests\Feature;

use App\Enums\KeyStatus;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductKey;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_product_writes_its_normalized_search_text(): void
    {
        $product = Product::factory()->create(['name' => 'Pokémon Scarlet', 'search_keywords' => 'PKMN']);
        $this->assertSame('pokemon scarlet pkmn', $product->search_text);

        $product->update(['name' => 'Pokémon Violet']);
        $this->assertSame('pokemon violet pkmn', $product->fresh()->search_text);
    }

    public function test_key_codes_are_encrypted_in_the_database(): void
    {
        $key = ProductKey::factory()->create(['code' => 'ABCDE-12345-FGHIJ']);

        $storedCode = DB::table('product_keys')->where('id', $key->id)->value('code');

        $this->assertStringNotContainsString('ABCDE', $storedCode);
        $this->assertSame('ABCDE-12345-FGHIJ', $key->fresh()->code);
    }

    public function test_key_codes_are_hidden_from_json(): void
    {
        $key = ProductKey::factory()->create();

        $this->assertArrayNotHasKey('code', $key->toArray());
    }

    public function test_offer_stock_counts_only_available_keys(): void
    {
        $offer = Offer::factory()->create();
        ProductKey::factory()->count(3)->for($offer)->create();

        $this->assertSame(3, Offer::withCount('availableKeys')->find($offer->id)->available_keys_count);
    }

    public function test_the_database_rejects_a_sold_key_without_an_order_item(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('product_keys_order_link_matches_status');

        ProductKey::factory()->create(['status' => KeyStatus::Sold, 'order_item_id' => null]);
    }
}

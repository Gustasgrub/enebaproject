<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesSeededCatalog;
use Tests\TestCase;

/**
 * Search against the real seeded catalog on real Postgres: these are the cases the thresholds in
 * App\Search\SearchService are tuned against (PLAN.md, Section 6).
 */
class SearchTest extends TestCase
{
    use UsesSeededCatalog;

    /** @return array<string, array{string, string}> query → expected first result */
    public static function queries(): array
    {
        return [
            'exact name' => ['Elden Ring', 'Elden Ring'],
            'lower case' => ['hogwarts legacy', 'Hogwarts Legacy'],
            'partial name' => ['cyber', 'Cyberpunk 2077'],
            'partial word in the middle' => ['tsushima', 'Ghost of Tsushima'],
            'swapped letters' => ['wticher', 'The Witcher 3: Wild Hunt'],
            'missing letter' => ['skyrm', 'The Elder Scrolls V: Skyrim Special Edition'],
            'wrong letter' => ['hogwarts legasy', 'Hogwarts Legacy'],
            'two typos' => ['battlefeild', 'Battlefield 2042'],
            'typo in the second word' => ['elden rnig', 'Elden Ring'],
            'words in another order' => ['wild hunt witcher', 'The Witcher 3: Wild Hunt'],
            'extra words' => ['elden ring game', 'Elden Ring'],
            'abbreviation gta' => ['gta', 'Grand Theft Auto V'],
            'abbreviation rdr2' => ['rdr2', 'Red Dead Redemption 2'],
            'abbreviation bg3' => ['bg3', "Baldur's Gate 3"],
            'abbreviation cod' => ['cod', 'Call of Duty: Black Ops 6'],
            'typed without accent' => ['pokemon', 'Pokémon Scarlet'],
            'typed with accent' => ['ragnarök', 'God of War Ragnarök'],
            'without apostrophe' => ['baldurs gate', "Baldur's Gate 3"],
            'punctuation ignored' => ['nier automata', 'NieR:Automata'],
            'shorter name first' => ['god of war', 'God of War'],
        ];
    }

    #[DataProvider('queries')]
    public function test_finds_the_expected_game_first(string $query, string $expectedFirstName): void
    {
        $response = $this->getJson('/api/products?'.http_build_query(['q' => $query]));

        $response->assertOk()->assertJsonPath('data.0.name', $expectedFirstName);
    }

    /** @return array<string, array{string}> */
    public static function queriesWithoutResults(): array
    {
        return [
            'a game we do not sell' => ['minecraft'],
            'unrelated but similar letters' => ['fortnite'],
            'one character' => ['x'],
            'only punctuation' => ['!!!'],
        ];
    }

    #[DataProvider('queriesWithoutResults')]
    public function test_finds_nothing_for_unrelated_queries(string $query): void
    {
        $this->getJson('/api/products?'.http_build_query(['q' => $query]))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_suggests_a_close_name_when_nothing_matches(): void
    {
        // "fortnite" is not sold, but shares enough letters with a title to be worth suggesting.
        $this->getJson('/api/products?q=fortnite')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('did_you_mean', 'Forza Horizon 5');
    }

    public function test_offers_no_suggestion_when_nothing_is_close(): void
    {
        $this->getJson('/api/products?q=minecraft')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('did_you_mean', null);
    }

    public function test_suggestions_return_at_most_eight_best_matches(): void
    {
        $this->getJson('/api/search/suggest?q=wticher')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'The Witcher 3: Wild Hunt');

        // "a" alone is too short, but "ar" appears in many titles: still no more than eight.
        $this->assertLessThanOrEqual(8, count($this->getJson('/api/search/suggest?q=ar')->json('data')));
        $this->assertSame([], $this->getJson('/api/search/suggest?q=')->json('data'));
    }

    public function test_search_combines_with_filters(): void
    {
        $response = $this->getJson('/api/products?'.http_build_query(['q' => 'witcher', 'platform' => ['Xbox']]));

        $response->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.platform', 'Xbox');
    }

    public function test_filter_counts_follow_the_search(): void
    {
        $platforms = collect($this->getJson('/api/filters?q=zelda')->json('data.platforms'))->pluck('count', 'value');

        $this->assertSame(3, $platforms['Nintendo']);
        $this->assertSame(0, $platforms['Steam']);
    }
}

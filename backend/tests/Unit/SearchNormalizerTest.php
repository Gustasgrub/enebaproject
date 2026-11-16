<?php

namespace Tests\Unit;

use App\Search\SearchNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SearchNormalizerTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function examples(): array
    {
        return [
            'lowercases' => ['ELDEN Ring', 'elden ring'],
            'removes accents' => ['Pokémon Ragnarök', 'pokemon ragnarok'],
            'drops apostrophes inside words' => ["Baldur's Gate 3", 'baldurs gate 3'],
            'turns punctuation into spaces' => ['NieR:Automata', 'nier automata'],
            'collapses and trims spaces' => ["  Call  of\tDuty:  MW3 ", 'call of duty mw3'],
            'keeps digits' => ['Cyberpunk 2077', 'cyberpunk 2077'],
            'empty stays empty' => ['', ''],
        ];
    }

    #[DataProvider('examples')]
    public function test_normalizes_text_for_search(string $input, string $expected): void
    {
        $this->assertSame($expected, SearchNormalizer::normalize($input));
    }
}

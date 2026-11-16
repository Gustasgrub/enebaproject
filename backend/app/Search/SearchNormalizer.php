<?php

namespace App\Search;

use Illuminate\Support\Str;

/**
 * Turns text into the form search compares: "Pokémon: Scarlet!" → "pokemon scarlet".
 *
 * It is applied both to the stored products.search_text and to every search query, so the two sides
 * always match (docs/DECISIONS.md #4).
 */
class SearchNormalizer
{
    public static function normalize(string $text): string
    {
        // Accented letters become plain ASCII ("é" → "e", "ö" → "o"), so typing without accents matches.
        $text = Str::lower(Str::ascii($text));

        // Apostrophes are dropped rather than turned into spaces, so "baldurs" matches "Baldur's".
        $text = str_replace("'", '', $text);

        // Every other non-alphanumeric character separates words.
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);

        return trim($text);
    }
}

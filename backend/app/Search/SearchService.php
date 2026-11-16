<?php

namespace App\Search;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Typo-tolerant product search with Postgres pg_trgm (PLAN.md, Section 6).
 *
 * It adds conditions to an existing product query instead of returning a finished list, so the store's
 * filters, sorting and pagination run in the same SQL query as the search.
 *
 * Both sides are normalized the same way (SearchNormalizer): the stored search_text and the query. The
 * normalizer leaves only a–z, 0–9 and single spaces, so the LIKE patterns below need no escaping:
 * `%` and `_` cannot reach them.
 */
class SearchService
{
    /**
     * How close a query must be to some part of a product's search text, from 0 (nothing in common) to 1
     * (contained exactly). Tuned against the query table in tests/Feature/SearchTest.php: on this
     * catalog every real typo tried scores 0.38 or more ("withcer" 0.38, "wticher" 0.40), while games we
     * do not sell score 0.33 or less against the closest title ("fortnite" → Forza Horizon 5). The
     * plan's starting value, 0.3, let those through.
     */
    public const MATCH_THRESHOLD = 0.35;

    /**
     * The lower bar for a "Did you mean …?" suggestion, offered only when nothing matched. Below it
     * ("minecraft" scores 0.20 at best) a suggestion would be noise.
     */
    public const SUGGESTION_THRESHOLD = 0.25;

    /** Queries shorter than this match nothing: one letter would match almost every product. */
    public const MIN_QUERY_LENGTH = 2;

    /**
     * Keeps only products that match the search text, by substring (partial names) or by word
     * similarity (typos, word order, extra words).
     *
     * @param  Builder<Product>  $query
     */
    public function whereMatches(Builder $query, string $searchText): void
    {
        $normalized = SearchNormalizer::normalize($searchText);

        if (mb_strlen($normalized) < self::MIN_QUERY_LENGTH) {
            $query->whereRaw('false');

            return;
        }

        // The `<%` operator compares against this setting. Unlike calling word_similarity() in the WHERE
        // clause, the operator can use the trigram index. It is set per request: in classic mode every
        // request has its own database session.
        DB::statement("SELECT set_config('pg_trgm.word_similarity_threshold', ?, false)", [(string) self::MATCH_THRESHOLD]);

        $query->where(function (Builder $query) use ($normalized) {
            $query->whereRaw('products.search_text LIKE ?', ["%{$normalized}%"])
                ->orWhereRaw('? <% products.search_text', [$normalized]);
        });
    }

    /**
     * Best matches first: names that start with the query, then texts that contain it, then the rest by
     * how similar they are. Among equals the shorter name wins, so "God of War" comes before
     * "God of War Ragnarök" for the query "god of war".
     *
     * @param  Builder<Product>  $query
     */
    public function orderByRelevance(Builder $query, string $searchText): void
    {
        $normalized = SearchNormalizer::normalize($searchText);

        $query
            ->orderByRaw(
                'CASE WHEN products.search_text LIKE ? THEN 0 WHEN products.search_text LIKE ? THEN 1 ELSE 2 END',
                ["{$normalized}%", "%{$normalized}%"],
            )
            ->orderByRaw('word_similarity(?, products.search_text) DESC', [$normalized])
            ->orderByRaw('char_length(products.name)');
    }

    /** The name of the product closest to a query that matched nothing, for "Did you mean …?". */
    public function closestProductName(string $searchText): ?string
    {
        $normalized = SearchNormalizer::normalize($searchText);

        if (mb_strlen($normalized) < self::MIN_QUERY_LENGTH) {
            return null;
        }

        return Product::query()
            ->whereRaw('word_similarity(?, search_text) >= ?', [$normalized, self::SUGGESTION_THRESHOLD])
            ->orderByRaw('word_similarity(?, search_text) DESC', [$normalized])
            ->value('name');
    }
}

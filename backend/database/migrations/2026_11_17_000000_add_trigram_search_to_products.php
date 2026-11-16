<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // pg_trgm compares texts by their three-letter pieces (trigrams), which is what makes typo-tolerant
        // search possible inside Postgres (docs/DECISIONS.md #21).
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Serves both search conditions: LIKE '%text%' and the word-similarity operator <%. With ~90
        // products Postgres scans the table anyway; the index keeps the same query fast as the catalog grows.
        DB::statement('CREATE INDEX products_search_text_trgm_index ON products USING gin (search_text gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_search_text_trgm_index');
    }
};

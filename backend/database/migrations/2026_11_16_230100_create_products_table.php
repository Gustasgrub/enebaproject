<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A product is one game on one platform in one region ("Elden Ring, Steam, Global"), as on Eneba:
        // keys only activate where they were issued, so each combination is sold separately.
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            // Path inside the API's public/ folder; the API turns it into a full URL in its responses.
            $table->string('image_path');
            // Check constraints keep the values in line with the App\Enums\Platform and Region enums.
            $table->enum('platform', ['Steam', 'PlayStation', 'Xbox', 'Nintendo', 'EA App', 'Ubisoft Connect']);
            $table->enum('region', ['Global', 'Europe', 'North America']);
            $table->string('genre');
            $table->smallInteger('release_year');
            // Extra words search should match, mostly abbreviations ("gta", "rdr2"). Never null, so
            // search_text below is never null either.
            $table->string('search_keywords')->default('');
            // name + search_keywords passed through App\Search\SearchNormalizer, written by the Product
            // model on every save. This is the column search compares against.
            $table->text('search_text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

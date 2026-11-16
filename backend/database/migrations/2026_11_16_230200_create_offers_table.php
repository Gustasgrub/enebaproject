<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One seller's listing of one product. Its stock is the number of its available keys, so there
        // is no stock column that could disagree with the keys.
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('seller_id')->index()->constrained();
            // Money is integer euro cents, never floats.
            $table->integer('price_cents');
            // Set for deals: the price before the discount, shown struck through.
            $table->integer('original_price_cents')->nullable();
            $table->timestamps();

            // A seller lists a product once. This index also serves lookups by product_id.
            $table->unique(['product_id', 'seller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};

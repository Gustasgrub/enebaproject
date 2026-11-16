<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained();
            // Encrypted with APP_KEY by the model's `encrypted` cast, so it is unreadable in the database.
            $table->text('code');
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            // The link between keys and orders is stored only here (docs/DECISIONS.md #10).
            $table->foreignId('order_item_id')->nullable()->index()->constrained();
            $table->timestamps();

            // Stock counts and checkout both look for an offer's available keys.
            $table->index(['offer_id', 'status']);
        });

        // A key belongs to an order item exactly when it is not available: reserved and sold keys are
        // linked to their order, available keys to nothing. Checkout relies on this, so the database
        // enforces it instead of trusting every code path to get it right.
        DB::statement(<<<'SQL'
            ALTER TABLE product_keys
            ADD CONSTRAINT product_keys_order_link_matches_status
            CHECK ((status = 'available') = (order_item_id IS NULL))
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_keys');
    }
};

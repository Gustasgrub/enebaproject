<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->index()->constrained();
            $table->foreignId('offer_id')->index()->constrained();
            // The item's keys point here (product_keys.order_item_id): quantity 3 means 3 keys.
            $table->smallInteger('quantity');
            // The price at the time of purchase; the offer's price can change later.
            $table->integer('unit_price_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

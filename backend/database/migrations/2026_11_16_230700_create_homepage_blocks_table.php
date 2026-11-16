<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The homepage layout: which blocks to show, in what order (PLAN.md, Section 7).
        Schema::create('homepage_blocks', function (Blueprint $table) {
            $table->id();
            // Which provider supplies the block's data, e.g. "deals" or "platform_row". A plain string,
            // not an enum: the frontend skips types it does not know, so new types can be added freely.
            $table->string('type');
            $table->string('title');
            $table->smallInteger('position');
            $table->boolean('enabled')->default(true);
            // Settings for the provider, e.g. {"platform": "Steam"} for a platform_row.
            $table->jsonb('config')->default('{}');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_blocks');
    }
};

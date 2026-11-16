<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Slides of the homepage hero carousel (the `banner` homepage block reads this table).
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle');
            // Path inside the API's public/ folder, like products.image_path.
            $table->string('image_path');
            // A path inside the frontend, e.g. "/store?platform[]=Nintendo".
            $table->string('link_url');
            $table->smallInteger('position');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};

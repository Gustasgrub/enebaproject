<?php

namespace Database\Seeders;

use App\Catalog\PlaceholderArt;
use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/banners.php' as $index => $banner) {
            Banner::create([
                ...$banner,
                'image_path' => PlaceholderArt::bannerPath($banner['title']),
                'position' => $index + 1,
            ]);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Catalog\PlaceholderArt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Writes the placeholder covers and banner art for the seeded catalog into public/. A development
 * tool: run it after changing database/seeders/data/, then commit the images. The images are served
 * as static files, so the server never spends a PHP request on one (docs/DECISIONS.md #19).
 */
#[Signature('catalog:generate-art')]
#[Description('Generate placeholder cover and banner images for the seeded catalog')]
class GeneratePlaceholderArt extends Command
{
    public function handle(): void
    {
        $images = [];

        foreach (require database_path('seeders/data/games.php') as $game) {
            $images[PlaceholderArt::coverPath($game['name'])] = PlaceholderArt::cover($game['name']);
        }
        foreach (require database_path('seeders/data/banners.php') as $banner) {
            $images[PlaceholderArt::bannerPath($banner['title'])] = PlaceholderArt::banner($banner['title']);
        }

        // Start from empty folders, so images of removed games do not linger.
        foreach (['covers', 'banners'] as $folder) {
            File::deleteDirectory(public_path($folder));
            File::ensureDirectoryExists(public_path($folder));
        }

        foreach ($images as $path => $svg) {
            File::put(public_path($path), $svg);
        }

        $this->info('Wrote '.count($images).' images to public/covers and public/banners.');
    }
}

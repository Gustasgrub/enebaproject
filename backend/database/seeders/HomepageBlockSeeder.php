<?php

namespace Database\Seeders;

use App\Models\HomepageBlock;
use Illuminate\Database\Seeder;

/**
 * The homepage layout. Adding a block of an existing type, such as another platform row, needs only
 * another row here (PLAN.md, Section 7).
 */
class HomepageBlockSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            ['type' => 'banner', 'title' => 'Featured', 'config' => []],
            ['type' => 'bestsellers', 'title' => 'Bestsellers', 'config' => []],
            ['type' => 'deals', 'title' => 'Top deals', 'config' => []],
            ['type' => 'new_releases', 'title' => 'New releases', 'config' => []],
            ['type' => 'platform_row', 'title' => 'Steam games', 'config' => ['platform' => 'Steam']],
            ['type' => 'platform_row', 'title' => 'Nintendo favourites', 'config' => ['platform' => 'Nintendo']],
            // Disabled: shows that a block can be switched off without deleting it.
            ['type' => 'platform_row', 'title' => 'Xbox games', 'config' => ['platform' => 'Xbox'], 'enabled' => false],
        ];

        foreach ($blocks as $index => $block) {
            HomepageBlock::create([...$block, 'position' => $index + 1]);
        }
    }
}

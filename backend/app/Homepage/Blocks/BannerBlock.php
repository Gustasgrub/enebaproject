<?php

namespace App\Homepage\Blocks;

use App\Models\Banner;

/** `banner`: the hero carousel, from the banners table. */
class BannerBlock implements BlockProvider
{
    public function getData(array $config): array
    {
        return Banner::query()
            ->where('active', true)
            ->orderBy('position')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'image_url' => asset($banner->image_path),
                'link_url' => $banner->link_url,
            ])
            ->all();
    }
}

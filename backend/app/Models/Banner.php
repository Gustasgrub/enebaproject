<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** A slide of the homepage hero carousel. */
#[Fillable(['title', 'subtitle', 'image_path', 'link_url', 'position', 'active'])]
class Banner extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'active' => 'boolean',
        ];
    }
}

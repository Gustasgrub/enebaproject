<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** One section of the homepage; its type decides which provider supplies its data (PLAN.md, Section 7). */
#[Fillable(['type', 'title', 'position', 'enabled', 'config'])]
class HomepageBlock extends Model
{
    protected static function booted(): void
    {
        // A changed or removed block must not keep showing its cached data.
        static::saved(fn (HomepageBlock $block) => $block->forgetCachedData());
        static::deleted(fn (HomepageBlock $block) => $block->forgetCachedData());
    }

    /** Where HomepageController caches this block's items. */
    public function cacheKey(): string
    {
        return "homepage-block:{$this->id}";
    }

    public function forgetCachedData(): void
    {
        Cache::forget($this->cacheKey());
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'enabled' => 'boolean',
            'config' => 'array',
        ];
    }
}

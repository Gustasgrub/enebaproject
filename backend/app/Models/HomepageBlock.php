<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** One section of the homepage; its type decides which provider supplies its data (PLAN.md, Section 7). */
#[Fillable(['type', 'title', 'position', 'enabled', 'config'])]
class HomepageBlock extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'enabled' => 'boolean',
            'config' => 'array',
        ];
    }
}

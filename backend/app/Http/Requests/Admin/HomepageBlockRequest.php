<?php

namespace App\Http\Requests\Admin;

use App\Enums\Platform;
use App\Homepage\BlockRegistry;
use App\Models\HomepageBlock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Creating (POST) or editing (PATCH) a homepage block. On PATCH every field is optional. */
class HomepageBlockRequest extends FormRequest
{
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $block = $this->route('block');
        // The type decides which config fields are required; on edit it is the block's existing type.
        $type = $isCreate ? $this->input('type') : ($block instanceof HomepageBlock ? $block->type : null);

        return [
            'type' => [$isCreate ? 'required' : 'prohibited', 'string', Rule::in(app(BlockRegistry::class)->types())],
            'title' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:100'],
            'enabled' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'config.platform' => [$type === 'platform_row' && $isCreate ? 'required' : 'sometimes', Rule::enum(Platform::class)],
        ];
    }
}

<?php

namespace App\Http\Requests\Pancawaluya;

use Illuminate\Foundation\Http\FormRequest;

class StoreRewardItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reward_category_id' => ['required', 'integer', 'exists:reward_categories,id'],
            'code' => ['required', 'string', 'min:2', 'max:40', 'unique:reward_items,code'],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'point' => ['required', 'integer'],
            'character_dimension_id' => ['required', 'integer', 'exists:character_dimensions,id'],
            'weight' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests\Pancawaluya;

use Illuminate\Foundation\Http\FormRequest;

class StoreViolationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'violation_category_id' => ['required', 'integer', 'exists:violation_categories,id'],
            'code' => ['required', 'string', 'min:2', 'max:40', 'unique:violation_items,code'],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'point' => ['required', 'integer'],
            'character_dimension_id' => ['required', 'integer', 'exists:character_dimensions,id'],
            'weight' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

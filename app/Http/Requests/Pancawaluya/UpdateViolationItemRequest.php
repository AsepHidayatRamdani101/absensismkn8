<?php

namespace App\Http\Requests\Pancawaluya;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateViolationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('violation')?->id;

        return [
            'violation_category_id' => ['required', 'integer', 'exists:violation_categories,id'],
            'code' => ['required', 'string', 'min:2', 'max:40', Rule::unique('violation_items', 'code')->ignore($id)],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'point' => ['required', 'integer'],
            'character_dimension_id' => ['required', 'integer', 'exists:character_dimensions,id'],
            'weight' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

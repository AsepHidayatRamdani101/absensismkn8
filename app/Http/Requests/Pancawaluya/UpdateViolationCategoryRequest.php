<?php

namespace App\Http\Requests\Pancawaluya;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateViolationCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('violation_category')?->id;

        return [
            'code' => ['required', 'string', 'min:2', 'max:30', Rule::unique('violation_categories', 'code')->ignore($id)],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

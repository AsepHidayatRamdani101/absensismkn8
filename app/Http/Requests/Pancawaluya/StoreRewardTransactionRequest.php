<?php

namespace App\Http\Requests\Pancawaluya;

use Illuminate\Foundation\Http\FormRequest;

class StoreRewardTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'max:20'],
            'transaction_date' => ['required', 'date'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'reward_category_id' => ['required', 'integer', 'exists:reward_categories,id'],
            'reward_item_id' => ['required', 'integer', 'exists:reward_items,id'],
            'source' => ['required', 'string', 'max:80'],
            'status' => ['nullable', 'in:draft,pending,validated,approved,rejected'],
            'description' => ['nullable', 'string', 'max:1500'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ];
    }
}

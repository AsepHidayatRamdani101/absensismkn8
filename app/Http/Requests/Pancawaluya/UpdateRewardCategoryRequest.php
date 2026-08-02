<?php

namespace App\Http\Requests\Pancawaluya;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRewardCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('reward_category')?->id;
        $codeRules = ['required', 'string', 'min:2', 'max:30'];

        if (Schema::hasTable('reward_categories')) {
            $codeRules[] = Rule::unique('reward_categories', 'code')->ignore($id);
        }

        return [
            'code' => $codeRules,
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (!Schema::hasTable('reward_categories')) {
            $validator->after(function (Validator $validator): void {
                $validator->errors()->add('code', 'Tabel reward_categories belum tersedia. Jalankan migrasi modul Pancawaluya terlebih dahulu.');
            });
        }
    }
}

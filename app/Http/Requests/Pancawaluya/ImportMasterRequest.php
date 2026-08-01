<?php

namespace App\Http\Requests\Pancawaluya;

use Illuminate\Foundation\Http\FormRequest;

class ImportMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File import wajib dipilih.',
            'file.mimes' => 'Format file harus xlsx, xls, atau csv.',
        ];
    }
}

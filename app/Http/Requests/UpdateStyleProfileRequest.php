<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStyleProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'in:university,thesis,report,article,custom'],
            'language' => ['nullable', 'string', 'max:10'],
            'rules' => ['sometimes', 'array'],
        ];
    }
}

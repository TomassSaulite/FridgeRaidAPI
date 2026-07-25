<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecipeSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ease' => ['nullable', Rule::in(['lazy', 'normal', 'ambitious'])],
            'ease_tier' => ['nullable', Rule::in(['lazy', 'normal', 'ambitious'])],
            'tag' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'include_expired' => ['nullable', 'boolean'],
            'expires_within' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}

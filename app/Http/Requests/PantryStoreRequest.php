<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PantryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ingredient_id' => ['nullable', 'integer', 'exists:ingredients,id', 'required_without:ingredient_name'],
            'ingredient_name' => ['nullable', 'string', 'max:255', 'required_without:ingredient_id'],
            'quantity' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}

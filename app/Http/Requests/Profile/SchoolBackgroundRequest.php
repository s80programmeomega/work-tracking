<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class SchoolBackgroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'etablissement' => ['required', 'string', 'max:255'],
            'diplome' => ['nullable', 'string', 'max:255'],
            'domaine' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'description' => ['nullable', 'string', 'max:2000'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

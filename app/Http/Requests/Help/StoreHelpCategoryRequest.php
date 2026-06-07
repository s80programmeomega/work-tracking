<?php

declare(strict_types=1);

namespace App\Http\Requests\Help;

use Illuminate\Foundation\Http\FormRequest;

class StoreHelpCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorisation gérée par AdminHelpController::authorizeManage()
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:help_categories,slug'],
            'nom_fr' => ['required', 'string', 'max:255'],
            'nom_en' => ['required', 'string', 'max:255'],
            'description_fr' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:32'],
            'position' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}

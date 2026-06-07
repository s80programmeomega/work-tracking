<?php

declare(strict_types=1);

namespace App\Http\Requests\Help;

use Illuminate\Foundation\Http\FormRequest;

class StoreHelpArticleRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:help_categories,id'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:help_articles,slug'],
            'titre_fr' => ['required', 'string', 'max:255'],
            'titre_en' => ['required', 'string', 'max:255'],
            'body_fr' => ['required', 'string'],
            'body_en' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:2048'],
        ];
    }
}

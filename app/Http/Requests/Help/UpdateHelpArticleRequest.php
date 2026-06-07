<?php

declare(strict_types=1);

namespace App\Http\Requests\Help;

use App\Models\HelpArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHelpArticleRequest extends FormRequest
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
        $article = $this->route('article');
        $articleId = $article instanceof HelpArticle ? $article->id : null;

        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:help_categories,id'],
            'slug' => [
                'sometimes', 'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('help_articles', 'slug')->ignore($articleId),
            ],
            'titre_fr' => ['sometimes', 'required', 'string', 'max:255'],
            'titre_en' => ['sometimes', 'required', 'string', 'max:255'],
            'body_fr' => ['sometimes', 'required', 'string'],
            'body_en' => ['sometimes', 'required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:2048'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Help;

use App\Models\HelpCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHelpCategoryRequest extends FormRequest
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
        $category = $this->route('category');
        $categoryId = $category instanceof HelpCategory ? $category->id : null;

        return [
            'slug' => [
                'sometimes', 'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('help_categories', 'slug')->ignore($categoryId),
            ],
            'nom_fr' => ['sometimes', 'required', 'string', 'max:255'],
            'nom_en' => ['sometimes', 'required', 'string', 'max:255'],
            'description_fr' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:32'],
            'position' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Help;

use Illuminate\Foundation\Http\FormRequest;

class SaveHelpArticleDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorisation gérée par AdminHelpController::authorizeHelp()
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'draft_body_fr' => ['sometimes', 'nullable', 'string'],
            'draft_body_en' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

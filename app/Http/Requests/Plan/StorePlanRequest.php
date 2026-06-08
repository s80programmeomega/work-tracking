<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Vérifié AVANT la validation : un non-super-admin reçoit 403 quel que soit le payload.
        return (bool) $this->user()?->isSuperAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:plans,slug'],
            'nom_fr' => ['required', 'string', 'max:255'],
            'nom_en' => ['required', 'string', 'max:255'],
            'description_fr' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'is_free' => ['boolean'],
            'price' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_period' => ['nullable', 'string', 'in:monthly,yearly'],
            'max_members' => ['required', 'integer', 'min:-1'],
            'max_storage_mb' => ['required', 'integer', 'min:-1'],
            'max_file_size_mb' => ['required', 'integer', 'min:-1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

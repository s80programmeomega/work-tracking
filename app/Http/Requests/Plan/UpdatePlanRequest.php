<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
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
        $plan = $this->route('plan');
        $planId = $plan instanceof Plan ? $plan->id : null;

        return [
            'slug' => [
                'sometimes', 'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('plans', 'slug')->ignore($planId),
            ],
            'nom_fr' => ['sometimes', 'required', 'string', 'max:255'],
            'nom_en' => ['sometimes', 'required', 'string', 'max:255'],
            'description_fr' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'is_free' => ['boolean'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_period' => ['nullable', 'string', 'in:monthly,yearly'],
            'max_members' => ['sometimes', 'required', 'integer', 'min:-1'],
            'max_storage_mb' => ['sometimes', 'required', 'integer', 'min:-1'],
            'max_file_size_mb' => ['sometimes', 'required', 'integer', 'min:-1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'appartenance/propriété est vérifiée dans le contrôleur
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'provider' => ['required', 'string', Rule::in([Payment::PROVIDER_MTN, Payment::PROVIDER_ORANGE])],
            // MSISDN du payeur (utilisé par MTN ; format souple, validé côté fournisseur).
            'payer_phone' => ['required', 'string', 'max:20'],
        ];
    }
}

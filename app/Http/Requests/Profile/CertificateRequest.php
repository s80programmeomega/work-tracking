<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class CertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'organisme_emetteur' => ['nullable', 'string', 'max:255'],
            'date_obtention' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date', 'after_or_equal:date_obtention'],
            'credential_id' => ['nullable', 'string', 'max:255'],
            'credential_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

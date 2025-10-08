<?php

namespace App\Http\Requests\Activite;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActiviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // All authenticated users can attempt to update
        // Authorization will be checked in the controller
        return true;
    }

    public function rules(): array
    {
        $activiteId = $this->route('activite')->id;
        $projetId = $this->projet_id ?? $this->route('activite')->projet_id;

        return [
            'nom' => [
                'sometimes',
                'string',
                'max:255',
                'unique:activites,nom,' . $activiteId . ',id,projet_id,' . $projetId
            ],
            'description' => ['nullable', 'string'],
            'responsable_id' => ['sometimes', 'exists:users,id'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,archived'],
            'progression' => ['nullable', 'integer', 'min:0', 'max:100'],
            'couleur' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.unique' => 'Une activité avec ce nom existe déjà dans ce projet.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'responsable_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'date_fin.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
            'progression.min' => 'La progression ne peut pas être négative.',
            'progression.max' => 'La progression ne peut pas dépasser 100%.',
            'couleur.regex' => 'Le format de la couleur est invalide. Utilisez un code hexadécimal (ex: #FF5733).',
        ];
    }
}

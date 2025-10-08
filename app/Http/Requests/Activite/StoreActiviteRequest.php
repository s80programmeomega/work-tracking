<?php

namespace App\Http\Requests\Activite;

use App\Models\Activite;
use Illuminate\Foundation\Http\FormRequest;

class StoreActiviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // All authenticated users can create activities
        return true;
    }

    public function rules(): array
    {
        return [
            'projet_id' => ['required', 'exists:projets,id'],
            'nom' => [
                'required',
                'string',
                'max:255',
                'unique:activites,nom,NULL,id,projet_id,' . $this->projet_id
            ],
            'description' => ['nullable', 'string'],
            'responsable_id' => ['required', 'exists:users,id'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,archived'],
            'progression' => ['nullable', 'integer', 'min:0', 'max:100'],
            'couleur' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de l\'activité est requis.',
            'nom.unique' => 'Une activité avec ce nom existe déjà dans ce projet.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'projet_id.required' => 'Le projet est requis.',
            'projet_id.exists' => 'Le projet sélectionné n\'existe pas.',
            'responsable_id.required' => 'Le responsable est requis.',
            'responsable_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'date_fin.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
            'progression.min' => 'La progression ne peut pas être négative.',
            'progression.max' => 'La progression ne peut pas dépasser 100%.',
            'couleur.regex' => 'Le format de la couleur est invalide. Utilisez un code hexadécimal (ex: #FF5733).',
        ];
    }
}

<?php

namespace App\Http\Requests\Projet;

use App\Enums\ProjetStatus;
use App\Enums\ProjetVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_debut' => ['sometimes', 'required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'responsable_id' => ['sometimes', 'required', 'exists:users,id'],
            'status' => ['nullable', 'string', Rule::in(ProjetStatus::values())],
            'visibility' => ['nullable', 'string', Rule::in(ProjetVisibility::values())],
            'couleur' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'progression' => ['nullable', 'integer', 'min:0', 'max:100'],
            'objectifs' => ['nullable', 'string'],
            'is_template' => ['nullable', 'boolean'],
            'is_favorite' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:projet_tags,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du projet est requis.',
            'nom.max' => 'Le nom du projet ne doit pas dépasser 255 caractères.',
            'date_debut.required' => 'La date de début est requise.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être après la date de début.',
            'responsable_id.required' => 'Le responsable est requis.',
            'responsable_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'status.in' => 'Le statut doit être active, archived ou completed.',
            'visibility.in' => 'La visibilité doit être public, private ou team.',
            'couleur.regex' => 'La couleur doit être un code hexadécimal valide.',
            'budget.numeric' => 'Le budget doit être un nombre.',
            'budget.min' => 'Le budget ne peut pas être négatif.',
            'progression.integer' => 'La progression doit être un nombre entier.',
            'progression.min' => 'La progression ne peut pas être négative.',
            'progression.max' => 'La progression ne peut pas dépasser 100.',
            'tags.*.exists' => 'Un ou plusieurs tags sélectionnés n\'existent pas.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prépare les données avant la validation
     */
    protected function prepareForValidation()
    {
        // Décoder les settings JSON si elles existent
        if ($this->has('settings') && is_string($this->settings)) {
            $decoded = json_decode($this->settings, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge([
                    'settings_array' => $decoded
                ]);
            }
        }
 
          // ✅ Debug: Log des fichiers uploadés
        if ($this->hasFile('logo')) {
            \Log::info('Fichier logo reçu', [
                'name' => $this->file('logo')->getClientOriginalName(),
                'mime' => $this->file('logo')->getMimeType(),
                'size' => $this->file('logo')->getSize(),
                'extension' => $this->file('logo')->getClientOriginalExtension()
            ]);
        }

    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'settings' => 'nullable|string', // Reçu comme JSON string
            
            // Validation des settings décodées
            'settings_array' => 'nullable|array',
            'settings_array.default_project_visibility' => 'nullable|string|in:public,team,private',
            'settings_array.members_can_create_projects' => 'nullable|boolean',
            'settings_array.members_can_invite' => 'nullable|boolean',
            'settings_array.require_task_validation' => 'nullable|boolean',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du workspace est obligatoire',
            'nom.min' => 'Le nom doit contenir au moins 3 caractères',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères',
            
            'description.max' => 'La description ne peut pas dépasser 500 caractères',
            
            'logo.image' => 'Le fichier doit être une image',
            'logo.mimes' => 'L\'image doit être au format: jpeg, png, jpg, gif ou webp',
            'logo.max' => 'L\'image ne peut pas dépasser 2 MB',
            
            'settings.string' => 'Le format des paramètres est invalide',
            'settings_array.default_project_visibility.in' => 'La visibilité doit être: public, team ou private',
        ];
    }

    /**
     * Attributs personnalisés pour les messages d'erreur
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom du workspace',
            'description' => 'description',
            'logo' => 'logo',
            'settings_array.default_project_visibility' => 'visibilité par défaut',
            'settings_array.members_can_create_projects' => 'permission de créer des projets',
            'settings_array.members_can_invite' => 'permission d\'inviter des membres',
            'settings_array.require_task_validation' => 'validation requise des tâches',
        ];
    }
}
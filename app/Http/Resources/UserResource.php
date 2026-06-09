<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @responseField id integer The user's unique identifier.
     * @responseField nom string Last name.
     * @responseField email string Email address.
     * @responseField prenom string First name.
     * @responseField fonction string|null Job title or function.
     * @responseField avatar string|null Avatar URL.
     * @responseField initials string Computed initials (e.g. "JD").
     * @responseField bio string|null Short biography.
     * @responseField numero_telephone string|null Phone number.
     * @responseField adresse string|null Physical address.
     * @responseField language string Locale code (default: fr).
     * @responseField timezone string|null IANA timezone identifier.
     * @responseField is_active boolean Whether the account is active.
     * @responseField is_super_admin boolean Whether the user holds super-admin status.
     * @responseField current_workspace_id integer|null ID of the currently selected workspace.
     * @responseField last_login_at string|null ISO 8601 datetime of last login.
     * @responseField email_verified_at string|null ISO 8601 datetime of email verification.
     * @responseField created_at string|null ISO 8601 creation datetime.
     * @responseField updated_at string|null ISO 8601 last-update datetime.
     * @responseField team null Always null (reserved for future use).
     * @responseField roles string[]|null Role names (when the roles relation is loaded).
     * @responseField permissions string[]|null Permission names (when the permissions relation is loaded).
     * @responseField stats object|null Arbitrary stats payload when provided by the caller.
     */
    public function toArray(Request $request): array
    {
        $isSuperAdmin = $this->resource->isSuperAdmin();

        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'prenom' => $this->prenom,
            'fonction' => $this->fonction,
            'avatar' => $this->avatar_url,
            'initials' => $this->initials,
            'bio' => $this->bio,
            'numero_telephone' => $this->numero_telephone,
            'adresse' => $this->adresse,
            'language' => $this->language ?? 'fr',
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
            'is_super_admin' => $isSuperAdmin,
            'current_workspace_id' => $this->current_workspace_id,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            // État MFA (non sensibles) : nécessaires au front pour afficher
            // « confirmé », le bouton de désactivation et l'activation de l'OTP email.
            // On n'expose JAMAIS two_factor_secret ni les codes de récupération.
            'two_factor_confirmed_at' => $this->two_factor_confirmed_at?->toISOString(),
            'email_otp_enabled' => (bool) $this->email_otp_enabled,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            // TODO: Uncomment when Team model exists
            // 'team' => $this->whenLoaded('team', fn() => [
            //     'id' => $this->team->id,
            //     'nom' => $this->team->nom,
            // ]),
            'team' => null,

            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')
            ),

            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')
            ),

            // Stats (only when specifically loaded)
            'stats' => $this->when(
                isset($this->stats),
                fn () => $this->stats
            ),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'role' => $this->role,
            'fonction' => $this->fonction,
            'avatar' => $this->avatar_url,
            'initials' => $this->initials,
            'bio' => $this->bio,
            'numero_telephone' => $this->numero_telephone,
            'adresse' => $this->adresse,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Relationships
            // TODO: Uncomment when Team model exists
            // 'team' => $this->whenLoaded('team', fn() => [
            //     'id' => $this->team->id,
            //     'nom' => $this->team->nom,
            // ]),
            'team' => null,

            'roles' => $this->whenLoaded('roles', fn() =>
                $this->roles->pluck('name')
            ),

            'permissions' => $this->whenLoaded('permissions', fn() =>
                $this->permissions->pluck('name')
            ),

            // Stats (only when specifically loaded)
            'stats' => $this->when(
                isset($this->stats),
                fn() => $this->stats
            ),
        ];
    }
}

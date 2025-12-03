<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjetMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'numero_telephone' => $this->numero_telephone,
            'avatar' => $this->avatar,
            'role' => $this->pivot->role,

            // Pivot data
            'projet_role' => $this->pivot->role ?? null,
            'can_edit' => $this->pivot->can_edit ?? false,
            'can_delete' => $this->pivot->can_delete ?? false,
            'can_invite' => $this->pivot->can_invite ?? false,
            'can_delete_member' => $this->pivot->can_delete_member ?? false,
            'joined_at' => $this->pivot->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

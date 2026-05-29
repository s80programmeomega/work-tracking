<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/**
 * @property User $resource
 *
 * @mixin User
 */
class ProjetMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @responseField id integer The user's unique identifier.
     * @responseField nom string Last name.
     * @responseField email string Email address.
     * @responseField numero_telephone string|null Phone number.
     * @responseField avatar string|null Avatar URL.
     * @responseField role string Role name within the project (e.g. responsable, collaborateur).
     * @responseField projet_role string|null Raw role name from the projet_user pivot.
     * @responseField can_edit boolean Permission to edit project content.
     * @responseField can_delete boolean Permission to delete project content.
     * @responseField can_invite boolean Permission to invite new members.
     * @responseField can_delete_member boolean Permission to remove members.
     * @responseField joined_at string|null ISO datetime when the member joined.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'numero_telephone' => $this->numero_telephone,
            'avatar' => $this->avatar,
            'role' => Role::find($this->pivot->role_id)?->name ?? 'collaborateur',

            // Pivot data
            'projet_role' => Role::find($this->pivot->role_id)?->name ?? null,
            'can_edit' => $this->pivot->can_edit ?? false,
            'can_delete' => $this->pivot->can_delete ?? false,
            'can_invite' => $this->pivot->can_invite ?? false,
            'can_delete_member' => $this->pivot->can_delete_member ?? false,
            'joined_at' => $this->pivot->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

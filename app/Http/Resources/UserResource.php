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
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'is_active' => $this->is_active ?? true,
            'last_login_at' => $this->last_login_at,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions' => $role->permissions->map(fn($p) => [
                            'id' => $p->id,
                            'name' => $p->name,
                        ]),
                    ];
                });
            }),
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->permissions->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                ]);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

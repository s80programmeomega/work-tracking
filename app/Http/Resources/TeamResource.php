<?php

namespace App\Http\Resources;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Team $team */
        $team = $this->resource;

        return [
            'id' => $team->id,
            'uuid' => $team->uuid,
            'name' => $team->name,
            'description' => $team->description,
            'avatar' => $team->avatar,
            'workspace_id' => $team->workspace_id,
            'project_id' => $team->project_id,
            'owner_id' => $team->owner_id,
            'members_count' => $team->relationLoaded('members')
                ? $team->members->count()
                : ($team->members_count ?? null),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'project' => $team->relationLoaded('project') && $team->project !== null
                ? ['id' => $team->project->id, 'nom' => $team->project->nom]
                : null,
            'created_at' => $team->created_at,
        ];
    }
}

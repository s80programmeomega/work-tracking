<?php

namespace App\Observers;

use App\Models\Workspace;
use App\Models\WorkspaceChannel;

class WorkspaceObserver
{
    /**
     * Crée automatiquement les canaux système lors de la création d'un workspace.
     */
    public function created(Workspace $workspace): void
    {
        $this->seedChannels($workspace);
    }

    /**
     * Initialise les deux canaux système pour un workspace donné.
     * Utilise firstOrCreate pour être idempotent.
     */
    public function seedChannels(Workspace $workspace): void
    {
        foreach ([
            [WorkspaceChannel::TYPE_RESPONSIBLES, 'Responsables'],
            [WorkspaceChannel::TYPE_GLOBAL, 'Global'],
        ] as [$type, $name]) {
            WorkspaceChannel::firstOrCreate(
                ['workspace_id' => $workspace->id, 'type' => $type],
                ['name' => $name]
            );
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Observers\WorkspaceObserver;
use Illuminate\Console\Command;

class SeedWorkspaceChannels extends Command
{
    protected $signature = 'workspace:seed-channels
                            {--workspace= : ID d\'un workspace spécifique (optionnel)}';

    protected $description = 'Crée les canaux système (responsibles, global) pour tous les workspaces existants';

    public function handle(): int
    {
        $observer = new WorkspaceObserver;

        $query = Workspace::query()->whereNull('deleted_at');

        if ($workspaceId = $this->option('workspace')) {
            $query->where('id', $workspaceId);
        }

        $count = 0;
        $query->each(function (Workspace $workspace) use ($observer, &$count): void {
            $observer->seedChannels($workspace);
            $count++;
        });

        $this->info("Canaux système initialisés pour {$count} workspace(s).");

        return self::SUCCESS;
    }
}

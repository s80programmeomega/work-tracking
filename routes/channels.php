<?php

use App\Models\Team;
use App\Models\Workspace;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('workspace.{workspaceId}', function ($user, int $workspaceId) {
    return Workspace::find($workspaceId)
        ?->members()
        ->where('user_id', $user->id)
        ->exists() ?? false;
});

Broadcast::channel('team.{teamId}', function ($user, int $teamId) {
    return Team::find($teamId)
        ?->members()
        ->where('user_id', $user->id)
        ->exists() ?? false;
});

// Canal des responsables : directeur, manager, cadre du workspace
Broadcast::channel('workspace.{workspaceId}.responsibles', function ($user, int $workspaceId) {
    $workspace = Workspace::find($workspaceId);
    if (! $workspace) {
        return false;
    }
    $roleName = app(PermissionService::class)->getWorkspaceRoleName($user, $workspace);

    return in_array($roleName, ['owner', 'manager', 'cadre'], true);
});

// Canal global : tous les membres du workspace
Broadcast::channel('workspace.{workspaceId}.global', function ($user, int $workspaceId) {
    return Workspace::find($workspaceId)
        ?->members()
        ->where('user_id', $user->id)
        ->whereNull('workspace_members.banned_at')
        ->exists() ?? false;
});

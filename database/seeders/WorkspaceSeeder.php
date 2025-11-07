<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workspace;

class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Créer un workspace par défaut pour chaque utilisateur
            $workspace = Workspace::create([
                'nom' => $user->nom . ' Workspace',
                'description' => 'Workspace par défaut pour ' . $user->nom,
                'code' => 'WS-' . strtoupper(substr($user->nom, 0, 3)) . '-' . $user->id,
                'owner_id' => $user->id,
                'settings' => [
                    "language" => "fr",
                    "timezone" => "Africa/Douala",
                    "visibility" => "private",
                    "weekly_digest" => false,
                    "members_can_invite" => true,
                    "notify_on_new_member" => true,
                    "notify_on_new_project" => true,
                    "require_task_validation" => true,
                    "default_project_visibility" => "team",
                    "members_can_create_projects" => true,
                    "members_can_delete_projects" => false,
                    "require_approval_for_time_off" => true,
                    "notify_on_deadline_approaching" => true
                ],
                'is_active' => true,
                'logo' => null,
            ]);

            // Mettre à jour l'utilisateur avec son workspace courant
            $user->current_workspace_id = $workspace->id;
            $user->save();
        }

        $this->command->info('Default workspaces created and assigned to all users!');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Arr;

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
                    "language" => $user->language ?? "fr",
                    "timezone" => $user->timezone ?? "Africa/Douala",
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

            // Assigner le workspace à l'utilisateur
            $user->current_workspace_id = $workspace->id;
            $user->save();

            // Ajouter l'utilisateur comme membre du workspace (role: owner)
            $workspace->members()->attach($user->id, [
                'role' => 'owner',
                'permissions' => json_encode([
                    "projets.view" => true,
                    "projets.create" => true,
                    "projets.update" => true,
                    "projets.delete" => true,
                    "can_create_projects" => true,
                    "can_invite_members" => true,
                    "can_manage_settings" => true,
                    "activites.view" => true,
                    "activites.create" => true,
                    "activites.update" => true,
                    "activites.delete" => true,
                    "taches.view" => true,
                    "taches.create" => true,
                    "taches.update" => true,
                    "taches.delete" => true,
                    "taches.validate" => true,
                    "taches.comment" => true,
                    "users.view" => true,
                    "users.create" => true,
                    "users.update" => true,
                    "users.delete" => true,
                    "users.assign" => true,
                    "reports.view" => true,
                    "reports.create" => true
                ]),
                'invited_at' => now(),
                'invited_by' => $user->id
            ]);
        }

        $this->command->info('Default workspaces created and assigned to all users with owner role!');
    }
}

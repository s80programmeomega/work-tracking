<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        // Reuse directeur created by RolePermissionSeeder
        $directeur = User::where('email', 'directeur@worktracking.com')->firstOrFail();

        $manager = User::create([
            'nom' => 'Manager',
            'prenom' => 'Test',
            'nom_complet' => 'Test Manager',
            'email' => 'manager@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $manager->assignRole(Role::UTILISATEUR->value);

        $cadre = User::create([
            'nom' => 'Cadre',
            'prenom' => 'Test',
            'nom_complet' => 'Test Cadre',
            'email' => 'cadre@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $cadre->assignRole(Role::UTILISATEUR->value);

        $collaborateur = User::create([
            'nom' => 'Collaborateur',
            'prenom' => 'Test',
            'nom_complet' => 'Test Collaborateur',
            'email' => 'collaborateur@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $collaborateur->assignRole(Role::UTILISATEUR->value);

        $stagiaire = User::create([
            'nom' => 'Stagiaire',
            'prenom' => 'Test',
            'nom_complet' => 'Test Stagiaire',
            'email' => 'stagiaire@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $stagiaire->assignRole(Role::UTILISATEUR->value);

        $observateur = User::create([
            'nom' => 'Observateur',
            'prenom' => 'Test',
            'nom_complet' => 'Test Observateur',
            'email' => 'observateur@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $observateur->assignRole(Role::UTILISATEUR->value);

        // Create test workspace owned by directeur
        $workspace = Workspace::create([
            'nom' => 'Workspace de Test',
            'description' => 'Workspace de démonstration avec tous les rôles',
            'code' => 'TEST-WS-001',
            'owner_id' => $directeur->id,
            'is_active' => true,
            'settings' => [
                'default_project_visibility' => 'team',
                'members_can_create_projects' => true,
                'members_can_invite' => false,
                'require_task_validation' => true,
            ],
        ]);

        $directeur->update(['current_workspace_id' => $workspace->id]);

        // Attach members with contextual roles
        $workspace->members()->attach($directeur->id, [
            'role' => 'owner',
            'permissions' => json_encode(['all']),
            'invited_at' => now(),
            'invited_by' => $directeur->id,
        ]);

        foreach ([
            ['user' => $manager,       'role' => 'manager'],
            ['user' => $cadre,         'role' => 'cadre'],
            ['user' => $collaborateur, 'role' => 'collaborateur'],
            ['user' => $stagiaire,     'role' => 'stagiaire'],
            ['user' => $observateur,   'role' => 'observateur'],
        ] as $entry) {
            $workspace->members()->attach($entry['user']->id, [
                'role' => $entry['role'],
                'invited_at' => now(),
                'invited_by' => $directeur->id,
            ]);
            $entry['user']->update(['current_workspace_id' => $workspace->id]);
        }

        // Create a test project
        $projet = Projet::create([
            'workspace_id' => $workspace->id,
            'nom' => 'Projet de Test',
            'description' => 'Projet de démonstration',
            'responsable_id' => $manager->id,
            'date_debut' => now(),
            'date_fin' => now()->addMonths(3),
            'visibility' => 'team',
        ]);

        $projet->members()->attach($manager->id, ['role' => 'owner']);
        $projet->members()->attach($cadre->id, ['role' => 'cadre']);
        $projet->members()->attach($collaborateur->id, ['role' => 'collaborateur']);

        // Create a test activity
        $activite = Activite::create([
            'projet_id' => $projet->id,
            'nom' => 'Activité de Test',
            'description' => 'Activité de démonstration',
            'responsable_id' => $cadre->id,
            'date_debut' => now(),
            'date_fin' => now()->addMonths(2),
        ]);

        $activite->members()->attach($cadre->id, [
            'role' => 'cadre',
            'can_create_tasks' => true,
            'can_edit_tasks' => true,
            'can_delete_tasks' => true,
            'can_validate_results' => true,
            'can_assign_users' => true,
        ]);

        $activite->members()->attach($collaborateur->id, [
            'role' => 'collaborateur',
            'can_create_tasks' => false,
            'can_edit_tasks' => true,
        ]);

        $this->command->info('Workspace seeded successfully!');
        $this->command->info('');
        $this->command->info('Test users (password: password):');
        $this->command->info('- directeur@worktracking.com    → directeur (workspace owner)');
        $this->command->info('- manager@worktracking.com      → manager (N2 validator)');
        $this->command->info('- cadre@worktracking.com        → cadre (N1 validator)');
        $this->command->info('- collaborateur@worktracking.com → collaborateur');
        $this->command->info('- stagiaire@worktracking.com    → stagiaire');
        $this->command->info('- observateur@worktracking.com  → observateur (read-only)');
    }
}

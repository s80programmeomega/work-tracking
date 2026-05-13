<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin  = User::where('email', 'superadmin@worktracking.com')->firstOrFail();
        $directeur   = User::where('email', 'directeur@worktracking.com')->firstOrFail();

        foreach ([
            ['nom' => 'Manager',       'prenom' => 'Test', 'email' => 'manager@worktracking.com'],
            ['nom' => 'Cadre',         'prenom' => 'Test', 'email' => 'cadre@worktracking.com'],
            ['nom' => 'Collaborateur', 'prenom' => 'Test', 'email' => 'collaborateur@worktracking.com'],
            ['nom' => 'Stagiaire',     'prenom' => 'Test', 'email' => 'stagiaire@worktracking.com'],
            ['nom' => 'Observateur',   'prenom' => 'Test', 'email' => 'observateur@worktracking.com'],
        ] as $data) {
            User::create([
                'nom'               => $data['nom'],
                'prenom'            => $data['prenom'],
                'nom_complet'       => $data['prenom'] . ' ' . $data['nom'],
                'email'             => $data['email'],
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active'         => true,
            ])->assignRole(Role::UTILISATEUR->value);
        }

        $manager       = User::where('email', 'manager@worktracking.com')->firstOrFail();
        $cadre         = User::where('email', 'cadre@worktracking.com')->firstOrFail();
        $collaborateur = User::where('email', 'collaborateur@worktracking.com')->firstOrFail();
        $stagiaire     = User::where('email', 'stagiaire@worktracking.com')->firstOrFail();
        $observateur   = User::where('email', 'observateur@worktracking.com')->firstOrFail();

        // ── Workspace ────────────────────────────────────────────────────────
        $workspace = Workspace::create([
            'nom'         => 'Workspace de Test',
            'description' => 'Workspace de démonstration avec tous les rôles',
            'code'        => 'TEST-WS-001',
            'owner_id'    => $directeur->id,
            'is_active'   => true,
            'settings'    => [
                'default_project_visibility' => 'team',
                'members_can_create_projects' => true,
                'members_can_invite'          => false,
                'require_task_validation'     => true,
            ],
        ]);

        // Set current workspace for all users including super_admin
        foreach ([$superAdmin, $directeur, $manager, $cadre, $collaborateur, $stagiaire, $observateur] as $user) {
            $user->update(['current_workspace_id' => $workspace->id]);
        }

        // Attach workspace members
        $workspace->members()->attach($directeur->id, [
            'role'        => 'owner',
            'permissions' => json_encode(['all']),
            'invited_at'  => now(),
            'invited_by'  => $directeur->id,
        ]);
        foreach ([
            [$manager,       'manager'],
            [$cadre,         'cadre'],
            [$collaborateur, 'collaborateur'],
            [$stagiaire,     'stagiaire'],
            [$observateur,   'observateur'],
        ] as [$user, $role]) {
            $workspace->members()->attach($user->id, [
                'role'       => $role,
                'invited_at' => now(),
                'invited_by' => $directeur->id,
            ]);
        }

        // ── Projects (3) ─────────────────────────────────────────────────────
        $projectsData = [
            ['nom' => 'Développement Application Mobile',  'responsable' => $manager],
            ['nom' => 'Refonte Site Web',                  'responsable' => $manager],
            ['nom' => 'Infrastructure Cloud',              'responsable' => $cadre],
        ];

        foreach ($projectsData as $pd) {
            $projet = Projet::create([
                'workspace_id'   => $workspace->id,
                'nom'            => $pd['nom'],
                'description'    => 'Projet de démonstration : ' . $pd['nom'],
                'responsable_id' => $pd['responsable']->id,
                'date_debut'     => now(),
                'date_fin'       => now()->addMonths(3),
                'visibility'     => 'team',
                'status'         => 'active',
            ]);

            $projet->members()->attach($manager->id,       ['role' => 'manager']);
            $projet->members()->attach($cadre->id,         ['role' => 'cadre']);
            $projet->members()->attach($collaborateur->id, ['role' => 'collaborateur']);
            $projet->members()->attach($stagiaire->id,     ['role' => 'stagiaire']);
            $projet->members()->attach($observateur->id,   ['role' => 'observateur']);

            // ── Activities (3 per project) ────────────────────────────────────
            $activitiesData = [
                ['nom' => 'Analyse & Conception',    'responsable' => $cadre],
                ['nom' => 'Développement',           'responsable' => $cadre],
                ['nom' => 'Tests & Déploiement',     'responsable' => $manager],
            ];

            foreach ($activitiesData as $ad) {
                $activite = Activite::create([
                    'projet_id'      => $projet->id,
                    'nom'            => $ad['nom'],
                    'description'    => 'Activité : ' . $ad['nom'],
                    'responsable_id' => $ad['responsable']->id,
                    'date_debut'     => now(),
                    'date_fin'       => now()->addMonths(2),
                ]);

                $activite->members()->attach($cadre->id, [
                    'role'                => 'cadre',
                    'can_create_tasks'    => true,
                    'can_edit_tasks'      => true,
                    'can_delete_tasks'    => true,
                    'can_validate_results'=> true,
                    'can_assign_users'    => true,
                ]);
                $activite->members()->attach($collaborateur->id, [
                    'role'             => 'collaborateur',
                    'can_create_tasks' => false,
                    'can_edit_tasks'   => false,
                ]);
                $activite->members()->attach($stagiaire->id, [
                    'role'             => 'stagiaire',
                    'can_create_tasks' => false,
                    'can_edit_tasks'   => false,
                ]);

                // ── Tasks (5 per activity) ────────────────────────────────────
                $tasksData = [
                    ['titre' => 'Recueil des besoins',          'statut' => 'termine',  'taux' => 100, 'priorite' => 'elevee'],
                    ['titre' => 'Rédaction des spécifications',  'statut' => 'en_cours', 'taux' => 60,  'priorite' => 'elevee'],
                    ['titre' => 'Conception technique',          'statut' => 'en_cours', 'taux' => 40,  'priorite' => 'moyenne'],
                    ['titre' => 'Développement module principal','statut' => 'a_faire',  'taux' => 0,   'priorite' => 'critique'],
                    ['titre' => 'Revue de code',                 'statut' => 'a_faire',  'taux' => 0,   'priorite' => 'faible'],
                ];

                foreach ($tasksData as $td) {
                    $tache = Tache::create([
                        'activite_id'            => $activite->id,
                        'responsable_id'         => $cadre->id,
                        'titre'                  => $td['titre'],
                        'description'            => 'Description de : ' . $td['titre'],
                        'statut'                 => $td['statut'],
                        'priorite'               => $td['priorite'],
                        'echeance'               => now()->addDays(rand(7, 30)),
                        'taux_realisation'       => $td['taux'],
                        'validation_n1_required' => true,
                        'validation_n2_required' => true,
                    ]);

                    $tache->assignees()->attach($collaborateur->id, [
                        'role'           => 'collaborateur',
                        'is_responsable' => false,
                        'can_edit'       => false,
                    ]);
                    $tache->assignees()->attach($stagiaire->id, [
                        'role'           => 'stagiaire',
                        'is_responsable' => false,
                        'can_edit'       => false,
                    ]);
                }
            }
        }

        // ── Second workspace (owned by manager, directeur is a member) ──────
        $workspace2 = Workspace::create([
            'nom'         => 'Workspace Secondaire',
            'description' => 'Second workspace pour tester le switch',
            'code'        => 'TEST-WS-002',
            'owner_id'    => $manager->id,
            'is_active'   => true,
            'settings'    => ['default_project_visibility' => 'team'],
        ]);

        foreach ([$superAdmin, $directeur, $manager, $cadre] as $user) {
            $user->update(['current_workspace_id' => $workspace->id]); // keep primary
        }

        $workspace2->members()->attach($manager->id, [
            'role' => 'owner', 'invited_at' => now(), 'invited_by' => $manager->id,
        ]);
        $workspace2->members()->attach($directeur->id, [
            'role' => 'manager', 'invited_at' => now(), 'invited_by' => $manager->id,
        ]);

        Projet::create([
            'workspace_id'   => $workspace2->id,
            'nom'            => 'Projet Workspace 2',
            'description'    => 'Projet de test dans le second workspace',
            'responsable_id' => $manager->id,
            'date_debut'     => now(),
            'date_fin'       => now()->addMonths(2),
            'visibility'     => 'team',
            'status'         => 'active',
        ]);

        $this->command->info('Workspace seeded successfully!');
        $this->command->info('3 projects × 3 activities × 5 tasks = 45 tasks total');
        $this->command->info('');
        $this->command->info('Test users (password: password):');
        $this->command->info('- superadmin@worktracking.com    → super_admin (sees everything)');
        $this->command->info('- directeur@worktracking.com     → directeur (workspace owner)');
        $this->command->info('- manager@worktracking.com       → manager (N2 validator)');
        $this->command->info('- cadre@worktracking.com         → cadre (N1 validator)');
        $this->command->info('- collaborateur@worktracking.com → collaborateur');
        $this->command->info('- stagiaire@worktracking.com     → stagiaire');
        $this->command->info('- observateur@worktracking.com   → observateur (read-only)');
    }
}

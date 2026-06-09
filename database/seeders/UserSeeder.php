<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'nom' => 'Super Administrateur',
                'email' => 'admin@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'fonction' => 'Administrateur Système',
            ],
            [
                'nom' => 'Manager Principal',
                'email' => 'manager@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'fonction' => 'Chef de Projet',
            ],
            [
                'nom' => 'Responsable N1',
                'email' => 'resp1@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'directeur',
                'fonction' => 'Chef d\'Équipe',
            ],
            [
                'nom' => 'Responsable N2',
                'email' => 'resp2@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'task_responsable',
                'fonction' => 'Superviseur',
            ],
            [
                'nom' => 'Jean Cadre',
                'email' => 'cadre@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'cadre',
                'fonction' => 'Ingénieur Senior',
            ],
            [
                'nom' => 'Marie Stagiaire',
                'email' => 'stagiaire@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'stagiaire',
                'fonction' => 'Stagiaire Développeur',
            ],
            [
                'nom' => 'Utilisateur Test',
                'email' => 'test@test.com',
                'password' => Hash::make('password'),
                'role' => 'cadre',
                'fonction' => 'Développeur',
            ],
        ];

        $created = [];
        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            // firstOrCreate : idempotent (le seed peut être rejoué sans collision
            // d'email — certaines entrées partagent une adresse).
            $user = User::firstOrCreate(['email' => $userData['email']], $userData);
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
            $created[] = $user;
        }

        // Aucun utilisateur ne doit rester « sans workspace » (sinon piégé sur
        // /workspaces/create). On crée un workspace de démo possédé par le premier
        // utilisateur, on y rattache les autres comme membres, et on pose
        // current_workspace_id pour tous.
        $owner = $created[0];
        $workspace = Workspace::firstOrCreate(
            ['code' => 'DEMO-WS-001'],
            ['nom' => 'Espace de démonstration', 'owner_id' => $owner->id, 'is_active' => true]
        );

        foreach ($created as $user) {
            if ($user->id !== $owner->id && ! $workspace->isMember($user)) {
                $workspace->addMember($user, 'collaborateur');
            }
            $user->ensureCurrentWorkspace();
        }
    }
}

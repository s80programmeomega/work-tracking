<?php

namespace Database\Seeders;

use App\Models\User;
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
                'email' => 'admin@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'fonction' => 'Chef de Projet',
            ],
            [
                'nom' => 'Responsable N1',
                'email' => 'resp1@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'responsable_n1',
                'fonction' => 'Chef d\'Équipe',
            ],
            [
                'nom' => 'Responsable N2',
                'email' => 'resp2@worktracking.com',
                'password' => Hash::make('password123'),
                'role' => 'responsable_n2',
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
            ]
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            // Assign the Spatie role
            $user->assignRole($userData['role']);
        }
    }
}

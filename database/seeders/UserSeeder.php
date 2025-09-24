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
        // Super Admin
        User::create([
            'nom' => 'Super Administrateur',
            'email' => 'admin@worktracking.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'fonction' => 'Administrateur Système',
        ]);

        // Manager
        User::create([
            'nom' => 'Manager Principal',
            'email' => 'manager@worktracking.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'fonction' => 'Chef de Projet',
        ]);

        // Responsable N1
        User::create([
            'nom' => 'Responsable N1',
            'email' => 'resp1@worktracking.com',
            'password' => Hash::make('password123'),
            'role' => 'responsable_n1',
            'fonction' => 'Chef d\'Équipe',
        ]);

        // Responsable N2
        User::create([
            'nom' => 'Responsable N2',
            'email' => 'resp2@worktracking.com',
            'password' => Hash::make('password123'),
            'role' => 'responsable_n2',
            'fonction' => 'Superviseur',
        ]);

        // Cadre
        User::create([
            'nom' => 'Jean Cadre',
            'email' => 'cadre@worktracking.com',
            'password' => Hash::make('password123'),
            'role' => 'cadre',
            'fonction' => 'Ingénieur Senior',
        ]);

        // Stagiaire
        User::create([
            'nom' => 'Marie Stagiaire',
            'email' => 'stagiaire@worktracking.com',
            'password' => Hash::make('password123'),
            'role' => 'stagiaire',
            'fonction' => 'Stagiaire Développeur',
        ]);

        // Utilisateur de test simple
        User::create([
            'nom' => 'Utilisateur Test',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'role' => 'cadre',
            'fonction' => 'Développeur',
        ]);
    }
}

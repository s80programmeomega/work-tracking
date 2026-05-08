<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Workspaces
            'workspaces.create',
            'workspaces.update',
            'workspaces.delete',
            'workspaces.manage_members',

            // Projets
            'projets.view',
            'projets.create',
            'projets.update',
            'projets.delete',
            'can_create_projects',
            'can_delete_members',
            'can_invite_members',
            'can_manage_settings',
            'can_view_all_projects',

            // Activités
            'activites.view',
            'activites.create',
            'activites.update',
            'activites.delete',

            // Tâches
            'taches.view',
            'taches.create',
            'taches.update',
            'taches.delete',
            'taches.validate',
            'taches.comment',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign',

            // Reports
            'reports.view',
            'reports.create',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create global roles
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::create([
                'name' => $roleEnum->value,
                'guard_name' => 'web',
            ]);

            $rolePermissions = $roleEnum->permissions();

            if (in_array('*', $rolePermissions)) {
                $role->givePermissionTo(Permission::all());
            } else {
                $role->givePermissionTo($rolePermissions);
            }
        }

        // Create super admin user
        $superAdmin = User::create([
            'nom' => 'Super',
            'prenom' => 'Admin',
            'nom_complet' => 'Super Admin',
            'email' => 'superadmin@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $superAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

        // Create directeur user
        $directeur = User::create([
            'nom' => 'Directeur',
            'prenom' => 'Test',
            'nom_complet' => 'Test Directeur',
            'email' => 'directeur@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $directeur->assignRole(RoleEnum::DIRECTEUR->value);

        // Create utilisateur user
        $utilisateur = User::create([
            'nom' => 'Utilisateur',
            'prenom' => 'Test',
            'nom_complet' => 'Test Utilisateur',
            'email' => 'utilisateur@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $utilisateur->assignRole(RoleEnum::UTILISATEUR->value);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('');
        $this->command->info('Default users created:');
        $this->command->info('- superadmin@worktracking.com (password: password)');
        $this->command->info('- directeur@worktracking.com (password: password)');
        $this->command->info('- utilisateur@worktracking.com (password: password)');
    }
}

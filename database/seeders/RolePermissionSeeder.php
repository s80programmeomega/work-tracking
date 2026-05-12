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
            'workspaces.manage_settings',
            'workspaces.invite_members',
            'workspaces.remove_members',
            'workspaces.view_all_projects',

            // Projets
            'projets.view',
            'projets.create',
            'projets.update',
            'projets.delete',
            'projets.manage_members',

            // Activités
            'activites.view',
            'activites.create',
            'activites.update',
            'activites.delete',
            'activites.manage_members',

            // Tâches
            'taches.view',
            'taches.create',
            'taches.update',
            'taches.delete',
            'taches.validate_n1',
            'taches.validate_n2',
            'taches.submit_result',
            'taches.comment',

            // Documents
            'documents.view',
            'documents.upload',
            'documents.delete',
            'documents.share',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign',

            // Reports
            'reports.view',
            'reports.create',

            // Legacy — kept for backward compat during transition
            'can_create_projects',
            'can_delete_members',
            'can_invite_members',
            'can_manage_settings',
            'can_view_all_projects',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Global roles (Spatie — assigned to user account)
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::firstOrCreate([
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

        // Contextual roles (stored in pivot tables — scoped per workspace/project/activity)
        $contextualRoles = [
            'owner' => Permission::all(),
            'manager' => [
                'projets.view', 'projets.update', 'projets.manage_members',
                'activites.view', 'activites.create', 'activites.update', 'activites.delete', 'activites.manage_members',
                'taches.view', 'taches.create', 'taches.update', 'taches.delete', 'taches.validate_n2', 'taches.comment',
                'documents.view', 'documents.upload', 'documents.delete', 'documents.share',
                'reports.view', 'reports.create',
            ],
            'cadre' => [
                'projets.view',
                'activites.view', 'activites.update',
                'taches.view', 'taches.create', 'taches.update', 'taches.validate_n1', 'taches.comment',
                'documents.view', 'documents.upload',
                'reports.view',
            ],
            'collaborateur' => [
                'projets.view',
                'activites.view',
                'taches.view', 'taches.submit_result', 'taches.comment',
                'documents.view', 'documents.upload',
            ],
            'stagiaire' => [
                'projets.view',
                'activites.view',
                'taches.view', 'taches.submit_result', 'taches.comment',
                'documents.view',
            ],
            'observateur' => [
                'projets.view',
                'activites.view',
                'taches.view',
                'documents.view',
            ],
        ];

        foreach ($contextualRoles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if ($rolePermissions instanceof \Illuminate\Database\Eloquent\Collection) {
                $role->syncPermissions($rolePermissions);
            } else {
                $role->syncPermissions(Permission::whereIn('name', $rolePermissions)->get());
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

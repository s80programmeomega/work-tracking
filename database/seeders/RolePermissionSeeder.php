<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Projets
            'projets.view',
            'projets.create',
            'projets.update',
            'projets.delete',
            'can_create_projects',
            'can_delete_members',
            'can_invite_members',
            'can_manage_settings',

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

        // Create roles and assign permissions
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::create([
                'name' => $roleEnum->value,
                'guard_name' => 'web'
            ]);

            $rolePermissions = $roleEnum->permissions();

            if (in_array('*', $rolePermissions)) {
                // Super admin gets all permissions
                $role->givePermissionTo(Permission::all());
            } else {
                $role->givePermissionTo($rolePermissions);
            }
        }

        // Create a super admin user
        $superAdmin = User::create([
            'nom' => 'Super',
            'prenom' => 'Admin',
            'nom_complet' => 'Super Admin',
            'email' => 'superadmin@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'role' => RoleEnum::SUPER_ADMIN->value,
        ]);
        $superAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

        // Create a manager user
        $manager = User::create([
            'nom' => 'Manager',
            'prenom' => 'Admin',
            'nom_complet' => 'Admin Manager',
            'email' => 'admin@worktracking.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'role' => RoleEnum::ADMIN->value,
        ]);
        $manager->assignRole(RoleEnum::ADMIN->value);

        

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('');
        $this->command->info('Default users created:');
        $this->command->info('- admin@worktracking.com (password: password)');
        $this->command->info('- manager@worktracking.com (password: password)'); 
    }
}

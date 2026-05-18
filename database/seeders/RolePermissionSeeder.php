<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Permissions\Permission as Perm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. Create all permissions from the single source of truth ─────
        foreach (Perm::all() as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // ── 2. Global Spatie roles (assigned to user accounts) ────────────
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::firstOrCreate(['name' => $roleEnum->value, 'guard_name' => 'web']);

            $rolePermissions = $roleEnum->permissions();

            if (in_array('*', $rolePermissions)) {
                $role->syncPermissions(Permission::where('guard_name', 'web')->get());
            } else {
                $role->syncPermissions(
                    Permission::whereIn('name', $rolePermissions)->where('guard_name', 'web')->get()
                );
            }
        }

        // ── 3. Contextual roles (stored in pivot role_id — scoped per resource) ──
        // These are Spatie roles but are NEVER assigned globally to users.
        // They live in pivot tables: workspace_members.role_id, projet_user.role_id, etc.
        $contextualRoles = ['owner', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur', 'task_responsable'];

        foreach ($contextualRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $permNames = Perm::forRole($roleName);

            $role->syncPermissions(
                Permission::whereIn('name', $permNames)->where('guard_name', 'web')->get()
            );
        }

        // ── 4. Test users ─────────────────────────────────────────────────
        $this->createTestUsers();

        $this->command->info('✅ Roles and permissions seeded successfully!');
        $this->command->info('');
        $this->command->info('Global roles seeded: super_admin, directeur, utilisateur');
        $this->command->info('Contextual roles seeded: owner, manager, cadre, collaborateur, stagiaire, observateur, task_responsable');
        $this->command->info('');
        $this->command->info('Test users:');
        $this->command->info('  superadmin@worktracking.com  (password)  — super_admin');
        $this->command->info('  directeur@worktracking.com   (password)  — directeur');
        $this->command->info('  manager@worktracking.com     (password)  — utilisateur + manager pivot');
        $this->command->info('  cadre@worktracking.com       (password)  — utilisateur + cadre pivot');
        $this->command->info('  collaborateur@worktracking.com (password)— utilisateur + collaborateur pivot');
        $this->command->info('  stagiaire@worktracking.com   (password)  — utilisateur + stagiaire pivot');
        $this->command->info('  observateur@worktracking.com (password)  — utilisateur + observateur pivot');
        $this->command->info('  utilisateur@worktracking.com (password)  — utilisateur (no workspace)');
    }

    private function createTestUsers(): void
    {
        // Super admin — global role, is_super_admin flag
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@worktracking.com'],
            [
                'nom' => 'Super',
                'prenom' => 'Admin',
                'nom_complet' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'is_super_admin' => true,
            ]
        );
        $superAdmin->update(['is_super_admin' => true]);
        $superAdmin->syncRoles([RoleEnum::SUPER_ADMIN->value]);

        // Directeur — global Spatie role; also receives owner pivot row when workspace created
        $this->createUserIfNotExists([
            'email' => 'directeur@worktracking.com',
            'nom' => 'Directeur',
            'prenom' => 'Test',
            'nom_complet' => 'Test Directeur',
        ], RoleEnum::DIRECTEUR->value);

        // Contextual-only users — global role is 'utilisateur'
        // Their contextual roles (manager, cadre, etc.) are assigned via pivot rows in workspace/project/activity
        foreach (['manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'] as $contextRole) {
            $this->createUserIfNotExists([
                'email' => "{$contextRole}@worktracking.com",
                'nom' => ucfirst($contextRole),
                'prenom' => 'Test',
                'nom_complet' => 'Test '.ucfirst($contextRole),
            ], RoleEnum::UTILISATEUR->value);
        }

        // Plain utilisateur — no workspace
        $this->createUserIfNotExists([
            'email' => 'utilisateur@worktracking.com',
            'nom' => 'Utilisateur',
            'prenom' => 'Test',
            'nom_complet' => 'Test Utilisateur',
        ], RoleEnum::UTILISATEUR->value);
    }

    private function createUserIfNotExists(array $attributes, string $globalRole): User
    {
        $user = User::firstOrCreate(
            ['email' => $attributes['email']],
            array_merge($attributes, [
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ])
        );
        $user->syncRoles([$globalRole]);

        return $user;
    }
}

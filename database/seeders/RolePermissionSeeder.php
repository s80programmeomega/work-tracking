<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Permissions\Permission as Perm;
use Illuminate\Database\Seeder;
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

        // ── 2. Role priority map (single source of truth) ────────────────
        $rolePriorities = [
            'super_admin' => 1,
            'directeur' => 2,
            'owner' => 3,
            'manager' => 4,
            'cadre' => 5,
            'task_responsable' => 5,
            'collaborateur' => 6,
            'stagiaire' => 6,
            'observateur' => 7,
            'utilisateur' => 8,
        ];

        // ── 3. Global Spatie roles (assigned to user accounts) ────────────
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::firstOrCreate(['name' => $roleEnum->value, 'guard_name' => 'web']);
            $role->priority = $rolePriorities[$roleEnum->value] ?? 99;
            $role->save();

            $rolePermissions = $roleEnum->permissions();

            if (in_array('*', $rolePermissions)) {
                $role->syncPermissions(Permission::where('guard_name', 'web')->get());
            } else {
                $role->syncPermissions(
                    Permission::whereIn('name', $rolePermissions)->where('guard_name', 'web')->get()
                );
            }
        }

        // ── 4. Contextual roles (stored in pivot role_id — scoped per resource) ──
        // These are Spatie roles but are NEVER assigned globally to users.
        // They live in pivot tables: workspace_members.role_id, projet_user.role_id, etc.
        $contextualRoles = ['owner', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur', 'task_responsable'];

        foreach ($contextualRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->priority = $rolePriorities[$roleName] ?? 99;
            $role->save();

            $permNames = Perm::forRole($roleName);

            $role->syncPermissions(
                Permission::whereIn('name', $permNames)->where('guard_name', 'web')->get()
            );
        }

        $this->command->info('✅ Roles and permissions seeded successfully!');
        $this->command->info('');
        $this->command->info('Global roles seeded: super_admin, directeur, utilisateur');
        $this->command->info('Contextual roles seeded: owner, manager, cadre, collaborateur, stagiaire, observateur, task_responsable');
    }
}

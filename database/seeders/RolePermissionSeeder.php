<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Auth module
            'auth.login' => 'Se connecter au système',
            'auth.register' => 'Créer un compte utilisateur',
            'auth.logout' => 'Se déconnecter',

            // User profile management
            'profile.view' => 'Voir son profil',
            'profile.edit' => 'Modifier son profil',
            'profile.change_password' => 'Changer son mot de passe',

            // Team management
            'team.view' => 'Voir les équipes',
            'team.create' => 'Créer une équipe',
            'team.edit' => 'Modifier une équipe',
            'team.delete' => 'Supprimer une équipe',
            'team.manage_members' => 'Gérer les membres d\'équipe',
            'team.invite_members' => 'Inviter des membres',

            // User management
            'user.view' => 'Voir les utilisateurs',
            'user.create' => 'Créer un utilisateur',
            'user.edit' => 'Modifier un utilisateur',
            'user.delete' => 'Supprimer un utilisateur',
            'user.assign_role' => 'Assigner des rôles',
            'user.manage_permissions' => 'Gérer les permissions',

            // Project management
            'projet.view' => 'Voir les projets',
            'projet.create' => 'Créer un projet',
            'projet.edit' => 'Modifier un projet',
            'projet.delete' => 'Supprimer un projet',
            'projet.assign' => 'Assigner des projets',

            // Task management
            'tache.view' => 'Voir les tâches',
            'tache.create' => 'Créer une tâche',
            'tache.edit' => 'Modifier une tâche',
            'tache.delete' => 'Supprimer une tâche',
            'tache.assign' => 'Assigner des tâches',
            'tache.validate' => 'Valider des tâches',
            'tache.change_status' => 'Changer le statut des tâches',

            // Reports
            'rapport.view' => 'Voir les rapports',
            'rapport.create' => 'Créer des rapports',
            'rapport.export' => 'Exporter des rapports',

            // Administration
            'admin.system' => 'Administration système',
            'admin.settings' => 'Paramètres système',
            'admin.logs' => 'Voir les logs système',
        ];

        foreach ($permissions as $name => $description) {
            Permission::create([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // Create roles and assign permissions
        $roles = [
            'super_admin' => [
                'description' => 'Administrateur système avec tous les droits',
                'permissions' => array_keys($permissions), // All permissions
            ],
            'manager' => [
                'description' => 'Manager avec droits de gestion de projets et équipes',
                'permissions' => [
                    'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
                    'team.view', 'team.create', 'team.edit', 'team.delete', 'team.manage_members', 'team.invite_members',
                    'user.view', 'user.create', 'user.edit', 'user.assign_role',
                    'projet.view', 'projet.create', 'projet.edit', 'projet.delete', 'projet.assign',
                    'tache.view', 'tache.create', 'tache.edit', 'tache.assign', 'tache.validate', 'tache.change_status',
                    'rapport.view', 'rapport.create', 'rapport.export',
                ],
            ],
            'responsable_n1' => [
                'description' => 'Responsable niveau 1 - supervision directe',
                'permissions' => [
                    'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
                    'team.view', 'team.manage_members',
                    'user.view',
                    'projet.view', 'projet.edit',
                    'tache.view', 'tache.create', 'tache.edit', 'tache.assign', 'tache.validate', 'tache.change_status',
                    'rapport.view', 'rapport.create',
                ],
            ],
            'responsable_n2' => [
                'description' => 'Responsable niveau 2 - coordination inter-équipes',
                'permissions' => [
                    'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
                    'team.view',
                    'user.view',
                    'projet.view', 'projet.edit',
                    'tache.view', 'tache.create', 'tache.edit', 'tache.assign', 'tache.change_status',
                    'rapport.view',
                ],
            ],
            'cadre' => [
                'description' => 'Cadre avec exécution et supervision limitée',
                'permissions' => [
                    'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
                    'team.view',
                    'user.view',
                    'projet.view',
                    'tache.view', 'tache.edit', 'tache.change_status',
                    'rapport.view',
                ],
            ],
            'stagiaire' => [
                'description' => 'Stagiaire avec accès en lecture et exécution de base',
                'permissions' => [
                    'auth.login', 'auth.logout', 'profile.view', 'profile.edit', 'profile.change_password',
                    'team.view',
                    'projet.view',
                    'tache.view', 'tache.change_status',
                ],
            ],
        ];

        foreach ($roles as $roleName => $roleData) {
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($roleData['permissions']);
        }
    }
}

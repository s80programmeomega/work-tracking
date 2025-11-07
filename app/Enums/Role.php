<?php

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case MEMBER = 'member';
    case VIEWER = 'viewer';
    case CADRE = 'cadre';
    case STAGIAIRE = 'stagiaire';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrateur',
            self::ADMIN => 'Administrateur',
            self::MANAGER => 'Manager',
            self::MEMBER => 'Member',
            self::VIEWER => 'viewer',
            self::CADRE => 'Cadre',
            self::STAGIAIRE => 'Stagiaire',
        };
    }

    public function permissions(): array
    {
        return match($this) {
            self::SUPER_ADMIN => ['*'], // All permissions
            self::ADMIN => [
                'projets.create', 'projets.update', 'projets.delete',
                'activites.create', 'activites.update', 'activites.delete',
                'taches.create', 'taches.update', 'taches.delete',
                'users.view', 'users.assign', 'reports.view',
                'can_invite_members', "can_create_projects", "can_manage_settings"
            ],
            self::MANAGER => [
                'projets.create', 'projets.update',
                'activites.create', 'activites.update',
                'taches.create', 'taches.update', 'taches.delete',
                'users.view', 'users.assign', 'reports.view',
                'can_invite_members', "can_create_projects", "can_manage_settings"

            ],
            self::MEMBER => [
                'activites.view', 'activites.update',
                'taches.create', 'taches.update', 'taches.delete',
                'taches.validate'
            ],
            self::VIEWER => [
                'taches.view','activites.view','taches.comment'
            ],
            self::CADRE => [
                'taches.view', 'taches.update', 'taches.comment'
            ],
            self::STAGIAIRE => [
                'taches.view', 'taches.comment'
            ],
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case DIRECTEUR = 'directeur';
    case UTILISATEUR = 'utilisateur';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrateur',
            self::DIRECTEUR => 'Directeur',
            self::UTILISATEUR => 'Utilisateur',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => ['*'],
            self::DIRECTEUR => [
                'workspaces.create',
                'workspaces.update',
                'workspaces.delete',
                'workspaces.manage_members',
                'projets.view',
                'projets.create',
                'projets.update',
                'projets.delete',
                'can_view_all_projects',
                'can_create_projects',
                'can_invite_members',
                'can_manage_settings',
                'can_delete_members',
            ],
            self::UTILISATEUR => [
                'workspaces.create',
            ],
        };
    }
}

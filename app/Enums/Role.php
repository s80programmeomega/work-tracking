<?php

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case RESPONSABLE_N1 = 'responsable_n1';
    case RESPONSABLE_N2 = 'responsable_n2';
    case CADRE = 'cadre';
    case STAGIAIRE = 'stagiaire';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrateur',
            self::ADMIN => 'Administrateur',
            self::MANAGER => 'Manager',
            self::RESPONSABLE_N1 => 'Responsable N1',
            self::RESPONSABLE_N2 => 'Responsable N2',
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
                'users.view', 'users.assign', 'reports.view'
            ],
            self::MANAGER => [
                'projets.create', 'projets.update',
                'activites.create', 'activites.update',
                'taches.create', 'taches.update', 'taches.delete',
                'users.view', 'users.assign', 'reports.view'
            ],
            self::RESPONSABLE_N1 => [
                'activites.view', 'activites.update',
                'taches.create', 'taches.update', 'taches.delete',
                'taches.validate'
            ],
            self::RESPONSABLE_N2 => [
                'taches.view', 'taches.update', 'taches.validate'
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

<?php

declare(strict_types=1);

namespace App\Permissions;

/**
 * Source unique des libellés affichables pour les 10 rôles de l'application
 * (3 rôles globaux Spatie + 7 rôles contextuels stockés via role_id en pivot).
 *
 * Les identifiants de rôle ('manager', 'cadre', etc.) sont le contrat interne
 * stable utilisé dans toute la logique d'autorisation (hasRole, role_id en
 * pivot, seeders) — ils ne changent jamais. Seul le libellé affiché change ici.
 *
 * Mirroir JS : resources/js/permissions/Permission.js (RoleLabels / getRoleLabel).
 */
final class RoleLabel
{
    /** @return list<string> Tous les noms de rôle connus (globaux + contextuels). */
    public static function roles(): array
    {
        return [
            'super_admin',
            'directeur',
            'utilisateur',
            'owner',
            'manager',
            'cadre',
            'task_responsable',
            'collaborateur',
            'stagiaire',
            'observateur',
        ];
    }

    public static function label(string $role): string
    {
        if (! in_array($role, self::roles(), true)) {
            return $role;
        }

        return __("roles.{$role}");
    }

    /** @return array<string,string> Nom de rôle => libellé traduit, pour listes déroulantes. */
    public static function all(): array
    {
        return array_combine(
            self::roles(),
            array_map(fn (string $role): string => self::label($role), self::roles())
        );
    }
}

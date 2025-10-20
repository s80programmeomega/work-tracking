<?php

namespace App\Enums;

enum ProjetMemberRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Propriétaire',
            self::ADMIN => 'Administrateur',
            self::MEMBER => 'Membre',
            self::VIEWER => 'Observateur',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::OWNER => [
                'can_edit' => true,
                'can_delete' => true,
                'can_invite' => true,
                'can_manage_members' => true,
                'can_archive' => true,
            ],
            self::ADMIN => [
                'can_edit' => true,
                'can_delete' => false,
                'can_invite' => true,
                'can_manage_members' => true,
                'can_archive' => false,
            ],
            self::MEMBER => [
                'can_edit' => true,
                'can_delete' => false,
                'can_invite' => false,
                'can_manage_members' => false,
                'can_archive' => false,
            ],
            self::VIEWER => [
                'can_edit' => false,
                'can_delete' => false,
                'can_invite' => false,
                'can_manage_members' => false,
                'can_archive' => false,
            ],
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::OWNER => 4,
            self::ADMIN => 3,
            self::MEMBER => 2,
            self::VIEWER => 1,
        };
    }

    public function isHigherThan(ProjetMemberRole $role): bool
    {
        return $this->level() > $role->level();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

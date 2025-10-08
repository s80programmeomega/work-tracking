<?php

namespace App\Enums;

enum ProjetVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case TEAM = 'team';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Privé',
            self::TEAM => 'Équipe',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PUBLIC => 'Visible par tous les utilisateurs',
            self::PRIVATE => 'Visible uniquement par le responsable',
            self::TEAM => 'Visible par les membres de l\'équipe',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

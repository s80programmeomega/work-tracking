<?php

namespace App\Enums;

enum ProjetStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::ARCHIVED => 'Archivé',
            self::COMPLETED => 'Terminé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'blue',
            self::ARCHIVED => 'gray',
            self::COMPLETED => 'green',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Enums;

enum TacheStatut: string
{
 case A_FAIRE = 'a_faire';
    case EN_COURS = 'en_cours';
    case EN_ATTENTE = 'en_attente';
    case TERMINE = 'termine';
    case ANNULE = 'annule';

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    { return match($this) {
            self::A_FAIRE => 'À faire',
            self::EN_COURS => 'En cours',
            self::EN_ATTENTE => 'En attente',
            self::TERMINE => 'Terminé',
            self::ANNULE => 'Annulé',
        };
    }

    /**
     * Get color for UI display
     */
    public function color(): string
    {
        return match($this) {
            self::A_FAIRE => 'orange',
            self::EN_COURS => 'blue',
            self::EN_ATTENTE => 'yellow',
            self::TERMINE => 'green',
            self::ANNULE => 'red',
        };
    }

    /**
     * Get badge color class for TailwindCSS
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::A_FAIRE => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            self::EN_COURS => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            self::TERMINE => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        };
    }
    public function icon(): string
    {
        return match($this) {
            self::A_FAIRE => 'circle',
            self::EN_COURS => 'clock',
            self::EN_ATTENTE => 'pause',
            self::TERMINE => 'check-circle',
            self::ANNULE => 'x-circle',
        };
    }
}

<?php

namespace App\Enums;

enum TachePriorite: string
{
    case FAIBLE = 'faible';
    case NORMALE = 'normale';
    case MOYENNE = 'moyenne';
    case ELEVEE = 'elevee';
    case CRITIQUE = 'critique';

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
    {
        return match($this) {
            self::FAIBLE => 'Faible',
            self::NORMALE => 'Normale',
            self::MOYENNE => 'Moyenne',
            self::ELEVEE => 'Élevée',
            self::CRITIQUE => 'Critique',
        };
    }

    /**
     * Get color for UI display
     */
    public function color(): string
    {
        return match($this) {
            self::FAIBLE => '#10B981',     // Green
            self::NORMALE => '#0babf5ff',    // Amber
            self::MOYENNE => '#F59E0B',    // Amber
            self::ELEVEE => '#F97316',     // Orange
            self::CRITIQUE => '#EF4444',   // Red
        };
    }

    /**
     * Get icon for UI display
     */
    public function icon(): string
    {
        return match($this) {
            self::FAIBLE => 'arrow-down',
            self::MOYENNE => 'minus',
            self::ELEVEE => 'arrow-up',
            self::CRITIQUE => 'exclamation',
        };
    }

    /**
     * Get badge color class for TailwindCSS
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::FAIBLE => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::MOYENNE => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
            self::ELEVEE => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            self::CRITIQUE => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        };
    }
}

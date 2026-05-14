<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActiviteFactory extends Factory
{
    private static array $noms = [
        'Cadrage et analyse des besoins',
        'Conception et architecture',
        'Développement backend',
        'Développement frontend',
        'Intégration et tests',
        'Recette utilisateur',
        'Déploiement et mise en production',
        'Formation et transfert de compétences',
        'Documentation technique',
        'Suivi post-déploiement',
    ];

    private static array $descriptions = [
        'Recueil et formalisation des besoins fonctionnels et techniques auprès des parties prenantes.',
        'Conception de l\'architecture technique et fonctionnelle de la solution.',
        'Développement des services, API et couche de données selon les spécifications validées.',
        'Développement des interfaces utilisateur et intégration avec le backend.',
        'Tests d\'intégration, de performance et de sécurité de la solution complète.',
        'Validation de la solution par les utilisateurs finaux et correction des anomalies.',
        'Déploiement en production et basculement depuis l\'ancien système.',
        'Formation des utilisateurs et transfert de compétences aux équipes support.',
        'Rédaction de la documentation technique, fonctionnelle et des guides utilisateurs.',
        'Surveillance de la solution en production et traitement des incidents.',
    ];

    public function definition(): array
    {
        $index = fake()->numberBetween(0, count(self::$noms) - 1);

        return [
            'projet_id' => Projet::factory(),
            'nom' => self::$noms[$index],
            'description' => self::$descriptions[$index],
            'responsable_id' => User::factory(),
            'date_debut' => now()->subDays(fake()->numberBetween(0, 20)),
            'date_fin' => now()->addMonths(fake()->numberBetween(1, 3)),
        ];
    }
}

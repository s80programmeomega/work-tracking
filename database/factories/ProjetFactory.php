<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjetFactory extends Factory
{
    private static array $noms = [
        'Refonte du système de facturation',
        'Migration infrastructure cloud',
        'Développement application mobile RH',
        'Déploiement ERP nouvelle génération',
        'Audit et conformité RGPD',
        'Portail client self-service',
        'Intégration API partenaires',
        'Tableau de bord analytique',
        'Modernisation base de données',
        'Système de gestion documentaire',
    ];

    private static array $descriptions = [
        'Remplacement du système legacy par une solution moderne, scalable et maintenable.',
        'Migration progressive des serveurs on-premise vers une architecture cloud hybride.',
        'Développement d\'une application mobile permettant aux employés de gérer leurs demandes RH.',
        'Déploiement et paramétrage du nouvel ERP pour l\'ensemble des départements.',
        'Mise en conformité des processus et systèmes avec le règlement général sur la protection des données.',
        'Création d\'un espace client permettant le suivi en temps réel des commandes et factures.',
        'Développement et documentation des connecteurs API pour les partenaires stratégiques.',
        'Conception d\'un tableau de bord centralisé pour le pilotage des indicateurs clés.',
        'Optimisation et migration de la base de données vers PostgreSQL avec mise en place de réplication.',
        'Mise en place d\'un système centralisé de gestion et d\'archivage des documents.',
    ];

    public function definition(): array
    {
        $index = fake()->numberBetween(0, count(self::$noms) - 1);

        return [
            'workspace_id' => Workspace::factory(),
            'nom' => self::$noms[$index],
            'description' => self::$descriptions[$index],
            'responsable_id' => User::factory(),
            'date_debut' => now()->subDays(fake()->numberBetween(0, 30)),
            'date_fin' => now()->addMonths(fake()->numberBetween(2, 6)),
            'status' => 'active',
        ];
    }
}

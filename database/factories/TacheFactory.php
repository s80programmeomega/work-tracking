<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TachePriorite;
use App\Enums\TacheStatut;
use Illuminate\Database\Eloquent\Factories\Factory;

class TacheFactory extends Factory
{
    private static array $titres = [
        'Rédiger le cahier des charges fonctionnel',
        'Analyser les processus métier existants',
        'Définir les indicateurs de performance',
        'Concevoir le modèle de données',
        'Implémenter l\'authentification et les autorisations',
        'Développer le module de reporting',
        'Configurer l\'environnement de staging',
        'Réaliser les tests de charge',
        'Corriger les anomalies de la recette',
        'Rédiger la documentation API',
        'Former les administrateurs système',
        'Valider les livrables avec le client',
        'Mettre en place la supervision applicative',
        'Optimiser les requêtes base de données',
        'Intégrer le système de notifications',
    ];

    private static array $descriptions = [
        'Formaliser l\'ensemble des exigences fonctionnelles validées avec les parties prenantes.',
        'Cartographier et analyser les processus actuels pour identifier les axes d\'amélioration.',
        'Définir et valider les KPIs permettant de mesurer l\'atteinte des objectifs du projet.',
        'Concevoir le schéma relationnel et les règles de gestion associées.',
        'Mettre en place le système d\'authentification SSO et la gestion des rôles applicatifs.',
        'Développer les fonctionnalités de génération et d\'export des rapports métier.',
        'Préparer et valider l\'environnement de staging pour les tests d\'intégration.',
        'Exécuter les scénarios de charge et analyser les résultats de performance.',
        'Traiter et corriger les anomalies remontées lors de la phase de recette utilisateur.',
        'Documenter tous les endpoints API avec exemples de requêtes et réponses.',
        'Organiser et animer les sessions de formation pour les équipes administrateurs.',
        'Présenter les livrables au client et recueillir les validations formelles.',
        'Mettre en place les outils de monitoring et les alertes de supervision.',
        'Analyser et optimiser les requêtes SQL critiques pour améliorer les performances.',
        'Développer et intégrer le système de notifications email et push.',
    ];

    private static array $objectifs = [
        'Obtenir un document de référence signé par toutes les parties prenantes.',
        'Produire une cartographie complète des processus avec identification des points d\'amélioration.',
        'Disposer d\'un tableau de bord de suivi opérationnel validé par la direction.',
        'Livrer un schéma de base de données validé et documenté.',
        'Garantir un accès sécurisé et tracé à l\'application pour tous les utilisateurs.',
        'Permettre aux managers de générer leurs rapports de suivi de manière autonome.',
        'Disposer d\'un environnement stable et représentatif de la production.',
        'Valider la capacité du système à supporter la charge nominale et les pics d\'utilisation.',
        'Obtenir un taux d\'anomalies bloquantes nul avant le passage en production.',
        'Permettre aux développeurs partenaires d\'intégrer l\'API sans assistance.',
        'Avoir 100% des administrateurs opérationnels sur le nouveau système.',
        'Obtenir le procès-verbal de recette signé par le client.',
        'Garantir une détection des incidents en moins de 5 minutes.',
        'Réduire le temps de réponse des pages critiques de 40%.',
        'Notifier les utilisateurs en temps réel sur tous les événements les concernant.',
    ];

    public function definition(): array
    {
        $index = fake()->numberBetween(0, count(self::$titres) - 1);

        return [
            'titre' => self::$titres[$index],
            'description' => self::$descriptions[$index],
            'objectif' => self::$objectifs[$index],
            'statut' => TacheStatut::A_FAIRE->value,
            'priorite' => TachePriorite::MOYENNE->value,
            'echeance' => now()->addDays(fake()->numberBetween(7, 30)),
            'taux_realisation' => 0,
            'validation_n1_required' => true,
            'validation_n2_required' => true,
        ];
    }

    public function enCours(): static
    {
        return $this->state([
            'statut' => TacheStatut::EN_COURS->value,
            'taux_realisation' => fake()->numberBetween(20, 80),
            'date_debut' => now()->subDays(fake()->numberBetween(3, 10)),
        ]);
    }

    public function termine(): static
    {
        return $this->state([
            'statut' => TacheStatut::TERMINE->value,
            'taux_realisation' => 100,
            'date_debut' => now()->subDays(fake()->numberBetween(10, 20)),
            'date_fin_reelle' => now()->subDays(fake()->numberBetween(1, 5)),
        ]);
    }

    public function enRetard(): static
    {
        return $this->state([
            'statut' => TacheStatut::EN_RETARD->value,
            'echeance' => now()->subDays(fake()->numberBetween(1, 7)),
        ]);
    }

    public function critique(): static
    {
        return $this->state(['priorite' => TachePriorite::CRITIQUE->value]);
    }
}

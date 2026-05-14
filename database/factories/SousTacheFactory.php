<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SousTache;
use App\Models\Tache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SousTache>
 */
class SousTacheFactory extends Factory
{
    private static array $titres = [
        ['titre' => 'Préparation',        'description' => 'Rassembler les ressources, outils et informations nécessaires avant de démarrer.'],
        ['titre' => 'Exécution',          'description' => 'Réaliser les travaux conformément aux spécifications validées.'],
        ['titre' => 'Livraison',          'description' => 'Finaliser, documenter et livrer le résultat au responsable pour validation.'],
        ['titre' => 'Rédaction',          'description' => 'Rédiger le document ou rapport attendu en respectant le format défini.'],
        ['titre' => 'Revue',              'description' => 'Relire, contrôler la qualité et soumettre les corrections nécessaires.'],
        ['titre' => 'Tests',              'description' => 'Exécuter les scénarios de test et consigner les résultats obtenus.'],
        ['titre' => 'Correction',         'description' => 'Traiter les anomalies identifiées lors de la phase de test ou de revue.'],
        ['titre' => 'Validation client',  'description' => 'Présenter le livrable au client et recueillir sa validation formelle.'],
        ['titre' => 'Déploiement',        'description' => 'Mettre en place la solution en environnement cible selon le plan de déploiement.'],
        ['titre' => 'Documentation',      'description' => 'Produire la documentation associée au livrable pour les équipes concernées.'],
    ];

    public function definition(): array
    {
        $item = fake()->randomElement(self::$titres);

        return [
            'tache_id' => Tache::factory(),
            'titre' => $item['titre'],
            'description' => $item['description'],
            'statut' => 'a_faire',
            'progression' => 0,
            'poids' => 0,
            'ordre' => fake()->numberBetween(1, 10),
        ];
    }

    public function termine(): static
    {
        return $this->state(['statut' => 'termine', 'progression' => 100]);
    }

    public function enRetard(): static
    {
        return $this->state([
            'statut' => 'en_retard',
            'date_echeance' => now()->subDays(fake()->numberBetween(1, 5)),
        ]);
    }

    public function enCours(): static
    {
        return $this->state([
            'statut' => 'en_cours',
            'progression' => fake()->numberBetween(20, 80),
        ]);
    }

    public function withPoids(int $poids): static
    {
        return $this->state(['poids' => $poids]);
    }
}

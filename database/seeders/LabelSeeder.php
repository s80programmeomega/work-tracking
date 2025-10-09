<?php

namespace Database\Seeders;

use App\Models\Label;
use Illuminate\Database\Seeder;

class LabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labels = [
            [
                'nom' => 'Bug',
                'couleur' => '#EF4444',
                'description' => 'Erreur ou problème à corriger',
                'ordre' => 0,
            ],
            [
                'nom' => 'Feature',
                'couleur' => '#3B82F6',
                'description' => 'Nouvelle fonctionnalité',
                'ordre' => 1,
            ],
            [
                'nom' => 'Enhancement',
                'couleur' => '#8B5CF6',
                'description' => 'Amélioration d\'une fonctionnalité existante',
                'ordre' => 2,
            ],
            [
                'nom' => 'Documentation',
                'couleur' => '#06B6D4',
                'description' => 'Documentation ou tutoriel',
                'ordre' => 3,
            ],
            [
                'nom' => 'Urgent',
                'couleur' => '#F59E0B',
                'description' => 'Tâche urgente',
                'ordre' => 4,
            ],
            [
                'nom' => 'Design',
                'couleur' => '#EC4899',
                'description' => 'Travail de design UI/UX',
                'ordre' => 5,
            ],
            [
                'nom' => 'Backend',
                'couleur' => '#10B981',
                'description' => 'Développement backend',
                'ordre' => 6,
            ],
            [
                'nom' => 'Frontend',
                'couleur' => '#14B8A6',
                'description' => 'Développement frontend',
                'ordre' => 7,
            ],
            [
                'nom' => 'Testing',
                'couleur' => '#6366F1',
                'description' => 'Tests unitaires ou d\'intégration',
                'ordre' => 8,
            ],
            [
                'nom' => 'Refactoring',
                'couleur' => '#84CC16',
                'description' => 'Refonte du code',
                'ordre' => 9,
            ],
        ];

        foreach ($labels as $labelData) {
            Label::create($labelData);
        }

        $this->command->info('✓ ' . count($labels) . ' labels créés avec succès!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Crée/maj les plans d'abonnement (idempotent par slug).
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'nom_fr' => 'Gratuit',
                'nom_en' => 'Free',
                'description_fr' => 'Pour démarrer : fonctionnalités essentielles avec des limites.',
                'description_en' => 'To get started: essential features with limits.',
                'is_free' => true,
                'price' => 0,
                'currency' => 'XAF',
                'billing_period' => 'monthly',
                'max_members' => 5,
                'max_storage_mb' => 100,
                'max_file_size_mb' => 2,
                'features' => ['5 membres', '100 Mo de stockage', 'Fichiers jusqu\'à 2 Mo'],
                'is_active' => true,
                'position' => 0,
            ],
            [
                'slug' => 'starter',
                'nom_fr' => 'Starter',
                'nom_en' => 'Starter',
                'description_fr' => 'Pour les petites équipes qui grandissent.',
                'description_en' => 'For small growing teams.',
                'is_free' => false,
                'price' => 15000,
                'currency' => 'XAF',
                'billing_period' => 'monthly',
                'max_members' => 25,
                'max_storage_mb' => 5000,
                'max_file_size_mb' => 25,
                'features' => ['25 membres', '5 Go de stockage', 'Fichiers jusqu\'à 25 Mo', 'Support prioritaire'],
                'is_active' => true,
                'position' => 1,
            ],
            [
                'slug' => 'pro',
                'nom_fr' => 'Pro',
                'nom_en' => 'Pro',
                'description_fr' => 'Pour les organisations sans limites.',
                'description_en' => 'For organizations without limits.',
                'is_free' => false,
                'price' => 50000,
                'currency' => 'XAF',
                'billing_period' => 'monthly',
                'max_members' => -1,
                'max_storage_mb' => -1,
                'max_file_size_mb' => 100,
                'features' => ['Membres illimités', 'Stockage illimité', 'Fichiers jusqu\'à 100 Mo', 'Support dédié'],
                'is_active' => true,
                'position' => 2,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}

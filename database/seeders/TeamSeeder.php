<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Get users to assign as team managers
        $manager = User::where('role', 'manager')->first();
        $responsable1 = User::where('role', 'responsable_n1')->first();
        $responsable2 = User::where('role', 'responsable_n2')->first();

        // Create teams
        $teams = [
            [
                'nom' => 'Équipe Développement',
                'description' => 'Équipe en charge du développement des applications',
                'responsable_id' => $manager->id,
            ],
            [
                'nom' => 'Équipe Support',
                'description' => 'Équipe de support technique et maintenance',
                'responsable_id' => $responsable1->id,
            ],
            [
                'nom' => 'Équipe Marketing',
                'description' => 'Équipe marketing et communication',
                'responsable_id' => $responsable2->id,
            ],
        ];

        foreach ($teams as $teamData) {
            Team::create($teamData);
        }

        // Assign some users to teams
        $devTeam = Team::where('nom', 'Équipe Développement')->first();
        $supportTeam = Team::where('nom', 'Équipe Support')->first();

        // Update users with team assignments
        User::where('role', 'cadre')->update(['team_id' => $devTeam->id]);
        User::where('role', 'stagiaire')->update(['team_id' => $supportTeam->id]);

        // Assign phone numbers to some users
        User::where('email', 'admin@worktracking.com')->update(['numero_telephone' => '+33123456789']);
        User::where('email', 'manager@worktracking.com')->update(['numero_telephone' => '+33123456790']);
        User::where('email', 'resp1@worktracking.com')->update(['numero_telephone' => '+33123456791']);
    }
}

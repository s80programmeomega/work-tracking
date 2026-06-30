<?php

namespace Database\Seeders;

use App\Models\Projet;
use App\Models\Team;
use App\Models\TeamActivity;
use App\Models\TeamAnnouncement;
use App\Models\TeamMessage;
use App\Models\TeamPresence;
use App\Models\TeamResource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Get users
        $admin = User::where('email', 'admin@worktracking.com')->first();
        $manager = User::where('role', 'manager')->first();
        $responsable1 = User::where('role', 'responsable_n1')->first();
        $responsable2 = User::where('role', 'responsable_n2')->first();
        $cadre = User::where('role', 'cadre')->first();
        $stagiaire = User::where('role', 'stagiaire')->first();

        // Get some projects to link
        $projets = Projet::limit(3)->get();

        // Create teams with the new collaboration model
        $teams = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Équipe Développement Web',
                'description' => 'Équipe en charge du développement des applications web et mobile. Nous travaillons avec les dernières technologies pour créer des solutions innovantes.',
                'owner_id' => $admin->id,
                'project_id' => $projets->count() > 0 ? $projets[0]->id : null,
                'settings' => json_encode([
                    'allow_member_invite' => true,
                    'require_approval' => false,
                    'default_notification' => true,
                ]),
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Équipe Support & Maintenance',
                'description' => 'Équipe dédiée au support technique, maintenance et résolution des incidents. Disponible 24/7 pour garantir la qualité de service.',
                'owner_id' => $manager->id ?? $admin->id,
                'project_id' => $projets->count() > 1 ? $projets[1]->id : null,
                'settings' => json_encode([
                    'allow_member_invite' => true,
                    'require_approval' => true,
                    'default_notification' => true,
                ]),
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Équipe Marketing Digital',
                'description' => 'Équipe marketing et communication digitale. Création de contenu, stratégie social media et campagnes publicitaires.',
                'owner_id' => $responsable1->id ?? $admin->id,
                'project_id' => $projets->count() > 2 ? $projets[2]->id : null,
                'settings' => json_encode([
                    'allow_member_invite' => false,
                    'require_approval' => true,
                    'default_notification' => false,
                ]),
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Équipe Innovation & R&D',
                'description' => 'Recherche et développement de nouvelles technologies. Veille technologique et prototypage de solutions innovantes.',
                'owner_id' => $responsable2->id ?? $admin->id,
                'project_id' => null,
                'settings' => json_encode([
                    'allow_member_invite' => false,
                    'require_approval' => true,
                    'default_notification' => true,
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($teams as $index => $teamData) {
            $team = Team::create($teamData);

            // Add owner as member with owner role
            $team->members()->attach($team->owner_id, [
                'role' => 'owner',
                'joined_at' => now(),
                'notifications_enabled' => true,
                'permissions' => json_encode([]),
            ]);

            // Create presence for owner
            TeamPresence::create([
                'team_id' => $team->id,
                'user_id' => $team->owner_id,
                'status' => 'online',
                'last_seen_at' => now(),
            ]);

            // Log team creation activity
            TeamActivity::create([
                'team_id' => $team->id,
                'user_id' => $team->owner_id,
                'action' => 'team_created',
                'subject_type' => Team::class,
                'subject_id' => $team->id,
                'metadata' => json_encode([
                    'team_name' => $team->name,
                ]),
            ]);

            // Add additional members based on team type
            $additionalMembers = [];

            if ($index === 0 && $cadre) { // Dev team
                $additionalMembers = [
                    ['user' => $cadre, 'role' => 'admin'],
                    ['user' => $stagiaire, 'role' => 'member'],
                ];
            } elseif ($index === 1 && $manager) { // Support team
                $additionalMembers = [
                    ['user' => $responsable1 ?? $cadre, 'role' => 'admin'],
                    ['user' => $stagiaire, 'role' => 'member'],
                ];
            } elseif ($index === 2 && $responsable2) { // Marketing team
                $additionalMembers = [
                    ['user' => $cadre, 'role' => 'member'],
                ];
            }

            foreach ($additionalMembers as $memberData) {
                if (! $memberData['user']) {
                    continue;
                }

                $team->members()->attach($memberData['user']->id, [
                    'role' => $memberData['role'],
                    'joined_at' => now()->subDays(rand(1, 30)),
                    'notifications_enabled' => true,
                    'permissions' => json_encode([]),
                ]);

                // Create presence
                TeamPresence::create([
                    'team_id' => $team->id,
                    'user_id' => $memberData['user']->id,
                    'status' => rand(0, 1) ? 'online' : 'offline',
                    'last_seen_at' => now()->subMinutes(rand(0, 120)),
                ]);

                // Log member addition
                TeamActivity::create([
                    'team_id' => $team->id,
                    'user_id' => $team->owner_id,
                    'action' => 'member_added',
                    'subject_type' => User::class,
                    'subject_id' => $memberData['user']->id,
                    'metadata' => json_encode([
                        'user_name' => $memberData['user']->nom,
                        'role' => $memberData['role'],
                    ]),
                ]);
            }

            // Add some messages
            $messages = [
                'Bienvenue dans l\'équipe! 👋',
                'N\'hésitez pas à poser vos questions ici.',
                'Réunion prévue demain à 10h.',
            ];

            foreach ($messages as $messageIndex => $content) {
                TeamMessage::create([
                    'team_id' => $team->id,
                    'user_id' => $team->owner_id,
                    'uuid' => Str::uuid(),
                    'content' => $content,
                    'is_pinned' => $messageIndex === 0,
                    'created_at' => now()->subHours(rand(1, 48)),
                ]);
            }

            // Add an announcement for the first team
            if ($index === 0) {
                TeamAnnouncement::create([
                    'team_id' => $team->id,
                    'user_id' => $team->owner_id,
                    'title' => '🎉 Nouveau sprint commence lundi!',
                    'content' => 'Le prochain sprint démarre lundi prochain. Pensez à préparer vos user stories et à mettre à jour le backlog.',
                    'priority' => 'high',
                    'published_at' => now(),
                    'expires_at' => now()->addDays(7),
                ]);
            }

            // Add some resources
            $resources = [
                [
                    'name' => 'Guide d\'onboarding',
                    'type' => 'document',
                    'description' => 'Documentation pour les nouveaux membres',
                ],
                [
                    'name' => 'Template de rapport',
                    'type' => 'template',
                    'description' => 'Template pour les rapports hebdomadaires',
                ],
            ];

            foreach ($resources as $resourceData) {
                TeamResource::create([
                    'team_id' => $team->id,
                    'user_id' => $team->owner_id,
                    'uuid' => Str::uuid(),
                    'name' => $resourceData['name'],
                    'type' => $resourceData['type'],
                    'description' => $resourceData['description'],
                    'content' => json_encode(['sample' => 'data']),
                ]);
            }
        }

        $this->command->info('✅ Teams seeded successfully with members, messages, and resources!');
    }
}

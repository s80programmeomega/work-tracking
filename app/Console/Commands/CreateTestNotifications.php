<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\DatabaseNotification;

class CreateTestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:create-test {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test notifications for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id') ?? User::first()?->id;

        if (!$userId) {
            $this->error('No users found in database');
            return 1;
        }

        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }

        $this->info("Creating test notifications for user: {$user->nom} (ID: {$user->id})");

        $testNotifications = [
            [
                'type' => 'task_assigned',
                'title' => 'Nouvelle tâche assignée',
                'message' => 'Marie Dubois vous a assigné la tâche "Finaliser le rapport Q1"',
                'task_id' => 1,
                'task_title' => 'Finaliser le rapport Q1',
                'action_url' => '/taches',
            ],
            [
                'type' => 'task_due_soon',
                'title' => 'Échéance proche',
                'message' => 'La tâche "Préparer la présentation" arrive à échéance dans 2 heures',
                'task_id' => 2,
                'task_title' => 'Préparer la présentation',
                'hours_until_due' => 2,
                'action_url' => '/taches',
            ],
            [
                'type' => 'mentioned_in_comment',
                'title' => 'Vous avez été mentionné',
                'message' => 'Jean Martin vous a mentionné dans un commentaire sur "Projet Alpha"',
                'comment_id' => 1,
                'task_id' => 3,
                'action_url' => '/taches',
            ],
            [
                'type' => 'comment_added',
                'title' => 'Nouveau commentaire',
                'message' => 'Sophie Leroux a ajouté un commentaire sur votre tâche "Révision du code"',
                'comment_id' => 2,
                'task_id' => 4,
                'action_url' => '/taches',
            ],
            [
                'type' => 'project_updated',
                'title' => 'Projet mis à jour',
                'message' => 'Le projet "Refonte du site web" a été mis à jour',
                'project_id' => 1,
                'project_title' => 'Refonte du site web',
                'action_url' => '/projets',
            ],
            [
                'type' => 'deadline_approaching',
                'title' => 'Attention : Échéance imminente',
                'message' => 'La tâche "Validation des tests" doit être terminée aujourd\'hui',
                'task_id' => 5,
                'task_title' => 'Validation des tests',
                'action_url' => '/taches',
            ],
            [
                'type' => 'document_uploaded',
                'title' => 'Nouveau document',
                'message' => 'Un nouveau document "Spécifications_v2.pdf" a été ajouté au projet',
                'document_id' => 1,
                'document_name' => 'Spécifications_v2.pdf',
                'action_url' => '/projets',
            ],
            [
                'type' => 'task_completed',
                'title' => 'Tâche terminée',
                'message' => 'Lucas Bernard a terminé la tâche "Migration base de données"',
                'task_id' => 6,
                'task_title' => 'Migration base de données',
                'action_url' => '/taches',
            ],
        ];

        foreach ($testNotifications as $notifData) {
            DatabaseNotification::create([
                'id' => \Str::uuid(),
                'type' => 'App\\Notifications\\TestNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => $notifData,
                'read_at' => null,
                'created_at' => now()->subMinutes(rand(1, 60)),
                'updated_at' => now(),
            ]);
        }

        $this->info('✓ Created ' . count($testNotifications) . ' test notifications');
        $this->info('Run: php artisan tinker');
        $this->info('Then: User::find(' . $user->id . ')->unreadNotifications');

        return 0;
    }
}

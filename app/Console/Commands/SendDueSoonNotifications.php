<?php

namespace App\Console\Commands;

use App\Models\Tache;
use Illuminate\Console\Command;

class SendDueSoonNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-due-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for tasks due soon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Tasks due in 24 hours
        $tasksDueIn24h = Tache::whereBetween('echeance', [
            now(),
            now()->addHours(24),
        ])->with('assignees')->get();

        foreach ($tasksDueIn24h as $task) {
            $hoursUntilDue = now()->diffInHours($task->echeance);

            foreach ($task->assignees as $assignee) {
                // TODO: Uncomment when TaskDueSoonNotification is created
                // $assignee->notify(new TaskDueSoonNotification($task, $hoursUntilDue));
            }

            $this->info("Would send notifications for task: {$task->titre}");
        }

        $this->info('Done sending due soon notifications');
    }
}

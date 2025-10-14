<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'in_app_enabled',
        'email_enabled',
        'push_enabled',
        'task_assigned_in_app',
        'task_assigned_email',
        'task_assigned_push',
        'task_due_soon_in_app',
        'task_due_soon_email',
        'task_due_soon_push',
        'task_completed_in_app',
        'task_completed_email',
        'task_completed_push',
        'mentioned_in_comment_in_app',
        'mentioned_in_comment_email',
        'mentioned_in_comment_push',
        'comment_added_in_app',
        'comment_added_email',
        'comment_added_push',
        'project_updated_in_app',
        'project_updated_email',
        'project_updated_push',
        'deadline_approaching_in_app',
        'deadline_approaching_email',
        'deadline_approaching_push',
        'document_uploaded_in_app',
        'document_uploaded_email',
        'document_uploaded_push',
        'team_announcements',
        'team_events',
        'team_resources',
        'team_member_added',
        'digest_frequency',
        'digest_time',
        'digest_day_of_week',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'task_assigned_in_app' => 'boolean',
        'task_assigned_email' => 'boolean',
        'task_assigned_push' => 'boolean',
        'task_due_soon_in_app' => 'boolean',
        'task_due_soon_email' => 'boolean',
        'task_due_soon_push' => 'boolean',
        'task_completed_in_app' => 'boolean',
        'task_completed_email' => 'boolean',
        'task_completed_push' => 'boolean',
        'mentioned_in_comment_in_app' => 'boolean',
        'mentioned_in_comment_email' => 'boolean',
        'mentioned_in_comment_push' => 'boolean',
        'comment_added_in_app' => 'boolean',
        'comment_added_email' => 'boolean',
        'comment_added_push' => 'boolean',
        'project_updated_in_app' => 'boolean',
        'project_updated_email' => 'boolean',
        'project_updated_push' => 'boolean',
        'deadline_approaching_in_app' => 'boolean',
        'deadline_approaching_email' => 'boolean',
        'deadline_approaching_push' => 'boolean',
        'document_uploaded_in_app' => 'boolean',
        'document_uploaded_email' => 'boolean',
        'document_uploaded_push' => 'boolean',
        'team_announcements' => 'boolean',
        'team_events' => 'boolean',
        'team_resources' => 'boolean',
        'team_member_added' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
        'digest_time' => 'datetime:H:i:s',
        'quiet_hours_start' => 'datetime:H:i:s',
        'quiet_hours_end' => 'datetime:H:i:s',
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user should receive notification on specific channel
     */
    public function shouldReceive(string $type, string $channel): bool
    {
        $channelKey = "{$channel}_enabled";
        $typeKey = "{$type}_{$channel}";

        // Check global channel setting
        if (!$this->{$channelKey}) {
            return false;
        }

        // Check specific notification type setting
        return $this->{$typeKey} ?? false;
    }

    /**
     * Check if currently in quiet hours
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled) {
            return false;
        }

        $now = now()->format('H:i:s');
        $start = $this->quiet_hours_start->format('H:i:s');
        $end = $this->quiet_hours_end->format('H:i:s');

        if ($start < $end) {
            return $now >= $start && $now <= $end;
        }

        // Overnight quiet hours
        return $now >= $start || $now <= $end;
    }
}

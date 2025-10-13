<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamMessage;
use App\Models\TeamMessageReaction;
use App\Models\TeamActivity;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class TeamMessageService
{
    /**
     * Get messages for a team
     */
    public function getMessages(Team $team, array $filters = [])
    {
        $query = $team->messages()
            ->with(['user', 'reactions.user', 'replyTo.user'])
            ->withCount('reactions', 'replies');

        if (isset($filters['pinned'])) {
            $query->where('is_pinned', $filters['pinned']);
        }

        if (isset($filters['search'])) {
            $query->where('content', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $perPage = $filters['per_page'] ?? 50;
        return $query->orderBy('created_at', 'asc')->paginate($perPage);
    }

    /**
     * Get pinned messages
     */
    public function getPinnedMessages(Team $team)
    {
        return $team->messages()
            ->pinned()
            ->with(['user', 'reactions.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Send a message
     */
    public function sendMessage(Team $team, User $user, array $data): TeamMessage
    {
        $message = TeamMessage::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'content' => $data['content'],
            'mentions' => $data['mentions'] ?? [],
            'attachments' => $data['attachments'] ?? [],
            'reply_to_id' => $data['reply_to_id'] ?? null,
        ]);

        // Log activity
        TeamActivity::log($team, $user, 'message_sent', $message);

        // TODO: Send notifications to mentioned users
        if (!empty($data['mentions'])) {
            // NotificationService can handle this
        }

        return $message->load(['user', 'replyTo.user']);
    }

    /**
     * Update a message
     */
    public function updateMessage(TeamMessage $message, string $content): TeamMessage
    {
        $message->update(['content' => $content]);
        $message->markAsEdited();

        // Log activity
        TeamActivity::log($message->team, auth()->user(), 'message_edited', $message);

        return $message->fresh(['user', 'reactions.user']);
    }

    /**
     * Delete a message
     */
    public function deleteMessage(TeamMessage $message): bool
    {
        // Log activity before deletion
        TeamActivity::log($message->team, auth()->user(), 'message_deleted', null, [
            'message_uuid' => $message->uuid,
        ]);

        return $message->delete();
    }

    /**
     * Pin a message
     */
    public function pinMessage(TeamMessage $message): bool
    {
        $message->update(['is_pinned' => true]);

        // Log activity
        TeamActivity::log($message->team, auth()->user(), 'message_pinned', $message);

        return true;
    }

    /**
     * Unpin a message
     */
    public function unpinMessage(TeamMessage $message): bool
    {
        $message->update(['is_pinned' => false]);

        // Log activity
        TeamActivity::log($message->team, auth()->user(), 'message_unpinned', $message);

        return true;
    }

    /**
     * Add reaction to message
     */
    public function addReaction(TeamMessage $message, User $user, string $emoji): TeamMessageReaction
    {
        // Check if reaction already exists
        $existing = TeamMessageReaction::where([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ])->first();

        if ($existing) {
            return $existing;
        }

        return TeamMessageReaction::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ]);
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction(TeamMessage $message, User $user, string $emoji): bool
    {
        return TeamMessageReaction::where([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ])->delete() > 0;
    }

    /**
     * Upload attachment
     */
    public function uploadAttachment($file): array
    {
        $path = $file->store('teams/attachments', 'public');

        return [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'url' => Storage::url($path),
        ];
    }

    /**
     * Get message by UUID
     */
    public function getMessageByUuid(string $uuid): TeamMessage
    {
        return TeamMessage::with(['user', 'reactions.user', 'replies.user'])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * Get replies to a message
     */
    public function getReplies(TeamMessage $message)
    {
        return $message->replies()
            ->with(['user', 'reactions.user'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Search messages
     */
    public function searchMessages(Team $team, string $query, int $limit = 50)
    {
        return $team->messages()
            ->where('content', 'LIKE', "%{$query}%")
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent messages
     */
    public function getRecentMessages(Team $team, int $minutes = 60)
    {
        return $team->messages()
            ->recent($minutes)
            ->with(['user', 'reactions.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get message statistics
     */
    public function getMessageStats(Team $team): array
    {
        return [
            'total_messages' => $team->messages()->count(),
            'messages_today' => $team->messages()
                ->whereDate('created_at', today())
                ->count(),
            'messages_this_week' => $team->messages()
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'total_reactions' => TeamMessageReaction::whereHas('message', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            })->count(),
            'top_contributors' => $team->messages()
                ->select('user_id', \DB::raw('count(*) as message_count'))
                ->groupBy('user_id')
                ->orderBy('message_count', 'desc')
                ->with('user')
                ->limit(5)
                ->get(),
        ];
    }
}

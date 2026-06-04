<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Realtime\Chat\MessageDeleted;
use App\Events\Realtime\Chat\MessageSent;
use App\Events\Realtime\Chat\MessageUpdated;
use App\Events\Realtime\Chat\ReactionChanged;
use App\Models\Team;
use App\Models\TeamActivity;
use App\Models\TeamMessage;
use App\Models\TeamMessageReaction;
use App\Models\User;
use App\Notifications\ChatMentionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeamMessageService
{
    public function __construct(protected NotificationService $notificationService) {}

    /**
     * Retourne les messages paginés d'une équipe avec filtres.
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
     * Retourne les messages épinglés d'une équipe.
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
     * Envoie un message, notifie les mentionnés, diffuse l'événement.
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

        TeamActivity::log($team, $user, 'message_sent', $message);

        // Notifier les utilisateurs mentionnés dans le message
        if (! empty($data['mentions'])) {
            $mentioned = User::whereIn('id', $data['mentions'])->get();
            foreach ($mentioned as $recipient) {
                $this->notificationService->sendUnlessSelf(
                    $recipient,
                    $user,
                    new ChatMentionNotification($message, $user)
                );
            }
        }

        $message->loadMissing(['user', 'replyTo.user', 'reactions']);

        broadcast(new MessageSent($message));

        return $message;
    }

    /**
     * Modifie le contenu d'un message et diffuse la mise à jour.
     */
    public function updateMessage(TeamMessage $message, string $content): TeamMessage
    {
        $message->update(['content' => $content]);
        $message->markAsEdited();
        $message->refresh();

        TeamActivity::log($message->team, auth()->user(), 'message_edited', $message);

        broadcast(new MessageUpdated($message));

        return $message->loadMissing(['user', 'reactions.user']);
    }

    /**
     * Supprime un message et diffuse la suppression.
     */
    public function deleteMessage(TeamMessage $message): bool
    {
        $uuid = $message->uuid;
        $teamId = $message->team_id;

        TeamActivity::log($message->team, auth()->user(), 'message_deleted', null, [
            'message_uuid' => $uuid,
        ]);

        $deleted = $message->delete();

        if ($deleted) {
            broadcast(new MessageDeleted($uuid, $teamId));
        }

        return (bool) $deleted;
    }

    /**
     * Épingle un message et journalise l'action.
     */
    public function pinMessage(TeamMessage $message): bool
    {
        $message->update(['is_pinned' => true]);
        TeamActivity::log($message->team, auth()->user(), 'message_pinned', $message);

        return true;
    }

    /**
     * Désépingle un message et journalise l'action.
     */
    public function unpinMessage(TeamMessage $message): bool
    {
        $message->update(['is_pinned' => false]);
        TeamActivity::log($message->team, auth()->user(), 'message_unpinned', $message);

        return true;
    }

    /**
     * Ajoute une réaction emoji et diffuse le changement.
     */
    public function addReaction(TeamMessage $message, User $user, string $emoji): TeamMessageReaction
    {
        $existing = TeamMessageReaction::where([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ])->first();

        if ($existing) {
            return $existing;
        }

        $reaction = TeamMessageReaction::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ]);

        $message->refresh();
        broadcast(new ReactionChanged($message, $emoji, 'added'));

        return $reaction;
    }

    /**
     * Supprime une réaction emoji et diffuse le changement.
     */
    public function removeReaction(TeamMessage $message, User $user, string $emoji): bool
    {
        $deleted = TeamMessageReaction::where([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ])->delete() > 0;

        if ($deleted) {
            $message->refresh();
            broadcast(new ReactionChanged($message, $emoji, 'removed'));
        }

        return $deleted;
    }

    /**
     * Stocke une pièce jointe et retourne ses métadonnées.
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
     * Retourne un message par son UUID (ou lève une 404).
     */
    public function getMessageByUuid(string $uuid): TeamMessage
    {
        return TeamMessage::with(['user', 'reactions.user', 'replies.user', 'replyTo.user'])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * Retourne les réponses à un message.
     */
    public function getReplies(TeamMessage $message)
    {
        return $message->replies()
            ->with(['user', 'reactions.user'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Recherche des messages par mot-clé.
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
     * Retourne les messages récents d'une équipe.
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
     * Marque tous les messages comme lus pour un utilisateur dans une équipe.
     * Met à jour le pivot last_read_at.
     */
    public function markTeamRead(Team $team, User $user): void
    {
        DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    /**
     * Retourne le nombre de messages non lus pour un utilisateur dans une équipe.
     */
    public function getUnreadCount(Team $team, User $user): int
    {
        $member = DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->value('last_read_at');

        if (! $member) {
            return $team->messages()->count();
        }

        return $team->messages()
            ->where('created_at', '>', $member)
            ->count();
    }

    /**
     * Statistiques de messagerie d'une équipe.
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
                ->select('user_id', DB::raw('count(*) as message_count'))
                ->groupBy('user_id')
                ->orderBy('message_count', 'desc')
                ->with('user')
                ->limit(5)
                ->get(),
        ];
    }
}

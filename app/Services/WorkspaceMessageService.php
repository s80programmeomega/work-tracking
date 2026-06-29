<?php

namespace App\Services;

use App\Events\Realtime\WorkspaceChat\WorkspaceMessageDeleted;
use App\Events\Realtime\WorkspaceChat\WorkspaceMessageSent;
use App\Events\Realtime\WorkspaceChat\WorkspaceMessageUpdated;
use App\Events\Realtime\WorkspaceChat\WorkspaceReactionChanged;
use App\Models\User;
use App\Models\WorkspaceChannel;
use App\Models\WorkspaceChannelRead;
use App\Models\WorkspaceMessage;
use App\Models\WorkspaceMessageReaction;
use App\Notifications\WorkspaceChat\WorkspaceMessageEveryoneNotification;
use App\Notifications\WorkspaceChat\WorkspaceMessageMentionNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class WorkspaceMessageService
{
    public function __construct(protected NotificationService $notificationService) {}

    /**
     * Retourne les messages paginés d'un canal, ordonnés du plus récent au plus ancien.
     */
    public function getMessages(WorkspaceChannel $channel, int $perPage = 50): LengthAwarePaginator
    {
        return WorkspaceMessage::query()
            ->where('workspace_channel_id', $channel->id)
            ->with(['user', 'replyTo.user', 'reactions.user'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Envoie un message dans un canal et diffuse l'événement Reverb.
     */
    public function sendMessage(WorkspaceChannel $channel, User $user, array $data): WorkspaceMessage
    {
        $message = WorkspaceMessage::create([
            'workspace_id' => $channel->workspace_id,
            'workspace_channel_id' => $channel->id,
            'user_id' => $user->id,
            'content' => $data['content'] ?? '',
            'mentions' => $data['mentions'] ?? [],
            'attachments' => $data['attachments'] ?? [],
            'reply_to_id' => $data['reply_to_id'] ?? null,
        ]);

        Log::info('Message envoyé dans le canal workspace', [
            'workspace_id' => $channel->workspace_id,
            'channel_type' => $channel->type,
            'user_id' => $user->id,
            'message_uuid' => $message->uuid,
        ]);

        // Notifier tous les membres si @everyone est utilisé
        if (! empty($data['mention_everyone'])) {
            $message->loadMissing('channel.workspace');
            $workspace = $message->channel?->workspace;
            if ($workspace) {
                $members = $workspace->members()->whereNull('workspace_members.banned_at')->get();
                foreach ($members as $recipient) {
                    $this->notificationService->sendUnlessSelf(
                        $recipient,
                        $user,
                        new WorkspaceMessageEveryoneNotification($message, $user)
                    );
                }
            }
        } elseif (! empty($data['mentions'])) {
            // Notifier les membres explicitement mentionnés
            $mentioned = User::whereIn('id', $data['mentions'])->get();
            foreach ($mentioned as $recipient) {
                $this->notificationService->sendUnlessSelf(
                    $recipient,
                    $user,
                    new WorkspaceMessageMentionNotification($message, $user)
                );
            }
        }

        $message->loadMissing(['user', 'replyTo.user', 'reactions', 'channel']);

        broadcast(new WorkspaceMessageSent($message));

        return $message;
    }

    /**
     * Modifie le contenu d'un message et diffuse la mise à jour.
     */
    public function updateMessage(WorkspaceMessage $message, string $content): WorkspaceMessage
    {
        $message->update(['content' => $content]);
        $message->markAsEdited();
        $message->loadMissing('channel');

        broadcast(new WorkspaceMessageUpdated($message));

        return $message->loadMissing(['user', 'reactions.user']);
    }

    /**
     * Supprime un message et diffuse la suppression.
     */
    public function deleteMessage(WorkspaceMessage $message): bool
    {
        $uuid = $message->uuid;
        $workspaceId = $message->workspace_id;
        $channelType = $message->channel?->type ?? 'global';

        Log::info('Message supprimé dans le canal workspace', [
            'workspace_id' => $workspaceId,
            'channel_type' => $channelType,
            'message_uuid' => $uuid,
        ]);

        $deleted = $message->delete();

        if ($deleted) {
            broadcast(new WorkspaceMessageDeleted($uuid, $workspaceId, $channelType));
        }

        return (bool) $deleted;
    }

    /**
     * Épingle ou désépingle un message.
     */
    public function pinMessage(WorkspaceMessage $message): bool
    {
        $message->update(['is_pinned' => true]);

        return true;
    }

    public function unpinMessage(WorkspaceMessage $message): bool
    {
        $message->update(['is_pinned' => false]);

        return true;
    }

    /**
     * Ajoute une réaction et diffuse le changement.
     */
    public function addReaction(WorkspaceMessage $message, User $user, string $emoji): WorkspaceMessageReaction
    {
        $distinctCount = WorkspaceMessageReaction::where('workspace_message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', '!=', $emoji)
            ->distinct('emoji')
            ->count();

        if ($distinctCount >= 3) {
            abort(422, 'Vous ne pouvez pas ajouter plus de 3 réactions différentes par message.');
        }

        $reaction = WorkspaceMessageReaction::firstOrCreate([
            'workspace_message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ]);

        $this->broadcastReaction($message, $user, $emoji, 'added');

        return $reaction;
    }

    /**
     * Retire une réaction et diffuse le changement.
     */
    public function removeReaction(WorkspaceMessage $message, User $user, string $emoji): bool
    {
        $deleted = WorkspaceMessageReaction::where([
            'workspace_message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ])->delete();

        if ($deleted) {
            $this->broadcastReaction($message, $user, $emoji, 'removed');
        }

        return (bool) $deleted;
    }

    /**
     * Marque un canal comme lu par l'utilisateur.
     */
    public function markRead(WorkspaceChannel $channel, User $user): void
    {
        WorkspaceChannelRead::updateOrCreate(
            ['user_id' => $user->id, 'workspace_channel_id' => $channel->id],
            ['last_read_at' => now()]
        );
    }

    /**
     * Retourne le nombre de messages non lus dans un canal pour un utilisateur.
     */
    public function getUnreadCount(WorkspaceChannel $channel, User $user): int
    {
        $read = WorkspaceChannelRead::where([
            'user_id' => $user->id,
            'workspace_channel_id' => $channel->id,
        ])->first();

        $query = WorkspaceMessage::where('workspace_channel_id', $channel->id);

        if ($read?->last_read_at) {
            $query->where('created_at', '>', $read->last_read_at);
        }

        return $query->count();
    }

    private function broadcastReaction(WorkspaceMessage $message, User $user, string $emoji, string $action): void
    {
        $message->loadMissing(['channel', 'reactions.user']);

        $reactions = $message->reactions
            ->groupBy('emoji')
            ->map(fn ($group, $emo) => ['emoji' => $emo, 'count' => $group->count()])
            ->values()
            ->toArray();

        broadcast(new WorkspaceReactionChanged(
            $message->uuid,
            $message->workspace_id,
            $message->channel?->type ?? 'global',
            $emoji,
            $action,
            $user->id,
            $reactions,
        ));
    }

    /**
     * Retourne le nombre de messages non lus pour tous les canaux d'un workspace.
     *
     * @return array{responsibles: int, global: int, total: int}
     */
    public function getWorkspaceUnreadCounts(int $workspaceId, User $user): array
    {
        $channels = WorkspaceChannel::where('workspace_id', $workspaceId)->get();
        $total = 0;
        $counts = [];

        foreach ($channels as $channel) {
            $count = $this->getUnreadCount($channel, $user);
            $counts[$channel->type] = $count;
            $total += $count;
        }

        $counts['total'] = $total;

        return $counts;
    }
}

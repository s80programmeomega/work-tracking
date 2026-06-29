<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceChat\SendWorkspaceMessageRequest;
use App\Http\Requests\WorkspaceChat\UpdateWorkspaceMessageRequest;
use App\Http\Resources\WorkspaceChat\WorkspaceChannelResource;
use App\Http\Resources\WorkspaceChat\WorkspaceMessageResource;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceChannel;
use App\Models\WorkspaceMessage;
use App\Services\WorkspaceMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class WorkspaceChatController extends Controller
{
    public function __construct(protected WorkspaceMessageService $service) {}

    /**
     * Liste les canaux du workspace (responsibles + global).
     */
    public function channels(Workspace $workspace): AnonymousResourceCollection
    {
        $this->authorize('view', $workspace);

        $channels = $workspace->channels()
            ->withCount('messages')
            ->get();

        return WorkspaceChannelResource::collection($channels);
    }

    /**
     * Retourne les messages paginés d'un canal.
     */
    public function messages(Workspace $workspace, string $channelType): AnonymousResourceCollection
    {
        $this->authorize('view', $workspace);

        $channel = WorkspaceChannel::where('workspace_id', $workspace->id)
            ->where('type', $channelType)
            ->firstOrFail();

        $messages = $this->service->getMessages($channel);

        return WorkspaceMessageResource::collection($messages);
    }

    /**
     * Envoie un message dans un canal.
     */
    public function send(SendWorkspaceMessageRequest $request, Workspace $workspace, string $channelType): JsonResponse
    {
        $this->authorize('view', $workspace);

        $channel = WorkspaceChannel::where('workspace_id', $workspace->id)
            ->where('type', $channelType)
            ->firstOrFail();

        $this->authorizeChannelAccess($channel, $workspace, $request->user());

        $message = $this->service->sendMessage($channel, $request->user(), $request->validated());

        return response()->json([
            'message' => new WorkspaceMessageResource($message),
        ], 201);
    }

    /**
     * Modifie un message (expéditeur uniquement).
     */
    public function update(UpdateWorkspaceMessageRequest $request, Workspace $workspace, string $uuid): JsonResponse
    {
        $message = WorkspaceMessage::where('workspace_id', $workspace->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->authorize('update', $message);

        $message = $this->service->updateMessage($message, $request->validated('content'));

        return response()->json(['message' => new WorkspaceMessageResource($message)]);
    }

    /**
     * Supprime un message (expéditeur ou manager du workspace).
     */
    public function destroy(Workspace $workspace, string $uuid): JsonResponse
    {
        $message = WorkspaceMessage::where('workspace_id', $workspace->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->authorize('delete', $message);

        $this->service->deleteMessage($message);

        return response()->json(['message' => 'Message supprimé.']);
    }

    /**
     * Marque un canal comme lu.
     */
    public function markRead(Request $request, Workspace $workspace, string $channelType): JsonResponse
    {
        $this->authorize('view', $workspace);

        $channel = WorkspaceChannel::where('workspace_id', $workspace->id)
            ->where('type', $channelType)
            ->firstOrFail();

        $this->service->markRead($channel, $request->user());

        return response()->json(['message' => 'Canal marqué comme lu.']);
    }

    /**
     * Épingle un message.
     */
    public function pin(Workspace $workspace, string $uuid): JsonResponse
    {
        $message = WorkspaceMessage::where('workspace_id', $workspace->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->authorize('pin', $message);

        $this->service->pinMessage($message);

        return response()->json(['message' => 'Message épinglé.']);
    }

    /**
     * Désépingle un message.
     */
    public function unpin(Workspace $workspace, string $uuid): JsonResponse
    {
        $message = WorkspaceMessage::where('workspace_id', $workspace->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->authorize('pin', $message);

        $this->service->unpinMessage($message);

        return response()->json(['message' => 'Message désépinglé.']);
    }

    /**
     * Ajoute une réaction à un message.
     */
    public function addReaction(Request $request, Workspace $workspace, string $uuid): JsonResponse
    {
        $request->validate(['emoji' => ['required', 'string', 'max:32']]);

        $message = WorkspaceMessage::where('workspace_id', $workspace->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->service->addReaction($message, $request->user(), $request->input('emoji'));

        return response()->json(['message' => 'Réaction ajoutée.']);
    }

    /**
     * Retire une réaction d'un message.
     */
    public function removeReaction(Request $request, Workspace $workspace, string $uuid): JsonResponse
    {
        $request->validate(['emoji' => ['required', 'string', 'max:32']]);

        $message = WorkspaceMessage::where('workspace_id', $workspace->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->service->removeReaction($message, $request->user(), $request->input('emoji'));

        return response()->json(['message' => 'Réaction retirée.']);
    }

    /**
     * Téléverse une photo dans le chat du workspace.
     */
    public function uploadPhoto(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,webp,gif', 'max:5120'],
        ]);

        $file = $request->file('photo');
        $path = $file->store("workspace-chat/{$workspace->id}", 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'name' => $file->getClientOriginalName(),
            'type' => 'image',
        ]);
    }

    /**
     * Retourne le total des messages non lus par type de canal.
     */
    public function unread(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $counts = $this->service->getWorkspaceUnreadCounts($workspace->id, $request->user());

        return response()->json($counts);
    }

    /**
     * Vérifie que l'utilisateur a accès au canal (le canal responsibles est restreint).
     */
    private function authorizeChannelAccess(WorkspaceChannel $channel, Workspace $workspace, User $user): void
    {
        if ($channel->type === WorkspaceChannel::TYPE_RESPONSIBLES) {
            $this->authorize('accessResponsibles', $workspace);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamMessageRequest;
use App\Http\Requests\UpdateTeamMessageRequest;
use App\Http\Resources\TeamMessageResource;
use App\Services\TeamMessageService;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamMessageController extends Controller
{
    public function __construct(
        protected TeamService $teamService,
        protected TeamMessageService $messageService,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request, string $teamUuid): JsonResponse
    {
        $team = $this->teamService->getTeamByUuid($teamUuid);
        $filters = $request->only(['pinned', 'search', 'user_id', 'per_page']);
        $messages = $this->messageService->getMessages($team, $filters);

        return response()->json([
            'success' => true,
            'data' => TeamMessageResource::collection($messages->getCollection()),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function store(StoreTeamMessageRequest $request, string $teamUuid): JsonResponse
    {
        $team = $this->teamService->getTeamByUuid($teamUuid);
        $data = $request->validated();

        // Décoder les mentions envoyées en JSON string via FormData
        if (isset($data['mentions']) && is_string($data['mentions'])) {
            $data['mentions'] = json_decode($data['mentions'], true) ?? [];
        }

        // Normaliser mention_everyone en booléen
        $data['mention_everyone'] = filter_var($request->input('mention_everyone', false), FILTER_VALIDATE_BOOLEAN);

        // Gérer la pièce jointe fichier si présente
        if ($request->hasFile('attachment')) {
            $data['attachments'] = [$this->messageService->uploadAttachment($request->file('attachment'))];
        }

        // Gérer les photos pré-uploadées transmises en JSON
        if ($request->filled('attachments_json')) {
            $photos = json_decode($request->input('attachments_json'), true) ?? [];
            $existing = $data['attachments'] ?? [];
            $data['attachments'] = array_merge($existing, $photos);
        }

        $message = $this->messageService->sendMessage($team, $request->user(), $data);
        $message->loadMissing(['user', 'replyTo.user', 'reactions']);

        return response()->json([
            'success' => true,
            'data' => new TeamMessageResource($message),
        ], 201);
    }

    public function update(UpdateTeamMessageRequest $request, string $uuid): JsonResponse
    {
        $message = $this->messageService->getMessageByUuid($uuid);

        abort_unless($message->user_id === $request->user()->id, 403);

        $message = $this->messageService->updateMessage($message, $request->validated()['content']);
        $message->loadMissing(['user', 'replyTo.user', 'reactions']);

        return response()->json([
            'success' => true,
            'data' => new TeamMessageResource($message),
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $message = $this->messageService->getMessageByUuid($uuid);
        $team = $message->team;

        // L'auteur ou le propriétaire de l'équipe peuvent supprimer
        $canDelete = $message->user_id === $request->user()->id
            || $team->owner_id === $request->user()->id;

        abort_unless($canDelete, 403);

        $this->messageService->deleteMessage($message);

        return response()->json(['success' => true]);
    }

    public function pinned(Request $request, string $teamUuid): JsonResponse
    {
        $team = $this->teamService->getTeamByUuid($teamUuid);
        $messages = $this->messageService->getPinnedMessages($team);

        return response()->json([
            'success' => true,
            'data' => TeamMessageResource::collection($messages),
        ]);
    }

    public function togglePin(Request $request, string $uuid): JsonResponse
    {
        $message = $this->messageService->getMessageByUuid($uuid);
        $team = $message->team;

        // Seul le propriétaire de l'équipe peut épingler des messages
        abort_unless($team->owner_id === $request->user()->id, 403);

        $message->is_pinned
            ? $this->messageService->unpinMessage($message)
            : $this->messageService->pinMessage($message);

        return response()->json([
            'success' => true,
            'is_pinned' => $message->fresh()->is_pinned,
        ]);
    }

    public function addReaction(Request $request, string $uuid): JsonResponse
    {
        $request->validate(['emoji' => 'required|string|max:10']);
        $message = $this->messageService->getMessageByUuid($uuid);
        $this->messageService->addReaction($message, $request->user(), $request->input('emoji'));

        return response()->json(['success' => true], 201);
    }

    public function removeReaction(Request $request, string $uuid): JsonResponse
    {
        $request->validate(['emoji' => 'required|string|max:10']);
        $message = $this->messageService->getMessageByUuid($uuid);
        $this->messageService->removeReaction($message, $request->user(), $request->input('emoji'));

        return response()->json(['success' => true]);
    }

    public function markRead(Request $request, string $teamUuid): JsonResponse
    {
        $team = $this->teamService->getTeamByUuid($teamUuid);
        $this->messageService->markTeamRead($team, $request->user());

        return response()->json(['success' => true]);
    }
}

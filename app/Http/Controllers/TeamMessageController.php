<?php

namespace App\Http\Controllers;

use App\Services\TeamService;
use App\Services\TeamMessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TeamMessageController extends Controller
{
    protected TeamService $teamService;
    protected TeamMessageService $messageService;

    public function __construct(TeamService $teamService, TeamMessageService $messageService)
    {
        $this->middleware('auth:sanctum');
        $this->teamService = $teamService;
        $this->messageService = $messageService;
    }

    public function index(Request $request, string $teamUuid): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($teamUuid);
            $filters = $request->only(['pinned', 'search', 'user_id', 'per_page']);
            $messages = $this->messageService->getMessages($team, $filters);

            return response()->json(['success' => true, 'messages' => $messages]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, string $teamUuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'mentions' => 'nullable|array',
            'reply_to_id' => 'nullable|exists:team_messages,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $team = $this->teamService->getTeamByUuid($teamUuid);
            $message = $this->messageService->sendMessage($team, $request->user(), $validator->validated());
            return response()->json(['success' => true, 'message' => $message], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addReaction(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), ['emoji' => 'required|string|max:10']);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $message = $this->messageService->getMessageByUuid($uuid);
            $reaction = $this->messageService->addReaction($message, $request->user(), $request->input('emoji'));
            return response()->json(['success' => true, 'reaction' => $reaction], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TeamResource;
use App\Notifications\TeamResourceNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamResourceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(string $teamUuid): JsonResponse
    {
        try {
            $resources = TeamResource::whereHas('team', function($q) use ($teamUuid) {
                $q->where('uuid', $teamUuid);
            })->with('user')->orderBy('created_at', 'desc')->get();
            
            return response()->json(['success' => true, 'resources' => $resources]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, string $teamUuid): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:file,link,document,template',
            'url' => 'required|string',
            'description' => 'nullable|string',
        ]);

        try {
            $team = \App\Models\Team::where('uuid', $teamUuid)->firstOrFail();
            $resource = TeamResource::create([
                'team_id' => $team->id,
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'type' => $request->type,
                'url' => $request->url,
                'description' => $request->description,
            ]);

            // Create activity
            $team->activities()->create([
                'user_id' => $request->user()->id,
                'type' => 'resource_added',
                'description' => $request->user()->nom . ' a ajouté une ressource : "' . $resource->title . '"',
                'metadata' => [
                    'resource_id' => $resource->id,
                    'resource_title' => $resource->title,
                    'resource_type' => $resource->type
                ]
            ]);

            // Send notifications to all team members + project members (except creator)
            $notifiableUsers = $team->getNotifiableUsersExcept($request->user()->id, true);

            foreach ($notifiableUsers as $user) {
                $user->notify(new TeamResourceNotification($team, $resource));
            }

            return response()->json(['success' => true, 'resource' => $resource], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $teamUuid, int $resourceId): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:file,link,document,template',
            'url' => 'sometimes|required|string',
            'description' => 'nullable|string',
        ]);

        try {
            $team = \App\Models\Team::where('uuid', $teamUuid)->firstOrFail();
            $resource = TeamResource::where('team_id', $team->id)
                ->where('id', $resourceId)
                ->firstOrFail();

            $resource->update($request->only(['title', 'type', 'url', 'description']));

            // Create activity
            $team->activities()->create([
                'user_id' => $request->user()->id,
                'type' => 'resource_updated',
                'description' => $request->user()->nom . ' a modifié la ressource : "' . $resource->title . '"',
                'metadata' => [
                    'resource_id' => $resource->id,
                    'resource_title' => $resource->title
                ]
            ]);

            return response()->json(['success' => true, 'resource' => $resource->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $teamUuid, int $resourceId): JsonResponse
    {
        try {
            $team = \App\Models\Team::where('uuid', $teamUuid)->firstOrFail();
            $resource = TeamResource::where('team_id', $team->id)
                ->where('id', $resourceId)
                ->firstOrFail();

            $resourceTitle = $resource->title;
            $resource->delete();

            // Create activity
            $team->activities()->create([
                'user_id' => auth()->id(),
                'type' => 'resource_deleted',
                'description' => auth()->user()->nom . ' a supprimé la ressource : "' . $resourceTitle . '"',
                'metadata' => [
                    'resource_title' => $resourceTitle
                ]
            ]);

            return response()->json(['success' => true, 'message' => 'Ressource supprimée avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

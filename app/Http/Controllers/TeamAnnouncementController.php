<?php

namespace App\Http\Controllers;

use App\Models\TeamAnnouncement;
use App\Notifications\TeamAnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamAnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(string $teamUuid): JsonResponse
    {
        try {
            $announcements = TeamAnnouncement::whereHas('team', function($q) use ($teamUuid) {
                $q->where('uuid', $teamUuid);
            })->with('user')->published()->active()->orderBy('created_at', 'desc')->get();
            
            return response()->json(['success' => true, 'announcements' => $announcements]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, string $teamUuid): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        try {
            $team = \App\Models\Team::where('uuid', $teamUuid)->firstOrFail();
            $announcement = TeamAnnouncement::create([
                'team_id' => $team->id,
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'content' => $request->content,
                'priority' => $request->priority ?? 'normal',
                'published_at' => $request->published_at ?? now(),
            ]);

            // Create activity
            $team->activities()->create([
                'user_id' => $request->user()->id,
                'type' => 'announcement_created',
                'description' => $request->user()->nom . ' a créé une annonce : "' . $announcement->title . '"',
                'metadata' => [
                    'announcement_id' => $announcement->id,
                    'announcement_title' => $announcement->title
                ]
            ]);

            // Send notifications to all team members + project members (except creator)
            $notifiableUsers = $team->getNotifiableUsersExcept($request->user()->id, true);

            foreach ($notifiableUsers as $user) {
                $user->notify(new TeamAnnouncementNotification($team, $announcement));
            }

            return response()->json(['success' => true, 'announcement' => $announcement], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $teamUuid, int $announcementId): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        try {
            $team = \App\Models\Team::where('uuid', $teamUuid)->firstOrFail();
            $announcement = TeamAnnouncement::where('team_id', $team->id)
                ->where('id', $announcementId)
                ->firstOrFail();

            $announcement->update($request->only(['title', 'content', 'priority', 'published_at']));

            // Create activity
            $team->activities()->create([
                'user_id' => $request->user()->id,
                'type' => 'announcement_updated',
                'description' => $request->user()->nom . ' a modifié l\'annonce : "' . $announcement->title . '"',
                'metadata' => [
                    'announcement_id' => $announcement->id,
                    'announcement_title' => $announcement->title
                ]
            ]);

            return response()->json(['success' => true, 'announcement' => $announcement->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $teamUuid, int $announcementId): JsonResponse
    {
        try {
            $team = \App\Models\Team::where('uuid', $teamUuid)->firstOrFail();
            $announcement = TeamAnnouncement::where('team_id', $team->id)
                ->where('id', $announcementId)
                ->firstOrFail();

            $announcementTitle = $announcement->title;
            $announcement->delete();

            // Create activity
            $team->activities()->create([
                'user_id' => auth()->id(),
                'type' => 'announcement_deleted',
                'description' => auth()->user()->nom . ' a supprimé l\'annonce : "' . $announcementTitle . '"',
                'metadata' => [
                    'announcement_title' => $announcementTitle
                ]
            ]);

            return response()->json(['success' => true, 'message' => 'Annonce supprimée avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TeamAnnouncement;
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
                'published_at' => now(),
            ]);
            
            return response()->json(['success' => true, 'announcement' => $announcement], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

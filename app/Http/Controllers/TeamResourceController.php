<?php

namespace App\Http\Controllers;

use App\Models\TeamResource;
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
            'name' => 'required|string|max:255',
            'type' => 'required|in:template,document,checklist,link,note',
            'description' => 'nullable|string',
            'content' => 'nullable|array',
        ]);

        try {
            $team = \App\Models\Team::where('uuid', $teamUuid)->firstOrFail();
            $resource = TeamResource::create([
                'team_id' => $team->id,
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'type' => $request->type,
                'description' => $request->description,
                'content' => $request->content,
            ]);
            
            return response()->json(['success' => true, 'resource' => $resource], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

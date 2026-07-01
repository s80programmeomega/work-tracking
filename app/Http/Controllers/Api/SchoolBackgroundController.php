<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\SchoolBackgroundRequest;
use App\Models\SchoolBackground;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolBackgroundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->schoolBackgrounds()->get();

        return response()->json(['data' => $items]);
    }

    public function store(SchoolBackgroundRequest $request): JsonResponse
    {
        $item = $request->user()->schoolBackgrounds()->create($request->validated());

        return response()->json(['data' => $item], 201);
    }

    public function update(SchoolBackgroundRequest $request, SchoolBackground $schoolBackground): JsonResponse
    {
        abort_unless($schoolBackground->user_id === $request->user()->id, 403);

        $schoolBackground->update($request->validated());

        return response()->json(['data' => $schoolBackground->fresh()]);
    }

    public function destroy(Request $request, SchoolBackground $schoolBackground): JsonResponse
    {
        abort_unless($schoolBackground->user_id === $request->user()->id, 403);

        $schoolBackground->delete();

        return response()->json(null, 204);
    }
}

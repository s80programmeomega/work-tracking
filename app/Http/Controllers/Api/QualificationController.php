<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\QualificationRequest;
use App\Models\Qualification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->qualifications()->get();

        return response()->json(['data' => $items]);
    }

    public function store(QualificationRequest $request): JsonResponse
    {
        $item = $request->user()->qualifications()->create($request->validated());

        return response()->json(['data' => $item], 201);
    }

    public function update(QualificationRequest $request, Qualification $qualification): JsonResponse
    {
        abort_unless($qualification->user_id === $request->user()->id, 403);

        $qualification->update($request->validated());

        return response()->json(['data' => $qualification->fresh()]);
    }

    public function destroy(Request $request, Qualification $qualification): JsonResponse
    {
        abort_unless($qualification->user_id === $request->user()->id, 403);

        $qualification->delete();

        return response()->json(null, 204);
    }
}

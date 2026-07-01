<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\CertificateRequest;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->certificates()->get();

        return response()->json(['data' => $items]);
    }

    public function store(CertificateRequest $request): JsonResponse
    {
        $item = $request->user()->certificates()->create($request->validated());

        return response()->json(['data' => $item], 201);
    }

    public function update(CertificateRequest $request, Certificate $certificate): JsonResponse
    {
        abort_unless($certificate->user_id === $request->user()->id, 403);

        $certificate->update($request->validated());

        return response()->json(['data' => $certificate->fresh()]);
    }

    public function destroy(Request $request, Certificate $certificate): JsonResponse
    {
        abort_unless($certificate->user_id === $request->user()->id, 403);

        $certificate->delete();

        return response()->json(null, 204);
    }
}

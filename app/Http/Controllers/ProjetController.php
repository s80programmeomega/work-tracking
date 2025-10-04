<?php

namespace App\Http\Controllers;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProjetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Projet::with(['responsable'])
            ->when($request->status, function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->priorite, function ($q) use ($request) {
                return $q->where('priorite', $request->priorite);
            })
            ->when($request->responsable_id, function ($q) use ($request) {
                return $q->where('responsable_id', $request->responsable_id);
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->where('nom', 'like', '%' . $request->search . '%')
                         ->orWhere('description', 'like', '%' . $request->search . '%');
            });

        $projets = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $projets,
            'stats' => [
                'total' => $projets->count(),
                'actifs' => $projets->where('status', 'en_cours')->count(),
                'planifies' => $projets->where('status', 'planifie')->count(),
                'termines' => $projets->where('status', 'termine')->count(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'responsable_id' => 'required|exists:users,id',
            'budget' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['planifie', 'en_cours', 'suspendu', 'termine', 'annule'])],
            'priorite' => ['required', Rule::in(['basse', 'normale', 'haute', 'critique'])],
        ]);

        $projet = Projet::create($validated);
        $projet->load('responsable');

        return response()->json([
            'message' => 'Projet créé avec succès',
            'data' => $projet
        ], 201);
    }

    public function show(Projet $projet): JsonResponse
    {
        $projet->load(['responsable', 'activites']);

        return response()->json([
            'data' => $projet
        ]);
    }

    public function update(Request $request, Projet $projet): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'responsable_id' => 'required|exists:users,id',
            'budget' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['planifie', 'en_cours', 'suspendu', 'termine', 'annule'])],
            'priorite' => ['required', Rule::in(['basse', 'normale', 'haute', 'critique'])],
        ]);

        $projet->update($validated);
        $projet->load('responsable');

        return response()->json([
            'message' => 'Projet mis à jour avec succès',
            'data' => $projet
        ]);
    }

    public function destroy(Projet $projet): JsonResponse
    {
        if ($projet->activites()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un projet contenant des activités'
            ], 422);
        }

        $projet->delete();

        return response()->json([
            'message' => 'Projet supprimé avec succès'
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_projets' => Projet::count(),
            'projets_actifs' => Projet::where('status', 'en_cours')->count(),
            'projets_en_retard' => Projet::where('status', 'en_cours')
                ->where('date_fin', '<', now())
                ->count(),
            'budget_total' => Projet::sum('budget') ?? 0,
        ];

        $projets_priorite = Projet::selectRaw('priorite, count(*) as count')
            ->groupBy('priorite')
            ->get()
            ->pluck('count', 'priorite');

        $projets_status = Projet::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $projets_recents = Projet::with('responsable')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'charts' => [
                'priorite' => $projets_priorite,
                'status' => $projets_status,
            ],
            'projets_recents' => $projets_recents
        ]);
    }

    public function responsables(): JsonResponse
    {
        $responsables = User::whereHas('permissions', function($query) {
            $query->whereIn('name', ['manage projects', 'create projects']);
        })->orWhereHas('roles', function($query) {
            $query->whereIn('name', ['manager', 'responsable_n1', 'super_admin']);
        })->select('id', 'nom', 'email')->get();

        return response()->json($responsables);
    }
}

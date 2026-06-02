<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\NewSupportTicketNotification;
use App\Notifications\SupportTicketStatusChangedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    /**
     * Soumet un nouveau ticket de support.
     * Notifie tous les super-admins et l'équipe support.
     */
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Traitement de la pièce jointe si présente
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')
                ->store('support-attachments', 'local');
        }

        $data['user_id'] = $request->user()->id;
        $data['workspace_id'] = $request->user()->current_workspace_id;

        $ticket = SupportTicket::create($data);

        Log::info('Nouveau ticket de support soumis', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'category' => $ticket->category,
        ]);

        // Notification aux super-admins
        $superAdmins = User::where('is_super_admin', true)->get();
        foreach ($superAdmins as $admin) {
            $admin->notify(new NewSupportTicketNotification($ticket, $request->user()));
        }

        return response()->json([
            'message' => __('support.ticket_submitted'),
            'data' => [
                'id' => $ticket->id,
                'status' => $ticket->status,
            ],
        ], 201);
    }

    /**
     * Liste les tickets de l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $tickets]);
    }

    /**
     * Liste tous les tickets (super-admin seulement).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = SupportTicket::with('user')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('subject', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        return response()->json(['data' => $query->paginate(25)]);
    }

    /**
     * Met à jour le statut d'un ticket (super-admin seulement).
     */
    public function updateStatus(Request $request, SupportTicket $ticket): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved'],
        ]);

        $oldStatus = $ticket->status;
        $ticket->update(['status' => $request->status]);

        Log::info('Statut de ticket de support mis à jour', [
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => $ticket->status,
            'admin_id' => $request->user()->id,
        ]);

        // Notifie le demandeur du changement de statut
        $ticket->user->notify(new SupportTicketStatusChangedNotification($ticket, $oldStatus));

        return response()->json([
            'message' => __('support.status_updated'),
            'data' => ['status' => $ticket->status],
        ]);
    }
}

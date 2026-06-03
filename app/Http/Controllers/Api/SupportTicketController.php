<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Notifications\NewSupportTicketNotification;
use App\Notifications\SupportTicketReplyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    /**
     * Soumet un nouveau ticket de support.
     * Calcule le délai SLA selon la catégorie et notifie les super-admins.
     */
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $data = $request->safe()->except('attachments');
        $data['user_id'] = $request->user()->id;
        $data['workspace_id'] = $request->user()->current_workspace_id;
        $data['sla_deadline'] = now()->addHours(SupportTicket::SLA_HOURS[$data['category']] ?? 24);

        $ticket = SupportTicket::create($data);

        // Pièces jointes multiples
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $ticket->attachments()->create([
                    'uploaded_by' => $request->user()->id,
                    'file_path' => $file->store('support-attachments', 'local'),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                ]);
            }
        }

        Log::info('Nouveau ticket de support soumis', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'user_id' => $request->user()->id,
            'category' => $ticket->category,
            'sla_deadline' => $ticket->sla_deadline,
        ]);

        $superAdmins = User::where('is_super_admin', true)->get();
        foreach ($superAdmins as $admin) {
            $admin->notify(new NewSupportTicketNotification($ticket, $request->user()));
        }

        return response()->json([
            'message' => __('support.ticket_submitted'),
            'data' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'sla_deadline' => $ticket->sla_deadline?->toISOString(),
            ],
        ], 201);
    }

    /**
     * Liste les tickets de l'utilisateur connecté avec réponses et pièces jointes.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::with(['replies.author', 'attachments'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $tickets]);
    }

    /**
     * Liste tous les tickets — super-admin seulement.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = SupportTicket::with(['user', 'replies.author', 'attachments'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('sla_breached')) {
            $query->where('sla_deadline', '<', now())
                ->where('status', '!=', 'resolved');
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
     * Ajoute une réponse admin à un ticket et met à jour son statut (obligatoire).
     * Enregistre first_responded_at si c'est la première réponse admin.
     * Enregistre resolved_at si le statut passe à 'resolved'.
     */
    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'string', 'in:open,in_progress,resolved'],
        ]);

        $oldStatus = $ticket->status;

        $reply = SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
            'is_admin_reply' => true,
        ]);

        $updates = ['status' => $request->status];

        // Première réponse admin — horodatage SLA
        if (! $ticket->first_responded_at) {
            $updates['first_responded_at'] = now();
        }

        // Résolution
        if ($request->status === 'resolved' && ! $ticket->resolved_at) {
            $updates['resolved_at'] = now();
        } elseif ($request->status !== 'resolved') {
            $updates['resolved_at'] = null;
        }

        $ticket->update($updates);

        Log::info('Réponse admin ajoutée au ticket de support', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'admin_id' => $request->user()->id,
            'new_status' => $ticket->status,
        ]);

        // Toujours notifier le demandeur — il doit voir la réponse même sans changement de statut.
        $ticket->user->notify(new SupportTicketReplyNotification($ticket, $reply));

        return response()->json([
            'message' => __('support.reply_added'),
            'data' => [
                'reply' => $reply->load('author'),
                'status' => $ticket->status,
            ],
        ]);
    }

    /**
     * Ajoute des pièces jointes à un ticket existant (accessible à l'auteur et aux admins).
     */
    public function addAttachments(Request $request, SupportTicket $ticket): JsonResponse
    {
        // Seul l'auteur ou un super-admin peut ajouter des pièces jointes
        if ($ticket->user_id !== $request->user()->id && ! $request->user()->is_super_admin) {
            return response()->json(['message' => __('support.unauthorized')], 403);
        }

        $request->validate([
            'attachments' => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png,gif,zip,webp'],
        ]);

        $added = [];
        foreach ($request->file('attachments') as $file) {
            $attachment = $ticket->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'file_path' => $file->store('support-attachments', 'local'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
            $added[] = $attachment;
        }

        return response()->json(['data' => $added], 201);
    }

    /**
     * Télécharge une pièce jointe (auteur ou super-admin).
     */
    public function downloadAttachment(Request $request, SupportTicketAttachment $attachment): mixed
    {
        $ticket = $attachment->ticket;

        if ($ticket->user_id !== $request->user()->id && ! $request->user()->is_super_admin) {
            return response()->json(['message' => __('support.unauthorized')], 403);
        }

        return response()->download(
            storage_path('app/'.$attachment->file_path),
            $attachment->original_name
        );
    }
}

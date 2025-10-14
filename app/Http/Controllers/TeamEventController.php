<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamEvent;
use App\Notifications\TeamEventNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamEventController extends Controller
{
    /**
     * Display a listing of team events.
     */
    public function index($uuid)
    {
        $team = Team::where('uuid', $uuid)->firstOrFail();

        $events = $team->events()
            ->with(['user', 'attendees'])
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request, $uuid)
    {
        $team = Team::where('uuid', $uuid)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:meeting,deadline,event,reminder',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'attendee_ids' => 'nullable|array',
            'attendee_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $event = $team->events()->create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'location' => $request->location,
        ]);

        // Attach attendees
        if ($request->has('attendee_ids')) {
            $event->attendees()->attach($request->attendee_ids);
        } else {
            // By default, invite all team members
            $event->attendees()->attach($team->members->pluck('id'));
        }

        // Create activity
        $team->activities()->create([
            'user_id' => auth()->id(),
            'type' => 'event_created',
            'description' => auth()->user()->nom . ' a créé l\'événement "' . $event->title . '"',
            'metadata' => [
                'event_id' => $event->id,
                'event_title' => $event->title,
                'event_type' => $event->type
            ]
        ]);

        // Send notifications to all attendees (except creator)
        // Note: For events, we only notify attendees, not all project members
        $event->attendees()->where('user_id', '!=', auth()->id())->each(function ($attendee) use ($team, $event) {
            $attendee->notify(new TeamEventNotification($team, $event, 'created'));
        });

        return response()->json([
            'success' => true,
            'message' => 'Événement créé avec succès',
            'data' => $event->load(['user', 'attendees'])
        ], 201);
    }

    /**
     * Display the specified event.
     */
    public function show($uuid, $eventId)
    {
        $team = Team::where('uuid', $uuid)->firstOrFail();
        $event = $team->events()->with(['user', 'attendees'])->findOrFail($eventId);

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, $uuid, $eventId)
    {
        $team = Team::where('uuid', $uuid)->firstOrFail();
        $event = $team->events()->findOrFail($eventId);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:meeting,deadline,event,reminder',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'attendee_ids' => 'nullable|array',
            'attendee_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $event->update($request->only([
            'title',
            'description',
            'type',
            'start_date',
            'end_date',
            'location'
        ]));

        // Update attendees if provided
        if ($request->has('attendee_ids')) {
            $event->attendees()->sync($request->attendee_ids);
        }

        // Create activity
        $team->activities()->create([
            'user_id' => auth()->id(),
            'type' => 'event_updated',
            'description' => auth()->user()->nom . ' a modifié l\'événement "' . $event->title . '"',
            'metadata' => [
                'event_id' => $event->id,
                'event_title' => $event->title
            ]
        ]);

        // Send notifications to all attendees (except updater)
        // Note: For events, we only notify attendees, not all project members
        $event->attendees()->where('user_id', '!=', auth()->id())->each(function ($attendee) use ($team, $event) {
            $attendee->notify(new TeamEventNotification($team, $event, 'updated'));
        });

        return response()->json([
            'success' => true,
            'message' => 'Événement mis à jour avec succès',
            'data' => $event->load(['user', 'attendees'])
        ]);
    }

    /**
     * Remove the specified event.
     */
    public function destroy($uuid, $eventId)
    {
        $team = Team::where('uuid', $uuid)->firstOrFail();
        $event = $team->events()->findOrFail($eventId);

        $eventTitle = $event->title;

        // Detach attendees
        $event->attendees()->detach();

        // Delete the event
        $event->delete();

        // Create activity
        $team->activities()->create([
            'user_id' => auth()->id(),
            'type' => 'event_deleted',
            'description' => auth()->user()->nom . ' a supprimé l\'événement "' . $eventTitle . '"',
            'metadata' => [
                'event_title' => $eventTitle
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Événement supprimé avec succès'
        ]);
    }
}

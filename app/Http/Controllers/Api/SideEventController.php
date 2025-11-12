<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SideEvent\CreateSideEventJob;
use App\Jobs\SideEvent\DeleteSideEventJob;
use App\Jobs\SideEvent\UpdateSideEventJob;
use App\Models\Conference;
use App\Models\SideEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SideEventController extends Controller
{
    /**
     * Display a listing of side events for a conference.
     */
    public function index(Request $request, Conference $conference)
    {
        $query = $conference->sideEvents()->with(['creator', 'siteManagers', 'volunteers']);

        // Filter by event type
        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        $sideEvents = $query->get();

        return response()->json($sideEvents);
    }

    /**
     * Store a newly created side event (Admin only).
     */
    public function store(Request $request, Conference $conference)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'room_number' => 'nullable|string|max:50',
            'event_type' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $sideEvent = CreateSideEventJob::dispatchSync($conference->id, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Side event created successfully',
                'side_event' => $sideEvent
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified side event.
     */
    public function show(Conference $conference, SideEvent $sideEvent)
    {
        $sideEvent->load(['conference', 'creator', 'siteManagers', 'volunteers']);

        return response()->json($sideEvent);
    }

    /**
     * Update the specified side event (Admin only).
     */
    public function update(Request $request, Conference $conference, SideEvent $sideEvent)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_datetime' => 'sometimes|required|date',
            'end_datetime' => 'sometimes|required|date|after:start_datetime',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'room_number' => 'nullable|string|max:50',
            'event_type' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $updatedSideEvent = UpdateSideEventJob::dispatchSync($sideEvent, $validated);

            return response()->json([
                'message' => 'Side event updated successfully',
                'side_event' => $updatedSideEvent
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified side event (Admin only).
     */
    public function destroy(Conference $conference, SideEvent $sideEvent)
    {
        try {
            DeleteSideEventJob::dispatchSync($sideEvent);

            return response()->json(['message' => 'Side event deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

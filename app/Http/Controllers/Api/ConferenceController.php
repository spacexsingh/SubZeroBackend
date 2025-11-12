<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Conference\CreateConferenceJob;
use App\Jobs\Conference\DeleteConferenceJob;
use App\Jobs\Conference\UpdateConferenceJob;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConferenceController extends Controller
{
    /**
     * Display a listing of conferences.
     */
    public function index(Request $request)
    {
        $query = Conference::with(['creator', 'sideEvents']);

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
            }
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $conferences = $query->paginate($request->get('per_page', 15));

        return response()->json($conferences);
    }

    /**
     * Store a newly created conference (Admin only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:conferences,slug',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'registration_start_datetime' => 'nullable|date',
            'registration_end_datetime' => 'nullable|date|after:registration_start_datetime',
            'timezone' => 'nullable|string|max:50',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        try {
            $conference = CreateConferenceJob::dispatchSync($validated, $request->user()->id);

            return response()->json([
                'message' => 'Conference created successfully',
                'conference' => $conference
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified conference.
     */
    public function show(Conference $conference)
    {
        $conference->load(['creator', 'sideEvents', 'siteManagers', 'volunteers']);

        return response()->json($conference);
    }

    /**
     * Update the specified conference (Admin only).
     */
    public function update(Request $request, Conference $conference)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('conferences', 'slug')->ignore($conference->id)
            ],
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'start_datetime' => 'sometimes|required|date',
            'end_datetime' => 'sometimes|required|date|after:start_datetime',
            'registration_start_datetime' => 'nullable|date',
            'registration_end_datetime' => 'nullable|date|after:registration_start_datetime',
            'timezone' => 'nullable|string|max:50',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        try {
            $updatedConference = UpdateConferenceJob::dispatchSync($conference, $validated);

            return response()->json([
                'message' => 'Conference updated successfully',
                'conference' => $updatedConference
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified conference (Admin only).
     */
    public function destroy(Conference $conference)
    {
        try {
            DeleteConferenceJob::dispatchSync($conference);

            return response()->json(['message' => 'Conference deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

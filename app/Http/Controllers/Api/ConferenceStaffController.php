<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Staff\AttachStaffToConferenceJob;
use App\Jobs\Staff\DetachStaffFromConferenceJob;
use App\Models\Conference;
use Illuminate\Http\Request;

class ConferenceStaffController extends Controller
{
    /**
     * Attach a site manager to a conference.
     */
    public function attachSiteManager(Request $request, Conference $conference)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = AttachStaffToConferenceJob::dispatchSync(
                $conference,
                $validated['user_id'],
                'site_manager'
            );

            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Detach a site manager from a conference.
     */
    public function detachSiteManager(Request $request, Conference $conference)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = DetachStaffFromConferenceJob::dispatchSync(
                $conference,
                $validated['user_id'],
                'site_manager'
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Attach a volunteer to a conference.
     */
    public function attachVolunteer(Request $request, Conference $conference)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = AttachStaffToConferenceJob::dispatchSync(
                $conference,
                $validated['user_id'],
                'volunteer'
            );

            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Detach a volunteer from a conference.
     */
    public function detachVolunteer(Request $request, Conference $conference)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = DetachStaffFromConferenceJob::dispatchSync(
                $conference,
                $validated['user_id'],
                'volunteer'
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * List site managers for a conference.
     */
    public function listSiteManagers(Conference $conference)
    {
        $siteManagers = $conference->siteManagers;

        return response()->json($siteManagers);
    }

    /**
     * List volunteers for a conference.
     */
    public function listVolunteers(Conference $conference)
    {
        $volunteers = $conference->volunteers;

        return response()->json($volunteers);
    }
}

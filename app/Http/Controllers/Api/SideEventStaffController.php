<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Staff\AttachStaffToSideEventJob;
use App\Jobs\Staff\DetachStaffFromSideEventJob;
use App\Jobs\Staff\GetAvailableStaffForSideEventJob;
use App\Models\Conference;
use App\Models\SideEvent;
use Illuminate\Http\Request;

class SideEventStaffController extends Controller
{
    /**
     * Attach a site manager to a side event.
     */
    public function attachSiteManager(Request $request, Conference $conference, SideEvent $sideEvent)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = AttachStaffToSideEventJob::dispatchSync(
                $sideEvent,
                $validated['user_id'],
                'site_manager'
            );

            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Detach a site manager from a side event.
     */
    public function detachSiteManager(Request $request, Conference $conference, SideEvent $sideEvent)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = DetachStaffFromSideEventJob::dispatchSync(
                $sideEvent,
                $validated['user_id'],
                'site_manager'
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Attach a volunteer to a side event.
     */
    public function attachVolunteer(Request $request, Conference $conference, SideEvent $sideEvent)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = AttachStaffToSideEventJob::dispatchSync(
                $sideEvent,
                $validated['user_id'],
                'volunteer'
            );

            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Detach a volunteer from a side event.
     */
    public function detachVolunteer(Request $request, Conference $conference, SideEvent $sideEvent)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = DetachStaffFromSideEventJob::dispatchSync(
                $sideEvent,
                $validated['user_id'],
                'volunteer'
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * List site managers for a side event.
     */
    public function listSiteManagers(Conference $conference, SideEvent $sideEvent)
    {
        $siteManagers = $sideEvent->siteManagers;

        return response()->json($siteManagers);
    }

    /**
     * List volunteers for a side event.
     */
    public function listVolunteers(Conference $conference, SideEvent $sideEvent)
    {
        $volunteers = $sideEvent->volunteers;

        return response()->json($volunteers);
    }

    /**
     * List available site managers for a side event (no time conflicts).
     */
    public function listAvailableSiteManagers(Conference $conference, SideEvent $sideEvent)
    {
        try {
            $availableStaff = GetAvailableStaffForSideEventJob::dispatchSync(
                $sideEvent,
                'site_manager'
            );

            return response()->json($availableStaff);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * List available volunteers for a side event (no time conflicts).
     */
    public function listAvailableVolunteers(Conference $conference, SideEvent $sideEvent)
    {
        try {
            $availableStaff = GetAvailableStaffForSideEventJob::dispatchSync(
                $sideEvent,
                'volunteer'
            );

            return response()->json($availableStaff);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

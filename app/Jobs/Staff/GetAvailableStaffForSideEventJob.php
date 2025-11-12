<?php

namespace App\Jobs\Staff;

use App\Models\SideEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GetAvailableStaffForSideEventJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SideEvent $sideEvent,
        public string $role // 'site_manager' or 'volunteer'
    ) {}

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Get all staff assigned to the conference
        $conferenceStaffIds = DB::table('conference_user')
            ->where('conference_id', $this->sideEvent->conference_id)
            ->where('role', $this->role)
            ->pluck('user_id');

        // Get IDs of staff already attached to THIS side event
        $alreadyAttachedIds = DB::table('side_event_user')
            ->where('side_event_id', $this->sideEvent->id)
            ->where('role', $this->role)
            ->pluck('user_id');

        // Get IDs of staff with time conflicts (in OTHER side events)
        $busyStaffIds = DB::table('side_event_user')
            ->join('side_events', 'side_event_user.side_event_id', '=', 'side_events.id')
            ->where('side_event_user.role', $this->role)
            ->where('side_events.conference_id', $this->sideEvent->conference_id)
            ->where('side_events.id', '!=', $this->sideEvent->id)
            ->where(function ($query) {
                $query->whereBetween('side_events.start_datetime', [
                    $this->sideEvent->start_datetime,
                    $this->sideEvent->end_datetime
                ])
                ->orWhereBetween('side_events.end_datetime', [
                    $this->sideEvent->start_datetime,
                    $this->sideEvent->end_datetime
                ])
                ->orWhere(function ($q) {
                    $q->where('side_events.start_datetime', '<=', $this->sideEvent->start_datetime)
                      ->where('side_events.end_datetime', '>=', $this->sideEvent->end_datetime);
                });
            })
            ->pluck('side_event_user.user_id');

        // Combine staff to exclude (already attached + busy with conflicts)
        $unavailableStaffIds = $alreadyAttachedIds->merge($busyStaffIds)->unique();

        // Get available staff (in conference but not already attached and not busy)
        $availableStaffIds = $conferenceStaffIds->diff($unavailableStaffIds);

        // Get the users
        $availableStaff = User::whereIn('id', $availableStaffIds)->get();

        return $availableStaff;
    }
}

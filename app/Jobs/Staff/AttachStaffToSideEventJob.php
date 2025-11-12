<?php

namespace App\Jobs\Staff;

use App\Enums\UserType;
use App\Models\SideEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AttachStaffToSideEventJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SideEvent $sideEvent,
        public int $userId,
        public string $role // 'site_manager' or 'volunteer'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        $user = User::findOrFail($this->userId);

        // Validate user type matches role
        if ($this->role === 'site_manager' && $user->user_type !== UserType::SITE_MANAGER) {
            throw new \Exception("User must be of type 'site_manager' to be assigned as a site manager.");
        }

        if ($this->role === 'volunteer' && $user->user_type !== UserType::VOLUNTEER) {
            throw new \Exception("User must be of type 'volunteer' to be assigned as a volunteer.");
        }

        // Check if user is attached to the conference first
        $isAttachedToConference = DB::table('conference_user')
            ->where('conference_id', $this->sideEvent->conference_id)
            ->where('user_id', $this->userId)
            ->where('role', $this->role)
            ->exists();

        if (!$isAttachedToConference) {
            throw new \Exception("User must be attached to the conference first before being assigned to a side event.");
        }

        // Check if already attached to this side event
        $existing = DB::table('side_event_user')
            ->where('side_event_id', $this->sideEvent->id)
            ->where('user_id', $this->userId)
            ->where('role', $this->role)
            ->exists();

        if ($existing) {
            throw new \Exception("User is already assigned to this side event as {$this->role}.");
        }

        // Check for time conflicts with other side events in the same conference
        $conflictingEvents = DB::table('side_event_user')
            ->join('side_events', 'side_event_user.side_event_id', '=', 'side_events.id')
            ->where('side_event_user.user_id', $this->userId)
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
            ->exists();

        if ($conflictingEvents) {
            throw new \Exception("User has a time conflict with another side event in this conference.");
        }

        // Attach user to side event
        DB::table('side_event_user')->insert([
            'side_event_id' => $this->sideEvent->id,
            'user_id' => $this->userId,
            'role' => $this->role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'message' => ucfirst($this->role) . ' attached to side event successfully.',
            'side_event_id' => $this->sideEvent->id,
            'user_id' => $this->userId,
            'role' => $this->role,
        ];
    }
}

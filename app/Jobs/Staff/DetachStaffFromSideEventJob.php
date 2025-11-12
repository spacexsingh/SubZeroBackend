<?php

namespace App\Jobs\Staff;

use App\Models\SideEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DetachStaffFromSideEventJob
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
        $deleted = DB::table('side_event_user')
            ->where('side_event_id', $this->sideEvent->id)
            ->where('user_id', $this->userId)
            ->where('role', $this->role)
            ->delete();

        if (!$deleted) {
            throw new \Exception("User is not assigned to this side event as {$this->role}.");
        }

        return [
            'message' => ucfirst($this->role) . ' detached from side event successfully.',
            'side_event_id' => $this->sideEvent->id,
            'user_id' => $this->userId,
            'role' => $this->role,
        ];
    }
}

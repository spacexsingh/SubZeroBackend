<?php

namespace App\Jobs\Staff;

use App\Models\Conference;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetachStaffFromConferenceJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Conference $conference,
        public int $userId,
        public string $role // 'site_manager' or 'volunteer'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        $deleted = \DB::table('conference_user')
            ->where('conference_id', $this->conference->id)
            ->where('user_id', $this->userId)
            ->where('role', $this->role)
            ->delete();

        if (!$deleted) {
            throw new \Exception("User is not assigned to this conference as {$this->role}.");
        }

        return [
            'message' => ucfirst($this->role) . ' detached from conference successfully.',
            'conference_id' => $this->conference->id,
            'user_id' => $this->userId,
            'role' => $this->role,
        ];
    }
}

<?php

namespace App\Jobs\Staff;

use App\Enums\UserType;
use App\Models\Conference;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AttachStaffToConferenceJob
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
        $user = User::findOrFail($this->userId);

        // Validate user exists in system
        if (!$user) {
            throw new \Exception("User with ID {$this->userId} not found in the system.");
        }

        // Validate user type matches role
        if ($this->role === 'site_manager' && $user->user_type !== UserType::SITE_MANAGER) {
            throw new \Exception("User must be of type 'site_manager' to be assigned as a site manager.");
        }

        if ($this->role === 'volunteer' && $user->user_type !== UserType::VOLUNTEER) {
            throw new \Exception("User must be of type 'volunteer' to be assigned as a volunteer.");
        }

        // Check if already attached
        $existing = \DB::table('conference_user')
            ->where('conference_id', $this->conference->id)
            ->where('user_id', $this->userId)
            ->where('role', $this->role)
            ->exists();

        if ($existing) {
            throw new \Exception("User is already assigned to this conference as {$this->role}.");
        }

        // Attach user to conference
        \DB::table('conference_user')->insert([
            'conference_id' => $this->conference->id,
            'user_id' => $this->userId,
            'role' => $this->role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'message' => ucfirst($this->role) . ' attached to conference successfully.',
            'conference_id' => $this->conference->id,
            'user_id' => $this->userId,
            'role' => $this->role,
        ];
    }
}

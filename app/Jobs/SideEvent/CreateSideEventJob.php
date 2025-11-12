<?php

namespace App\Jobs\SideEvent;

use App\Models\Conference;
use App\Models\SideEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CreateSideEventJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $conferenceId,
        public array $data,
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): SideEvent
    {
        // Auto-generate slug if not provided
        if (empty($this->data['slug'])) {
            $this->data['slug'] = Str::slug($this->data['title']);

            // Ensure uniqueness within the conference
            $originalSlug = $this->data['slug'];
            $counter = 1;
            while (SideEvent::where('conference_id', $this->conferenceId)
                ->where('slug', $this->data['slug'])
                ->exists()) {
                $this->data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $this->data['conference_id'] = $this->conferenceId;
        $this->data['created_by'] = $this->userId;

        $sideEvent = SideEvent::create($this->data);

        return $sideEvent->load(['conference', 'creator']);
    }
}

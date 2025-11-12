<?php

namespace App\Jobs\Conference;

use App\Models\Conference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CreateConferenceJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): Conference
    {
        // Auto-generate slug if not provided
        if (empty($this->data['slug'])) {
            $this->data['slug'] = Str::slug($this->data['title']);

            // Ensure uniqueness
            $originalSlug = $this->data['slug'];
            $counter = 1;
            while (Conference::where('slug', $this->data['slug'])->exists()) {
                $this->data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $this->data['created_by'] = $this->userId;

        $conference = Conference::create($this->data);

        return $conference->load(['creator', 'sideEvents']);
    }
}
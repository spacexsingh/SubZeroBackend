<?php

namespace App\Jobs\Conference;

use App\Models\Conference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteConferenceJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Conference $conference
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        return $this->conference->delete();
    }
}
<?php

namespace App\Jobs\SideEvent;

use App\Models\SideEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteSideEventJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SideEvent $sideEvent
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        return $this->sideEvent->delete();
    }
}

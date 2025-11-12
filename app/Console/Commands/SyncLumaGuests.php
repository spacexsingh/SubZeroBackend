<?php

namespace App\Console\Commands;

use App\Models\LumaEvent;
use App\Models\LumaGuest;
use App\Services\LumaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncLumaGuests extends Command
{
    /**
     * The Luma service instance.
     */
    protected LumaService $lumaService;

    /**
     * Create a new command instance.
     */
    public function __construct(LumaService $lumaService)
    {
        parent::__construct();
        $this->lumaService = $lumaService;
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luma:sync-guests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Luma event guests from Luma API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Luma guest sync...');

        // Get all active Luma events with their side event and conference relationships
        $lumaEvents = LumaEvent::where('is_active', true)
            ->with(['sideEvent.conference.lumaApiKey'])
            ->get();

        if ($lumaEvents->isEmpty()) {
            $this->info('No active Luma events found.');
            return 0;
        }

        $this->info("Found {$lumaEvents->count()} active Luma event(s) to sync.");

        foreach ($lumaEvents as $lumaEvent) {
            $this->syncEventGuests($lumaEvent);
        }

        $this->info('Luma guest sync completed!');
        return 0;
    }

    /**
     * Sync guests for a specific Luma event.
     */
    protected function syncEventGuests(LumaEvent $lumaEvent): void
    {
        $conference = $lumaEvent->sideEvent->conference ?? null;

        if (!$conference) {
            $this->warn("Skipping {$lumaEvent->name}: No associated conference found.");
            return;
        }

        $lumaApiKey = $conference->lumaApiKey ?? null;

        if (!$lumaApiKey || !$lumaApiKey->is_active) {
            $this->warn("Skipping {$lumaEvent->name}: No active Luma API key for conference {$conference->title}.");
            return;
        }

        $this->info("Syncing guests for: {$lumaEvent->name} (Event ID: {$lumaEvent->luma_event_id})");

        try {
            $syncedCount = 0;
            $updatedCount = 0;
            $cursor = null;
            $hasMorePages = true;
            $pageCount = 0;
            $maxPages = 200; // Safety limit to prevent infinite loops

            // Paginate through all guests using cursor
            while ($hasMorePages && $pageCount < $maxPages) {
                $pageCount++;

                $response = $this->fetchGuestsFromLuma($lumaEvent->luma_event_id, $lumaApiKey->api_key, $cursor);

                $guests = $response['entries'] ?? [];

                if (empty($guests)) {
                    break;
                }

                foreach ($guests as $guestData) {
                    $wasUpdated = $this->upsertGuest($lumaEvent, $guestData);

                    if ($wasUpdated) {
                        $updatedCount++;
                    } else {
                        $syncedCount++;
                    }
                }

                // Check if there's a next page
                $newCursor = $response['next_cursor'] ?? null;
                $hasMore = $response['has_more'] ?? false;

                // Stop if we have the same cursor (prevents infinite loop)
                if ($newCursor === $cursor) {
                    $this->warn("  Cursor not changing, stopping pagination to prevent infinite loop");
                    break;
                }

                $cursor = $newCursor;
                $hasMorePages = $hasMore && !empty($cursor);
            }

            if ($pageCount >= $maxPages) {
                $this->warn("  Reached maximum page limit of {$maxPages} pages, stopping sync");
            }

            // Update sync metadata
            $lumaEvent->update([
                'last_synced_at' => now(),
                'cursor' => $cursor, // Store the last cursor for reference
            ]);

            if ($syncedCount === 0 && $updatedCount === 0) {
                $this->info("  No guests found for {$lumaEvent->name}");
            } else {
                $this->info("  Synced: {$syncedCount} new, {$updatedCount} updated guests for {$lumaEvent->name}");
            }
        } catch (\Exception $e) {
            $this->error("  Failed to sync {$lumaEvent->name}: {$e->getMessage()}");
            Log::error("Luma sync failed for event {$lumaEvent->luma_event_id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Fetch guests from Luma API using the service.
     */
    protected function fetchGuestsFromLuma(string $lumaEventId, string $apiKey, ?string $cursor = null): array
    {
        return $this->lumaService->getGuests($lumaEventId, $apiKey, $cursor);
    }

    /**
     * Insert or update a guest record.
     */
    protected function upsertGuest(LumaEvent $lumaEvent, array $guestData): bool
    {
        // Extract guest API ID and nested guest object
        $guestApiId = $guestData['api_id'] ?? null;
        $guestInfo = $guestData['guest'] ?? [];

        if (!$guestApiId) {
            return false;
        }

        $guest = LumaGuest::where('guest_id', $guestApiId)->first();

        $guestAttributes = [
            'luma_event_id' => $lumaEvent->id,
            'guest_id' => $guestApiId,
            'luma_user_id' => $guestInfo['user_id'] ?? null,
            'approval_status' => $guestInfo['approval_status'] ?? 'pending',
            'user_name' => $guestInfo['name'] ?? '',
            'user_first_name' => $guestInfo['first_name'] ?? '',
            'user_last_name' => $guestInfo['last_name'] ?? '',
            'user_email' => $guestInfo['email'] ?? '',
            'registered_at' => $guestInfo['registered_at'] ?? null,
            'checked_in_at' => $guestInfo['checked_in_at'] ?? null,
            'registration_answers' => $guestInfo['registration_answers'] ?? null,
            'event_tickets' => $guestInfo['event_tickets'] ?? null,
            'event_ticket' => $guestInfo['event_ticket'] ?? null,
            'event_ticket_orders' => $guestInfo['event_ticket_orders'] ?? null,
            'raw_data' => $guestData,
        ];

        if ($guest) {
            $guest->update($guestAttributes);
            return true; // Updated
        } else {
            LumaGuest::create($guestAttributes);
            return false; // Created
        }
    }
}

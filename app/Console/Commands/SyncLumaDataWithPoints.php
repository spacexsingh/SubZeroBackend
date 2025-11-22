<?php

namespace App\Console\Commands;

use App\Models\LumaEvent;
use App\Models\LumaGuest;
use App\Models\User;
use App\Models\PointAction;
use App\Models\PointTransaction;
use App\Models\LumaGuestWalletAddress;
use App\Services\LumaService;
use App\Traits\ManagesLumaGuestUsers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncLumaDataWithPoints extends Command
{
    use ManagesLumaGuestUsers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luma:sync-with-points
                            {--event-id= : Specific Luma event ID to sync}
                            {--skip-guests : Skip syncing guests, only sync points}
                            {--force : Force re-sync even if already synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Luma event data including guests, wallet addresses, and point transactions';

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
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting comprehensive Luma data sync...');

        // Get events to sync
        $lumaEvents = $this->getLumaEventsToSync();

        if ($lumaEvents->isEmpty()) {
            $this->info('No Luma events found to sync.');
            return 0;
        }

        $this->info("Found {$lumaEvents->count()} Luma event(s) to sync.\n");

        foreach ($lumaEvents as $lumaEvent) {
            $this->syncEvent($lumaEvent);
        }

        $this->info("\n✅ Luma data sync completed!");
        return 0;
    }

    /**
     * Get Luma events to sync based on command options.
     */
    protected function getLumaEventsToSync()
    {
        $query = LumaEvent::with(['sideEvent.conference.lumaApiKey']);

        if ($eventId = $this->option('event-id')) {
            $query->where('luma_event_id', $eventId);
        } else {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Sync a single Luma event.
     */
    protected function syncEvent(LumaEvent $lumaEvent): void
    {
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📋 Event: {$lumaEvent->name}");
        $this->line("   ID: {$lumaEvent->luma_event_id}");

        $conference = $lumaEvent->sideEvent->conference ?? null;

        if (!$conference) {
            $this->warn("⚠️  Skipping: No associated conference found.\n");
            return;
        }

        $lumaApiKey = $conference->lumaApiKey ?? null;

        if (!$lumaApiKey || !$lumaApiKey->is_active) {
            $this->warn("⚠️  Skipping: No active Luma API key for conference {$conference->title}.\n");
            return;
        }

        try {
            DB::beginTransaction();

            // Sync guests first (unless skipped)
            if (!$this->option('skip-guests')) {
                $this->syncEventGuests($lumaEvent, $lumaApiKey->api_key);
            }

            // Then process point transactions from registration answers
            $this->syncPointTransactions($lumaEvent);

            DB::commit();

            // Update sync timestamp
            $lumaEvent->update(['last_synced_at' => now()]);

            $this->info("✓ Successfully synced {$lumaEvent->name}\n");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("✗ Failed to sync {$lumaEvent->name}: {$e->getMessage()}\n");
            Log::error("Luma sync failed for event {$lumaEvent->luma_event_id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Sync guests for a specific Luma event.
     */
    protected function syncEventGuests(LumaEvent $lumaEvent, string $apiKey): void
    {
        $this->line("   📥 Syncing guests...");

        $syncedCount = 0;
        $updatedCount = 0;
        $cursor = null;
        $hasMorePages = true;
        $pageCount = 0;
        $maxPages = 200;

        while ($hasMorePages && $pageCount < $maxPages) {
            $pageCount++;

            $response = $this->lumaService->getGuests($lumaEvent->luma_event_id, $apiKey, $cursor, true);

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

            // Handle pagination
            $newCursor = $response['next_cursor'] ?? null;
            $hasMore = $response['has_more'] ?? false;

            if ($newCursor === $cursor) {
                $this->warn("      ⚠️  Cursor not changing, stopping pagination");
                break;
            }

            $cursor = $newCursor;
            $hasMorePages = $hasMore && !empty($cursor);
        }

        if ($syncedCount > 0 || $updatedCount > 0) {
            $this->info("      ✓ Guests: {$syncedCount} new, {$updatedCount} updated");
        } else {
            $this->line("      → No new guests");
        }
    }

    /**
     * Insert or update a guest record.
     */
    protected function upsertGuest(LumaEvent $lumaEvent, array $guestData): bool
    {
        $guestApiId = $guestData['api_id'] ?? null;
        $guestInfo = $guestData['guest'] ?? [];

        if (!$guestApiId) {
            return false;
        }

        // Determine user type
        $userType = $this->determineUserType($guestInfo);

        // Check if guest already exists
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
            // Update existing guest
            $guest->update($guestAttributes);

            // Create user if doesn't exist
            if (!$guest->user_id) {
                $user = $this->createUser($guestInfo, $userType);
                $guest->update(['user_id' => $user->id]);
            }

            // Process wallet addresses from registration answers
            $this->processWalletAddresses($guest, $guestInfo);

            return true; // Updated
        } else {
            // Create new user
            $user = $this->createUser($guestInfo, $userType);

            // Create new guest
            $guestAttributes['user_id'] = $user->id;
            $newGuest = LumaGuest::create($guestAttributes);

            // Process wallet addresses
            $this->processWalletAddresses($newGuest, $guestInfo);

            return false; // Created
        }
    }

    /**
     * Process wallet addresses from registration answers.
     */
    protected function processWalletAddresses(LumaGuest $guest, array $guestInfo): void
    {
        $registrationAnswers = $guestInfo['registration_answers'] ?? [];

        if (empty($registrationAnswers)) {
            return;
        }

        foreach ($registrationAnswers as $answer) {
            $question = strtolower($answer['question'] ?? '');
            $value = $answer['value'] ?? '';

            // Look for wallet address questions (common patterns)
            if (
                str_contains($question, 'wallet') ||
                str_contains($question, 'address') ||
                str_contains($question, 'nova') ||
                str_contains($question, 'polkadot')
            ) {
                if (!empty($value) && is_string($value)) {
                    // Store or update wallet address
                    LumaGuestWalletAddress::updateOrCreate(
                        [
                            'luma_guest_id' => $guest->id,
                            'address' => $value,
                        ],
                        [
                            'chain' => $this->detectChain($question, $value),
                            'is_verified' => false,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Detect blockchain chain from question or address format.
     */
    protected function detectChain(string $question, string $address): string
    {
        // All addresses are Polkadot/Substrate addresses
        return 'polkadot';
    }

    /**
     * Sync point transactions from guest data.
     */
    protected function syncPointTransactions(LumaEvent $lumaEvent): void
    {
        $this->line("   💰 Processing point transactions...");

        // Get all guests for this event with users
        $guests = LumaGuest::where('luma_event_id', $lumaEvent->id)
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        if ($guests->isEmpty()) {
            $this->line("      → No guests with users found");
            return;
        }

        $transactionsCreated = 0;

        // Create default point actions if they don't exist
        $earnAction = PointAction::firstOrCreate(
            ['code' => 'conference_activity'],
            [
                'name' => 'Conference Activity',
                'points' => 0, // Variable points
                'meta' => ['type' => 'earn', 'auto_assigned' => true]
            ]
        );

        $redeemAction = PointAction::firstOrCreate(
            ['code' => 'merchandise_redemption'],
            [
                'name' => 'Merchandise Redemption',
                'points' => 0, // Variable points
                'meta' => ['type' => 'redeem', 'auto_assigned' => true]
            ]
        );

        foreach ($guests as $guest) {
            // Try to extract point data from registration answers or raw_data
            $pointData = $this->extractPointData($guest);

            if (!empty($pointData)) {
                $transactions = $this->createPointTransactions(
                    $guest->user,
                    $pointData,
                    $earnAction,
                    $redeemAction
                );
                $transactionsCreated += $transactions;
            }
        }

        if ($transactionsCreated > 0) {
            $this->info("      ✓ Created {$transactionsCreated} point transactions");
        } else {
            $this->line("      → No point data found in registration answers");
            $this->line("      💡 Tip: Point data should be in registration_answers or custom fields");
        }
    }

    /**
     * Extract point data from guest registration answers.
     */
    protected function extractPointData(LumaGuest $guest): array
    {
        $registrationAnswers = $guest->registration_answers ?? [];
        $pointData = [];

        if (empty($registrationAnswers)) {
            return $pointData;
        }

        // Look for point-related fields in registration answers
        foreach ($registrationAnswers as $answer) {
            $question = strtolower($answer['question'] ?? '');
            $value = $answer['value'] ?? '';

            // Match common point field patterns
            if (preg_match('/(earned|redeemed).*(\d{1,2})[_\s-]*(nov|november)/i', $question, $matches)) {
                $type = strtolower($matches[1]); // earned or redeemed
                $day = $matches[2]; // day number
                $pointData["{$type}_{$day}"] = is_numeric($value) ? (int) $value : 0;
            }
        }

        return $pointData;
    }

    /**
     * Create point transactions for a user.
     */
    protected function createPointTransactions(
        User $user,
        array $pointData,
        PointAction $earnAction,
        PointAction $redeemAction
    ): int {
        $count = 0;

        // Process earned and redeemed points by day
        foreach ($pointData as $key => $points) {
            if ($points == 0) {
                continue;
            }

            [$type, $day] = explode('_', $key);
            $isEarn = $type === 'earned';

            // Create transaction date (assuming November 2025 for sub0)
            $date = Carbon::create(2025, 11, (int) $day, 12, 0, 0);

            // Skip if transaction already exists
            $exists = PointTransaction::where('user_id', $user->id)
                ->where('type', $isEarn ? 'earn' : 'redeem')
                ->whereDate('created_at', $date->toDateString())
                ->exists();

            if ($exists && !$this->option('force')) {
                continue;
            }

            // Create transaction
            PointTransaction::create([
                'user_id' => $user->id,
                'type' => $isEarn ? 'earn' : 'redeem',
                'points' => abs($points),
                'point_action_id' => $isEarn ? $earnAction->id : $redeemAction->id,
                'meta' => [
                    'source' => 'luma_sync',
                    'date' => $date->toDateString(),
                    'auto_imported' => true,
                ],
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $count++;
        }

        return $count;
    }
}
<?php

namespace App\Jobs;

use App\Models\LumaEvent;
use App\Models\LumaGuest;
use App\Services\LumaService;
use App\Traits\ManagesLumaGuestUsers;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterLumaGuestJob implements ShouldQueue
{
    use Queueable, ManagesLumaGuestUsers;

    public string $guestApiId;
    public string $eventApiId;
    public string $apiKey;

    /**
     * Create a new job instance.
     */
    public function __construct(string $guestApiId, string $eventApiId, string $apiKey)
    {
        $this->guestApiId = $guestApiId;
        $this->eventApiId = $eventApiId;
        $this->apiKey = $apiKey;
    }

    /**
     * Execute the job.
     */
    public function handle(LumaService $lumaService): array
    {
        try {
            return DB::transaction(function () use ($lumaService) {
                // Fetch guest data from Luma API
                $guestResponse = $lumaService->getGuestById($this->guestApiId, $this->eventApiId, $this->apiKey);
                $guestData = $guestResponse['guest'] ?? null;

                if (!$guestData) {
                    throw new \Exception("Guest data not found in API response");
                }

                // Find the luma event
                $lumaEvent = LumaEvent::where('luma_event_id', $this->eventApiId)->firstOrFail();

                // Extract guest information
                $guestInfo = $guestData['guest'] ?? $guestData;
                $guestApiId = $guestData['api_id'] ?? $this->guestApiId;

                // Determine user type based on event_ticket name
                $userType = $this->determineUserType($guestInfo);

                // Check if guest already exists
                $lumaGuest = LumaGuest::where('guest_id', $guestApiId)->first();

                if ($lumaGuest) {
                    // Update existing guest
                    $this->updateLumaGuest($lumaGuest, $lumaEvent->id, $guestInfo, $guestData);

                    // If no user exists, create one
                    if (!$lumaGuest->user_id) {
                        $user = $this->createUser($guestInfo, $userType);
                        $lumaGuest->update(['user_id' => $user->id]);
                    } else {
                        $user = $lumaGuest->user;
                    }
                } else {
                    // Create new user first
                    $user = $this->createUser($guestInfo, $userType);

                    // Create new guest
                    $lumaGuest = $this->createLumaGuest($lumaEvent->id, $user->id, $guestApiId, $guestInfo, $guestData);
                }

                return [
                    'luma_guest_id' => $lumaGuest->id,
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to register Luma guest', [
                'guest_api_id' => $this->guestApiId,
                'event_api_id' => $this->eventApiId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a new Luma guest record.
     */
    protected function createLumaGuest(int $lumaEventId, int $userId, string $guestApiId, array $guestInfo, array $rawData): LumaGuest
    {
        return LumaGuest::create([
            'luma_event_id' => $lumaEventId,
            'user_id' => $userId,
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
            'raw_data' => $rawData,
        ]);
    }

    /**
     * Update an existing Luma guest record.
     */
    protected function updateLumaGuest(LumaGuest $lumaGuest, int $lumaEventId, array $guestInfo, array $rawData): void
    {
        $lumaGuest->update([
            'luma_event_id' => $lumaEventId,
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
            'raw_data' => $rawData,
        ]);
    }
}

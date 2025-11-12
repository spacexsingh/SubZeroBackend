<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RegisterLumaGuestJob;
use App\Models\LumaEvent;
use App\Models\LumaGuest;
use App\Models\LumaGuestWalletAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LumaGuestController extends Controller
{
    /**
     * Register a Luma guest from QR code scan.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|string',
            'qr_code_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Parse the QR code URL to extract guest_id from 'pk' query parameter
            // Example: https://luma.com/check-in/evt-qi1HirmtDE6sCg6?pk=g-418le06V5cDsoA9
            $parsed = parse_url($request->qr_code_url);

            if (!isset($parsed['query'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code URL format - missing query parameters',
                ], 400);
            }

            // Extract guest_id from pk query parameter
            parse_str($parsed['query'], $queryParams);
            $guestId = $queryParams['pk'] ?? null;

            if (!$guestId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not extract guest ID from QR code URL (pk parameter missing)',
                ], 400);
            }

            // Find the luma event to get the API key
            $lumaEvent = LumaEvent::where('luma_event_id', $request->event_id)
                ->with('sideEvent.conference.lumaApiKey')
                ->firstOrFail();

            $apiKey = $lumaEvent->sideEvent->conference->lumaApiKey->api_key ?? null;

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Luma API key not configured for this event',
                ], 400);
            }

            // Dispatch the job synchronously to get immediate results
            $job = new RegisterLumaGuestJob(
                $guestId,
                $request->event_id,
                $apiKey
            );

            $result = $job->handle(app(\App\Services\LumaService::class));

            return response()->json([
                'success' => true,
                'message' => 'Guest registered successfully',
                'data' => $result,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found in our system',
            ], 404);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Check if error is from Luma API (guest not found - 404)
            if (str_contains($errorMessage, 'Luma API error: 404')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guest not found in Luma',
                ], 404);
            }

            // Check for other Luma API client errors (400-499)
            if (preg_match('/Luma API error: (4\d{2})/', $errorMessage, $matches)) {
                $statusCode = (int) $matches[1];
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request to Luma API',
                ], $statusCode);
            }

            // Log unexpected errors
            Log::error('Unexpected error in guest registration', [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register guest',
            ], 500);
        }
    }

    /**
     * Connect a wallet address to a Luma guest.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function connectWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'luma_guest_id' => 'required|integer|exists:luma_guests,id',
            'wallet_address' => 'required|string|max:255',
            'wallet_type' => 'sometimes|string|in:polkadot,ethereum,bitcoin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $walletType = $request->wallet_type ?? 'polkadot';

            // Validate Polkadot wallet address format
            if ($walletType === 'polkadot') {
                if (!$this->isValidPolkadotAddress($request->wallet_address)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid Polkadot wallet address format',
                    ], 422);
                }
            }

            $lumaGuest = LumaGuest::findOrFail($request->luma_guest_id);

            // Check if wallet already exists for this guest and type
            $walletType = $request->wallet_type ?? 'polkadot';
            $existingWallet = LumaGuestWalletAddress::where('luma_guest_id', $lumaGuest->id)
                ->where('wallet_type', $walletType)
                ->first();

            if ($existingWallet) {
                // Update existing wallet
                $existingWallet->update([
                    'wallet_address' => $request->wallet_address,
                ]);

                $walletAddress = $existingWallet;
                $action = 'updated';
            } else {
                // Create new wallet address
                $walletAddress = LumaGuestWalletAddress::create([
                    'luma_guest_id' => $lumaGuest->id,
                    'wallet_address' => $request->wallet_address,
                    'wallet_type' => $walletType,
                ]);

                $action = 'connected';

                // Update status to wallet_registered when first wallet is connected
                $lumaGuest->updateStatus('wallet_registered', 'Wallet address connected');
            }

            return response()->json([
                'success' => true,
                'message' => "Wallet {$action} successfully",
                'data' => [
                    'wallet_address_id' => $walletAddress->id,
                    'luma_guest_id' => $lumaGuest->id,
                    'wallet_address' => $walletAddress->wallet_address,
                    'wallet_type' => $walletAddress->wallet_type,
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Luma guest not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to connect wallet', [
                'error' => $e->getMessage(),
                'luma_guest_id' => $request->luma_guest_id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect wallet',
            ], 500);
        }
    }

    /**
     * Get user details by wallet address.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserByWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string|max:255',
            'wallet_type' => 'sometimes|string|in:polkadot,ethereum,bitcoin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $walletType = $request->wallet_type ?? 'polkadot';

            // Find wallet address
            $walletAddress = LumaGuestWalletAddress::where('wallet_address', $request->wallet_address)
                ->where('wallet_type', $walletType)
                ->with(['lumaGuest.user'])
                ->first();

            if (!$walletAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet address not found',
                ], 404);
            }

            $lumaGuest = $walletAddress->lumaGuest;
            $user = $lumaGuest->user;

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found for this wallet address',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User found successfully',
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'luma_guest_id' => $lumaGuest->id,
                    'current_status' => $lumaGuest->current_status,
                    'wallet_address' => $walletAddress->wallet_address,
                    'wallet_type' => $walletAddress->wallet_type,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve user by wallet', [
                'error' => $e->getMessage(),
                'wallet_address' => $request->wallet_address ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user details',
            ], 500);
        }
    }

    /**
     * Update the status of a Luma guest.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:synced,app_registered,wallet_registered,nfc_initialized',
            'notes' => 'sometimes|string|max:500',
        ]);

        Log::info('in updateStatus', $request->all());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $lumaGuest = LumaGuest::findOrFail($id);

            // Update status
            $lumaGuest->updateStatus(
                $request->status,
                $request->notes ?? "Status updated via API"
            );

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => [
                    'luma_guest_id' => $lumaGuest->id,
                    'current_status' => $lumaGuest->current_status,
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Luma guest not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to update guest status', [
                'error' => $e->getMessage(),
                'luma_guest_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
            ], 500);
        }
    }

    /**
     * Validate Polkadot/Substrate address format.
     *
     * @param string $address
     * @return bool
     */
    protected function isValidPolkadotAddress(string $address): bool
    {
        // Polkadot addresses are base58 encoded and typically 47-48 characters long
        if (strlen($address) < 47 || strlen($address) > 48) {
            return false;
        }

        // Check if it matches the base58 character set
        // Polkadot uses SS58 address format which is base58 encoded
        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]+$/', $address)) {
            return false;
        }

        // Additional check: Polkadot addresses typically start with specific prefixes
        // 1-5: Polkadot mainnet, F-G: Kusama
        $firstChar = $address[0];
        $validPrefixes = ['1', '2', '3', '4', '5', 'F', 'G', 'C', 'D', 'E', 'H', 'J'];

        if (!in_array($firstChar, $validPrefixes)) {
            return false;
        }

        return true;
    }
}

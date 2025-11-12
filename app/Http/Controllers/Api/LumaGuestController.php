<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RegisterLumaGuestJob;
use App\Models\LumaEvent;
use App\Models\LumaGuest;
use App\Models\LumaGuestWalletAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            // Check if error is from Luma API (guest not found)
            if (str_contains($e->getMessage(), 'Luma API error: 404')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guest not found in Luma',
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to register guest',
                'error' => $e->getMessage(),
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect wallet',
                'error' => $e->getMessage(),
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
                    'wallet_address' => $walletAddress->wallet_address,
                    'wallet_type' => $walletAddress->wallet_type,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
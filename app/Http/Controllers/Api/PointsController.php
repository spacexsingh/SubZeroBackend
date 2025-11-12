<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LumaGuestWalletAddress;
use App\Models\Merchandise;
use App\Models\PointAction;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PointsController extends Controller
{
    /** GET /api/points/balance*/
    public function balance(Request $request)
    {
        $data = $request->validate([
            'wallet_address' => ['required', 'string', 'exists:luma_guest_wallet_addresses,wallet_address'],
        ]);

        $lumaGuestWalletAddresses = LumaGuestWalletAddress::where('wallet_address', $data['wallet_address'])->first();

        $balance = PointTransaction::where('user_id', $lumaGuestWalletAddresses->lumaGuest->user_id)->sum('points');

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_address' => $data['wallet_address'],
                'balance' => (int) $balance,
            ],
        ]);
    }

    /** GET /api/points/transactions?user_id= */
    public function transactions(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required','integer','exists:users,id'],
        ]);

        $tx = PointTransaction::with(['action:id,code,name','merchandise:id,name'])
            ->where('user_id', $data['user_id'])
            ->orderByDesc('id')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $tx,
        ]);
    }

    /** POST /api/points/earn { wallet_address, action_code } */
    public function earn(Request $request)
    {
        $data = $request->validate([
            'wallet_address' => ['required', 'string', 'exists:luma_guest_wallet_addresses,wallet_address'],
            'action_code' => ['required', 'string', Rule::exists('point_actions', 'code')],
        ]);

        $action = PointAction::where('code', $data['action_code'])->firstOrFail();

        $lumaGuestWalletAddresses = LumaGuestWalletAddress::where('wallet_address', $data['wallet_address'])->first();
        $userId = $lumaGuestWalletAddresses->lumaGuest->user_id;

        $alreadyEarned = PointTransaction::where('user_id', $userId)
            ->where('point_action_id', $action->id)
            ->where('type', 'earn')
            ->exists();

        if ($alreadyEarned) {
            return response()->json([
                'success' => false,
                'message' => "You have already earned points for '{$action->name}'.",
            ], 422);
        }

        // ✅ Record transaction
        $tx = PointTransaction::create([
            'user_id'        => $userId,
            'type'           => 'earn',
            'points'         => $action->points,
            'point_action_id'=> $action->id,
        ]);

        $balance = PointTransaction::where('user_id', $userId)->sum('points');

        return response()->json([
            'success' => true,
            'message' => "Earned {$action->points} points for '{$action->name}'.",
            'data'    => ['transaction_id' => $tx->id, 'new_balance' => (int) $balance],
        ]);
    }

    /** POST /api/points/redeem { wallet_address, merch_code } */
    public function redeem(Request $request)
    {
        $data = $request->validate([
            'wallet_address' => ['required', 'string', 'exists:luma_guest_wallet_addresses,wallet_address'],
            'merch_code' => ['required', 'string', Rule::exists('merchandises', 'code')],
        ]);

        $lumaGuestWalletAddresses = LumaGuestWalletAddress::where('wallet_address', $data['wallet_address'])->first();

        $user = $lumaGuestWalletAddresses->lumaGuest->user;
        $merch = Merchandise::where('code', $data['merch_code'])->firstOrFail();

        return DB::transaction(function () use ($user, $merch) {
            $balance = PointTransaction::where('user_id', $user->id)->sum('points');
            if ($balance < $merch->points_cost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient points to redeem this item.',
                    'data'    => ['balance' => (int)$balance, 'cost' => (int)$merch->points_cost],
                ], 422);
            }

            if (!is_null($merch->stock) && $merch->stock <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This merchandise is out of stock.',
                ], 422);
            }

            // Spend (negative points)
            $tx = PointTransaction::create([
                'user_id'        => $user->id,
                'type'           => 'spend',
                'points'         => -1 * (int)$merch->points_cost,
                'merchandise_id' => $merch->id,
            ]);

            // Decrement stock if tracked
            if (!is_null($merch->stock)) {
                $merch->decrement('stock');
            }

            $newBalance = $balance - (int)$merch->points_cost;

            return response()->json([
                'success' => true,
                'message' => "Redeemed '{$merch->name}' for {$merch->points_cost} points.",
                'data'    => ['transaction_id' => $tx->id, 'new_balance' => (int)$newBalance],
            ]);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LumaGuestWalletAddress extends Model
{
    protected $fillable = [
        'luma_guest_id',
        'wallet_address',
        'wallet_type',
    ];

    /**
     * Get the luma guest that owns this wallet address.
     */
    public function lumaGuest(): BelongsTo
    {
        return $this->belongsTo(LumaGuest::class);
    }
}
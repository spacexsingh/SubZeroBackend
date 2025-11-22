<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointBalanceSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'luma_event_id',
        'snapshot_date',
        'earned_points',
        'redeemed_points',
        'balance',
        'meta',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'meta' => 'array',
    ];

    /**
     * Get the user that owns this snapshot.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Luma event associated with this snapshot.
     */
    public function lumaEvent(): BelongsTo
    {
        return $this->belongsTo(LumaEvent::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LumaGuestStatusHistory extends Model
{
    protected $table = 'luma_guest_status_history';

    protected $fillable = [
        'luma_guest_id',
        'status',
        'from_status',
        'notes',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    /**
     * Get the luma guest that owns this status history.
     */
    public function lumaGuest(): BelongsTo
    {
        return $this->belongsTo(LumaGuest::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LumaGuest extends Model
{
    protected $fillable = [
        'luma_event_id',
        'user_id',
        'guest_id',
        'luma_user_id',
        'approval_status',
        'user_name',
        'user_first_name',
        'user_last_name',
        'user_email',
        'registered_at',
        'checked_in_at',
        'registration_answers',
        'event_tickets',
        'event_ticket',
        'event_ticket_orders',
        'raw_data',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'registration_answers' => 'array',
        'event_tickets' => 'array',
        'event_ticket' => 'array',
        'event_ticket_orders' => 'array',
        'raw_data' => 'array',
    ];

    /**
     * Get the Luma event that owns this guest.
     */
    public function lumaEvent(): BelongsTo
    {
        return $this->belongsTo(LumaEvent::class);
    }

    /**
     * Get the user that this guest is associated with.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet addresses for this guest.
     */
    public function walletAddresses(): HasMany
    {
        return $this->hasMany(LumaGuestWalletAddress::class);
    }
}

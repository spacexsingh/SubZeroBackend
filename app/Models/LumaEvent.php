<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LumaEvent extends Model
{
    protected $fillable = [
        'side_event_id',
        'luma_event_id',
        'name',
        'url',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the side event that owns this Luma event.
     */
    public function sideEvent(): BelongsTo
    {
        return $this->belongsTo(SideEvent::class);
    }

    /**
     * Get the guests for this Luma event.
     */
    public function guests(): HasMany
    {
        return $this->hasMany(LumaGuest::class);
    }
}

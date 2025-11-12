<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LumaApiKey extends Model
{
    protected $fillable = [
        'conference_id',
        'api_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the conference that owns this API key.
     */
    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }
}

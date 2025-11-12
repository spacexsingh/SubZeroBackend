<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SideEvent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'conference_id',
        'title',
        'slug',
        'description',
        'start_datetime',
        'end_datetime',
        'venue_name',
        'venue_address',
        'room_number',
        'event_type',
        'sort_order',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the conference this side event belongs to.
     */
    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    /**
     * Get the creator of this side event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the site managers assigned to this side event.
     */
    public function siteManagers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'side_event_user')
                    ->wherePivot('role', 'site_manager')
                    ->withTimestamps();
    }

    /**
     * Get the volunteers assigned to this side event.
     */
    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'side_event_user')
                    ->wherePivot('role', 'volunteer')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active side events.
     */
    public function scopeActive($query)
    {
        return $query->where('start_datetime', '<=', now())
                     ->where('end_datetime', '>=', now());
    }

    /**
     * Scope a query to only include upcoming side events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>', now())
                     ->orderBy('start_datetime');
    }

    /**
     * Scope a query to only include past side events.
     */
    public function scopePast($query)
    {
        return $query->where('end_datetime', '<', now())
                     ->orderBy('start_datetime', 'desc');
    }

    /**
     * Scope a query to filter by event type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Check if the side event is currently active/ongoing.
     */
    public function isActive(): bool
    {
        $now = now();
        return $now->gte($this->start_datetime) && $now->lte($this->end_datetime);
    }

    /**
     * Check if the side event is in the future.
     */
    public function isUpcoming(): bool
    {
        return now()->lt($this->start_datetime);
    }

    /**
     * Check if the side event has ended.
     */
    public function isPast(): bool
    {
        return now()->gt($this->end_datetime);
    }

    /**
     * Get the duration of the side event in minutes.
     */
    public function getDurationInMinutes(): int
    {
        return $this->start_datetime->diffInMinutes($this->end_datetime);
    }

    /**
     * Get the Luma event for this side event.
     */
    public function lumaEvent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LumaEvent::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conference extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'start_datetime',
        'end_datetime',
        'registration_start_datetime',
        'registration_end_datetime',
        'timezone',
        'venue_name',
        'venue_address',
        'city',
        'state',
        'country',
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
            'registration_start_datetime' => 'datetime',
            'registration_end_datetime' => 'datetime',
        ];
    }

    /**
     * Get the side events for this conference.
     */
    public function sideEvents(): HasMany
    {
        return $this->hasMany(SideEvent::class)->orderBy('sort_order');
    }

    /**
     * Get the creator/organizer of this conference.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the site managers assigned to this conference.
     */
    public function siteManagers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conference_user')
                    ->wherePivot('role', 'site_manager')
                    ->withTimestamps();
    }

    /**
     * Get the volunteers assigned to this conference.
     */
    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conference_user')
                    ->wherePivot('role', 'volunteer')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active conferences.
     */
    public function scopeActive($query)
    {
        return $query->where('start_datetime', '<=', now())
                     ->where('end_datetime', '>=', now());
    }

    /**
     * Scope a query to only include upcoming conferences.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>', now())
                     ->orderBy('start_datetime');
    }

    /**
     * Scope a query to only include past conferences.
     */
    public function scopePast($query)
    {
        return $query->where('end_datetime', '<', now())
                     ->orderBy('start_datetime', 'desc');
    }

    /**
     * Check if registration is open for this conference.
     */
    public function isRegistrationOpen(): bool
    {
        $now = now();

        if ($this->registration_start_datetime && $now->lt($this->registration_start_datetime)) {
            return false;
        }

        if ($this->registration_end_datetime && $now->gt($this->registration_end_datetime)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the conference is currently active/ongoing.
     */
    public function isActive(): bool
    {
        $now = now();
        return $now->gte($this->start_datetime) && $now->lte($this->end_datetime);
    }

    /**
     * Check if the conference is in the future.
     */
    public function isUpcoming(): bool
    {
        return now()->lt($this->start_datetime);
    }

    /**
     * Check if the conference has ended.
     */
    public function isPast(): bool
    {
        return now()->gt($this->end_datetime);
    }

    /**
     * Get the Luma API key for this conference.
     */
    public function lumaApiKey(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LumaApiKey::class);
    }
}

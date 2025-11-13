<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserType::class,
        ];
    }

    /**
     * Helper methods for user type checking
     */
    public function isAdministrator(): bool
    {
        return $this->user_type === UserType::ADMINISTRATOR;
    }

    public function isAdministratorAssistant(): bool
    {
        return $this->user_type === UserType::ADMINISTRATOR_ASSISTANT;
    }

    public function isSiteManager(): bool
    {
        return $this->user_type === UserType::SITE_MANAGER;
    }

    public function isVolunteer(): bool
    {
        return $this->user_type === UserType::VOLUNTEER;
    }

    public function isVipAttendee(): bool
    {
        return $this->user_type === UserType::VIP_ATTENDEE;
    }

    public function isGeneralAttendee(): bool
    {
        return $this->user_type === UserType::GENERAL_ATTENDEE;
    }

    public function isAttendee(): bool
    {
        return $this->user_type->isAttendee();
    }

    public function isStaff(): bool
    {
        return $this->user_type->isStaff();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->user_type->can($permission);
    }

    public function hasHigherOrEqualPrivilegeThan(User $user): bool
    {
        return $this->user_type->hasHigherOrEqualPrivilegeThan($user->user_type);
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow administrators and administrator assistants to access the admin panel
        return $this->isAdministrator() || $this->isAdministratorAssistant();
    }
}

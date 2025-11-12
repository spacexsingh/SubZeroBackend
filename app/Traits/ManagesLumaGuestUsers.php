<?php

namespace App\Traits;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait ManagesLumaGuestUsers
{
    /**
     * Determine user type based on event ticket name.
     */
    protected function determineUserType(array $guestInfo): UserType
    {
        $eventTicket = $guestInfo['event_ticket'] ?? [];
        $ticketName = strtolower($eventTicket['name'] ?? '');

        // Check if ticket name contains "vip"
        if (str_contains($ticketName, 'vip')) {
            return UserType::VIP_ATTENDEE;
        }

        return UserType::GENERAL_ATTENDEE;
    }

    /**
     * Create a new user.
     */
    protected function createUser(array $guestInfo, UserType $userType): User
    {
        $firstName = $guestInfo['first_name'] ?? '';
        $lastName = $guestInfo['last_name'] ?? '';
        $name = $guestInfo['name'] ?? trim("{$firstName} {$lastName}");
        $email = $guestInfo['email'] ?? '';

        // Check if user already exists with this email
        if ($email && $existingUser = User::where('email', $email)->first()) {
            return $existingUser;
        }

        return User::create([
            'name' => $name ?: 'Guest User',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'user_type' => $userType,
            'password' => Hash::make(Str::random(32)), // Random password, user will need to reset
        ]);
    }
}
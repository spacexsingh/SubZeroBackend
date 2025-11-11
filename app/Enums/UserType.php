<?php

namespace App\Enums;

enum UserType: string
{
    case ADMINISTRATOR = 'administrator';
    case ADMINISTRATOR_ASSISTANT = 'administrator_assistant';
    case SITE_MANAGER = 'site_manager';
    case VOLUNTEER = 'volunteer';
    case VIP_ATTENDEE = 'vip_attendee';
    case GENERAL_ATTENDEE = 'general_attendee';

    /**
     * Get the hierarchy level of the user type (higher = more privileges)
     */
    public function hierarchy(): int
    {
        return match($this) {
            self::ADMINISTRATOR => 6,
            self::ADMINISTRATOR_ASSISTANT => 5,
            self::SITE_MANAGER => 4,
            self::VOLUNTEER => 3,
            self::VIP_ATTENDEE => 2,
            self::GENERAL_ATTENDEE => 1,
        };
    }

    /**
     * Check if this user type has higher or equal privilege than another
     */
    public function hasHigherOrEqualPrivilegeThan(UserType $type): bool
    {
        return $this->hierarchy() >= $type->hierarchy();
    }

    /**
     * Check if this user type is an attendee (VIP or General)
     */
    public function isAttendee(): bool
    {
        return in_array($this, [self::VIP_ATTENDEE, self::GENERAL_ATTENDEE]);
    }

    /**
     * Check if this user type is staff (not an attendee)
     */
    public function isStaff(): bool
    {
        return !$this->isAttendee();
    }

    /**
     * Get the display label for the user type
     */
    public function label(): string
    {
        return match($this) {
            self::ADMINISTRATOR => 'Administrator',
            self::ADMINISTRATOR_ASSISTANT => 'Administrator Assistant',
            self::SITE_MANAGER => 'Site Manager',
            self::VOLUNTEER => 'Volunteer',
            self::VIP_ATTENDEE => 'VIP Attendee',
            self::GENERAL_ATTENDEE => 'General Attendee',
        };
    }

    /**
     * Get permissions/capabilities for this user type
     */
    public function permissions(): array
    {
        return match($this) {
            self::ADMINISTRATOR => [
                'create_event',
                'modify_event',
                'manage_assistants',
                'manage_site_managers',
                'manage_volunteers',
                'view_all_users',
            ],
            self::ADMINISTRATOR_ASSISTANT => [
                'modify_event',
                'manage_inventory_list',
                'manage_points_redemption_items',
            ],
            self::SITE_MANAGER => [
                'add_volunteers',
                'remove_volunteers',
                'view_site_volunteers',
            ],
            self::VOLUNTEER => [
                'precondition_wristband',
                'manage_points_redemption',
                'process_redemption_requests',
            ],
            self::VIP_ATTENDEE => [
                'attend_event',
                'earn_proof_of_attendance',
                'request_points_redemption',
                'view_points_balance',
                'access_vip_areas',
            ],
            self::GENERAL_ATTENDEE => [
                'attend_event',
                'earn_proof_of_attendance',
                'request_points_redemption',
                'view_points_balance',
            ],
        };
    }

    /**
     * Check if this user type has a specific permission
     */
    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions());
    }
}
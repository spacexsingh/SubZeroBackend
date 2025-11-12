<?php

namespace App\Enums;

enum LumaGuestStatus: string
{
    case APP_REGISTERED = 'app_registered';
    case WALLET_REGISTERED = 'wallet_registered';

    case NFC_INITIALIZED = 'nfc_initialized';

    /**
     * Get the display label for the user type
     */
    public function label(): string
    {
        return match($this) {
            self::APP_REGISTERED => 'App Registered',
            self::WALLET_REGISTERED => 'Wallet Registered',
            self::NFC_INITIALIZED => 'NFC Initialized',
        };
    }

}

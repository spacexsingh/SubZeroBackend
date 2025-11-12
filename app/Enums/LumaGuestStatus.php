<?php

namespace App\Enums;

enum LumaGuestStatus: string
{
    case APP_REGISTERED = 'app_registered';
    case WALLET_REGISTERED = 'wallet_registered';

    case NFC_INITIALIZED = 'nfc_initialized';

}

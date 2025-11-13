<?php

namespace App\Filament\Widgets;

use App\Enums\LumaGuestStatus;
use App\Models\LumaGuest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Count approved/attending guests (excluding first 6 seeded users)
        $approvedGuests = LumaGuest::where(function($query) {
                $query->where('approval_status', 'approved')
                    ->orWhere('approval_status', 'attending');
            })
            ->where(function($query) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', '>', 6);
            })
            ->count();

        // Count NFC initialized guests (excluding first 6 seeded users)
        $nfcInitializedGuests = LumaGuest::where('current_status', LumaGuestStatus::NFC_INITIALIZED->value)
            ->where(function($query) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', '>', 6);
            })
            ->count();

        return [
            Stat::make('Approved/Attending Guests', $approvedGuests)
                ->description('Total guests approved or attending')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('NFC Tags Initialized', $nfcInitializedGuests)
                ->description('Guests with initialized NFC tags')
                ->descriptionIcon('heroicon-m-identification')
                ->color('info'),
        ];
    }
}

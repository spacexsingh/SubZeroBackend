<?php

namespace App\Filament\Resources\LumaGuestResource\Pages;

use App\Filament\Resources\LumaGuestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLumaGuest extends ViewRecord
{
    protected static string $resource = LumaGuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

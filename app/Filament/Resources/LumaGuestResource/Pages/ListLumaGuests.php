<?php

namespace App\Filament\Resources\LumaGuestResource\Pages;

use App\Filament\Resources\LumaGuestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLumaGuests extends ListRecords
{
    protected static string $resource = LumaGuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

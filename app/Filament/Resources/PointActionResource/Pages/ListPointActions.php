<?php

namespace App\Filament\Resources\PointActionResource\Pages;

use App\Filament\Resources\PointActionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPointActions extends ListRecords
{
    protected static string $resource = PointActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

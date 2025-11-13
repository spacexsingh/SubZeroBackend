<?php

namespace App\Filament\Resources\PointActionResource\Pages;

use App\Filament\Resources\PointActionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPointAction extends EditRecord
{
    protected static string $resource = PointActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LumaGuestResource\Pages;
use App\Filament\Resources\LumaGuestResource\RelationManagers;
use App\Models\LumaGuest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LumaGuestResource extends Resource
{
    protected static ?string $model = LumaGuest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Luma Guests';

    protected static ?string $pluralModelLabel = 'Luma Guests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Guest Information')
                    ->schema([
                        Forms\Components\TextInput::make('guest_id')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_name')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_email')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_first_name')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_last_name')
                            ->disabled(),
                    ])->columns(2),
                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\TextInput::make('approval_status')
                            ->disabled(),
                        Forms\Components\TextInput::make('current_status')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('registered_at')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('checked_in_at')
                            ->disabled(),
                    ])->columns(2),
                Forms\Components\Section::make('Event Information')
                    ->schema([
                        Forms\Components\TextInput::make('luma_event_id')
                            ->disabled(),
                        Forms\Components\TextInput::make('luma_user_id')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guest_id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_status')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registered_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('checked_in_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for view-only resource
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLumaGuests::route('/'),
            'view' => Pages\ViewLumaGuest::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

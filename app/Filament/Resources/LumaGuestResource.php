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
                Forms\Components\Section::make('Points Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('current_points')
                            ->label('Current Points Balance')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && $record->user) {
                                    $points = $record->user->pointTransactions()
                                        ->selectRaw('SUM(CASE WHEN type = "earn" THEN points ELSE -points END) as total')
                                        ->value('total') ?? 0;
                                    $component->state($points);
                                } else {
                                    $component->state(0);
                                }
                            })
                            ->prefix('🏆')
                            ->numeric(),
                        Forms\Components\TextInput::make('actions_completed')
                            ->label('Actions Completed')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && $record->user) {
                                    $count = $record->user->pointTransactions()
                                        ->where('type', 'earn')
                                        ->whereNotNull('point_action_id')
                                        ->distinct('point_action_id')
                                        ->count('point_action_id');
                                    $component->state($count);
                                } else {
                                    $component->state(0);
                                }
                            })
                            ->prefix('✅')
                            ->numeric(),
                        Forms\Components\TextInput::make('items_redeemed')
                            ->label('Items Redeemed')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && $record->user) {
                                    $count = $record->user->pointTransactions()
                                        ->where('type', 'spend')
                                        ->whereNotNull('merchandise_id')
                                        ->count();
                                    $component->state($count);
                                } else {
                                    $component->state(0);
                                }
                            })
                            ->prefix('🎁')
                            ->numeric(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_status')
                    ->label('Luma Status')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points')
                    ->label('Points')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function ($record) {
                        if (!$record->user) {
                            return 0;
                        }
                        return $record->user->pointTransactions()
                            ->selectRaw('SUM(CASE WHEN type = "earn" THEN points ELSE -points END) as total')
                            ->value('total') ?? 0;
                    })
                    ->numeric()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('point_transactions', 'luma_guests.user_id', '=', 'point_transactions.user_id')
                            ->selectRaw('luma_guests.*, SUM(CASE WHEN point_transactions.type = "earn" THEN point_transactions.points ELSE -point_transactions.points END) as total_points')
                            ->groupBy('luma_guests.id')
                            ->orderBy('total_points', $direction);
                    }),
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

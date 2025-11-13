<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointActionResource\Pages;
use App\Filament\Resources\PointActionResource\RelationManagers;
use App\Models\PointAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PointActionResource extends Resource
{
    protected static ?string $model = PointAction::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Actions';

    protected static ?string $modelLabel = 'Action';

    protected static ?string $pluralModelLabel = 'Actions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('points')
                            ->required()
                            ->numeric()
                            ->label('Points Value')
                            ->helperText('Can be negative for deductions or positive for rewards'),
                        Forms\Components\TextInput::make('meta.identifier')
                            ->label('QR Code')
                            ->maxLength(255)
                            ->helperText('Enter the QR code identifier for this action'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state < 0 => 'danger',
                        $state === 0 => 'gray',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state): string => $state > 0 ? "+{$state}" : (string) $state),
                Tables\Columns\TextColumn::make('meta.identifier')
                    ->label('QR Code')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPointActions::route('/'),
            'create' => Pages\CreatePointAction::route('/create'),
            'edit' => Pages\EditPointAction::route('/{record}/edit'),
        ];
    }
}

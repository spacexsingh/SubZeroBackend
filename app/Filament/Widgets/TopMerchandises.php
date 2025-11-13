<?php

namespace App\Filament\Widgets;

use App\Models\Merchandise;
use App\Models\PointTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopMerchandises extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top 5 Redeemed Merchandises')
            ->query(
                Merchandise::query()
                    ->leftJoin('point_transactions', 'merchandises.id', '=', 'point_transactions.merchandise_id')
                    ->join('luma_guests', 'point_transactions.user_id', '=', 'luma_guests.user_id') // Only count users with luma guest entries
                    ->where('point_transactions.type', 'spend')
                    ->selectRaw('merchandises.*, COUNT(point_transactions.id) as redemption_count')
                    ->groupBy('merchandises.id')
                    ->orderByDesc('redemption_count')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Merchandise')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable(),
                Tables\Columns\TextColumn::make('points_cost')
                    ->label('Points Cost')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('redemption_count')
                    ->label('Times Redeemed')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}

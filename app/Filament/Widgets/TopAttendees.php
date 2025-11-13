<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\PointTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopAttendees extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top 10 Attendees by Points')
            ->query(
                User::query()
                    ->join('luma_guests', 'users.id', '=', 'luma_guests.user_id') // Only users with luma guest entries
                    ->leftJoin('point_transactions', 'users.id', '=', 'point_transactions.user_id')
                    ->selectRaw('
                        users.*,
                        SUM(CASE WHEN point_transactions.type = "earn" THEN point_transactions.points ELSE -point_transactions.points END) as total_points,
                        MIN(point_transactions.created_at) as first_transaction_at
                    ')
                    ->groupBy('users.id')
                    ->orderByDesc('total_points')
                    ->orderBy('first_transaction_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->getStateUsing(function ($rowLoop) {
                        return $rowLoop->index + 1;
                    })
                    ->badge()
                    ->color(fn ($rowLoop): string => match ($rowLoop->index + 1) {
                        1 => 'warning',
                        2 => 'gray',
                        3 => 'orange',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Attendee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_points')
                    ->label('Total Points')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_transaction_at')
                    ->label('First Points Earned')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->paginated(false);
    }
}

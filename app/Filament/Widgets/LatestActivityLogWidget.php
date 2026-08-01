<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Spatie\Activitylog\Models\Activity;

class LatestActivityLogWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Aktor')
                    ->default('Sistem'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subjek')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
            ])
            ->paginated(false);
    }
}

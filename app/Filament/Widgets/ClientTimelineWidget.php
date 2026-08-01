<?php

namespace App\Filament\Widgets;

use Spatie\Activitylog\Models\Activity;
use App\Modules\Projects\Models\Project;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ClientTimelineWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        if (!auth()->user()->isClient()) {
            return false;
        }

        return request('section') === 'timeline';
    }

    public function table(Table $table): Table
    {
        // Temukan ID Project milik klien
        $projectId = Project::where('client_id', auth()->user()->client_id)->value('id');

        return $table
            ->query(
                Activity::query()
                    ->where('subject_type', Project::class)
                    ->where('subject_id', $projectId)
                    // ->where('properties->is_client_visible', true) // Asumsi field meta jika ada
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable(),
            ])
            ->emptyStateHeading('Belum ada riwayat aktivitas.')
            ->emptyStateDescription('Perubahan status pada project akan tercatat di sini.')
            ->paginated([5, 10]);
    }
}

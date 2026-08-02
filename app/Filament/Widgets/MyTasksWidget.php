<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Tasks\TaskResource;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Models\Task;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MyTasksWidget extends BaseWidget
{
    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->isInternalStaff() && ! auth()->user()->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where('assigned_to', auth()->id())
                    ->whereNull('ended_at')
                    ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('project.client.client_id_formatted')
                    ->label('ID Klien')
                    ->searchable(),
                Tables\Columns\TextColumn::make('task_name')
                    ->label('Tugas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Deadline')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp Masuk')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('kerjakan')
                    ->label('Kerjakan')
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-m-arrow-right-circle'),
            ])
            ->emptyStateHeading('Semua pekerjaan telah selesai.')
            ->emptyStateDescription('Saat ini tidak ada tugas aktif yang menanti Anda.')
            ->paginated([5, 10]);
    }
}

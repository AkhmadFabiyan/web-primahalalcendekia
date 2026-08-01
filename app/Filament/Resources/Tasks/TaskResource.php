<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Tugas';
    protected static ?string $modelLabel = 'Tugas';
    protected static ?string $pluralModelLabel = 'Tugas';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        // If user is super admin or manajerial, we might show a different count or just their own.
        // For Phase 30, we use PersonalWorkloadService for consistency.
        $service = app(\App\Modules\Workflows\Services\PersonalWorkloadService::class);
        $count = $service->getActiveTasksCount($user);

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.client.business_id')
                    ->label('ID Klien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Tugas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.client.name')
                    ->label('Perusahaan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('entered_at')
                    ->label('Timestamp Masuk')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('PIC Aktif')
                    ->searchable()
                    ->sortable(),
                // Seluruh PIC bisa didapatkan via relasi project_assignments, di MVP ini kita render PIC Aktif Tugas dan Client
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TaskStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('bukaProject')
                    ->label('Buka Project')
                    ->icon('heroicon-o-folder-open')
                    ->url(fn (Task $record) => \App\Filament\Resources\Clients\ClientResource::getUrl('view', ['record' => $record->project->client_id]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('kerjakan')
                    ->label('Kerjakan')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn (Task $record) => $record->status === TaskStatus::TODO && $record->assigned_to === auth()->id())
                    ->action(function (Task $record) {
                        try {
                            if ($record->task_type === \App\Modules\Workflows\Enums\TaskType::SPV_ENTRY_REVIEW) {
                                app(\App\Modules\Workflows\Services\SpvEntryWorkflowService::class)->startReview($record, auth()->user());
                            } else {
                                app(\App\Modules\Workflows\Services\EntryWorkflowService::class)->startEntry($record, auth()->user());
                            }
                            \Filament\Notifications\Notification::make()->title('Tugas dimulai')->success()->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()->title('Gagal memulai')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions
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
            'index' => Pages\ListTasks::route('/'),
        ];
    }
}

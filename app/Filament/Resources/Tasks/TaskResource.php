<?php

namespace App\Filament\Resources\Tasks;

use App\Enums\Role;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Support\RoleNavigation;
use App\Models\User;
use App\Modules\Workflows\Enums\SlaCycleStatus;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Models\Task;
use App\Modules\Workflows\Services\EntryWorkflowService;
use App\Modules\Workflows\Services\PersonalWorkloadService;
use App\Modules\Workflows\Services\SlaManagerService;
use App\Modules\Workflows\Services\SpvEntryWorkflowService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Tugas';

    protected static ?string $modelLabel = 'Tugas';

    protected static ?string $pluralModelLabel = 'Tugas';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return RoleNavigation::forModule('tasks');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        // Admin uses the purpose-built document queue. Direktur monitors
        // work from dashboards/reports, while Klien never sees internal menus.
        return $user !== null && ! $user->hasAnyRole([
            Role::ADMIN->value,
            Role::DIREKTUR->value,
            Role::KLIEN->value,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['project.client', 'assignee']);
        /** @var User|null $user */
        $user = auth()->user();

        if ($user && ! $user->hasAnyRole([
            Role::SUPER_ADMIN->value,
            Role::DIREKTUR->value,
            Role::MANAGER_OPERASIONAL->value,
        ])) {
            $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var User */
        $user = auth()->user();

        // If user is super admin or manajerial, we might show a different count or just their own.
        // For Phase 30, we use PersonalWorkloadService for consistency.
        $service = app(PersonalWorkloadService::class);
        $count = $service->getActiveTasksCount($user);

        return $count > 0 ? (string) $count : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'project.client.business_id', 'project.client.company_name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Client' => $record->project?->client?->company_name ?? '-',
            'ID Klien' => $record->project?->client?->business_id ?? '-',
            'Status' => $record->status?->value ?? '-',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return TaskResource::getUrl('index'); // We don't have a view page for task directly yet, usually open project
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        $query = parent::getGlobalSearchEloquentQuery()->with(['project.client']);

        $user = auth()->user();
        if ($user && ! $user->hasAnyRole([
            Role::SUPER_ADMIN->value,
            Role::DIREKTUR->value,
            Role::MANAGER_OPERASIONAL->value,
        ])) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereExists(function ($sub) use ($user) {
                        $sub->select(DB::raw(1))
                            ->from('project_assignments')
                            ->whereColumn('project_assignments.project_id', 'tasks.project_id')
                            ->where('project_assignments.user_id', $user->id);
                    });
            });
        }

        return $query;
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->dateTime()
                    ->sortable()
                    ->color(fn (Task $record): string => match (true) {
                        $record->status === TaskStatus::COMPLETED => 'success',
                        $record->deadline && now()->greaterThan($record->deadline) => 'danger',
                        $record->deadline && now()->diffInHours($record->deadline) < 24 => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Action::make('bukaProject')
                    ->label('Buka Project')
                    ->icon('heroicon-o-folder-open')
                    ->url(fn (Task $record) => ClientResource::getUrl('view', ['record' => $record->project->client_id]))
                    ->openUrlInNewTab(),
                Action::make('kerjakan')
                    ->label('Kerjakan')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn (Task $record) => $record->status === TaskStatus::TODO && $record->assigned_to === auth()->id())
                    ->action(function (Task $record) {
                        try {
                            if ($record->task_type === TaskType::SPV_ENTRY_REVIEW) {
                                app(SpvEntryWorkflowService::class)->startReview($record, auth()->user());
                            } else {
                                app(EntryWorkflowService::class)->startEntry($record, auth()->user());
                            }
                            Notification::make()->title('Tugas dimulai')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal memulai')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('pauseSla')
                    ->label('Pause SLA')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Alasan Pause')->required(),
                    ])
                    ->visible(function (Task $record) {
                        $cycle = $record->slaCycles()->latest('cycle_number')->first();

                        return $cycle && $cycle->status === SlaCycleStatus::ACTIVE;
                    })
                    ->action(function (Task $record, array $data) {
                        app(SlaManagerService::class)->pauseCycle($record, $data['reason'], auth()->id());
                        Notification::make()->title('SLA Dipause')->success()->send();
                    }),
                Action::make('resumeSla')
                    ->label('Resume SLA')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Alasan Resume')->required(),
                    ])
                    ->visible(function (Task $record) {
                        $cycle = $record->slaCycles()->latest('cycle_number')->first();

                        return $cycle && $cycle->status === SlaCycleStatus::PAUSED;
                    })
                    ->action(function (Task $record, array $data) {
                        app(SlaManagerService::class)->resumeCycle($record, $data['reason'], auth()->id());
                        Notification::make()->title('SLA Diresume')->success()->send();
                    }),
                Action::make('adjustDeadline')
                    ->label('Perpanjang SLA')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\DateTimePicker::make('new_deadline')->label('Deadline Baru')->required(),
                        Forms\Components\Textarea::make('reason')->label('Alasan')->required(),
                    ])
                    ->visible(function (Task $record) {
                        $cycle = $record->slaCycles()->latest('cycle_number')->first();

                        return $cycle && in_array($cycle->status, [SlaCycleStatus::ACTIVE, SlaCycleStatus::PAUSED]);
                    })
                    ->action(function (Task $record, array $data) {
                        app(SlaManagerService::class)->adjustDeadline($record, Carbon::parse($data['new_deadline']), $data['reason'], auth()->id());
                        Notification::make()->title('Deadline Diperbarui')->success()->send();
                    }),
                ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession();
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

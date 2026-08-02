<?php

namespace App\Filament\Resources\DocumentAdministration;

use App\Enums\Role;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\DocumentAdministration\Pages\ListDocumentAdministrations;
use App\Filament\Support\RoleNavigation;
use App\Models\User;
use App\Modules\Documents\Enums\DocumentRecordStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Workflows\Enums\TaskStatus;
use App\Modules\Workflows\Enums\TaskType;
use App\Modules\Workflows\Models\Task;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DocumentAdministrationResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Pengisian Dokumen';

    protected static ?string $modelLabel = 'Pengisian Dokumen';

    protected static ?string $pluralModelLabel = 'Pengisian Dokumen';

    protected static ?string $slug = 'pengisian-dokumen';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return RoleNavigation::DOCUMENTS;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([
            Role::SUPER_ADMIN->value,
            Role::DIREKTUR->value,
            Role::MANAGER_OPERASIONAL->value,
            Role::ADMIN->value,
        ]) ?? false;
    }

    public static function canView(Model $record): bool
    {
        if (! $record instanceof Task || $record->task_type !== TaskType::DOCUMENT_COMPLETION) {
            return false;
        }

        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole([
            Role::SUPER_ADMIN->value,
            Role::DIREKTUR->value,
            Role::MANAGER_OPERASIONAL->value,
        ]) || ($user->hasRole(Role::ADMIN->value) && $record->assigned_to === $user->id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('task_type', TaskType::DOCUMENT_COMPLETION->value)
            ->with([
                'assignee',
                'project.client',
                'project.projectDocumentRequirements',
                'project.documents.media',
            ]);

        /** @var User|null $user */
        $user = auth()->user();

        if ($user?->hasRole(Role::ADMIN->value)) {
            $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }

        $count = static::getEloquentQuery()
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function requiredDocumentProgress(Task $task): string
    {
        $project = $task->project;

        if (! $project) {
            return '0 / 0';
        }

        $requirements = $project->projectDocumentRequirements
            ->where('is_required', true);

        $fulfilledTypeIds = $project->documents
            ->filter(fn ($document): bool => $document->status === DocumentRecordStatus::UPLOADED
                && $document->hasMedia('document-file')
            )
            ->pluck('document_type_id')
            ->unique();

        $fulfilled = $requirements
            ->whereIn('document_type_id', $fulfilledTypeIds)
            ->count();

        return "{$fulfilled} / {$requirements->count()}";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('deadline')
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('No.')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('project.client.business_id')
                    ->label('ID Klien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.client.company_name')
                    ->label('Nama Klien')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('entered_at')
                    ->label('Timestamp Masuk')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Tenggat')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Belum ditentukan')
                    ->sortable()
                    ->color(fn (Task $record): string => match (true) {
                        $record->status === TaskStatus::COMPLETED => 'success',
                        $record->deadline?->isPast() => 'danger',
                        $record->deadline?->isBefore(now()->addDay()) => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('PIC (Staff Internal)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('required_document_progress')
                    ->label('Dokumen Wajib Terpenuhi')
                    ->state(fn (Task $record): string => static::requiredDocumentProgress($record))
                    ->badge()
                    ->color(function (Task $record): string {
                        [$fulfilled, $required] = array_map('intval', explode(' / ', static::requiredDocumentProgress($record)));

                        return $required > 0 && $fulfilled === $required ? 'success' : 'warning';
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(TaskStatus::class),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('PIC')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                        Role::SUPER_ADMIN->value,
                        Role::DIREKTUR->value,
                        Role::MANAGER_OPERASIONAL->value,
                    ]) ?? false),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label(fn (Task $record): string => auth()->user()?->can('upload', [Document::class, $record->project]) ? 'Kelola' : 'Detail')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Task $record): string => ClientResource::getUrl('view', ['record' => $record->project->client_id])),
            ])
            ->emptyStateHeading('Belum ada antrean pengisian dokumen')
            ->emptyStateDescription('Antrean dibuat otomatis ketika Project aktif dan PIC Admin sudah ditetapkan.')
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentAdministrations::route('/'),
        ];
    }
}

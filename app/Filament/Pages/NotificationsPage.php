<?php

namespace App\Filament\Pages;

use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationPriority;
use App\Modules\Notifications\Models\DatabaseNotification;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected string $view = 'filament.pages.notifications-page';

    protected static ?string $navigationLabel = 'Semua Notifikasi';

    protected static ?string $title = 'Notifikasi Anda';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', auth()->user()->getMorphClass())
                    ->where('notifiable_id', auth()->id())
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->getStateUsing(fn (DatabaseNotification $record): bool => $record->read_at !== null),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (NotificationPriority $state): string => match ($state) {
                        NotificationPriority::LOW => 'info',
                        NotificationPriority::MEDIUM => 'warning',
                        NotificationPriority::HIGH => 'danger',
                        NotificationPriority::CRITICAL => 'danger',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('event_code')
                    ->label('Event')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('data.title')
                    ->label('Judul')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data.body')
                    ->label('Pesan')
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('unread')
                    ->label('Belum Dibaca')
                    ->query(fn (Builder $query): Builder => $query->whereNull('read_at')),
                Tables\Filters\Filter::make('archived')
                    ->label('Diarsipkan')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('archived_at'))
                    ->default(false), // By default, hide archived? Wait, if we use Filter, we should toggle it. Actually, we should only show unarchived by default.
                Tables\Filters\TernaryFilter::make('is_archived')
                    ->label('Arsip')
                    ->placeholder('Semua')
                    ->trueLabel('Hanya Arsip')
                    ->falseLabel('Sembunyikan Arsip')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('archived_at'),
                        false: fn (Builder $query) => $query->whereNull('archived_at'),
                        blank: fn (Builder $query) => $query,
                    )
                    ->default(false), // Default hide archived
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options([
                        NotificationPriority::LOW->value => 'Rendah',
                        NotificationPriority::MEDIUM->value => 'Sedang',
                        NotificationPriority::HIGH->value => 'Tinggi',
                        NotificationPriority::CRITICAL->value => 'Kritis',
                    ]),
                Tables\Filters\SelectFilter::make('event_code')
                    ->label('Event')
                    ->options(
                        collect(NotificationEvent::cases())->mapWithKeys(fn ($enum) => [$enum->value => $enum->name])->toArray()
                    ),
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'project_name'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Mulai Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Action::make('mark_as_read')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check')
                    ->action(fn (DatabaseNotification $record) => $record->markAsRead())
                    ->visible(fn (DatabaseNotification $record): bool => $record->unread()),
                Action::make('mark_as_unread')
                    ->label('Tandai Belum Dibaca')
                    ->icon('heroicon-o-envelope')
                    ->action(fn (DatabaseNotification $record) => $record->markAsUnread())
                    ->visible(fn (DatabaseNotification $record): bool => $record->read()),
                Action::make('archive')
                    ->label('Arsipkan')
                    ->icon('heroicon-o-archive-box')
                    ->action(function (DatabaseNotification $record) {
                        $record->archived_at = now();
                        $record->save();
                    })
                    ->visible(fn (DatabaseNotification $record): bool => $record->archived_at === null),
                Action::make('unarchive')
                    ->label('Batal Arsip')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->action(function (DatabaseNotification $record) {
                        $record->archived_at = null;
                        $record->save();
                    })
                    ->visible(fn (DatabaseNotification $record): bool => $record->archived_at !== null),
            ])
            ->bulkActions([
                // No bulk delete allowed
            ]);
    }
}

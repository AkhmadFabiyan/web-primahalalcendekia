<?php

namespace App\Filament\Resources\Logs;

use App\Filament\Resources\Logs\ActivityLogResource\Pages;
use App\Modules\Logs\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static \UnitEnum|string|null $navigationGroup = 'Arsip & Laporan';
    protected static ?string $modelLabel = 'Activity Log';
    protected static ?string $pluralModelLabel = 'Activity Logs';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Actor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Modul')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created', 'submitted' => 'success',
                        'updated', 'status_changed' => 'warning',
                        'deleted', 'cancelled' => 'danger',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                Tables\Columns\TextColumn::make('project.title')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Modul')
                    ->options([
                        'projects' => 'Projects',
                        'documents' => 'Documents',
                        'users' => 'Users',
                        'invoices' => 'Invoices',
                        'payments' => 'Payments',
                        'certificates' => 'Certificates',
                        'workflows' => 'Workflows',
                        'archives' => 'Archives',
                    ]),
                Tables\Filters\SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'status_changed' => 'Status Changed',
                        'deleted' => 'Deleted',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                        'reopened' => 'Reopened',
                        'submitted' => 'Submitted',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')->label('Sampai Tanggal'),
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Grid::make(3)->schema([
                    Section::make('Informasi Utama')->schema([
                        TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y, H:i:s'),
                        TextEntry::make('causer.name')->label('Aktor'),
                        TextEntry::make('log_name')->label('Modul')->badge(),
                        TextEntry::make('event')->label('Aksi')->badge(),
                        TextEntry::make('project.title')->label('Terkait Project')->placeholder('-'),
                        TextEntry::make('description')->label('Deskripsi Lengkap'),
                        TextEntry::make('batch_uuid')->label('Batch UUID')->placeholder('-'),
                    ])->columnSpan(1),
                    Section::make('Detail Perubahan')->schema(function ($record) {
                        $components = [];

                        $properties = collect($record->properties);
                        $old = $properties->get('old', []);
                        $new = $properties->get('attributes', []);
                        $context = $properties->get('context', []);

                        if (!empty($context)) {
                            $components[] = KeyValueEntry::make('context_data')
                                ->label('Konteks')
                                ->getStateUsing(fn () => $context);
                        }

                        if (!empty($old) || !empty($new)) {
                            // Find differences
                            $changes = [];
                            $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
                            foreach ($allKeys as $key) {
                                $oldVal = isset($old[$key]) ? (is_array($old[$key]) ? json_encode($old[$key]) : (string) $old[$key]) : '-';
                                $newVal = isset($new[$key]) ? (is_array($new[$key]) ? json_encode($new[$key]) : (string) $new[$key]) : '-';
                                
                                if ($oldVal !== $newVal) {
                                    $changes[$key] = "Sebelum: {$oldVal} \nSesudah: {$newVal}";
                                }
                            }

                            if (!empty($changes)) {
                                $components[] = KeyValueEntry::make('changes_data')
                                    ->label('Perubahan Data')
                                    ->getStateUsing(fn () => $changes);
                            }
                        }

                        // Fallback RAW JSON if needed
                        $components[] = TextEntry::make('raw_properties')
                            ->label('Raw Properties (JSON)')
                            ->getStateUsing(fn () => json_encode($record->properties, JSON_PRETTY_PRINT))
                            ->fontFamily('mono')
                            ->extraAttributes(['class' => 'whitespace-pre-wrap']);

                        return $components;
                    })->columnSpan(2)
                ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}

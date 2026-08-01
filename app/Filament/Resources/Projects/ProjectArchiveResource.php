<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\ProjectArchiveResource\Pages;
use App\Modules\Projects\Enums\ArchiveVisibility;
use App\Modules\Projects\Models\ProjectArchive;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectArchiveResource extends Resource
{
    protected static ?string $model = ProjectArchive::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box';
    protected static \UnitEnum|string|null $navigationGroup = 'Arsip & Laporan';
    protected static ?string $modelLabel = 'Arsip Project';
    protected static ?string $pluralModelLabel = 'Arsip Project';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->whereNull('invalidated_at') // only show active archives
            ->with(['project.client']);

        if (auth()->user()->isKlien()) {
            $query->whereHas('project', function ($q) {
                $q->where('client_id', auth()->user()->client_id);
            });
        } elseif (auth()->user()->isAdminPerusahaan()) {
            $query->whereHas('project', function ($q) {
                // Assuming admin perusahaan also bounded to client_id or handled by policy
                $q->where('client_id', auth()->user()->client_id);
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.client.client_id')
                    ->label('ID Klien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.client.name')
                    ->label('Nama Klien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.client.type')
                    ->label('Tipe Klien')
                    ->badge(),
                Tables\Columns\TextColumn::make('project.status')
                    ->label('Status Penutupan')
                    ->badge(),
                Tables\Columns\TextColumn::make('closed_date')
                    ->label('Tanggal Penutupan')
                    ->getStateUsing(fn ($record) => $record->project->status->value === 'COMPLETED' ? $record->project->completed_at?->format('d M Y') : $record->project->cancelled_at?->format('d M Y')),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Dokumen')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('archive_version')
                    ->label('Versi Arsip'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status ZIP')
                    ->badge(),
            ])
            ->filters([
                //
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
                    Section::make('Informasi Arsip')->schema([
                        TextEntry::make('project.client.name')->label('Klien'),
                        TextEntry::make('project.title')->label('Project'),
                        TextEntry::make('archive_version')->label('Versi'),
                        TextEntry::make('status')->label('Status ZIP')->badge(),
                    ])->columnSpan(1),
                    Section::make('Daftar Dokumen')->schema(function ($record) {
                        $items = $record->items;
                        if (auth()->user()->isKlien()) {
                            $items = $items->where('visibility', ArchiveVisibility::CLIENT);
                        }

                        // Group by category
                        $grouped = $items->groupBy('category');
                        $components = [];

                        foreach ($grouped as $cat => $docs) {
                            $components[] = TextEntry::make('cat_'.$cat)
                                ->label($cat)
                                ->getStateUsing(function () use ($docs) {
                                    $list = [];
                                    foreach ($docs as $doc) {
                                        $list[] = $doc->document_name . ($doc->document_version ? ' (' . $doc->document_version . ')' : '');
                                    }
                                    return implode(', ', $list);
                                });
                        }

                        return $components;
                    })->columnSpan(2)
                ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectArchives::route('/'),
            'view' => Pages\ViewProjectArchive::route('/{record}'),
        ];
    }
}

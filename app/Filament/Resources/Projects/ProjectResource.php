<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages;
use App\Modules\Projects\Models\Project;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';
    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';
    protected static ?string $modelLabel = 'Project';
    protected static ?string $pluralModelLabel = 'Projects';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.business_id')
                    ->label('ID Klien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.company_name')
                    ->label('Perusahaan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.client_type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('client.partner.name')
                    ->label('Mitra')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('service_type')
                    ->label('Jenis Layanan')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('assignments.user.name')
                    ->label('Assigned To')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('buka_workspace')
                    ->label('Buka Workspace')
                    ->icon('heroicon-m-arrow-right-circle')
                    ->url(fn (Project $record): string => route('filament.admin.resources.clients.view', ['record' => $record->client_id])),
            ])
            ->recordUrl(fn (Project $record): string => route('filament.admin.resources.clients.view', ['record' => $record->client_id]))
            ->bulkActions([
                //
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession();
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ProjectResource\RelationManagers\AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProjects::route('/'),
        ];
    }
}


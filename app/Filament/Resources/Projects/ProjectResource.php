<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\ProjectResource\RelationManagers\AssignmentsRelationManager;
use App\Filament\Support\RoleNavigation;
use App\Modules\Projects\Models\Project;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static bool $isGloballySearchable = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    public static function getNavigationGroup(): string
    {
        return RoleNavigation::forModule('projects');
    }

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
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProjects::route('/'),
        ];
    }
}

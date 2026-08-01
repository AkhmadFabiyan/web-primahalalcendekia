<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages;
use App\Modules\Clients\Models\Client;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static bool $isGloballySearchable = true;
    protected static ?string $recordTitleAttribute = 'company_name';
    protected static int $globalSearchResultsLimit = 10;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?string $modelLabel = 'Klien';
    protected static ?string $pluralModelLabel = 'Klien';

    public static function getGloballySearchableAttributes(): array
    {
        return ['business_id', 'company_name', 'pic_name', 'pic_email', 'pic_phone', 'partner.name'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->business_id . ' — ' . $record->company_name;
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Layanan' => $record->project?->service_type?->value ?? '-',
            'Status Project' => $record->project?->status?->value ?? '-',
            'Tipe Klien' => $record->client_type?->value ?? '-',
        ];
    }

    public static function getGlobalSearchResultUrl(\Illuminate\Database\Eloquent\Model $record): string
    {
        return ClientResource::getUrl('view', ['record' => $record]);
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getGlobalSearchEloquentQuery()->with(['partner', 'project']);
        
        $user = auth()->user();
        if ($user && $user->hasRole(\App\Enums\Role::KLIEN->value) && $user->client_id) {
            $query->where('id', $user->client_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(Schemas\ClientForm::schema());
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema(Infolists\WorkspaceInfolist::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Tables\ClientsTable::columns())
            ->filters(Tables\ClientsTable::filters())
            ->recordActions(Tables\ClientsTable::actions())
            ->toolbarActions(Tables\ClientsTable::bulkActions())
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession();
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Clients\RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}

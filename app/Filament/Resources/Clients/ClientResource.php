<?php

namespace App\Filament\Resources\Clients;

use App\Enums\Role;
use App\Filament\Resources\Clients\RelationManagers\DocumentsRelationManager;
use App\Filament\Support\RoleNavigation;
use App\Modules\Clients\Models\Client;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static int $globalSearchResultsLimit = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $modelLabel = 'Klien';

    protected static ?string $pluralModelLabel = 'Klien';

    public static function getNavigationGroup(): string
    {
        return RoleNavigation::forModule('clients');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['business_id', 'company_name', 'pic_name', 'pic_email', 'pic_phone', 'partner.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->business_id.' — '.$record->company_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Layanan' => $record->project?->service_type?->value ?? '-',
            'Status Project' => $record->project?->status?->value ?? '-',
            'Tipe Klien' => $record->client_type?->value ?? '-',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ClientResource::getUrl('view', ['record' => $record]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        $query = parent::getGlobalSearchEloquentQuery()->with(['partner', 'project']);

        $user = auth()->user();
        if ($user && $user->hasRole(Role::KLIEN->value) && $user->client_id) {
            $query->where('id', $user->client_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(Schemas\ClientForm::schema());
    }

    public static function infolist(Schema $schema): Schema
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
            DocumentsRelationManager::class,
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

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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?string $modelLabel = 'Klien';
    protected static ?string $pluralModelLabel = 'Klien';

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
            ->toolbarActions(Tables\ClientsTable::bulkActions());
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

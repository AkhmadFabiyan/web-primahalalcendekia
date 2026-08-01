<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages;
use App\Modules\Clients\Models\Partner;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?string $modelLabel = 'Mitra';
    protected static ?string $pluralModelLabel = 'Mitra';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(Schemas\PartnerForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(Tables\PartnersTable::columns())
            ->filters(Tables\PartnersTable::filters())
            ->recordActions(Tables\PartnersTable::actions())
            ->toolbarActions(Tables\PartnersTable::bulkActions()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'view' => Pages\ViewPartner::route('/{record}'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}


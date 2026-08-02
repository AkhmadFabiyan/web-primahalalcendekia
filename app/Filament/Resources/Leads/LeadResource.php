<?php

namespace App\Filament\Resources\Leads;

use App\Enums\Role;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Filament\Support\RoleNavigation;
use App\Modules\Leads\Models\Lead;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static int $globalSearchResultsLimit = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Lead';

    protected static ?string $pluralModelLabel = 'Leads';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return RoleNavigation::forModule('leads');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['company_name', 'pic_name', 'pic_email', 'pic_phone', 'partner.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->company_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'PIC' => $record->pic_name,
            'Status' => $record->status?->value ?? '-',
            'Marketing' => $record->marketing?->name ?? '-',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return LeadResource::getUrl('view', ['record' => $record]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['partner', 'marketing']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(LeadForm::schema());
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table)
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user && $user->hasRole(Role::MARKETING->value)) {
            $query->ownedByMarketing($user->id);
        }

        return $query;
    }
}

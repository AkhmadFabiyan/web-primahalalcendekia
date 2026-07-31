<?php

namespace App\Filament\Resources\Partners\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;

class PartnersTable
{
    public static function columns(): array
    {
        return [
            TextColumn::make('partner_code')
                ->label('Kode Mitra')
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->label('Nama Mitra')
                ->searchable()
                ->sortable(),
            TextColumn::make('pic_name')
                ->label('PIC')
                ->searchable(),
            TextColumn::make('phone')
                ->label('No. Telepon')
                ->searchable(),
            TextColumn::make('email')
                ->label('Email')
                ->searchable(),
        ];
    }

    public static function filters(): array
    {
        return [];
    }

    public static function actions(): array
    {
        return [
            ViewAction::make(),
            EditAction::make(),
        ];
    }

    public static function bulkActions(): array
    {
        // No bulk actions allowed
        return [];
    }
}

<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class PartnerForm
{
    public static function schema(): array
    {
        return [
            Section::make('Informasi Mitra')
                ->schema([
                    TextInput::make('partner_code')
                        ->label('Kode Mitra')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('name')
                        ->label('Nama Mitra')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('pic_name')
                        ->label('Nama PIC')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('No. Telepon')
                        ->tel()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    Textarea::make('address')
                        ->label('Alamat')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}

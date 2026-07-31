<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Modules\Clients\Enums\ClientType;

class ClientForm
{
    public static function schema(): array
    {
        return [
            Section::make('Informasi Klien')
                ->schema([
                    TextInput::make('business_id')
                        ->label('ID Klien')
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('client_type')
                        ->label('Tipe Klien')
                        ->options(ClientType::class)
                        ->required()
                        ->disabled(), // Diset otomatis saat create dari Lead, tidak bisa diubah
                    Select::make('partner_id')
                        ->label('Mitra (Opsional)')
                        ->relationship('partner', 'name')
                        ->disabled(), // Diset otomatis, tidak bisa diubah
                    TextInput::make('company_name')
                        ->label('Nama Perusahaan')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('company_type')
                        ->label('Bentuk Badan Usaha')
                        ->maxLength(255),
                    TextInput::make('business_sector')
                        ->label('Sektor Bisnis')
                        ->maxLength(255),
                ])
                ->columns(2),
            Section::make('Kontak Klien')
                ->schema([
                    TextInput::make('pic_name')
                        ->label('Nama PIC')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('pic_phone')
                        ->label('No. Telepon PIC')
                        ->tel()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('pic_email')
                        ->label('Email PIC')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),
            Section::make('Alamat Klien')
                ->schema([
                    Textarea::make('address')
                        ->label('Alamat Lengkap')
                        ->columnSpanFull(),
                    TextInput::make('city')
                        ->label('Kota/Kabupaten')
                        ->maxLength(255),
                    TextInput::make('province')
                        ->label('Provinsi')
                        ->maxLength(255),
                    TextInput::make('postal_code')
                        ->label('Kode Pos')
                        ->maxLength(255),
                ])
                ->columns(2),
        ];
    }
}

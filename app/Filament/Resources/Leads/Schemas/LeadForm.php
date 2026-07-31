<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Modules\Clients\Enums\ClientType;
use App\Modules\Leads\Enums\PaymentScheme;
use Filament\Schemas\Components\Utilities\Get;

class LeadForm
{
    public static function schema(): array
    {
        return [
            Section::make('Informasi Perusahaan')
                ->schema([
                    TextInput::make('company_name')
                        ->label('Nama Perusahaan')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('business_sector')
                        ->label('Sektor Bisnis')
                        ->maxLength(255),
                    Select::make('client_type')
                        ->label('Tipe Klien')
                        ->options(ClientType::class)
                        ->required()
                        ->live()
                        ->default(ClientType::DIRECT->value),
                ])->columns(2),

            Section::make('Data Mitra')
                ->description('Pilih Mitra yang sudah ada atau isi data Mitra baru jika belum terdaftar.')
                ->schema([
                    Select::make('partner_id')
                        ->label('Mitra Terdaftar')
                        ->relationship('partner', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(fn (Get $get) => blank($get('partner_name')))
                        ->rule(function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (filled($value) && filled($get('partner_name'))) {
                                    $fail('Data mitra terdaftar dan mitra baru tidak boleh diisi bersamaan.');
                                }
                            };
                        }),
                    
                    TextInput::make('partner_name')
                        ->label('Nama Mitra Baru')
                        ->live()
                        ->required(fn (Get $get) => blank($get('partner_id')))
                        ->rule(function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (filled($value) && filled($get('partner_id'))) {
                                    $fail('Data mitra terdaftar dan mitra baru tidak boleh diisi bersamaan.');
                                }
                            };
                        }),
                    
                    TextInput::make('partner_pic_name')
                        ->label('PIC Mitra Baru')
                        ->required(fn (Get $get) => blank($get('partner_id')) && filled($get('partner_name'))),
                        
                    TextInput::make('partner_phone')
                        ->label('No. Telepon Mitra Baru')
                        ->tel()
                        ->required(fn (Get $get) => blank($get('partner_id')) && filled($get('partner_name'))),
                        
                    TextInput::make('partner_email')
                        ->label('Email Mitra Baru')
                        ->email(),
                ])
                ->columns(2)
                ->visible(fn (Get $get) => ($get('client_type') instanceof ClientType ? $get('client_type')->value : $get('client_type')) === ClientType::PARTNER->value),

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
                        ->maxLength(255),
                ])->columns(2),

            Section::make('Lokasi Klien')
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
                ])->columns(2),

            Section::make('Informasi Layanan & Keuangan')
                ->schema([
                    TextInput::make('service_type')
                        ->label('Tipe Layanan')
                        ->maxLength(255),
                    Select::make('payment_scheme')
                        ->label('Skema Pembayaran')
                        ->options(PaymentScheme::class)
                        ->required()
                        ->live(),
                    TextInput::make('installment_count')
                        ->label('Jumlah Termin')
                        ->numeric()
                        ->default(1)
                        ->required()
                        ->minValue(fn (Get $get) => $get('payment_scheme') === PaymentScheme::INSTALLMENT->value ? 2 : 1)
                        ->maxValue(fn (Get $get) => $get('payment_scheme') === PaymentScheme::INSTALLMENT->value ? 12 : 1)
                        ->disabled(fn (Get $get) => $get('payment_scheme') === PaymentScheme::FULL_PAYMENT->value),
                    TextInput::make('client_nominal')
                        ->label('Nominal Klien')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                    TextInput::make('partner_nominal')
                        ->label('Nominal Mitra')
                        ->numeric()
                        ->minValue(1)
                        ->required(fn (Get $get) => ($get('client_type') instanceof ClientType ? $get('client_type')->value : $get('client_type')) === ClientType::PARTNER->value)
                        ->visible(fn (Get $get) => ($get('client_type') instanceof ClientType ? $get('client_type')->value : $get('client_type')) === ClientType::PARTNER->value),
                ])->columns(2),

            Section::make('Informasi Marketing')
                ->schema([
                    Select::make('marketing_id')
                        ->label('Marketing')
                        ->relationship('marketing', 'name', fn ($query) => $query->role(\App\Enums\Role::MARKETING->value)->where('status', 'ACTIVE'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('lead_source')
                        ->label('Sumber Lead')
                        ->maxLength(255),
                    Textarea::make('notes')
                        ->label('Catatan Follow Up')
                        ->columnSpanFull(),
                ])->columns(2),
        ];
    }
}

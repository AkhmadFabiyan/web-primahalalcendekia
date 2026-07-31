<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\ManageDocumentTypes;
use App\Modules\Documents\Models\DocumentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $modelLabel = 'Jenis Dokumen';

    protected static ?string $pluralModelLabel = 'Jenis Dokumen';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null)
                    ->maxLength(255),
                
                TextInput::make('name')
                    ->label('Nama Dokumen')
                    ->required()
                    ->maxLength(255),
                
                Select::make('category')
                    ->label('Kategori')
                    ->options([
                        'ADMINISTRASI' => 'Administrasi',
                        'AUDIT' => 'Audit',
                        'SERTIFIKAT' => 'Sertifikat',
                    ])
                    ->required(),
                
                TextInput::make('sort_order')
                    ->label('Urutan Tampil')
                    ->numeric()
                    ->default(0)
                    ->required(),
                
                Toggle::make('is_required')
                    ->label('Wajib Diunggah')
                    ->helperText('Perubahan status wajib hanya memengaruhi Project baru.')
                    ->default(true),
                
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),
                
                TextColumn::make('name')
                    ->label('Nama Dokumen')
                    ->searchable(),
                
                TextColumn::make('category')
                    ->label('Kategori')
                    ->sortable(),
                
                IconColumn::make('is_required')
                    ->label('Wajib')
                    ->boolean()
                    ->sortable(),
                
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions based on rules (no delete)
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDocumentTypes::route('/'),
        ];
    }
}

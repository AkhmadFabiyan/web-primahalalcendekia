<?php

namespace App\Filament\Widgets;

use App\Modules\Documents\Models\Document;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ClientDocumentsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        if (!auth()->user()->isClient()) {
            return false;
        }

        return request('section') === 'documents';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->whereHas('project', function (Builder $query) {
                        $query->where('client_id', auth()->user()->client_id);
                    })
                    ->where('is_client_visible', true)
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipe Dokumen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Dokumen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Diunggah')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Unduh')
                    ->icon('heroicon-m-arrow-down-tray')
                    // Nanti dapat dihubungkan dengan File Download logic yang aman
            ])
            ->emptyStateHeading('Belum ada dokumen.')
            ->emptyStateDescription('Dokumen yang dibagikan untuk Anda akan tampil di sini.')
            ->paginated([5, 10]);
    }
}

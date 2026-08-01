<?php

namespace App\Filament\Widgets;

use App\Modules\Payments\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ClientInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        if (!auth()->user()->isClient()) {
            return false;
        }

        return request('section') === 'payments';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->whereHas('project', function (Builder $query) {
                        $query->where('client_id', auth()->user()->client_id);
                    })
                    ->where('audience', 'CLIENT')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nomor Tagihan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Deskripsi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('idr'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date(),
            ])
            ->emptyStateHeading('Belum ada tagihan.')
            ->emptyStateDescription('Semua tagihan akan muncul di sini.')
            ->paginated([5, 10]);
    }
}

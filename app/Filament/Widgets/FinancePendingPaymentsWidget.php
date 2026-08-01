<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Dashboards\Services\FinanceDashboardService;
use App\Modules\Dashboards\DataTransferObjects\FinanceDashboardFilterData;

class FinancePendingPaymentsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 13;
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('dashboard.finance.view');
    }

    public function table(Table $table): Table
    {
        $filterData = FinanceDashboardFilterData::fromArray($this->filters);
        $service = new FinanceDashboardService($filterData);
        
        return $table
            ->query($service->getPendingPaymentsQuery())
            ->heading('Pembayaran Menunggu Verifikasi (PENDING)')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tgl Bayar')
                    ->date(),
                Tables\Columns\TextColumn::make('invoice.project.client.company_name')
                    ->label('Nama Klien')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Review Pembayaran')
                    ->url(fn ($record) => '/dashboard/payments/' . $record->id) // Assuming resource route is /dashboard/payments
                    ->icon('heroicon-m-eye'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}

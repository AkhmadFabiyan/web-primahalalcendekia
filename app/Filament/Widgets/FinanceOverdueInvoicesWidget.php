<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\DataTransferObjects\FinanceDashboardFilterData;
use App\Modules\Dashboards\Services\FinanceDashboardService;
use App\Modules\Payments\Enums\PaymentStatus;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class FinanceOverdueInvoicesWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 14;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->can('dashboard.finance.view');
    }

    public function table(Table $table): Table
    {
        $filterData = FinanceDashboardFilterData::fromArray($this->filters);
        $service = new FinanceDashboardService($filterData);
        $asOfDate = $filterData->period_end ?? Carbon::now();

        return $table
            ->query($service->getOverdueInvoicesQuery())
            ->heading('Overdue Invoices (Jatuh Tempo)')
            ->columns([
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date(),
                Tables\Columns\TextColumn::make('project.client.company_name')
                    ->label('Nama Klien')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total Tagihan')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('remaining')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->state(function ($record) use ($asOfDate) {
                        $paid = $record->payments->where('status', PaymentStatus::VERIFIED->value)
                            ->where('payment_date', '<=', $asOfDate)
                            ->sum('amount');

                        return $record->total - $paid;
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Detail Invoice')
                    ->url(fn ($record) => '/dashboard/invoices/'.$record->id)
                    ->icon('heroicon-m-eye'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}

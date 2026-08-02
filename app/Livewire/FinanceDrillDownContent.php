<?php

namespace App\Livewire;

use App\Modules\Dashboards\DataTransferObjects\FinanceDashboardFilterData;
use App\Modules\Dashboards\Services\FinanceDashboardService;
use App\Modules\Payments\Enums\PaymentStatus;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class FinanceDrillDownContent extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $type;

    public string $key;

    public array $filters;

    public function mount(string $type, string $key, array $filters)
    {
        $this->type = $type;
        $this->key = $key;
        $this->filters = $filters;

        $user = auth()->user();
        abort_if(! $user || ! $user->can('dashboard.finance.view'), 403, 'Unauthorized');
    }

    public function table(Table $table): Table
    {
        $filterData = FinanceDashboardFilterData::fromArray($this->filters);
        $service = new FinanceDashboardService($filterData);
        $asOfDate = $filterData->period_end ?? Carbon::now();

        if ($this->type === 'invoice') {
            return $table
                ->query($service->getInvoiceDrillDownQuery($this->key))
                ->columns([
                    Tables\Columns\TextColumn::make('invoice_number')
                        ->label('Nomor Invoice')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('project.client.business_id')
                        ->label('ID Klien')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('project.client.company_name')
                        ->label('Nama Klien')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('audience')
                        ->label('Audience')
                        ->badge(),
                    Tables\Columns\TextColumn::make('invoice_type')
                        ->label('Jenis Invoice')
                        ->badge(),
                    Tables\Columns\TextColumn::make('total')
                        ->label('Total')
                        ->money('IDR'),
                    Tables\Columns\TextColumn::make('paid')
                        ->label('Total Terbayar')
                        ->money('IDR')
                        ->state(function ($record) use ($asOfDate) {
                            return $record->payments->where('status', PaymentStatus::VERIFIED->value)
                                ->where('payment_date', '<=', $asOfDate)
                                ->sum('amount');
                        }),
                    Tables\Columns\TextColumn::make('remaining')
                        ->label('Sisa')
                        ->money('IDR')
                        ->state(function ($record) use ($asOfDate) {
                            $paid = $record->payments->where('status', PaymentStatus::VERIFIED->value)
                                ->where('payment_date', '<=', $asOfDate)
                                ->sum('amount');

                            return $record->total - $paid;
                        }),
                    Tables\Columns\TextColumn::make('due_date')
                        ->label('Jatuh Tempo')
                        ->date(),
                    Tables\Columns\TextColumn::make('aging')
                        ->label('Aging')
                        ->state(function ($record) use ($asOfDate) {
                            $paid = $record->payments->where('status', PaymentStatus::VERIFIED->value)
                                ->where('payment_date', '<=', $asOfDate)
                                ->sum('amount');
                            $sisa = $record->total - $paid;
                            if ($sisa <= 0) {
                                return '-';
                            }

                            $dueDate = Carbon::parse($record->due_date);
                            if ($dueDate->greaterThanOrEqualTo($asOfDate)) {
                                return 'Belum Jatuh Tempo';
                            }

                            return $dueDate->diffInDays($asOfDate).' Hari';
                        }),
                    Tables\Columns\TextColumn::make('status')
                        ->label('Status')
                        ->badge(),
                    // PIC might be complex if multiple, typically we just take the assignments or omit if complicated,
                    // but the requirement says 'PIC'. Let's assume Project assignments to staff.
                ])
                ->actions([
                    Action::make('view_invoice')
                        ->label('Buka Detail Invoice')
                        ->url(fn ($record) => '/dashboard/invoices/'.$record->id)
                        ->icon('heroicon-m-document-text'),
                ])
                ->paginated([5, 10, 25])
                ->defaultPaginationPageOption(5);
        } else {
            // Payment
            return $table
                ->query($service->getPaymentDrillDownQuery($this->key))
                ->columns([
                    Tables\Columns\TextColumn::make('invoice.invoice_number')
                        ->label('Nomor Invoice')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('invoice.project.client.company_name')
                        ->label('Nama Klien')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('invoice.audience')
                        ->label('Audience')
                        ->badge(),
                    Tables\Columns\TextColumn::make('amount')
                        ->label('Nominal')
                        ->money('IDR'),
                    Tables\Columns\TextColumn::make('payment_date')
                        ->label('Tanggal Bayar')
                        ->date(),
                    Tables\Columns\TextColumn::make('payment_method')
                        ->label('Metode'),
                    Tables\Columns\TextColumn::make('reference_number')
                        ->label('Referensi'),
                    Tables\Columns\TextColumn::make('status')
                        ->label('Status')
                        ->badge(),
                ])
                ->actions([
                    Action::make('view_payment')
                        ->label('Review Pembayaran')
                        ->url(fn ($record) => '/dashboard/payments/'.$record->id)
                        ->icon('heroicon-m-eye'),
                ])
                ->paginated([5, 10, 25])
                ->defaultPaginationPageOption(5);
        }
    }

    public function render()
    {
        return view('livewire.finance-drill-down-content');
    }
}

<?php

namespace App\Filament\Widgets\Reports;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Reports\Services\ManagementReportService;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;
use App\Filament\Exports\ProjectExporter;
use Filament\Actions\Exports\Enums\ExportFormat;

class ReportTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 10;
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('report.view');
    }

    public function table(Table $table): Table
    {
        $filterData = ManagementReportFilterData::fromArray($this->filters);
        $service = new ManagementReportService($filterData);
        
        return $table
            ->query($service->getReportQuery())
            ->heading('Detail Project')
            ->columns([
                Tables\Columns\TextColumn::make('client.business_id')
                    ->label('ID Klien')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('sourceLead.pic.name')
                    ->label('Marketing')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activated_at')
                    ->label('Tanggal Aktivasi')
                    ->date(),
                Tables\Columns\TextColumn::make('certificate.issued_at')
                    ->label('Tanggal Sertifikat')
                    ->date(),
                Tables\Columns\TextColumn::make('invoices_sum_subtotal')
                    ->label('Nilai Invoice')
                    ->money('IDR')
                    ->state(function ($record) {
                        return $record->invoices->sum(fn($inv) => $inv->subtotal - $inv->discount_total);
                    }),
                Tables\Columns\TextColumn::make('payments_sum_amount')
                    ->label('Total Payment')
                    ->money('IDR')
                    ->state(function ($record) {
                        return $record->invoices->flatMap->payments->where('status', 'VERIFIED')->sum('amount');
                    }),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make('export')
                    ->label('Export CSV')
                    ->exporter(ProjectExporter::class)
                    ->formats([
                        ExportFormat::Csv,
                    ])
                    ->chunkSize(100)
                    // Filament ExportAction automatically uses the table's query which includes our filters
            ])
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->url(fn ($record) => '/dashboard/projects/' . $record->id)
                    ->icon('heroicon-m-eye'),
            ])
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}

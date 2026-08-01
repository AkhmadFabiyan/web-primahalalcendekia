<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Reports\Services\ManagementReportService;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueTrendChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Revenue Trend (Kas Masuk Komersial)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('report.view');
    }

    protected function getData(): array
    {
        $filterData = ManagementReportFilterData::fromArray($this->filters);
        
        $start = Carbon::parse($filterData->start_date);
        $end = Carbon::parse($filterData->end_date);
        
        // Simple daily trend
        // In real cases, group by month if period > 60 days
        $payments = Payment::query()
            ->where('status', PaymentStatus::VERIFIED->value)
            ->whereBetween('payment_date', [$start->toDateString() . ' 00:00:00', $end->toDateString() . ' 23:59:59'])
            ->whereHas('invoice', function ($q) {
                $q->whereIn('invoice_type', ['ACTIVATION', 'INSTALLMENT', 'SETTLEMENT']);
            })
            ->select(DB::raw('DATE(payment_date) as date'), DB::raw('sum(amount) as total'))
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data = [];

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateString = $current->toDateString();
            $labels[] = $dateString;
            $data[] = $payments[$dateString] ?? 0;
            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kas Masuk',
                    'data' => $data,
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => '#22c55e',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Or area
    }
}

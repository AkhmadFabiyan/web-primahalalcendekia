<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Dashboards\Services\FinanceDashboardService;
use App\Modules\Dashboards\DataTransferObjects\FinanceDashboardFilterData;

class FinanceAgingReceivablesWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 12;
    protected ?string $heading = 'Aging Receivables';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('dashboard.finance.view');
    }

    protected function getData(): array
    {
        $filterData = FinanceDashboardFilterData::fromArray($this->filters);
        $service = new FinanceDashboardService($filterData);
        $agingData = $service->getAgingSummary();

        return [
            'datasets' => [
                [
                    'label' => 'Total Tagihan',
                    'data' => array_values($agingData),
                    'backgroundColor' => [
                        '#10B981', // Belum Jatuh Tempo - Green
                        '#FCD34D', // 1-30 Hari - Yellow
                        '#F59E0B', // 31-60 Hari - Orange
                        '#EF4444', // 61-90 Hari - Red
                        '#7F1D1D', // > 90 Hari - Dark Red
                    ],
                ],
            ],
            'labels' => array_keys($agingData),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Dashboards\Services\FinanceDashboardService;
use App\Modules\Dashboards\DataTransferObjects\FinanceDashboardFilterData;

class FinanceRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 11;
    protected ?string $heading = 'Tren Kas Masuk Terverifikasi';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('dashboard.finance.view');
    }

    protected function getData(): array
    {
        $filterData = FinanceDashboardFilterData::fromArray($this->filters);
        $service = new FinanceDashboardService($filterData);
        $trendData = $service->getRevenueTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Kas Masuk',
                    'data' => array_values($trendData),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)', // green-500
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ],
            ],
            'labels' => array_keys($trendData),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Modules\Reports\Services\ManagementReportService;
use App\Modules\Reports\DataTransferObjects\ManagementReportFilterData;

class WorkflowPerformanceChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Cycle Time (Hari)';
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->can('report.view');
    }

    protected function getData(): array
    {
        $filterData = ManagementReportFilterData::fromArray($this->filters);
        $service = new ManagementReportService($filterData);
        $metrics = $service->getCycleTimeMetrics();

        return [
            'datasets' => [
                [
                    'label' => 'Hari',
                    'data' => [
                        $metrics['avg'],
                        $metrics['median'],
                        $metrics['p75'],
                        $metrics['min'],
                        $metrics['max'],
                    ],
                    'backgroundColor' => [
                        '#3b82f6', '#8b5cf6', '#a855f7', '#22c55e', '#ef4444'
                    ],
                ],
            ],
            'labels' => ['Rata-rata', 'Median', 'P75', 'Tercepat', 'Terlama'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
